<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the theater-selection feature flag and per-section prices to shows.
     *
     * - theater_type: 'other' (default, manual ticket count) or 'anba_ruweis'
     *   (cinema-style seat picker, configured in PR B).
     * - balcony_price / hall_price: only used when theater_type = 'anba_ruweis';
     *   null otherwise. Stored as unsigned integers (EGP, no decimals) to match
     *   the existing show_times.ticket_price convention.
     */
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->string('theater_type', 32)->default('other')->after('is_active');
            $table->unsignedInteger('balcony_price')->nullable()->after('theater_type');
            $table->unsignedInteger('hall_price')->nullable()->after('balcony_price');
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->dropColumn(['theater_type', 'balcony_price', 'hall_price']);
        });
    }
};
