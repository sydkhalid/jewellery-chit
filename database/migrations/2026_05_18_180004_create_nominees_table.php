<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name')->index();
            $table->string('relationship')->index();
            $table->string('mobile', 20)->nullable()->index();
            $table->text('address')->nullable();
            $table->string('aadhaar_no', 20)->nullable()->index();
            $table->timestamps();

            $table->index(['customer_id', 'relationship']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominees');
    }
};
