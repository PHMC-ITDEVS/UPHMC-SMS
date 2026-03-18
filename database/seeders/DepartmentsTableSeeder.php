<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentsTableSeeder extends Seeder
{
    public function run(): void
    {
        Department::firstOrCreate(
            ['name' => 'Information Technology'],
            ['description' => 'Information Technology department']
        );
    }
}
