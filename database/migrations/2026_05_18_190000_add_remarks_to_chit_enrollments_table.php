<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chit_enrollments', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('agreement_file');
        });
    }

    public function down(): void
    {
        Schema::table('chit_enrollments', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
