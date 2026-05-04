<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Originally added Cloudinary public_id columns to the archives /
     * archive_images tables. Those tables were removed in
     * 2026_05_04_231630_drop_about_and_archive_tables, but this migration
     * still has a row in the migrations table on environments that ran it
     * before the drop. Make it a no-op when the tables aren't there so
     * fresh installs (which never had archives at all) can run cleanly.
     */
    public function up(): void
    {
        if (Schema::hasTable('archives')) {
            Schema::table('archives', function (Blueprint $table) {
                $table->string('poster_public_id')->nullable();
            });
        }

        if (Schema::hasTable('archive_images')) {
            Schema::table('archive_images', function (Blueprint $table) {
                $table->string('image_public_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        // No-op: the archives tables themselves are dropped in a later
        // migration, so reversing column additions is unnecessary.
    }
};
