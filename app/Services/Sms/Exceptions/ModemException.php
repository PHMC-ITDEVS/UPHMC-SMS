<?php

namespace App\Services\Sms\Exceptions;

class ModemException extends GatewayException
{
    public static function portNotFound(string $port): self
    {
        return new self("Serial port [{$port}] not found or not accessible.");
    }

    public static function connectionFailed(string $port, string $reason): self
    {
        return new self("Failed to open serial port [{$port}]: {$reason}");
    }

    public static function atCommandFailed(string $command, string $response): self
    {
        return new self("AT command [{$command}] failed. Modem response: {$response}");
    }

    public static function timeout(string $command): self
    {
        return new self("Timeout waiting for modem response to command [{$command}].");
    }

    public static function sendFailed(string $to, string $reason): self
    {
        return new self("Failed to send SMS to [{$to}]: {$reason}");
    }
}