<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date')->index();
            $table->decimal('gold_22k', 12, 2);
            $table->decimal('gold_24k', 12, 2);
            $table->decimal('silver_rate', 12, 2)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->index();
            $table->boolean('rate_locked')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['rate_date', 'status']);
            $table->index(['rate_locked', 'rate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_rates');
    }
};
