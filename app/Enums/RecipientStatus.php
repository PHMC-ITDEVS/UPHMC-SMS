<?php

namespace App\Enums;

enum RecipientStatus: string
{
    case PENDING = 'pending';
    case SENT    = 'sent';
    case FAILED  = 'failed';

    public static function values(): array
    {
        return collect(self::cases())
            ->map(fn ($case): string => $case->value)
            ->toArray();
    }
}