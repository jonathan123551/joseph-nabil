<?php

namespace Database\Seeders;

use App\Models\Seat;
use App\Models\Theater;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the مسرح الأنبا رويس theater + its full seat layout.
 *
 * Layout matches the unified "hall" JSON the user supplied — rows A–R, all
 * tagged as `section = hall`. Balcony was deprecated in this iteration (the
 * physical hall is one continuous seating area). The previous balcony/hall
 * split has been removed and the migration that calls this seeder wipes
 * the old layout before re-seeding.
 *
 * For each row we record three groups separated by the two aisles, drawn
 * left-to-right as printed:
 *
 *   - 'left'   : odd numbers, descending toward the center aisle
 *   - 'center' : odd 9..1 then even 2..(8 or 10) — narrower middle block
 *   - 'right'  : even numbers, ascending away from the center aisle
 *
 * Rows A and R have no center block (just two outer wings) — they are the
 * curved edge rows.
 *
 * Idempotent: existing seats are upserted on (theater_id, section,
 * row_letter, seat_number).
 */
class AnbaRuweisTheaterSeeder extends Seeder
{
    public function run(): void
    {
        $theater = Theater::updateOrCreate(
            ['slug' => Theater::SLUG_ANBA_RUWEIS],
            ['name' => 'مسرح الأنبا رويس']
        );

        $rows = $this->layout();

        $now  = now();
        $rows_to_upsert = [];

        foreach ($rows as $row) {
            foreach (['left', 'center', 'right'] as $side) {
                $numbers = $row[$side] ?? [];
                foreach ($numbers as $i => $seatNumber) {
                    $rows_to_upsert[] = [
                        'theater_id'    => $theater->id,
                        'section'       => Theater::SECTION_HALL,
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

        // Defensive dedupe by the unique key used by the upsert. Postgres rejects
        // an upsert batch where two rows collide on the conflict target with:
        //   "ON CONFLICT DO UPDATE command cannot affect row a second time"
        // The layout below is hand-transcribed, so a typo could reintroduce a
        // collision. Keeping this guard means a typo manifests as a missing seat
        // (recoverable) rather than a hard migration failure on Railway.
        $rows_to_upsert = Collection::make($rows_to_upsert)
            ->unique(fn ($r) => $r['theater_id'].'|'.$r['section'].'|'.$r['row_letter'].'|'.$r['seat_number'])
            ->values()
            ->all();

        foreach (array_chunk($rows_to_upsert, 200) as $chunk) {
            DB::table('seats')->upsert(
                $chunk,
                ['theater_id', 'section', 'row_letter', 'seat_number'],
                ['group_side', 'display_order', 'updated_at']
            );
        }
    }

    /**
     * Layout = exact transcription of the user-supplied JSON
     * (theater = anba_ruweis_hall, rows A–R).
     */
    private function layout(): array
    {
        return [
            // Row A — curved edge, no center block
            ['row' => 'A',
                'left'   => [21, 19, 17, 15, 13, 11],
                'right'  => [10, 12, 14, 16, 18, 20]],

            ['row' => 'B',
                'left'   => [23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8, 10],
                'right'  => [12, 14, 16, 18, 20, 22, 24]],

            ['row' => 'C',
                'left'   => [23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22]],

            ['row' => 'D',
                'left'   => [25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8, 10],
                'right'  => [12, 14, 16, 18, 20, 22, 24, 26]],

            ['row' => 'E',
                'left'   => [25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24]],

            ['row' => 'F',
                'left'   => [27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8, 10],
                'right'  => [12, 14, 16, 18, 20, 22, 24, 26, 28]],

            ['row' => 'G',
                'left'   => [27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26]],

            ['row' => 'H',
                'left'   => [27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8, 10],
                'right'  => [12, 14, 16, 18, 20, 22, 24, 26, 28]],

            ['row' => 'I',
                'left'   => [27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26]],

            ['row' => 'J',
                'left'   => [29, 27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26, 28]],

            ['row' => 'K',
                'left'   => [29, 27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26, 28]],

            ['row' => 'L',
                'left'   => [29, 27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26, 28]],

            ['row' => 'M',
                'left'   => [29, 27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26, 28]],

            ['row' => 'N',
                'left'   => [31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]],

            ['row' => 'O',
                'left'   => [31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]],

            ['row' => 'P',
                'left'   => [31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]],

            ['row' => 'Q',
                'left'   => [31, 29, 27, 25, 23, 21, 19, 17, 15, 13, 11],
                'center' => [9, 7, 5, 3, 1, 2, 4, 6, 8],
                'right'  => [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]],

            // Row R — curved last row, no center block
            ['row' => 'R',
                'left'   => [23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1],
                'right'  => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24]],
        ];
    }
}
