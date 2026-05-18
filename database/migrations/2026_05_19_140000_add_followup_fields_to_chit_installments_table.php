<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chit_installments', function (Blueprint $table) {
            $table->string('followup_status', 30)->default('pending')->after('paid_date')->index();
            $table->date('promise_to_pay_date')->nullable()->after('followup_status')->index();
            $table->text('followup_remarks')->nullable()->after('promise_to_pay_date');
            $table->timestamp('last_followup_at')->nullable()->after('followup_remarks')->index();
            $table->unsignedInteger('reminder_count')->default(0)->after('last_followup_at');
            $table->timestamp('last_reminder_at')->nullable()->after('reminder_count')->index();

            $table->index(['followup_status', 'promise_to_pay_date']);
        });
    }

    public function down(): void
    {
        Schema::table('chit_installments', function (Blueprint $table) {
            $table->dropIndex(['followup_status', 'promise_to_pay_date']);
            $table->dropColumn([
                'followup_status',
                'promise_to_pay_date',
                'followup_remarks',
                'last_followup_at',
                'reminder_count',
                'last_reminder_at',
            ]);
        });
    }
};
