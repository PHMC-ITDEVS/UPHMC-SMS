<?php

namespace App\Enums;

enum Permission: string
{
    // User & Role Management
    case MANAGE_USERS       = 'manage-users';
    case MANAGE_ROLES       = 'manage-roles';

    // Phonebook
    case MANAGE_CONTACTS    = 'manage-contacts';
    case MANAGE_GROUPS      = 'manage-groups';

    // SMS
    case SEND_SMS           = 'send-sms';
    case VIEW_SMS_LOGS      = 'view-sms-logs';
    case EXPORT_REPORTS     = 'export-reports';

    // Gateway (admin-only)
    case MANAGE_GATEWAYS    = 'manage-gateways';

    public static function values(): array
    {
        return collect(self::cases())
            ->map(fn ($case): string => $case->value)
            ->toArray();
    }

    public static function labels(): array
    {
        return [
            self::MANAGE_USERS->value    => 'Manage Users',
            self::MANAGE_ROLES->value    => 'Manage Roles',
            self::MANAGE_CONTACTS->value => 'Manage Contacts',
            self::MANAGE_GROUPS->value   => 'Manage Groups',
            self::SEND_SMS->value        => 'Send SMS',
            self::VIEW_SMS_LOGS->value   => 'View SMS Logs',
            self::EXPORT_REPORTS->value  => 'Export Reports',
            self::MANAGE_GATEWAYS->value => 'Manage Gateways',
        ];
    }
}