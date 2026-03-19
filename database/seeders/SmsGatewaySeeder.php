<?php

namespace Database\Seeders;

use App\Enums\GatewayType;
use App\Models\SmsGateway;
use Illuminate\Database\Seeder;

class SmsGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $defaultPort = match (PHP_OS_FAMILY) {
            'Windows' => 'COM3',
            'Darwin'  => '/dev/cu.usbserial-0001',
            default   => '/dev/ttyUSB0',
        };

        SmsGateway::firstOrCreate(
            ['name' => 'Itegno W3800U'],
            [
                'type'      => GatewayType::MODEM,
                'is_active' => true,
                'priority'  => 1,
                'config'    => [
                    'port'           => $defaultPort,
                    'baud_rate'      => 115200,
                    'timeout'        => 10,
                    'max_sms_length' => 160,
                ],
            ]
        );

        $this->command->info("✅ SMS Gateway seeded. Default port: {$defaultPort}");
        $this->command->warn("   → Update the port via Admin > Gateway Settings if needed.");
    }
}