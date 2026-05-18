<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jewellery_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('jewellery_invoices')->cascadeOnDelete();
            $table->string('item_name')->index();
            $table->string('purity')->nullable()->index();
            $table->decimal('gross_weight', 10, 3);
            $table->decimal('net_weight', 10, 3);
            $table->decimal('rate', 12, 2);
            $table->decimal('making_charge', 14, 2)->default(0);
            $table->decimal('wastage', 14, 2)->default(0);
            $table->decimal('gst_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->timestamps();

            $table->index(['invoice_id', 'item_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jewellery_invoice_items');
    }
};
