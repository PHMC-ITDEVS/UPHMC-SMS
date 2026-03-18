<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateways', function (Blueprint $table) {
            $table->id();

            $table->string('name');                       // e.g. "Itegno W3800U - SIM1", "Semaphore API"
            $table->string('type');                       // 'modem' | 'api'  (GatewayType enum value)
            $table->boolean('is_active')->default(false);
            $table->unsignedTinyInteger('priority')->default(1);  // 1 = highest priority

            /**
             * JSONB config — differs per type:
             *
             * Modem (Itegno W3800U via USB/Serial AT commands):
             * {
             *   "port": "/dev/ttyUSB0",      -- serial device path on Linux
             *   "baud_rate": 9600,
             *   "timeout": 10,               -- seconds to wait for AT response
             *   "pin": null                  -- SIM PIN if required
             * }
             *
             * API (REST-based, e.g. Semaphore PH, Twilio):
             * {
             *   "base_url": "https://api.semaphore.co/api/v4/messages",
             *   "api_key": "xxxx",
             *   "sender_name": "YourClinic",
             *   "timeout": 30
             * }
             */
            $table->jsonb('config')->default('{}');

            $table->timestamps();

            $table->index('type');
            $table->index('priority');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateways');
    }
};