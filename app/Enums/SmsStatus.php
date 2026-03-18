<?php

namespace App\Enums;

enum SmsStatus: string
{
    case DRAFT      = 'draft';
    case QUEUED     = 'queued';
    case PROCESSING = 'processing';
    case DONE       = 'done';
    case FAILED     = 'failed';

    public static function values(): array
    {
        return collect(self::cases())
            ->map(fn ($case): string => $case->value)
            ->toArray();
    }
}