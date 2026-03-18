<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::where('name', 'Information Technology')->first();

        if (! $department) {
            $this->command?->warn('Information Technology department not found. Skipping positions seeder.');
            return;
        }

        $positions = [
            'Programmer & System Analyst',
            'Application Support',
            'Technician Lead',
            'Technician Support',
            'Database & System Development Supervisor',
            'System Administrator',
            'Network Administrator',
            'Network Cable Technician',
            'Cyber Security Administrator',
            'Project Manager',
            'Coordinator',
            'Director',
            'Operations Manager',
        ];

        foreach ($positions as $positionName) {
            Position::firstOrCreate(
                [
                    'department_id' => $department->id,
                    'name' => $positionName,
                ],
                [
                    'description' => $positionName,
                ]
            );
        }
    }
}
