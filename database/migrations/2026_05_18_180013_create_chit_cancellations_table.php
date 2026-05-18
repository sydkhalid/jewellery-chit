<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chit_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('chit_enrollments')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('cancellation_date')->index();
            $table->text('reason');
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->decimal('deduction_amount', 14, 2)->default(0);
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['enrollment_id', 'cancellation_date']);
            $table->index(['customer_id', 'cancellation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chit_cancellations');
    }
};
