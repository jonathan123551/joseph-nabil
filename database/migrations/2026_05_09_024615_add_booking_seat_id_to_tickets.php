<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wire each Ticket to the specific BookingSeat it represents, so the
     * gate scanner can resolve "this QR -> this attendee + this seat"
     * instead of showing the booking owner's name and the whole list of
     * booked seats. The column is intentionally nullable: bookings made
     * through the manual / "Other" venue flow have no booking_seats rows,
     * and those tickets remain valid with booking_seat_id = NULL.
     *
     * The migration is additive and idempotent. The back-fill pairs each
     * ticket with the booking_seat at the same ordinal position within the
     * booking. This is safe because the seat-based booking flow inserts
     * the booking_seats and the tickets in the same order
     * (request.names[]) inside a single transaction, so id-order is the
     * insertion order on every existing row.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('booking_seat_id')
                ->nullable()
                ->after('booking_id')
                ->constrained('booking_seats')
                ->nullOnDelete();
        });

        // Back-fill: match tickets to booking_seats by ordinal position
        // within the same booking. Skip tickets that are already wired or
        // whose booking has no seats (manual / "Other" flow).
        $bookingsWithSeats = DB::table('booking_seats')
            ->select('booking_id')
            ->distinct()
            ->pluck('booking_id');

        foreach ($bookingsWithSeats as $bookingId) {
            $tickets = DB::table('tickets')
                ->where('booking_id', $bookingId)
                ->orderBy('id')
                ->get(['id', 'booking_seat_id']);

            $seats = DB::table('booking_seats')
                ->where('booking_id', $bookingId)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            foreach ($tickets as $i => $ticket) {
                if ($ticket->booking_seat_id !== null) {
                    continue;
                }
                if (!isset($seats[$i])) {
                    continue;
                }
                DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->update(['booking_seat_id' => $seats[$i]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_seat_id');
        });
    }
};
