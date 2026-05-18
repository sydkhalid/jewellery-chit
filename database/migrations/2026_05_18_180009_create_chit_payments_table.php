<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chit_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->unique();
            $table->foreignId('enrollment_id')->constrained('chit_enrollments')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('chit_installments')->nullOnDelete();
            $table->foreignId('payment_mode_id')->constrained('payment_modes')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('payment_date')->index();
            $table->decimal('amount', 14, 2);
            $table->decimal('late_fee_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->string('transaction_id')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('success')->index();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enrollment_id', 'status']);
            $table->index(['customer_id', 'payment_date']);
            $table->index(['branch_id', 'payment_date']);
            $table->index(['staff_id', 'payment_date']);
            $table->index(['payment_mode_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chit_payments');
    }
};
