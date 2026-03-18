<?php

namespace App\Services\Sms;

use App\Enums\GatewayType;
use App\Models\SmsGateway;
use App\Services\Sms\Contracts\SmsGatewayInterface;
use App\Services\Sms\Drivers\ModemDriver;
use App\Services\Sms\Exceptions\NoAvailableGatewayException;
use Illuminate\Support\Facades\Log;

class GatewayRouter
{
    /**
     * Resolve the primary active gateway driver from DB.
     *
     * @throws NoAvailableGatewayException
     */
    public function resolve(): SmsGatewayInterface
    {
        $gateway = SmsGateway::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();

        if ($gateway === null) {
            Log::error('[GatewayRouter] No active gateway found in database.');
            throw new NoAvailableGatewayException();
        }

        Log::debug("[GatewayRouter] Resolved gateway: {$gateway->name} [{$gateway->type->value}]");

        return $this->buildDriver($gateway);
    }

    /**
     * Resolve gateway by explicit DB record ID.
     *
     * @throws NoAvailableGatewayException
     */
    public function resolveById(int $gatewayId): SmsGatewayInterface
    {
        $gateway = SmsGateway::find($gatewayId);

        if ($gateway === null || ! $gateway->is_active) {
            throw new NoAvailableGatewayException();
        }

        return $this->buildDriver($gateway);
    }

    /**
     * Health check all active gateways.
     *
     * @return array<string, bool>
     */
    public function healthCheck(): array
    {
        $results = [];

        SmsGateway::where('is_active', true)->orderBy('priority')->each(function ($gateway) use (&$results) {
            try {
                $results[$gateway->name] = $this->buildDriver($gateway)->isHealthy();
            } catch (\Throwable $e) {
                Log::warning("[GatewayRouter] Health check failed for {$gateway->name}: {$e->getMessage()}");
                $results[$gateway->name] = false;
            }
        });

        return $results;
    }

    private function buildDriver(SmsGateway $gateway): SmsGatewayInterface
    {
        return match ($gateway->type) {
            GatewayType::MODEM => new ModemDriver((array) ($gateway->config ?? [])),
            default => throw new \InvalidArgumentException(
                "Unsupported gateway type: [{$gateway->type->value}]"
            ),
        };
    }
}
