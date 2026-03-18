<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\AtCommandRunner;
use App\Services\Sms\Contracts\SmsGatewayInterface;
use App\Services\Sms\Exceptions\ModemException;
use App\Services\Sms\ModemConnectionManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends SMS via Itegno W3800U using AT commands.
 *
 * AT Command flow:
 *   1. AT              → OK     (ping)
 *   2. AT+CMGF=1       → OK     (text mode)
 *   3. AT+CSCS="GSM"   → OK     (character set)
 *   4. AT+CMGS="<to>"  → >      (start SMS, wait for prompt)
 *   5. <body>+chr(26)  → +CMGS  (send, Ctrl+Z terminates)
 *
 * Config keys (from sms_gateways.config JSONB):
 *   port, baud_rate, timeout, max_sms_length
 */
class ModemDriver implements SmsGatewayInterface
{
    private ModemConnectionManager $connectionManager;

    public function __construct(private readonly array $config)
    {
        $this->connectionManager = new ModemConnectionManager(
            port: $this->getPort(),
            baudRate: (int) ($config['baud_rate'] ?? 9600),
        );
    }

    public function send(string $to, string $message): array
    {
        $reference = Str::uuid()->toString();

        if (! $this->isPortAvailable()) {
            return [
                'success'   => false,
                'gateway'   => $this->getName(),
                'reference' => null,
                'error'     => 'modem_disconnected',
            ];
        }

        if (! $this->connectionManager->acquire()) {
            return [
                'success'   => false,
                'gateway'   => $this->getName(),
                'reference' => null,
                'error'     => 'modem_busy',
            ];
        }

        try {
            $runner = $this->connectionManager->getRunner();
            $this->initializeModem($runner);
            $this->sendSmsCommand($runner, $to, $message);

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

            return [
                'success'   => false,
                'gateway'   => $this->getName(),
                'reference' => null,
                'error'     => $e->getMessage(),
            ];

        } finally {
            $this->connectionManager->release();
        }
    }

    public function isHealthy(): bool
    {
        if (! $this->isPortAvailable()) {
            return false;
        }

        if (! $this->connectionManager->acquire()) {
            return true; // busy but alive
        }

        try {
            $response = $this->connectionManager->getRunner()->send('AT', 'OK', 5);
            return str_contains($response, 'OK');
        } catch (ModemException) {
            return false;
        } finally {
            $this->connectionManager->release();
        }
    }

    public function getName(): string
    {
        return 'modem:' . $this->getPort();
    }

    private function initializeModem(AtCommandRunner $runner): void
    {
        $runner->send('AT', 'OK');
        $runner->send('AT+CMGF=1', 'OK');
        $runner->send('AT+CSCS="GSM"', 'OK');
    }

    private function sendSmsCommand(AtCommandRunner $runner, string $to, string $message): void
    {
        $body = mb_substr($message, 0, (int) ($this->config['max_sms_length'] ?? 160));

        $runner->send('AT+CMGS="' . $to . '"', '>');

        $response = $runner->sendRaw($body . chr(26));

        if (! str_contains($response, '+CMGS:')) {
            throw ModemException::sendFailed($to, 'No +CMGS confirmation received.');
        }
    }

    private function isPortAvailable(): bool
    {
        $port = $this->getPort();

        return $port !== '' && file_exists($port);
    }

    private function getPort(): string
    {
        $port = $this->config['port'] ?? null;

        if (blank($port)) {
            throw new ModemException('Modem port is not configured in gateway settings.');
        }

        return $port;
    }
}
