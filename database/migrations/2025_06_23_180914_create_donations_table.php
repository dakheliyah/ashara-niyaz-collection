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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collector_session_id')->constrained('collector_sessions');
            $table->string('donor_its_id');
            $table->foreignId('donation_type_id')->constrained('donation_types');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('amount', 10, 2);
            $table->timestamp('donated_at');
            $table->string('receipt_url')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
