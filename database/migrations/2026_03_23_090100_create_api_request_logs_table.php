<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->string('ip_address')->nullable();
            $table->json('request_payload')->nullable();
            $table->unsignedSmallInteger('response_code');
            $table->json('response_summary')->nullable();
            $table->timestamps();

            $table->index(['api_client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
