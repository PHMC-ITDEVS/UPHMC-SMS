<?php

namespace App\Services\Sms\Contracts;

interface SmsGatewayInterface
{
    /**
     * Send a single SMS message.
     *
     * @param  string  $to      Phone number
     * @param  string  $message Message body
     * @return array{
     *     success: bool,
     *     gateway: string,
     *     reference: string|null,
     *     error: string|null
     * }
     */
    public function send(string $to, string $message): array;

    /**
     * Check if this gateway is currently healthy/reachable.
     */
    public function isHealthy(): bool;

    /**
     * Human-readable name for logging and UI display.
     */
    public function getName(): string;
}