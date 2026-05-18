<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('chit_enrollments')->nullOnDelete();
            $table->string('notification_type', 30)->index();
            $table->string('title');
            $table->text('message');
            $table->string('channel', 20)->index();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['enrollment_id', 'status']);
            $table->index(['channel', 'status']);
            $table->index(['notification_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
