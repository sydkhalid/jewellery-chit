<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_code')->unique();
            $table->string('name')->index();
            $table->string('mobile', 20)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->text('address');
            $table->string('city')->index();
            $table->string('state')->index();
            $table->string('pincode', 12)->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
