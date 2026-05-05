<?php

use App\Models\Theater;
use Database\Seeders\AnbaRuweisTheaterSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-seed the مسرح الأنبا رويس layout under the unified "hall" model.
 *
 * Background: the previous layout split rows into balcony (A–H) and hall
 * (I–R). The user clarified that the physical theater is a single continuous
 * seating area; the spec JSON labels everything as "hall". Some row counts
 * also differed (e.g. row I had a 10th center seat in DB but only 9 in the
 * spec). This migration wipes the old layout and re-runs the seeder so the
 * DB exactly matches the supplied JSON.
 *
 * Safety:
 *   - `seat_blocks` and `booking_seats` both have `seat_id` foreign keys with
 *     ON DELETE CASCADE, so deleting seats automatically removes any rows
 *     referencing them.
 *   - The Anba Ruweis seat-based booking flow shipped a few hours ago in
 *     PR B; production has no real seat-based bookings yet.
 *
 * Idempotent: re-running this migration is a no-op since the seeder it
 * delegates to is idempotent (upsert on theater_id/section/row/seat_number).
 */
return new class extends Migration
{
    public function up(): void
    {
        $theater = Theater::where('slug', Theater::SLUG_ANBA_RUWEIS)->first();

        if ($theater) {
            // Cascades through seat_blocks and booking_seats.
            DB::table('seats')->where('theater_id', $theater->id)->delete();
        }

        // Re-create the layout from the seeder (now uses section = hall for
        // every row and matches the supplied JSON exactly).
        (new AnbaRuweisTheaterSeeder())->run();
    }

    public function down(): void
    {
        // Intentionally a no-op: rolling back this migration would leave the
        // theater seatless. If a rollback is genuinely needed, the previous
        // layout can be restored by checking out the prior version of
        // AnbaRuweisTheaterSeeder and re-running it manually.
    }
};
