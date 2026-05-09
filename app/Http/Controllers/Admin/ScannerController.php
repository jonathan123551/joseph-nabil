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

        // Eager-load the booking's seats so the scanner UI can show seat
        // labels + section without a follow-up round trip. Bookings made
        // through the manual ("Other") flow won't have any rows in
        // booking_seats — that's expected and the UI hides the seat block.
        $ticket = Ticket::with(['booking.showTime.show', 'booking.seats'])
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

        $seats       = $booking->seats ?? collect();
        $sectionList = $seats->pluck('section')
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
            'tickets_count' => (int) ($booking->tickets_count ?? $seats->count()),
            'reference'     => $booking->reference_code ?? '',
            // Premium UX (PR #69): seat labels + sections so the scan-result
            // card can show "Hall · A12 · A14" instead of just a name.
            'seats'         => $seats->map(fn ($s) => [
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
