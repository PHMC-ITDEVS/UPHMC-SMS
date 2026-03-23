<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_key', 32)->unique();
            $table->text('client_secret_hash');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->json('allowed_ips')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
