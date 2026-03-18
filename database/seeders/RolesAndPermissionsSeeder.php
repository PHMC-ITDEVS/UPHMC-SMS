<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Laratrust-compatible roles and permissions seeder.
     *
     * Idempotent: safe to re-run via db:seed without duplicates.
     * Uses firstOrCreate to avoid unique constraint violations.
     */
    public function run(): void
    {
        $permissions = [];

        foreach (PermissionEnum::cases() as $permission) {
            $permissions[$permission->value] = Permission::firstOrCreate(
                ['name' => $permission->value],
                ['display_name' => $permission->name]
            );
        }

        $admin = Role::firstOrCreate(
            ['name' => RoleEnum::ADMIN->value],
            ['display_name' => 'ADMIN']
        );
        $adminPermissions = array_filter(
            $permissions,
            fn ($key) => $key !== PermissionEnum::MANAGE_ROLES->value,
            ARRAY_FILTER_USE_KEY
        );
        $admin->syncPermissions(array_values($adminPermissions));

        $staff = Role::firstOrCreate(
            ['name' => RoleEnum::STAFF->value],
            ['display_name' => 'STAFF']
        );
        $staffPermissions = [
            $permissions[PermissionEnum::SEND_SMS->value],
            $permissions[PermissionEnum::MANAGE_CONTACTS->value],
            $permissions[PermissionEnum::VIEW_SMS_LOGS->value],
        ];
        $staff->syncPermissions($staffPermissions);

        $manager = Role::firstOrCreate(
            ['name' => RoleEnum::MANAGER->value],
            ['display_name' => 'MANAGER']
        );
        $managerPermissions = [
            $permissions[PermissionEnum::SEND_SMS->value],
            $permissions[PermissionEnum::VIEW_SMS_LOGS->value],
        ];
        $manager->syncPermissions($managerPermissions);

        $this->command->info('✅ Roles and permissions seeded successfully.');
        $this->command->table(
            ['Role', 'Permissions Count'],
            [
                [RoleEnum::ADMIN->value,       count($adminPermissions)],
                [RoleEnum::STAFF->value,       count($staffPermissions)],
                [RoleEnum::MANAGER->value,     count($managerPermissions)],
            ]
        );
    }
}
