<?php

use Database\Seeders\AnbaRuweisTheaterSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Run the مسرح الأنبا رويس layout seeder as part of the regular migration
 * pipeline so production deploys end up with a populated theater + seats
 * table without any manual `db:seed` step.
 *
 * The seeder itself is idempotent (DB upsert on
 * theater_id + section + row_letter + seat_number), so re-running this
 * migration is safe in any environment.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new AnbaRuweisTheaterSeeder())->run();
    }

    public function down(): void
    {
        DB::table('seats')
            ->whereIn('theater_id', DB::table('theaters')->where('slug', 'anba-ruweis')->pluck('id'))
            ->delete();
        DB::table('theaters')->where('slug', 'anba-ruweis')->delete();
    }
};
