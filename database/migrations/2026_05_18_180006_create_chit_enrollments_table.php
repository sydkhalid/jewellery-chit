<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chit_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('chit_no')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('scheme_id')->constrained('chit_schemes')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->index();
            $table->unsignedTinyInteger('monthly_due_date')->index();
            $table->date('maturity_date')->index();
            $table->string('agreement_file')->nullable();
            $table->unsignedSmallInteger('total_months');
            $table->decimal('monthly_amount', 12, 2)->nullable();
            $table->decimal('total_payable', 14, 2);
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->decimal('total_pending', 14, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['scheme_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['assigned_staff_id', 'status']);
            $table->index(['maturity_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chit_enrollments');
    }
};
