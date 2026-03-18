<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- sms_messages ---
        // One record per "send action" (could be bulk or single)
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();

            // Who composed and sent this message
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('message_body');

            // single | bulk | scheduled
            $table->string('type', 20)->default('single');

            // Overall status of the batch
            // draft | queued | processing | done | failed
            $table->string('status', 20)->default('draft');

            // Which gateway was selected/used for this send
            $table->foreignId('gateway_id')
                ->nullable()
                ->constrained('sms_gateways')
                ->nullOnDelete();

            // Populated when gateway auto-switches on failure
            $table->foreignId('fallback_gateway_id')
                ->nullable()
                ->constrained('sms_gateways')
                ->nullOnDelete();

            // For scheduled sends
            $table->timestamp('scheduled_at')->nullable();

            // Aggregate counters — updated as jobs complete (avoids COUNT queries)
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('sender_id');
            $table->index('scheduled_at');
        });

        // --- sms_recipients ---
        // One record per phone number per message send
        Schema::create('sms_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sms_message_id')
                ->constrained('sms_messages')
                ->cascadeOnDelete();

            // Nullable — could be a manually-typed number not in phonebook
            $table->foreignId('contact_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();

            // Always store the number denormalized for log integrity
            // (contact could be deleted later, we still want the history)
            $table->string('phone_number', 20);

            // pending | sent | failed
            $table->string('status', 20)->default('pending');

            // Raw response from modem (AT result) or API (HTTP body snippet)
            $table->text('gateway_response')->nullable();

            $table->string('error_message')->nullable();

            // Which gateway actually delivered this specific recipient's SMS
            $table->string('gateway_used', 10)->nullable(); // 'modem' | 'api'

            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index('sms_message_id');
            $table->index('status');
            $table->index('phone_number');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_recipients');
        Schema::dropIfExists('sms_messages');
    }
};