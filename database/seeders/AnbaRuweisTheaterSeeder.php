<?php

namespace Database\Seeders;

use App\Models\Seat;
use App\Models\Theater;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the مسرح الأنبا رويس theater + its seat layout.
 *
 * Layout transcription comes from the printed seat map provided by the user
 * (rows A–R, fan-shaped). For each row we record three groups separated by
 * the two aisles, drawn left-to-right as printed:
 *
 *   - 'left'   : odd numbers, descending toward the center aisle
 *   - 'center' : odd 9..1 then even 2..10 (~10 seats wide)
 *   - 'right'  : even numbers, ascending away from the center aisle
 *
 * Rows A–H are بلكون (balcony, near the stage).
 * Rows I–R are صالة (hall, main floor). Row R is the last curved row and has
 * no center block — only the two outer wings.
 *
 * Idempotent: safe to re-run; existing seats are upserted by
 * (theater_id, section, row_letter, seat_number).
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
                        'section'       => $row['section'],
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

        // chunk to keep a single transactional upsert under typical PG limits
        foreach (array_chunk($rows_to_upsert, 200) as $chunk) {
            DB::table('seats')->upsert(
                $chunk,
                ['theater_id', 'section', 'row_letter', 'seat_number'],
                ['group_side', 'display_order', 'updated_at']
            );
        }
    }

    /**
     * Defining the layout in code — easy to tweak if the printed map changes.
     * Each row is { row, section, left[], center[], right[] }.
     *
     * Numbering convention copied off the printed map: odd left, even right,
     * with the center block holding the lowest numbers (closest to the aisle).
     */
    private function layout(): array
    {
        $balcony = Theater::SECTION_BALCONY;
        $hall    = Theater::SECTION_HALL;

        // Helper builders for the descending-odd left wing and ascending-even
        // right wing. They yield the visual left→right order.
        $leftOdd = function (int $highestOdd, int $lowestOdd = 11): array {
            $out = [];
            for ($n = $highestOdd; $n >= $lowestOdd; $n -= 2) {
                $out[] = $n;
            }
            return $out;
        };

        $rightEven = function (int $lowestEven, int $highestEven): array {
            $out = [];
            for ($n = $lowestEven; $n <= $highestEven; $n += 2) {
                $out[] = $n;
            }
            return $out;
        };

        $center9to1Plus2to10 = [9, 7, 5, 3, 1, 2, 4, 6, 8, 10];
        $center9to1Plus2to8  = [9, 7, 5, 3, 1, 2, 4, 6, 8];

        return [
            // ===== BALCONY (rows A–H, near stage) =====
            // For rows whose center block already includes seat 10 (the
            // $center9to1Plus2to10 variant), the right wing must start at 12
            // — starting at 10 would duplicate seat 10 in the same row.
            ['row' => 'A', 'section' => $balcony,
                'left'   => $leftOdd(21),                 // 21,19,...,11
                'center' => $center9to1Plus2to10,
                'right'  => $rightEven(12, 20)],

            ['row' => 'B', 'section' => $balcony,
                'left'   => $leftOdd(23),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 22)],

            ['row' => 'C', 'section' => $balcony,
                'left'   => $leftOdd(23),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 22)],

            ['row' => 'D', 'section' => $balcony,
                'left'   => $leftOdd(25),
                'center' => $center9to1Plus2to10,
                'right'  => $rightEven(12, 24)],

            ['row' => 'E', 'section' => $balcony,
                'left'   => $leftOdd(25),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 24)],

            ['row' => 'F', 'section' => $balcony,
                'left'   => $leftOdd(27),
                'center' => $center9to1Plus2to10,
                'right'  => $rightEven(12, 26)],

            ['row' => 'G', 'section' => $balcony,
                'left'   => $leftOdd(27),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 26)],

            ['row' => 'H', 'section' => $balcony,
                'left'   => $leftOdd(27),
                'center' => $center9to1Plus2to10,
                'right'  => $rightEven(12, 28)],

            // ===== HALL (rows I–R, main floor) =====
            ['row' => 'I', 'section' => $hall,
                'left'   => $leftOdd(27),
                'center' => $center9to1Plus2to10,
                'right'  => $rightEven(12, 28)],

            ['row' => 'J', 'section' => $hall,
                'left'   => $leftOdd(29),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 28)],

            ['row' => 'K', 'section' => $hall,
                'left'   => $leftOdd(29),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 28)],

            ['row' => 'L', 'section' => $hall,
                'left'   => $leftOdd(29),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 28)],

            ['row' => 'M', 'section' => $hall,
                'left'   => $leftOdd(29),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 28)],

            ['row' => 'N', 'section' => $hall,
                'left'   => $leftOdd(31),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 30)],

            ['row' => 'O', 'section' => $hall,
                'left'   => $leftOdd(31),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 30)],

            ['row' => 'P', 'section' => $hall,
                'left'   => $leftOdd(31),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 30)],

            ['row' => 'Q', 'section' => $hall,
                'left'   => $leftOdd(31),
                'center' => $center9to1Plus2to8,
                'right'  => $rightEven(10, 30)],

            // Last row R is curved with NO center block — odd 1..23 on the
            // left half, even 2..24 on the right half.
            ['row' => 'R', 'section' => $hall,
                'left'   => [23, 21, 19, 17, 15, 13, 11, 9, 7, 5, 3, 1],
                'center' => [],
                'right'  => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24]],
        ];
    }
}
