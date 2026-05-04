<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permanently drops the About + Archive feature tables and removes the
     * orphaned migration rows for the deleted migration files so that
     * `php artisan migrate:status` stays clean.
     */
    public function up(): void
    {
        Schema::dropIfExists('archive_images');
        Schema::dropIfExists('archives');
        Schema::dropIfExists('abouts');

        DB::table('migrations')
            ->whereIn('migration', [
                '2025_12_08_023052_create_about_table',
                '2025_12_16_191545_add_founded_year_to_abouts_table',
                '2025_12_18_174812_create_archives_table',
                '2025_12_18_194837_create_archive_images_table.php',
                '2025_12_20_234055_remove_images_column_from_archives_table',
                '2025_12_22_230643_add_facebook_reel_to_archives_table',
            ])
            ->delete();
    }

    /**
     * Irreversible — these features were removed deliberately.
     */
    public function down(): void
    {
        // no-op
    }
};
