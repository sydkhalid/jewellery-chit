<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chit_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->foreignId('payment_id')->constrained('chit_payments')->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained('chit_enrollments')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('receipt_date')->index();
            $table->decimal('amount', 14, 2);
            $table->string('pdf_path')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enrollment_id', 'receipt_date']);
            $table->index(['customer_id', 'receipt_date']);
            $table->index(['status', 'receipt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chit_receipts');
    }
};
