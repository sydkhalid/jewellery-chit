<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chit_enrollments', function (Blueprint $table): void {
            $table->index(['status', 'start_date'], 'ce_status_start_idx');
            $table->index(['branch_id', 'status', 'start_date'], 'ce_branch_status_start_idx');
            $table->index(['assigned_staff_id', 'status', 'start_date'], 'ce_staff_status_start_idx');
        });

        Schema::table('chit_installments', function (Blueprint $table): void {
            $table->index(['status', 'balance_amount', 'due_date'], 'ci_status_balance_due_idx');
            $table->index(['enrollment_id', 'due_date', 'status'], 'ci_enrollment_due_status_idx');
            $table->index(['followup_status', 'due_date'], 'ci_followup_due_idx');
        });

        Schema::table('chit_payments', function (Blueprint $table): void {
            $table->index(['status', 'payment_date'], 'cp_status_payment_date_idx');
            $table->index(['status', 'branch_id', 'payment_date'], 'cp_status_branch_date_idx');
            $table->index(['status', 'staff_id', 'payment_date'], 'cp_status_staff_date_idx');
            $table->index(['status', 'payment_mode_id', 'payment_date'], 'cp_status_mode_date_idx');
        });

        Schema::table('chit_receipts', function (Blueprint $table): void {
            $table->index(['payment_id', 'status'], 'cr_payment_status_idx');
            $table->index(['status', 'customer_id', 'receipt_date'], 'cr_status_customer_date_idx');
        });

        Schema::table('gold_rates', function (Blueprint $table): void {
            $table->index(['status', 'rate_date', 'rate_locked'], 'gr_status_date_locked_idx');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['channel', 'status', 'created_at'], 'notifications_channel_status_created_idx');
        });

        Schema::table('whatsapp_logs', function (Blueprint $table): void {
            $table->index(['message_type', 'status', 'created_at'], 'wa_type_status_created_idx');
            $table->index(['mobile', 'message_type', 'created_at'], 'wa_mobile_type_created_idx');
        });

        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->index(['message_type', 'status', 'created_at'], 'sms_type_status_created_idx');
            $table->index(['mobile', 'message_type', 'created_at'], 'sms_mobile_type_created_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->index(['action', 'created_at'], 'activity_action_created_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['auditable_type', 'event', 'created_at'], 'audit_type_event_created_idx');
        });

        Schema::table('integration_transactions', function (Blueprint $table): void {
            $table->index(['gateway_type', 'status', 'created_at'], 'integration_type_status_created_idx');
            $table->index(['provider', 'status', 'created_at'], 'integration_provider_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('integration_transactions', function (Blueprint $table): void {
            $table->dropIndex('integration_type_status_created_idx');
            $table->dropIndex('integration_provider_status_created_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_type_event_created_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex('activity_action_created_idx');
        });

        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->dropIndex('sms_type_status_created_idx');
            $table->dropIndex('sms_mobile_type_created_idx');
        });

        Schema::table('whatsapp_logs', function (Blueprint $table): void {
            $table->dropIndex('wa_type_status_created_idx');
            $table->dropIndex('wa_mobile_type_created_idx');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_channel_status_created_idx');
        });

        Schema::table('gold_rates', function (Blueprint $table): void {
            $table->dropIndex('gr_status_date_locked_idx');
        });

        Schema::table('chit_receipts', function (Blueprint $table): void {
            $table->dropIndex('cr_payment_status_idx');
            $table->dropIndex('cr_status_customer_date_idx');
        });

        Schema::table('chit_payments', function (Blueprint $table): void {
            $table->dropIndex('cp_status_payment_date_idx');
            $table->dropIndex('cp_status_branch_date_idx');
            $table->dropIndex('cp_status_staff_date_idx');
            $table->dropIndex('cp_status_mode_date_idx');
        });

        Schema::table('chit_installments', function (Blueprint $table): void {
            $table->dropIndex('ci_status_balance_due_idx');
            $table->dropIndex('ci_enrollment_due_status_idx');
            $table->dropIndex('ci_followup_due_idx');
        });

        Schema::table('chit_enrollments', function (Blueprint $table): void {
            $table->dropIndex('ce_status_start_idx');
            $table->dropIndex('ce_branch_status_start_idx');
            $table->dropIndex('ce_staff_status_start_idx');
        });
    }
};
