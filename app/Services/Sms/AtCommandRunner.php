<?php

namespace App\Services\Sms;

use App\Services\Sms\Exceptions\ModemException;
use Illuminate\Support\Facades\Log;

/**
 * Handles raw serial communication with the modem via AT commands.
 *
 * Windows : COM3  → normalized to \\.\COM3 for fopen()
 * macOS   : /dev/cu.usbserial-*
 * Linux   : /dev/ttyUSB0, /dev/ttyACM0
 *
 * WINDOWS BLOCKING FIX:
 *   On Windows COM port streams, neither stream_set_blocking(false) nor
 *   stream_set_timeout(0) prevents fread() from blocking — the Windows
 *   UART driver ignores these hints and blocks until data arrives or the
 *   driver-level read timeout expires (default ~60 seconds).
 *
 *   The ONLY reliable way to make fread() non-blocking on Windows COM
 *   ports from PHP is to use stream_select() with a zero timeout BEFORE
 *   each fread() call. stream_select() probes the OS for available bytes
 *   without blocking, and we only call fread() when bytes are confirmed
 *   to be waiting in the buffer.
 */
class AtCommandRunner
{
    private mixed $handle = null;
    private string $normalizedPort;

    // Settle time after open before wake sequence (ms)
    private const OPEN_SETTLE_MS = 200;

    // Settle time between wake attempts (ms)
    private const WAKE_INTERVAL_MS = 200;

    // Number of AT wake attempts before giving up
    private const WAKE_ATTEMPTS = 5;

    // Poll interval inside readUntil() (microseconds)
    private const READ_POLL_US = 50_000; // 50ms

    public function __construct(
        private readonly string $port,
        private readonly int $baudRate = 115200,
        private readonly int $timeout = 10,
    ) {
        $this->normalizedPort = $this->normalizePort($port);
    }

    /**
     * Open the serial port and wake the modem.
     *
     * @throws ModemException if port cannot be opened or modem does not respond
     */
    public function open(): void
    {
        if ($this->handle !== null) {
            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $this->configureWindows();
        } else {
            $this->configureUnix();
        }

        $openError = null;
        set_error_handler(function (int $errno, string $errstr) use (&$openError): bool {
            $openError = $errstr;
            return true;
        });

        $handle = fopen($this->normalizedPort, 'r+b');

        restore_error_handler();

        if ($handle === false) {
            throw ModemException::connectionFailed(
                $this->port,
                $openError ?? 'Could not open port. Ensure the device is connected and no other application has it open.'
            );
        }

        // These are set for completeness but on Windows COM ports
        // stream_select() is what actually makes reads non-blocking
        stream_set_blocking($handle, false);
        stream_set_timeout($handle, 0);
        stream_set_read_buffer($handle, 0);
        stream_set_write_buffer($handle, 0);

        $this->handle = $handle;

        Log::info("[Modem] Port opened: {$this->port}");

        // Drain any garbage in RX buffer using stream_select (non-blocking)
        $this->flushInputBuffer();

        // Brief settle
        usleep(self::OPEN_SETTLE_MS * 1000);

        // Wake modem
        $this->wakeModem();
    }

    public function close(): void
    {
        if ($this->handle !== null) {
            @fclose($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Send an AT command and wait for expected response.
     *
     * @throws ModemException on write failure, error response, or timeout
     */
    public function send(string $command, string $expect = 'OK', ?int $timeout = null): string
    {
        $this->assertOpen();

        $written = fwrite($this->handle, $command . "\r");

        if ($written === false) {
            throw ModemException::atCommandFailed($command, 'Write to port failed.');
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            fflush($this->handle);
        }

        return $this->readUntil($expect, $command, $timeout ?? $this->timeout);
    }

    /**
     * Send raw bytes — SMS body terminated with Ctrl+Z (chr(26)).
     *
     * @throws ModemException
     */
    public function sendRaw(string $data): string
    {
        $this->assertOpen();

        fwrite($this->handle, $data);

        if (PHP_OS_FAMILY !== 'Windows') {
            fflush($this->handle);
        }

        return $this->readUntil('+CMGS:', 'SMS_SEND', 30);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Configure COM port via mode.exe BEFORE fopen().
     * Sets baud rate, DTR=on, RTS=on which wakes the modem.
     * Must run before fopen() — mode.exe cannot access an already-open port.
     */
    private function configureWindows(): void
    {
        if (! $this->execEnabled()) {
            Log::warning("[Modem] exec() disabled — cannot configure Windows COM port");
            return;
        }

        $portSafe = preg_replace('/[^A-Z0-9]/', '', strtoupper($this->port));

        $cmd = "mode {$portSafe}: BAUD={$this->baudRate} PARITY=N DATA=8 STOP=1 to=on xon=off odsr=off octs=off dtr=on rts=on 2>&1";
        exec($cmd, $output, $code);

        if ($code !== 0) {
            Log::warning("[Modem] Windows port config warning (exit {$code}): " . implode(' | ', $output));
        } else {
            Log::info("[Modem] Windows port configured: {$this->port} at {$this->baudRate} baud");
        }

        usleep(200_000); // 200ms — let OS apply settings
    }

    /**
     * Wake modem with repeated AT pings (autobaud sync).
     *
     * @throws ModemException if modem does not respond after all attempts
     */
    private function wakeModem(): void
    {
        for ($attempt = 1; $attempt <= self::WAKE_ATTEMPTS; $attempt++) {
            try {
                $this->flushInputBuffer();

                fwrite($this->handle, "AT\r");

                $response = $this->readUntil('OK', 'AT_WAKE', 2);
                return;

            } catch (ModemException $e) {
                usleep(self::WAKE_INTERVAL_MS * 1000);
            }
        }

        throw new ModemException(
            "Modem on {$this->port} did not respond after " . self::WAKE_ATTEMPTS . " wake attempts."
        );
    }

    /**
     * Drain RX buffer using stream_select() to avoid blocking.
     *
     * stream_select() with tv_sec=0, tv_usec=0 returns immediately,
     * telling us whether bytes are available WITHOUT blocking.
     * We only call fread() when stream_select confirms data is waiting.
     */
    private function flushInputBuffer(): void
    {
        if ($this->handle === null) {
            return;
        }

        $deadline = microtime(true) + 0.5;
        $flushed  = '';

        while (microtime(true) < $deadline) {
            $read   = [$this->handle];
            $write  = null;
            $except = null;

            // stream_select with zero timeout — non-blocking check
            $ready = stream_select($read, $write, $except, 0, 0);

            if ($ready === false || $ready === 0) {
                // No data available — buffer is empty, stop draining
                break;
            }

            $chunk = fread($this->handle, 4096);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $flushed .= $chunk;
        }

    }

    /**
     * Poll the port until the expected string appears or timeout is reached.
     *
     * Uses stream_select() with a short timeout to check for data availability
     * before each fread(). This is the ONLY way to get truly non-blocking reads
     * from a Windows COM port stream in PHP.
     */
    private function readUntil(string $expect, string $command, int $timeout): string
    {
        $response   = '';
        $deadline   = microtime(true) + $timeout;
        $emptyReads = 0;

        while (microtime(true) < $deadline) {
            $read   = [$this->handle];
            $write  = null;
            $except = null;

            // Wait up to READ_POLL_US for data to become available.
            // This replaces usleep() — stream_select() naturally sleeps
            // until data arrives or the timeout expires.
            $ready = stream_select(
                $read,
                $write,
                $except,
                0,                       // tv_sec  = 0
                self::READ_POLL_US       // tv_usec = 50ms
            );

            if ($ready === false) {
                // stream_select error — handle is likely broken
                throw ModemException::atCommandFailed($command, 'stream_select() error on port handle.');
            }

            if ($ready === 0) {
                // No data yet — continue polling
                $emptyReads++;
                continue;
            }

            // Data is available — safe to fread() without blocking
            $chunk = fread($this->handle, 1024);

            if ($chunk !== false && $chunk !== '') {
                $emptyReads = 0;
                $response  .= $chunk;
                if (str_contains($response, $expect)) {
                    return $response;
                }

                if (str_contains($response, 'ERROR') || str_contains($response, '+CMS ERROR')) {
                    throw ModemException::atCommandFailed($command, trim($response));
                }
            }
        }

        Log::warning("[Modem] Timeout on '{$command}' waiting for '{$expect}'. " .
            "Response: '" . trim($response) . "' | Empty polls: {$emptyReads}");

        throw ModemException::timeout($command);
    }

    private function assertOpen(): void
    {
        if ($this->handle === null) {
            throw new ModemException('Serial port is not open. Call open() first.');
        }
    }

    private function normalizePort(string $port): string
    {
        if (PHP_OS_FAMILY === 'Windows' && preg_match('/^COM\d+$/i', trim($port))) {
            return '\\\\.\\'  . strtoupper(trim($port));
        }
        return $port;
    }

    private function configureUnix(): void
    {
        $flag = PHP_OS_FAMILY === 'Darwin' ? '-f' : '-F';
        $cmd  = sprintf(
            'stty %s %s %d cs8 -cstopb -parenb raw 2>&1',
            $flag,
            escapeshellarg($this->normalizedPort),
            $this->baudRate
        );
        exec($cmd, $output, $code);

        if ($code !== 0) {
            Log::warning("[Modem] stty config warning: " . implode(' ', $output));
        }
    }

    private function execEnabled(): bool
    {
        if (! function_exists('exec')) {
            return false;
        }
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return ! in_array('exec', $disabled, true);
    }

    public function __destruct()
    {
        $this->close();
    }
}
