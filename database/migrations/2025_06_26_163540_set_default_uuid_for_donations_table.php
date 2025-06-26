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
            // Backfill existing records
            \App\Models\Donation::whereNull('uuid')->cursor()->each(function ($donation) {
                $donation->uuid = \Illuminate\Support\Str::uuid();
                $donation->save();
            });

            // Now, alter the column to be non-nullable and have a default
            $table->uuid('uuid')->nullable(false)->default(\Illuminate\Support\Facades\DB::raw('(UUID())'))->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            // Revert the column to be nullable and remove the default
            $table->uuid('uuid')->nullable()->default(null)->change();
        });
    }
};
