<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chit_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('scheme_code')->unique();
            $table->string('name')->index();
            $table->string('scheme_type', 30)->index();
            $table->decimal('monthly_amount', 12, 2)->nullable();
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->decimal('gold_weight', 10, 3)->nullable();
            $table->unsignedSmallInteger('duration_months')->index();
            $table->string('shop_bonus_type', 20)->default('none')->index();
            $table->decimal('shop_bonus_value', 12, 2)->default(0);
            $table->unsignedSmallInteger('grace_period_days')->default(0);
            $table->string('late_fee_type', 20)->default('none')->index();
            $table->decimal('late_fee_value', 12, 2)->default(0);
            $table->text('maturity_rule')->nullable();
            $table->text('early_closing_rule')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scheme_type', 'status']);
            $table->index(['duration_months', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chit_schemes');
    }
};
