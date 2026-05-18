<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chit_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('chit_enrollments')->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_no');
            $table->date('due_date')->index();
            $table->decimal('due_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->date('paid_date')->nullable()->index();
            $table->timestamps();

            $table->unique(['enrollment_id', 'installment_no']);
            $table->index(['enrollment_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chit_installments');
    }
};
