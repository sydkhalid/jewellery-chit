<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chit_closures', function (Blueprint $table) {
            $table->id();
            $table->string('closure_no')->unique();
            $table->foreignId('enrollment_id')->constrained('chit_enrollments')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('closure_type', 20)->index();
            $table->decimal('total_paid', 14, 2);
            $table->decimal('shop_bonus', 14, 2);
            $table->decimal('deductions', 14, 2);
            $table->decimal('final_maturity_value', 14, 2);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->decimal('jewellery_adjustment_amount', 14, 2)->default(0);
            $table->string('customer_signature')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enrollment_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['closure_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chit_closures');
    }
};
