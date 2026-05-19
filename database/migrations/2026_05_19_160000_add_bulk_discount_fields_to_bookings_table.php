<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk-discount bookkeeping columns.
 *
 *   original_price    — the pre-discount total (unit_price * tickets_count).
 *                       Nullable for legacy rows; the controller backfills
 *                       it for every new booking.
 *   discount_percent  — discount applied as a whole-number percent (e.g. 20).
 *                       0 when no discount.
 *   discount_amount   — discount in EGP, derived server-side. 0 when no
 *                       discount.
 *
 * `total_price` continues to be the FINAL amount the customer paid
 * (post-discount), so existing admin dashboards / revenue queries keep
 * working without changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('original_price', 10, 2)->nullable()->after('total_price');
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('original_price');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'discount_percent', 'discount_amount']);
        });
    }
};
