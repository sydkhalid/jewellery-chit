<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jewellery_invoices', function (Blueprint $table) {
            $table->foreignId('finalized_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable()->after('finalized_by')->index();
            $table->foreignId('cancelled_by')->nullable()->after('finalized_at')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by')->index();
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('jewellery_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'finalized_at',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};
