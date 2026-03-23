<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->string('source', 20)->default('web')->after('status');
            $table->foreignId('api_client_id')->nullable()->after('sender_id')->constrained('api_clients')->nullOnDelete();

            $table->index('source');
            $table->index('api_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('api_client_id');
            $table->dropColumn('source');
        });
    }
};
