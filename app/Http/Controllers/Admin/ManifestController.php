<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingSeat;
use App\Models\SeatBlock;
use App\Models\ShowTime;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Seat Occupancy / Attendee Manifest — Phase 2.
 *
 * Single per-showtime read-only page used by event organizers / ushers on
 * event day to map every physical seat to its attendee. Backed by three
 * eager-loaded queries (booked seats + admin-blocked seats + full layout
 * for seatmap shows) so it stays flat regardless of booking count.
 *
 * Three operator-led surfaces share one Blade template, each tuned to a
 * device + workflow:
 *
 *   ?mode=ops    (default on ≥ md) — Operations Console for desktop head
 *                                    ushers / box office. Chart-left,
 *                                    detail rail-right, persistent filter
 *                                    chips, live capacity gauge, bookings
 *                                    sidebar. Polls JSON for check-ins.
 *   ?mode=floor  (default on phones)— Floor Mode for floor ushers in dim
 *                                    halls. Forced dark, search pinned to
 *                                    the thumb-reach bottom, 80 px cards,
 *                                    booked + pending + blocked by default
 *                                    (empties off). Scanner FAB.
 *   ?mode=paper                    — A4-landscape paper sheet. Attendees-
 *                                    only by default (`include_empty=1`
 *                                    for full roll-call), glyph status,
 *                                    family color bands as soft backgrounds,
 *                                    hand-check-in ☐ column, per-section
 *                                    page breaks with running headers.
 *
 * Legacy `?view=grid|usher|grouped|print` URLs are mapped to the new modes
 * for back-compat (scanner deep-links + bookmarked pages keep working).
 *
 * Live polling endpoint `/admin/show-times/{id}/manifest.json` returns the
 * minimal payload Operations + Floor need to keep their gauge + check-in
 * ✓ markers current without a full reload.
 *
 * CSV export uses the same data assembly, streamed to download.
 */
class ManifestController extends Controller
{
    /**
     * Render the manifest page in the operator-led surface that fits the
     * caller's device and intent.
     *
     * Mode resolution order (first match wins):
     *   1. Explicit ?mode=ops|floor|paper.
     *   2. Legacy ?view=grid|usher|grouped|print  -> mapped to a mode.
     *   3. Mobile-class User-Agent                -> floor.
     *   4. Default                                -> ops.
     */
    public function show(ShowTime $showTime, Request $request)
    {
        $data = $this->buildManifest($showTime);

        $mode = $this->resolveMode($request);

        $showFullPhone = (bool) $request->query('full_phone', false);
        $includeEmpty = (bool) $request->query('include_empty', false);

        // Comma-separated allow-list of status filters; defaults to the
        // operationally useful set (booked + pending + blocked). The view
        // applies these client-side so a URL like
        // ?status=approved,checked_in remains shareable.
        $statusFilter = $this->resolveStatusFilter($request);

        // `?focus=A12` — used by the scanner deep-link and by the usher
        // chip taps. The view scrolls the matching seat into view and
        // animates the pulse + auto-opens the detail rail.
        $focusSeat = trim((string) $request->query('focus', ''));
        if ($focusSeat !== '') {
            $focusSeat = strtoupper($focusSeat);
        }

        // Bookings collection (grouped by booking_id) powers the
        // Operations Console right-rail bookings list and the booking-
        // colored hue rings on the chart.
        $bookings = $this->buildBookings($data['rows']);

        return view('admin.show_times.manifest', array_merge($data, [
            'mode' => $mode,
            'showFullPhone' => $showFullPhone,
            'includeEmpty' => $includeEmpty,
            'statusFilter' => $statusFilter,
            'focusSeat' => $focusSeat,
            'bookings' => $bookings,
        ]));
    }

    /**
     * Live-polling JSON endpoint. Returns the minimal payload Operations
     * + Floor need to keep their capacity gauge and per-seat ✓ markers
     * current. Intentionally smaller than the full row payload so polling
     * every 10 s stays cheap.
     */
    public function showJson(ShowTime $showTime): \Illuminate\Http\JsonResponse
    {
        $data = $this->buildManifest($showTime);

        $checkedInCount = 0;
        $scanned = [];
        foreach ($data['rows'] as $r) {
            if (! empty($r['is_scanned'])) {
                $checkedInCount++;
                $scanned[$r['row_letter'].$r['seat_number']] = $r['scanned_at'];
            }
        }

        return response()->json([
            'summary' => $data['summary'],
            'checked_in' => $checkedInCount,
            'scanned_seats' => $scanned,
            'server_time' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, max-age=0');
    }

    /**
     * Pick a surface for this request.
     *
     * Manifest v3 collapses to exactly two surfaces — Floor (live ops) and
     * Paper (printable sheet). Anything else (the retired Operations
     * Console, or any legacy `view=...` query string we used to honor) is
     * redirected to Floor so old URLs keep working without us shipping a
     * dashboard surface that nobody asked for.
     */
    protected function resolveMode(Request $request): string
    {
        $allowed = ['floor', 'paper'];

        $mode = strtolower((string) $request->query('mode', ''));
        if (in_array($mode, $allowed, true)) {
            return $mode;
        }

        // Legacy aliases — Operations Console and the older `view=` keys
        // all collapse into Floor. `print` is the only paper alias.
        $legacy = strtolower((string) $request->query('view', ''));
        if ($legacy === 'print' || $mode === 'print') {
            return 'paper';
        }

        return 'floor';
    }

    /**
     * Parse the `?status=` query into a normalized set of allowed statuses.
     * Accepts comma-separated tokens; falls back to the default operational
     * set (everything except empty + checked-in-only filter).
     */
    protected function resolveStatusFilter(Request $request): array
    {
        $defaults = ['approved', 'pending', 'blocked'];
        $valid = ['approved', 'pending', 'blocked', 'empty', 'checked_in'];

        $raw = (string) $request->query('status', '');
        if ($raw === '') {
            return $defaults;
        }

        $tokens = array_values(array_unique(array_filter(array_map(
            fn ($t) => strtolower(trim($t)),
            explode(',', $raw)
        ))));

        $tokens = array_values(array_intersect($tokens, $valid));

        return empty($tokens) ? $defaults : $tokens;
    }

    /**
     * Reduce the seat-major rows to a booking-major collection used by the
     * Operations Console right-rail list. Each entry carries enough to
     * render a one-line booking card + light up the booking's seats on
     * the chart when selected.
     */
    protected function buildBookings(array $rows): array
    {
        $byBooking = [];
        foreach ($rows as $r) {
            if ($r['booking_id'] === null) {
                continue;
            }
            $id = $r['booking_id'];
            if (! isset($byBooking[$id])) {
                $byBooking[$id] = [
                    'id' => $id,
                    'ref' => $r['booking_ref'],
                    'owner' => $r['booking_owner'],
                    'phone' => $r['phone'],
                    'status' => $r['status'],
                    'status_en' => $r['status_en'],
                    'seats' => [],
                    'seat_labels' => [],
                    'checked_in' => 0,
                ];
            }
            $label = $r['row_letter'].$r['seat_number'];
            $byBooking[$id]['seats'][] = $label;
            $byBooking[$id]['seat_labels'][] = $label;
            if (! empty($r['is_scanned'])) {
                $byBooking[$id]['checked_in']++;
            }
        }

        // Sort: pending first (they need approval), then approved, then by
        // owner name for stable ordering during scrubbing.
        $bookings = array_values($byBooking);
        usort($bookings, function ($a, $b) {
            $statusOrder = ['pending' => 0, 'approved' => 1];
            $ao = $statusOrder[$a['status']] ?? 9;
            $bo = $statusOrder[$b['status']] ?? 9;
            if ($ao !== $bo) {
                return $ao <=> $bo;
            }

            return strcmp((string) $a['owner'], (string) $b['owner']);
        });

        return $bookings;
    }

    /**
     * Stream the manifest as a CSV file. Always exports the seat-major
     * ordering (one row per seat) which is the most useful shape for
     * reconciliation / offline review. PII (full phone) is gated behind
     * an explicit query flag so an accidental click doesn't leak it.
     */
    public function exportCsv(ShowTime $showTime, Request $request): StreamedResponse
    {
        $data = $this->buildManifest($showTime);
        $showFullPhone = (bool) $request->query('full_phone', false);

        $filename = sprintf(
            'manifest-%s-%s.csv',
            optional($showTime->date)->format('Y-m-d') ?: 'showtime-'.$showTime->id,
            $showTime->id
        );

        return response()->streamDownload(function () use ($data, $showFullPhone) {
            $out = fopen('php://output', 'w');

            // BOM so Excel opens UTF-8 cleanly with the Arabic names.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Section',
                'Row',
                'Seat',
                'Attendee',
                'Booking Ref',
                'Booking Owner',
                'Phone',
                'Status',
                'Checked-In At',
            ]);

            foreach ($data['rows'] as $r) {
                $phoneCsv = '';
                if ($r['phone']) {
                    $phoneCsv = $showFullPhone ? $r['phone'] : $this->maskPhone($r['phone']);
                }
                fputcsv($out, [
                    $r['section_label_en'],
                    $r['row_letter'],
                    $r['seat_number'],
                    $r['attendee_name'] ?? '',
                    $r['booking_ref'] ?? '',
                    $r['booking_owner'] ?? '',
                    $phoneCsv,
                    $r['status_en'],
                    $r['scanned_at'] ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Assemble the manifest rows + summary counts from the database.
     *
     * For seatmap-backed shows we walk the full physical seat layout and
     * fill in attendee / blocked status per seat — so empties show up as
     * "—" rows. For "Other" / manual-capacity shows we just list the
     * attendees (no seat axis to anchor against).
     */
    protected function buildManifest(ShowTime $showTime): array
    {
        $showTime->loadMissing('show');
        $show = $showTime->show;

        $usesSeatMap = $show && $show->usesSeatMap();

        // Pending + approved booking seats with their booking. We pull
        // the matched ticket separately (keyed by booking_seat_id) and
        // index it locally rather than relying on a constrained eager-
        // load — Eloquent's constrained closure can't reference the
        // parent `booking_seats.id` from inside the `booking.tickets`
        // load, so a closure-based whereColumn there would silently
        // produce wrong rows for multi-attendee bookings.
        $bookedQ = BookingSeat::query()
            ->where('show_time_id', $showTime->id)
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['approved', 'pending']);
            })
            ->with([
                'booking:id,reference_code,full_name,phone,status,paid_at,approved_at',
            ])
            ->orderBy('section')
            ->orderBy('row_letter')
            ->orderBy('seat_number');

        $bookedSeats = $bookedQ->get();

        // PR #70: tickets carry the per-attendee identity. Pull them in
        // one query keyed by booking_seat_id so each seat row resolves
        // to the right attendee in O(1) — no N+1, no closure trickery.
        $ticketsBySeatId = \App\Models\Ticket::query()
            ->whereIn('booking_seat_id', $bookedSeats->pluck('id'))
            ->get(['id', 'booking_id', 'booking_seat_id', 'name', 'phone', 'is_scanned', 'scanned_at'])
            ->keyBy('booking_seat_id');

        // Stash the matching ticket on each BookingSeat as a transient
        // attribute so the row-builder can reach it without extra args.
        foreach ($bookedSeats as $bs) {
            $bs->setRelation(
                'matchedTicket',
                $ticketsBySeatId->get($bs->id)
            );
        }

        $booked = $bookedSeats->keyBy(function (BookingSeat $bs) {
            return $this->seatKey($bs->section, $bs->row_letter, (int) $bs->seat_number);
        });

        // Admin-held seats (separate from booked seats). We render them
        // as BLOCKED rows so ushers don't seat anyone there even when
        // the row otherwise looks empty.
        $blocked = collect();
        if ($usesSeatMap) {
            $blocked = SeatBlock::query()
                ->where('show_time_id', $showTime->id)
                ->with('seat:id,section,row_letter,seat_number')
                ->get()
                ->filter(fn ($b) => $b->seat) // tolerate orphan blocks
                ->keyBy(function ($b) {
                    return $this->seatKey(
                        $b->seat->section,
                        $b->seat->row_letter,
                        (int) $b->seat->seat_number
                    );
                });
        }

        // Physical seat layout for the venue. Drives the "—" empty rows
        // in seatmap shows; ignored otherwise.
        $layout = collect();
        if ($usesSeatMap && $show) {
            $theater = $show->theater();
            if ($theater) {
                $layout = $theater->seats()
                    ->orderBy('section')
                    ->orderBy('row_letter')
                    ->orderBy('seat_number')
                    ->get();
            }
        }

        // Build the flat rows array used by both the view and the CSV.
        $rows = [];

        if ($usesSeatMap && $layout->isNotEmpty()) {
            foreach ($layout as $seat) {
                $key = $this->seatKey($seat->section, $seat->row_letter, (int) $seat->seat_number);

                if (isset($booked[$key])) {
                    $rows[] = $this->rowFromBookedSeat($booked[$key]);
                } elseif (isset($blocked[$key])) {
                    $rows[] = $this->rowFromBlockedSeat($seat);
                } else {
                    $rows[] = $this->rowFromEmptySeat($seat);
                }
            }
        } else {
            // Non-seatmap shows: just list the attendees, no empties.
            foreach ($booked as $bs) {
                $rows[] = $this->rowFromBookedSeat($bs);
            }
        }

        $summary = [
            'approved' => 0,
            'pending' => 0,
            'blocked' => 0,
            'empty' => 0,
            'total' => count($rows),
        ];
        foreach ($rows as $r) {
            $summary[$r['status']] = ($summary[$r['status']] ?? 0) + 1;
        }

        return [
            'showTime' => $showTime,
            'show' => $show,
            'rows' => $rows,
            'summary' => $summary,
            'usesSeatMap' => $usesSeatMap,
        ];
    }

    /**
     * Stable index key — "{section}.{row}.{seat#}" — so we can match
     * physical seats against booked / blocked entries without joins.
     */
    protected function seatKey(?string $section, ?string $row, int $seatNumber): string
    {
        return strtolower((string) $section).'.'.strtoupper((string) $row).'.'.$seatNumber;
    }

    protected function rowFromBookedSeat(BookingSeat $bs): array
    {
        $booking = $bs->booking;
        // Set by buildManifest() via setRelation() so this resolves in
        // memory without another DB hit. Null = no ticket row was paired
        // with this seat yet (PR #70 back-fill edge case).
        $ticket = $bs->getRelation('matchedTicket');

        $status = $booking && $booking->status === 'pending' ? 'pending' : 'approved';

        // Attendee name resolution: prefer the per-ticket name (PR #70),
        // fall back to the booking owner if the ticket row is missing
        // (back-fill edge cases).
        $attendeeName = $ticket->name ?? optional($booking)->full_name ?? '—';
        $attendeePhone = $ticket->phone
            ?? optional($booking)->phone
            ?? '';

        $scannedAt = $ticket && $ticket->is_scanned && $ticket->scanned_at
            ? \Carbon\Carbon::parse($ticket->scanned_at)->format('g:i A')
            : null;

        return [
            'section' => $bs->section,
            'section_label_ar' => $bs->section === 'balcony' ? 'بلكون' : 'صالة',
            'section_label_en' => $bs->section === 'balcony' ? 'Balcony' : 'Hall',
            'row_letter' => strtoupper((string) $bs->row_letter),
            'seat_number' => (int) $bs->seat_number,
            'attendee_name' => $attendeeName,
            'phone' => $attendeePhone,
            'booking_id' => optional($booking)->id,
            'booking_ref' => optional($booking)->reference_code,
            'booking_owner' => optional($booking)->full_name,
            'status' => $status,
            'status_en' => $status === 'pending' ? 'PENDING' : 'APPROVED',
            'is_scanned' => (bool) ($ticket->is_scanned ?? false),
            'scanned_at' => $scannedAt,
        ];
    }

    protected function rowFromBlockedSeat($seat): array
    {
        return [
            'section' => $seat->section,
            'section_label_ar' => $seat->section === 'balcony' ? 'بلكون' : 'صالة',
            'section_label_en' => $seat->section === 'balcony' ? 'Balcony' : 'Hall',
            'row_letter' => strtoupper((string) $seat->row_letter),
            'seat_number' => (int) $seat->seat_number,
            'attendee_name' => null,
            'phone' => null,
            'booking_id' => null,
            'booking_ref' => null,
            'booking_owner' => null,
            'status' => 'blocked',
            'status_en' => 'BLOCKED',
            'is_scanned' => false,
            'scanned_at' => null,
        ];
    }

    protected function rowFromEmptySeat($seat): array
    {
        return [
            'section' => $seat->section,
            'section_label_ar' => $seat->section === 'balcony' ? 'بلكون' : 'صالة',
            'section_label_en' => $seat->section === 'balcony' ? 'Balcony' : 'Hall',
            'row_letter' => strtoupper((string) $seat->row_letter),
            'seat_number' => (int) $seat->seat_number,
            'attendee_name' => null,
            'phone' => null,
            'booking_id' => null,
            'booking_ref' => null,
            'booking_owner' => null,
            'status' => 'empty',
            'status_en' => 'EMPTY',
            'is_scanned' => false,
            'scanned_at' => null,
        ];
    }

    /**
     * Mask all but the first 2 and last 4 digits of a phone number so
     * the printed manifest is operationally useful (operator can confirm
     * "is the last 4 digits 1234?") without exposing the full number.
     */
    protected function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        $len = strlen($digits);
        if ($len <= 6) {
            return $phone;
        }
        $prefix = substr($digits, 0, 2);
        $suffix = substr($digits, -4);
        $maskedLen = max(0, $len - 6);

        return $prefix.str_repeat('●', $maskedLen).$suffix;
    }
}
