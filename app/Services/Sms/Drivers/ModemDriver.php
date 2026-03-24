<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\AtCommandRunner;
use App\Services\Sms\Contracts\SmsGatewayInterface;
use App\Services\Sms\Exceptions\ModemException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends SMS via Itegno W3800U using AT commands.
 *
 * ON WINDOWS:
 *   Delegates to scripts/sms/sms_send.py via Python + pyserial.
 *
 *   PHP fopen() streams on Windows COM ports block unconditionally regardless
 *   of stream_set_blocking(), stream_set_timeout(), or stream_select() — the
 *   Windows UART driver ignores all of these and uses its own internal timeout
 *   (default ~60 seconds). The only way to configure this is via the Win32
 *   SetCommTimeouts() API, which PHP cannot call.
 *
 *   pyserial calls SetCommTimeouts() directly and also correctly asserts the
 *   DTR signal (dsrdtr=True) which is required for the Itegno W3800U to
 *   respond to AT commands at all.
 *
 * ON UNIX/macOS:
 *   Uses AtCommandRunner directly — PHP streams work correctly on Unix serial
 *   ports via stty configuration.
 *
 * Setup (one-time, Windows):
 *   pip install -r scripts/sms/requirements.txt
 *
 * Setup (one-time, Unix):
 *   No extra dependencies needed.
 */
class ModemDriver implements SmsGatewayInterface
{
    public function __construct(private readonly array $config) {}

    // ── SmsGatewayInterface ───────────────────────────────────────────────────

    public function send(string $to, string $message): array
    {
        $reference = Str::uuid()->toString();
        Log::info("[ModemDriver] Starting send to {$to}", [
            'reference' => $reference,
            'gateway'   => $this->getName(),
        ]);

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $this->sendViaPython($to, $message);
            } else {
                $this->sendViaPhp($to, $message);
            }

            Log::info("[ModemDriver] SMS sent to {$to}", [
                'reference' => $reference,
                'gateway'   => $this->getName(),
            ]);

            return [
                'success'   => true,
                'gateway'   => $this->getName(),
                'reference' => $reference,
                'error'     => null,
            ];

        } catch (ModemException $e) {
            Log::error("[ModemDriver] Send failed to {$to}: {$e->getMessage()}");

            $isDisconnected = str_contains($e->getMessage(), 'not found')
                || str_contains($e->getMessage(), 'Could not open')
                || str_contains($e->getMessage(), 'Permission denied')
                || str_contains($e->getMessage(), 'not accessible')
                || str_contains($e->getMessage(), 'could not open port');

            return [
                'success'   => false,
                'gateway'   => $this->getName(),
                'reference' => null,
                'error'     => $isDisconnected ? 'modem_disconnected' : $e->getMessage(),
            ];
        }
    }

    public function isHealthy(): bool
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                return $this->pingViaPython();
            }
            return $this->pingViaPhp();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'modem:' . ($this->config['port'] ?? 'unknown');
    }

    // ── Windows — Python/pyserial ─────────────────────────────────────────────

    private function sendViaPython(string $to, string $message): void
    {
        $script  = $this->getPythonScriptPath();
        $port    = $this->getPort();
        $baud    = (int) ($this->config['baud_rate']      ?? 115200);
        $timeout = (int) ($this->config['timeout']         ?? 10);
        $maxLen  = (int) ($this->config['max_sms_length']  ?? 160);
        $body    = mb_substr($this->normalizeSmsBody($message), 0, $maxLen);
        $bodyB64 = base64_encode($body);

        $cmd = sprintf(
            'python %s --port %s --baud %d --to %s --message-b64 %s --timeout %d --max-len %d 2>&1',
            escapeshellarg($script),
            escapeshellarg($port),
            $baud,
            escapeshellarg($to),
            escapeshellarg($bodyB64),
            $timeout,
            $maxLen
        );

        exec($cmd, $output, $exitCode);

        $outputStr = implode("\n", $output);

        if ($exitCode !== 0) {
            throw new ModemException(
                "Python SMS script failed (exit {$exitCode}): {$outputStr}"
            );
        }

        if (! str_contains($outputStr, 'OK:')) {
            throw new ModemException(
                "Unexpected Python script output: {$outputStr}"
            );
        }
    }

    private function pingViaPython(): bool
    {
        $port = $this->getPort();
        $baud = (int) ($this->config['baud_rate'] ?? 115200);

        // Pass port as sys.argv[1] to avoid shell quoting issues with COM port names
        $cmd = sprintf(
            'python -c "import sys,serial; s=serial.Serial(sys.argv[1],%d,timeout=3,dsrdtr=True); s.close(); print(\'OK\')" %s 2>&1',
            $baud,
            escapeshellarg($port)
        );

        exec($cmd, $output, $code);

        return $code === 0 && str_contains(implode('', $output), 'OK');
    }

    /**
     * Get the absolute path to scripts/sms/sms_send.py.
     *
     * @throws ModemException if script is missing
     */
    private function getPythonScriptPath(): string
    {
        $path = base_path('scripts/sms/sms_send.py');

        if (! file_exists($path)) {
            throw new ModemException(
                "SMS Python script not found at [{$path}]. " .
                "Ensure scripts/sms/sms_send.py exists in the project root. " .
                "Install dependencies: pip install -r scripts/sms/requirements.txt"
            );
        }

        return $path;
    }

    // ── Unix/macOS — PHP AtCommandRunner ─────────────────────────────────────

    private function sendViaPhp(string $to, string $message): void
    {
        $runner = $this->makeRunner();
        $runner->open();

        try {
            $runner->send('AT', 'OK');
            $runner->send('AT+CMGF=1', 'OK');
            $runner->send('AT+CSCS="GSM"', 'OK');

            $body = mb_substr(
                $this->normalizeSmsBody($message),
                0,
                (int) ($this->config['max_sms_length'] ?? 160)
            );
            $runner->send('AT+CMGS="' . $to . '"', '>');

            $response = $runner->sendRaw($body . chr(26));

            if (! str_contains($response, '+CMGS:')) {
                throw ModemException::sendFailed($to, 'No +CMGS confirmation received.');
            }
        } finally {
            $runner->close();
        }
    }

    private function pingViaPhp(): bool
    {
        $runner = $this->makeRunner();
        try {
            $runner->open();
            $response = $runner->send('AT', 'OK', 5);
            return str_contains($response, 'OK');
        } catch (ModemException) {
            return false;
        } finally {
            $runner->close();
        }
    }

    private function makeRunner(): AtCommandRunner
    {
        return new AtCommandRunner(
            port:     $this->getPort(),
            baudRate: (int) ($this->config['baud_rate'] ?? 115200),
            timeout:  (int) ($this->config['timeout']   ?? 10),
        );
    }

    private function getPort(): string
    {
        $port = $this->config['port'] ?? null;

        if (blank($port)) {
            throw new ModemException('Modem port is not configured in gateway settings.');
        }

        return $port;
    }

    /**
     * Normalize web textarea newlines into modem-friendly SMS line breaks.
     * Also preserve user-entered spacing as much as possible by converting
     * leading/repeated spaces into non-breaking spaces.
     */
    private function normalizeSmsBody(string $message): string
    {
        $normalizedNewlines = preg_replace("/\r\n|\r|\n/", "\r\n", $message) ?? $message;

        return collect(explode("\r\n", $normalizedNewlines))
            ->map(function (string $line) {
                $line = preg_replace_callback('/^ +/u', function ($matches) {
                    return str_repeat("\u{00A0}", strlen($matches[0]));
                }, $line) ?? $line;

                return preg_replace_callback('/ {2,}/u', function ($matches) {
                    return str_repeat("\u{00A0}", strlen($matches[0]));
                }, $line) ?? $line;
            })
            ->implode("\r\n");
    }

}
