<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_recipients', function (Blueprint $table) {
            $table->timestamp('dispatch_at')->nullable()->after('sent_at');
            $table->index('dispatch_at');
        });
    }

    public function down(): void
    {
        Schema::table('sms_recipients', function (Blueprint $table) {
            $table->dropIndex(['dispatch_at']);
            $table->dropColumn('dispatch_at');
        });
    }
};
