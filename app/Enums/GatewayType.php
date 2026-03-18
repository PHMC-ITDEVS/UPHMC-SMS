<?php

namespace App\Enums;

enum GatewayType: string
{
    case MODEM = 'modem';  // Itegno W3800U via USB/Serial AT commands
    case API   = 'api';    // REST-based SMS API (fallback)

    public static function values(): array
    {
        return collect(self::cases())
            ->map(fn ($case): string => $case->value)
            ->toArray();
    }
}