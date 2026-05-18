<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table): void {
            $table->string('message_type', 30)->default('general')->after('customer_id')->index();
            $table->index(['message_type', 'status']);
        });

        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->string('message_type', 30)->default('general')->after('customer_id')->index();
            $table->index(['message_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table): void {
            $table->dropIndex(['message_type', 'status']);
            $table->dropColumn('message_type');
        });

        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->dropIndex(['message_type', 'status']);
            $table->dropColumn('message_type');
        });
    }
};
