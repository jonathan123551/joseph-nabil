<?php

use App\Models\Theater;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seed the new "anba_ruweis_ballacon" balcony layout for مسرح الأنبا رويس.
 *
 * The hall layout (rows A–R, section = hall) is preserved untouched. This
 * migration adds a SECOND seating area on top of it: section = balcony,
 * rows A–H, with the row/seat numbers supplied by the user.
 *
 * Layout (matches the user-supplied JSON exactly):
 *   - Rows A/B/C : left + center + right (center stays perfectly centered)
 *   - Rows D/E/F : left + right only (no center; geometry anchors to row C)
 *   - Rows G/H   : left + right only, slightly wider wings
 *
 * The seat-picker partial reads these rows under section='balcony' and
 * applies a layout preset that inserts a real geometry gap between row C
 * and row D — the cinematic balcony walkway.
 *
 * Idempotent: existing balcony seats are upserted on the unique key
 * (theater_id, section, row_letter, seat_number), so re-running the
 * migration is a no-op.
 *
 * Safety: this migration only INSERTS rows; it does not delete any
 * existing seats. Hall seats remain exactly as the prior unified-hall
 * migration left them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $theater = Theater::where('slug', Theater::SLUG_ANBA_RUWEIS)->first();
        if (!$theater) {
            // No theater yet → nothing to attach balcony seats to. The
            // earlier seeder migration is responsible for creating it.
            return;
        }

        $now  = now();
        $rows = $this->balconyLayout();

        $batch = [];
        foreach ($rows as $row) {
            foreach (['left', 'center', 'right'] as $side) {
                $numbers = $row[$side] ?? [];
                foreach ($numbers as $i => $seatNumber) {
                    $batch[] = [
                        'theater_id'    => $theater->id,
                        'section'       => Theater::SECTION_BALCONY,
                        'row_letter'    => $row['row'],
                        'seat_number'   => $seatNumber,
                        'group_side'    => $side,
                        'display_order' => $i,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
        }

        // Defensive dedupe on the unique key (matches the hall seeder's
        // safety net): a typo in the layout would manifest as a missing
        // seat, not a hard upsert failure.
        $batch = Collection::make($batch)
            ->unique(fn ($r) => $r['theater_id'].'|'.$r['section'].'|'.$r['row_letter'].'|'.$r['seat_number'])
            ->values()
            ->all();

        foreach (array_chunk($batch, 200) as $chunk) {
            DB::table('seats')->upsert(
                $chunk,
                ['theater_id', 'section', 'row_letter', 'seat_number'],
                ['group_side', 'display_order', 'updated_at']
            );
        }
    }

    public function down(): void
    {
        $theater = Theater::where('slug', Theater::SLUG_ANBA_RUWEIS)->first();
        if (!$theater) {
            return;
        }

        // Cascade through seat_blocks and booking_seats via the FK
        // ON DELETE CASCADE on seats.id.
        DB::table('seats')
            ->where('theater_id', $theater->id)
            ->where('section', Theater::SECTION_BALCONY)
            ->delete();
    }

    /**
     * Exact transcription of the user-supplied JSON
     * (theater = anba_ruweis_ballacon).
     */
    private function balconyLayout(): array
    {
        return [
            ['row' => 'A',
                'left'   => [35, 33, 31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9],
                'center' => [7, 5, 3, 1, 2, 4, 6],
                'right'  => [8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34]],

            ['row' => 'B',
                'left'   => [35, 33, 31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9],
                'center' => [7, 5, 3, 1, 2, 4, 6],
                'right'  => [8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34]],

            ['row' => 'C',
                'left'   => [35, 33, 31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9],
                'center' => [7, 5, 3, 1, 2, 4, 6],
                'right'  => [8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34]],

            // Rows D/E/F — no center; left+right only.
            ['row' => 'D',
                'left'  => [29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1],
                'right' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]],

            ['row' => 'E',
                'left'  => [29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1],
                'right' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]],

            ['row' => 'F',
                'left'  => [29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1],
                'right' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]],

            // Rows G/H — slightly wider wings (16 seats per side).
            ['row' => 'G',
                'left'  => [31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1],
                'right' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32]],

            ['row' => 'H',
                'left'  => [31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1],
                'right' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32]],
        ];
    }
};
