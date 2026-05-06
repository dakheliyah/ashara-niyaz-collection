<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('reconciliation_status')->default('unreconciled')->after('remarks');
            $table->decimal('reconciled_amount', 15, 2)->nullable()->after('reconciliation_status');
            $table->timestamp('reconciled_at')->nullable()->after('reconciled_amount');
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->onDelete('set null')->after('reconciled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['reconciled_by']);
            $table->dropColumn(['reconciliation_status', 'reconciled_amount', 'reconciled_at', 'reconciled_by']);
        });
    }
};
