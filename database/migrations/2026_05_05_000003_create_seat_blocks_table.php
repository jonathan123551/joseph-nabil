<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-show-time admin overrides: any row here marks a seat as blocked
     * (unavailable / management-reserved) for that specific show time.
     * Removing the row re-opens the seat.
     */
    public function up(): void
    {
        Schema::create('seat_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_time_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['show_time_id', 'seat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_blocks');
    }
};
