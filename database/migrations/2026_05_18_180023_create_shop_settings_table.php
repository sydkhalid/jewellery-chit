<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('text')->index();
            $table->string('group_name')->nullable()->index();
            $table->timestamps();

            $table->index(['group_name', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
