<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_cash_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('handover_no')->unique();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('handover_date')->index();
            $table->decimal('cash_amount', 14, 2)->default(0);
            $table->decimal('upi_amount', 14, 2)->default(0);
            $table->decimal('card_amount', 14, 2)->default(0);
            $table->decimal('bank_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['staff_id', 'handover_date']);
            $table->index(['branch_id', 'handover_date']);
            $table->index(['status', 'handover_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_cash_handovers');
    }
};
