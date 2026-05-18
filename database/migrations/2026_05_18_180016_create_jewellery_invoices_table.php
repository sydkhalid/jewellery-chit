<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jewellery_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('chit_enrollments')->nullOnDelete();
            $table->date('invoice_date')->index();
            $table->decimal('gold_rate', 12, 2);
            $table->decimal('gross_weight', 10, 3);
            $table->decimal('net_weight', 10, 3);
            $table->decimal('making_charge', 14, 2);
            $table->decimal('wastage', 14, 2);
            $table->decimal('gst_amount', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('chit_adjustment_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->decimal('balance_payable', 14, 2);
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'invoice_date']);
            $table->index(['enrollment_id', 'invoice_date']);
            $table->index(['status', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jewellery_invoices');
    }
};
