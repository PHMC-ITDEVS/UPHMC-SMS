<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('phone_number', 20);      // E.164 format: +639XXXXXXXXX
            // $table->string('email')->nullable();
            $table->text('notes')->nullable();

            // Soft ownership — who added this contact
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate phone numbers
            $table->unique('phone_number');

            // Fast lookups by name/phone
            $table->index('name');
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};