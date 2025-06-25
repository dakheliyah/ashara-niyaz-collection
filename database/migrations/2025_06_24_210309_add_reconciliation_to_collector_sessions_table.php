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
        Schema::table('collector_sessions', function (Blueprint $table) {
            $table->enum('reconciliation_status', ['pending', 'reconciled'])->default('pending')->after('status');
            $table->string('reconciled_by')->nullable()->after('reconciliation_status');
            $table->timestamp('reconciled_at')->nullable()->after('reconciled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collector_sessions', function (Blueprint $table) {
            $table->dropColumn(['reconciliation_status', 'reconciled_by', 'reconciled_at']);
        });
    }
};
