<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each row of this table is a single physical seat in a theater.
     *
     * - section: 'balcony' (بلكون) or 'hall' (صالة).
     * - group_side: 'left' | 'center' | 'right' — three visual groups per row,
     *   separated by aisles, used to render the fan-shaped layout.
     * - display_order: 0-based ordering inside (row_letter, group_side),
     *   left-to-right as drawn on the printed map.
     */
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theater_id')->constrained()->cascadeOnDelete();
            $table->string('section', 16);
            $table->string('row_letter', 2);
            $table->unsignedSmallInteger('seat_number');
            $table->string('group_side', 8);
            $table->unsignedSmallInteger('display_order');
            $table->timestamps();

            $table->unique(['theater_id', 'section', 'row_letter', 'seat_number']);
            $table->index(['theater_id', 'section', 'row_letter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
