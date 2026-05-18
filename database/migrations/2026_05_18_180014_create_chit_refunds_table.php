<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chit_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_no')->unique();
            $table->foreignId('enrollment_id')->constrained('chit_enrollments')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->date('refund_date')->index();
            $table->decimal('amount', 14, 2);
            $table->string('transaction_id')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enrollment_id', 'status']);
            $table->index(['customer_id', 'refund_date']);
            $table->index(['payment_mode_id', 'refund_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chit_refunds');
    }
};
