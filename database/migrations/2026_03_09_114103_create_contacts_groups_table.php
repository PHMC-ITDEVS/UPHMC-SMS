<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Groups (e.g. "Ward A", "ICU Staff", "OPD Patients")
        Schema::create('contact_groups', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });

        // Many-to-many: contacts ↔ groups
        Schema::create('contact_group_members', function (Blueprint $table) {
            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->foreignId('contact_group_id')
                ->constrained('contact_groups')
                ->cascadeOnDelete();

            $table->timestamp('added_at')->useCurrent();

            $table->primary(['contact_id', 'contact_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_group_members');
        Schema::dropIfExists('contact_groups');
    }
};