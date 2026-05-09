<?php

use App\Models\Theater;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drop the 9 historical row-Q center seats from مسرح الأنبا رويس.
 *
 * Background: row Q in the hall layout used to ship a 9-seat
 * "admin-only" center block (numbers 1..9 under group_side = 'center').
 * The seat-picker preset already hid those seats from rendering via
 * `adminOnlyCenter: ['Q']`, but they were still present in the seats
 * table — which means the new auto-capacity calculation
 * (`Show::seatMapCapacity()`, introduced in PR #67) was reporting
 * Hall = 484 instead of the live-layout truth of 475.
 *
 * The accompanying seeder change drops Q.center from the layout source
 * of truth so a fresh `db:seed` produces 475 directly. This migration
 * cleans up the orphaned rows on environments that were already seeded.
 *
 * Safety:
 *   - `seat_blocks.seat_id` and `booking_seats.seat_id` are FKs with
 *     ON DELETE CASCADE, so any blocked / booked Q.center seat reference
 *     is removed automatically. Q.center was never customer-bookable
 *     (the picker hid it), so in practice only stale admin blocks would
 *     be affected.
 *
 * Idempotent: re-running is a no-op once the rows have been removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $theater = Theater::where('slug', Theater::SLUG_ANBA_RUWEIS)->first();
        if (!$theater) {
            return;
        }

        DB::table('seats')
            ->where('theater_id', $theater->id)
            ->where('section', Theater::SECTION_HALL)
            ->where('row_letter', 'Q')
            ->where('group_side', 'center')
            ->delete();
    }

    public function down(): void
    {
        // Intentionally a no-op. Restoring the retired Q.center block
        // would require re-inserting 9 seats, which collides with the
        // current seeder/source-of-truth (it no longer lists them) and
        // would re-introduce the 484 vs 475 capacity drift.
    }
};
