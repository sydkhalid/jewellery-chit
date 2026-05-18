<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chit_payments', function (Blueprint $table) {
            $table->string('payment_type', 30)->default('partial')->after('remarks')->index();
            $table->string('edit_status', 30)->nullable()->after('status')->index();
            $table->json('edit_payload')->nullable()->after('edit_status');
            $table->foreignId('edit_requested_by')->nullable()->after('edit_payload')->constrained('users')->nullOnDelete();
            $table->timestamp('edit_requested_at')->nullable()->after('edit_requested_by')->index();
            $table->foreignId('edit_approved_by')->nullable()->after('edit_requested_at')->constrained('users')->nullOnDelete();
            $table->timestamp('edit_approved_at')->nullable()->after('edit_approved_by')->index();
        });
    }

    public function down(): void
    {
        Schema::table('chit_payments', function (Blueprint $table) {
            $table->dropForeign(['edit_requested_by']);
            $table->dropForeign(['edit_approved_by']);
            $table->dropColumn([
                'payment_type',
                'edit_status',
                'edit_payload',
                'edit_requested_by',
                'edit_requested_at',
                'edit_approved_by',
                'edit_approved_at',
            ]);
        });
    }
};
