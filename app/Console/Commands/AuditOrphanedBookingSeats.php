<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit: lists every booking_seats row whose parent booking
 * has status = 'rejected'. These rows are orphans — they block new
 * bookings via the unique(show_time_id, seat_id) constraint but are
 * invisible to unavailableSeatIds() which only checks approved/pending.
 *
 * Usage:   php artisan booking:audit-orphaned-seats
 * Output:  Table of orphaned rows + summary counts.
 * Safety:  This command does NOT delete or modify any data.
 */
class AuditOrphanedBookingSeats extends Command
{
    protected $signature = 'booking:audit-orphaned-seats';

    protected $description = 'List booking_seats rows linked to rejected bookings (read-only audit)';

    public function handle(): int
    {
        $this->info('');
        $this->info('=== Orphaned Booking Seats Audit (READ-ONLY) ===');
        $this->info('');

        $rows = DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->where('bookings.status', 'rejected')
            ->select([
                'booking_seats.id as booking_seat_id',
                'booking_seats.booking_id',
                'booking_seats.seat_id',
                'booking_seats.show_time_id',
                'booking_seats.section',
                'booking_seats.row_letter',
                'booking_seats.seat_number',
                'bookings.status as booking_status',
                'bookings.full_name',
                'bookings.phone',
                'bookings.created_at as booking_created_at',
            ])
            ->orderBy('booking_seats.show_time_id')
            ->orderBy('booking_seats.booking_id')
            ->orderBy('booking_seats.seat_number')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('✅ No orphaned booking_seats found. All clean.');
            return self::SUCCESS;
        }

        $this->warn("⚠️  Found {$rows->count()} orphaned booking_seats row(s):");
        $this->info('');

        $this->table(
            [
                'booking_seat_id',
                'booking_id',
                'seat_id',
                'show_time_id',
                'section',
                'row',
                'seat#',
                'booking_status',
                'customer',
                'phone',
                'booking_created_at',
            ],
            $rows->map(fn ($r) => [
                $r->booking_seat_id,
                $r->booking_id,
                $r->seat_id,
                $r->show_time_id,
                $r->section,
                $r->row_letter,
                $r->seat_number,
                $r->booking_status,
                $r->full_name,
                $r->phone,
                $r->booking_created_at,
            ])
        );

        // Summary
        $this->info('');
        $this->info('--- Summary ---');

        $byShowTime = $rows->groupBy('show_time_id');
        foreach ($byShowTime as $stId => $group) {
            $seatLabels = $group->map(fn ($r) => "{$r->section} {$r->row_letter}{$r->seat_number}")->implode(', ');
            $bookingIds = $group->pluck('booking_id')->unique()->implode(', ');
            $this->line("  show_time_id={$stId}: {$group->count()} seat(s) [{$seatLabels}] from booking(s) [{$bookingIds}]");
        }

        $this->info('');
        $this->warn("Total orphaned rows: {$rows->count()}");
        $this->warn("Affected show_times: {$byShowTime->count()}");
        $this->warn("Affected bookings:   " . $rows->pluck('booking_id')->unique()->count());
        $this->info('');
        $this->info('To clean up, run a separate cleanup command or manually delete these rows.');
        $this->info('This command is READ-ONLY and made no changes.');

        return self::SUCCESS;
    }
}
