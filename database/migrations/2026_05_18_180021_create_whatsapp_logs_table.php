<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('mobile', 20)->index();
            $table->text('message');
            $table->longText('response')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['mobile', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
