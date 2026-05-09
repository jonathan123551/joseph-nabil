<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ScannerController extends Controller
{
    public function index()
    {
        return view('admin.scanner');
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = trim($data['code']);

        // Eager-load:
        //   - bookingSeat: this ticket's specific seat (PR #70). Lets the
        //     scanner resolve a QR straight to one attendee + one seat.
        //   - booking.seats: full booking seat list, used as a fallback
        //     when the ticket has no booking_seat_id yet (e.g. a manual /
        //     "Other" booking, or a legacy ticket the back-fill missed).
        //   - booking.showTime.show: title / date / time on the result card.
        $ticket = Ticket::with([
                'bookingSeat',
                'booking.showTime.show',
                'booking.seats',
            ])
            ->where('ticket_code', $code)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status'  => 'error',
                'message' => 'غير موجود',
            ]);
        }

        if ($ticket->booking->status !== 'approved') {
            return response()->json([
                'status'  => 'error',
                'message' => 'غير معتمد',
            ]);
        }

        $time    = $ticket->booking->showTime;
        $booking = $ticket->booking;

        // ---- Per-ticket seat identity (PR #70) ----
        // Prefer the ticket's own bookingSeat (each QR -> one attendee +
        // one seat). Fall back to the booking's first seat for legacy
        // tickets that pre-date PR #70's back-fill, or to nothing for
        // manual / "Other" venue bookings.
        $bookingSeats = $booking->seats ?? collect();
        $ticketSeat   = $ticket->bookingSeat ?? $bookingSeats->first();

        $seatPayload = $ticketSeat ? [
            'section'     => $ticketSeat->section,
            'row_letter'  => $ticketSeat->row_letter,
            'seat_number' => (int) $ticketSeat->seat_number,
            'label'       => trim((string) $ticketSeat->row_letter) . (int) $ticketSeat->seat_number,
        ] : null;

        $sectionList = $bookingSeats->pluck('section')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $payload = [
            'name'          => $ticket->name,
            'phone'         => $ticket->phone,
            'show_title'    => optional($time->show)->title ?? '',
            'date'          => optional($time->date)->format('d/m/Y'),
            'time'          => $time->time
                ? Carbon::parse($time->time)->format('g:i A')
                : '',
            'tickets_count' => (int) ($booking->tickets_count ?? $bookingSeats->count()),
            'reference'     => $booking->reference_code ?? '',
            // The seat THIS QR represents — used for the big seat badge.
            'seat'          => $seatPayload,
            // Full booking seat list, kept for backward compatibility and
            // shown as small chips alongside the primary seat.
            'seats'         => $bookingSeats->map(fn ($s) => [
                'section'     => $s->section,
                'row_letter'  => $s->row_letter,
                'seat_number' => (int) $s->seat_number,
                'label'       => trim((string) $s->row_letter) . (int) $s->seat_number,
            ])->values()->all(),
            'sections'      => $sectionList,
            'scanned_at'    => $ticket->scanned_at
                ? Carbon::parse($ticket->scanned_at)->format('g:i A')
                : null,
        ];

        // Already scanned — return the same enriched payload but flagged
        // as "used" so the front-end shows the amber state.
        if ($ticket->scanned_at) {
            return response()->json(array_merge([
                'status'  => 'used',
                'message' => 'تم استخدامها',
            ], $payload));
        }

        // First successful scan — mark the ticket and respond with the
        // freshly-stamped scan time.
        $ticket->scanned_at = now();
        $ticket->is_scanned = true;
        $ticket->save();

        $payload['scanned_at'] = now()->format('g:i A');

        return response()->json(array_merge([
            'status'  => 'ok',
            'message' => 'دخول مسموح',
        ], $payload));
    }
}
