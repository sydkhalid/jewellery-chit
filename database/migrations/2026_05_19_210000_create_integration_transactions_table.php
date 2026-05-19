<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway_type', 40);
            $table->string('provider', 80);
            $table->string('mode', 20)->default('sandbox');
            $table->string('direction', 40)->default('request');
            $table->string('status', 40)->default('pending');
            $table->nullableMorphs('reference');
            $table->string('local_reference')->nullable();
            $table->string('external_id')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['gateway_type', 'provider', 'status'], 'integration_type_provider_status_idx');
            $table->index(['mode', 'direction'], 'integration_mode_direction_idx');
            $table->index(['external_id', 'provider'], 'integration_external_provider_idx');
            $table->index(['local_reference', 'provider'], 'integration_local_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_transactions');
    }
};
