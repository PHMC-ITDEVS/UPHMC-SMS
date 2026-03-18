<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;


class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'ADMIN',
                'description' => 'Complete access to system', 
            ],
            [
                'name' => 'manager',
                'display_name' => 'MANAGER',
                'description' => 'Responsible for overseeing a specific department, Duties may include task delegation, planning, performance monitoring, coaching, and decision-making',
            ],
        ];

        Role::insert($roles);
    }
}
