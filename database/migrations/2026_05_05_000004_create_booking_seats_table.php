<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Join table: which physical seats are tied to which booking, scoped by
     * show_time so the same seat can be booked across different show times.
     *
     * The unique(show_time_id, seat_id) constraint is the database-level guard
     * that makes double-booking impossible even under race conditions.
     */
    public function up(): void
    {
        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('show_time_id')->constrained()->cascadeOnDelete();
            $table->string('section', 16);
            $table->string('row_letter', 2);
            $table->unsignedSmallInteger('seat_number');
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->unique(['show_time_id', 'seat_id']);
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
    }
};
