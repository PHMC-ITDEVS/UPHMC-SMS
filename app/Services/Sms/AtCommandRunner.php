<?php

namespace App\Services\Sms;

use App\Services\Sms\Exceptions\ModemException;
use Illuminate\Support\Facades\Log;

/**
 * Handles raw serial communication with the modem via AT commands.
 *
 * Windows : COM3  → normalized to \\.\COM3
 * macOS   : /dev/cu.usbserial-*
 * Linux   : /dev/ttyUSB0, /dev/ttyACM0
 */
class AtCommandRunner
{
    private mixed $handle = null;
    private string $normalizedPort;

    public function __construct(
        private readonly string $port,
        private readonly int $baudRate = 9600,
        private readonly int $timeout = 10,
    ) {
        $this->normalizedPort = $this->normalizePort($port);
    }

    public function open(): void
    {
        if ($this->handle !== null) {
            return;
        }

        $this->configurePort();

        $handle = @fopen($this->normalizedPort, 'r+b');

        if ($handle === false) {
            throw ModemException::connectionFailed(
                $this->port,
                'Could not open port. Check permissions and port name.'
            );
        }

        stream_set_blocking($handle, false);
        stream_set_timeout($handle, $this->timeout);

        $this->handle = $handle;

        Log::debug("[Modem] Port opened: {$this->port}");
    }

    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
            Log::debug("[Modem] Port closed: {$this->port}");
        }
    }

    /**
     * Send an AT command and wait for expected response.
     *
     * @throws ModemException
     */
    public function send(string $command, string $expect = 'OK', ?int $timeout = null): string
    {
        $this->assertOpen();

        $written = fwrite($this->handle, $command . "\r");

        if ($written === false) {
            throw ModemException::atCommandFailed($command, 'Write to port failed.');
        }

        Log::debug("[Modem] >> {$command}");

        return $this->readUntil($expect, $command, $timeout ?? $this->timeout);
    }

    /**
     * Send raw bytes — used for SMS body + Ctrl+Z terminator.
     *
     * @throws ModemException
     */
    public function sendRaw(string $data): string
    {
        $this->assertOpen();

        fwrite($this->handle, $data);

        return $this->readUntil('+CMGS:', 'SMS_SEND', 30);
    }

    private function readUntil(string $expect, string $command, int $timeout): string
    {
        $response = '';
        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $chunk = fread($this->handle, 1024);

            if ($chunk !== false && $chunk !== '') {
                $response .= $chunk;
                Log::debug("[Modem] << " . trim($chunk));

                if (str_contains($response, $expect)) {
                    return $response;
                }

                if (str_contains($response, 'ERROR') || str_contains($response, '+CMS ERROR')) {
                    throw ModemException::atCommandFailed($command, trim($response));
                }
            }

            usleep(50000); // 50ms poll
        }

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
        if (PHP_OS_FAMILY === 'Windows' && preg_match('/^COM\d+$/i', $port)) {
            return '\\\\.\\'  . strtoupper($port);
        }

        return $port;
    }

    private function configurePort(): void
    {
        match (PHP_OS_FAMILY) {
            'Windows' => $this->configureWindows(),
            'Darwin'  => $this->configureUnix(),
            'Linux'   => $this->configureUnix(),
            default   => throw new ModemException("Unsupported OS: " . PHP_OS_FAMILY),
        };
    }

    private function configureWindows(): void
    {
        $cmd = sprintf(
            'mode %s BAUD=%d PARITY=N DATA=8 STOP=1 2>&1',
            escapeshellarg($this->port),
            $this->baudRate
        );
        exec($cmd, $output, $code);

        if ($code !== 0) {
            Log::warning("[Modem] Windows port config warning: " . implode(' ', $output));
        }
    }

    private function configureUnix(): void
    {
        $flag = PHP_OS_FAMILY === 'Darwin' ? '-f' : '-F';
        $cmd = sprintf(
            'stty %s %s %d cs8 -cstopb -parenb 2>&1',
            $flag,
            escapeshellarg($this->normalizedPort),
            $this->baudRate
        );
        exec($cmd, $output, $code);

        if ($code !== 0) {
            Log::warning("[Modem] stty config warning: " . implode(' ', $output));
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
