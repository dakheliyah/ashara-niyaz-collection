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
        Schema::create('mumineen', function (Blueprint $table) {
            $table->bigInteger('its_id')->primary();
            $table->bigInteger('hof_id')->nullable();
            $table->string('fullname');
            $table->string('gender')->nullable();
            $table->integer('age')->nullable();
            $table->string('mobile')->nullable();
            $table->string('country')->nullable();
            $table->string('jamaat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mumineen');
    }
};
