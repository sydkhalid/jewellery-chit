<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('name')->index();
            $table->string('mobile', 20)->unique();
            $table->string('alternate_mobile', 20)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('photo')->nullable();
            $table->string('aadhaar_no', 20)->nullable()->index();
            $table->string('pan_no', 20)->nullable()->index();
            $table->text('address');
            $table->string('city')->index();
            $table->string('state')->index();
            $table->string('pincode', 12)->index();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'city']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
