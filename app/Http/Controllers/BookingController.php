<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Seat;
use App\Models\Setting;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Theater;
use App\Support\BookingPricing;
use App\Support\ImageOptimizer;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    // ================= CREATE =================
    //
    // Anba Ruweis booking is a 3-step flow:
    //
    //   create()  → Step 1: section selection (Sala / Balcony)
    //   seats()   → Step 2: canvas seat picker
    //   form()    → Step 3: attendee names, phones, payment screenshot
    //   store()   → POST   : final submit (unchanged contract)
    //
    // Non-Anba-Ruweis shows continue to use the original single-page form
    // rendered straight from create().
    public function create(ShowTime $showTime)
    {
        $base = $this->baseBookingPayload($showTime);
        if ($base === null) {
            abort(404);
        }

        // Step 1 only needs prices + transfer info; seat data is loaded in
        // step 2. For non-Anba-Ruweis the original create.blade.php still
        // renders an inline form so we keep returning that view either way.
        return view('bookings.create', $base);
    }

    public function seats(Request $request, ShowTime $showTime)
    {
        $base = $this->baseBookingPayload($showTime);
        if ($base === null) {
            abort(404);
        }

        // Seat picker only exists for Anba Ruweis. Anything else falls back
        // to step 1 (section picker / single-page manual form).
        if (! $base['isAnbaRuweis']) {
            return redirect()->route('bookings.create', $showTime);
        }

        $section = $request->query('section', Theater::SECTION_HALL);
        if (! in_array($section, [Theater::SECTION_HALL, Theater::SECTION_BALCONY], true)) {
            $section = Theater::SECTION_HALL;
        }

        $payload = $base + $this->anbaSeatPayload($showTime) + ['section' => $section];

        return view('bookings.seats', $payload);
    }

    public function form(Request $request, ShowTime $showTime)
    {
        $base = $this->baseBookingPayload($showTime);
        if ($base === null) {
            abort(404);
        }

        if (! $base['isAnbaRuweis']) {
            return redirect()->route('bookings.create', $showTime);
        }

        $section = $request->query('section', Theater::SECTION_HALL);
        if (! in_array($section, [Theater::SECTION_HALL, Theater::SECTION_BALCONY], true)) {
            $section = Theater::SECTION_HALL;
        }

        // The form pre-fills selected seats from localStorage on the client.
        // No seat grid is needed here, just price + transfer info.
        return view('bookings.form', $base + ['section' => $section]);
    }

    /**
     * Shared base payload (show details, prices, transfer info, ticket
     * remaining). Returns null when the show time is sold out / disabled.
     */
    private function baseBookingPayload(ShowTime $showTime): ?array
    {
        // For seatmap-backed shows this also subtracts admin-blocked seats —
        // see ShowTime::effectiveRemainingTickets() — so the storefront
        // never advertises capacity that the seat picker would refuse.
        $remaining = $showTime->effectiveRemainingTickets();
        if ($remaining <= 0 || $showTime->is_sold_out) {
            return null;
        }

        $showTime->loadMissing('show');
        $isAnbaRuweis = $showTime->show && $showTime->show->theater_type === Show::THEATER_ANBA_RUWEIS;

        return [
            'showTime' => $showTime,
            'transferWallet' => Setting::get('transfer_wallet', ''),
            'transferInsta' => Setting::get('transfer_insta', ''),
            'remaining' => $remaining,
            'isAnbaRuweis' => $isAnbaRuweis,
            'balconyPrice' => (int) ($showTime->show->balcony_price ?? 0),
            'hallPrice' => (int) ($showTime->show->hall_price ?? 0),
            // Bulk-discount offer config (mirrored to JS for the
            // pricing summary; the server still re-computes the
            // final price on POST).
            'bulkDiscount' => BookingPricing::toJs(),
        ];
    }

    /**
     * Heavy seat-grid payload — only loaded for the seat-picker page.
     */
    private function anbaSeatPayload(ShowTime $showTime): array
    {
        $theater = Theater::anbaRuweis();
        $seats = $theater
            ? $theater->seats()->orderBy('row_letter')->orderBy('group_side')->orderBy('display_order')->get()
            : collect();

        $unavailable = $showTime->unavailableSeatIds();
        $blocked = $showTime->seatBlocks()->pluck('seat_id')->all();

        return [
            'theater' => $theater,
            'seatsByRow' => $this->groupSeatsByRow($seats),
            'unavailableSeats' => $unavailable,
            'blockedSeats' => $blocked,
        ];
    }

    /**
     * Group seats into a structure the seat-picker view can render directly:
     *   ['balcony' => ['A' => ['left' => [...], 'center' => [...], 'right' => [...]]]]
     */
    private function groupSeatsByRow($seats): array
    {
        $grouped = [];
        foreach ($seats as $seat) {
            $grouped[$seat->section][$seat->row_letter][$seat->group_side][] = $seat;
        }

        // ensure every row has all three keys to keep view code simple
        foreach ($grouped as $section => $rows) {
            foreach ($rows as $row => $sides) {
                foreach (['left', 'center', 'right'] as $side) {
                    if (! isset($grouped[$section][$row][$side])) {
                        $grouped[$section][$row][$side] = [];
                    }
                }
            }
        }

        return $grouped;
    }

    // ================= STORE =================
    public function store(Request $request, ShowTime $showTime)
    {
        $requestId = (string) Str::uuid();
        $request->merge(['request_id' => $requestId]);

        Log::info('BOOKING_ATTEMPT_START', [
            'request_id' => $requestId,
            'show_time_id' => $showTime->id,
            'ip' => $request->ip(),
            'phones' => $request->phones,
            'seat_ids' => $request->seat_ids,
        ]);

        $lockKey = 'booking_lock_'.sha1(
            $request->ip().json_encode($request->phones ?? []).$showTime->id
        );

        if (! Cache::add($lockKey, true, 20)) {
            Log::warning('BOOKING_CACHE_LOCK_REJECTED', [
                'request_id' => $requestId,
                'lock_key' => $lockKey
            ]);
            return back()->withErrors([
                'general' => '⏳ الطلب قيد المعالجة بالفعل',
            ])->withInput();
        }

        try {
            $showTime->loadMissing('show');
            $isAnbaRuweis = $showTime->show && $showTime->show->theater_type === Show::THEATER_ANBA_RUWEIS;

            return $isAnbaRuweis
                ? $this->storeSeatBased($request, $showTime)
                : $this->storeManual($request, $showTime);
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Existing manual-count flow (Other-theater shows). Untouched behaviour.
     */
    private function storeManual(Request $request, ShowTime $showTime)
    {
        // ✅ VALIDATION
        try {
            $request->validate([
                'names' => ['required', 'array'],
                'names.*' => ['required', 'string', 'max:255'],
                'phones' => ['required', 'array'],
                'phones.*' => ['required', 'string', 'min:8', 'max:20'],
                'payment_screenshot' => 'required|image|max:20480',
            ]);
            Log::info('BOOKING_VALIDATION_PASSED', ['request_id' => $request->request_id]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('BOOKING_VALIDATION_FAILED', [
                'request_id' => $request->request_id,
                'errors' => $e->errors()
            ]);
            throw $e;
        }

        // 🔥 حساب التذاكر المتاحة
        $reserved = $showTime->bookings()
            ->whereIn('status', ['approved', 'pending'])
            ->sum('tickets_count');

        $remaining = max(0, $showTime->total_tickets - $reserved);

        $ticketsCount = count($request->names);

        // ❌ منع الحجز لو أكتر من المتاح
        if ($ticketsCount > $remaining) {
            Log::warning('BOOKING_CAPACITY_REJECTED', [
                'request_id' => $request->request_id,
                'reason' => 'requested more than remaining',
                'requested' => $ticketsCount,
                'remaining' => $remaining
            ]);
            return back()->withErrors([
                'general' => '❌ المتاح فقط: '.$remaining.' تذاكر',
            ])->withInput();
        }

        // ❌ لو خلصت
        if ($remaining <= 0) {
            Log::warning('BOOKING_CAPACITY_REJECTED', [
                'request_id' => $request->request_id,
                'reason' => 'sold out'
            ]);
            return back()->withErrors([
                'general' => '❌ لا توجد تذاكر متاحة',
            ])->withInput();
        }

        // 📞 أول رقم
        $mainPhone = $this->normalizeEgyptPhone($request->phones[0]);

        // ☁️ رفع الصورة
        $upload = $this->uploadPaymentScreenshot($request->file('payment_screenshot'));

        // Apply the bulk-discount rules server-side. Even if the
        // client has stale JS, the persisted price is whatever
        // BookingPricing says it should be.
        $pricing = BookingPricing::calculate(
            (int) $showTime->ticket_price,
            $ticketsCount
        );

        Log::info('BOOKING_DB_TRANSACTION_START', ['request_id' => $request->request_id]);

        // ✅ إنشاء الحجز
        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name' => $request->names[0],
            'phone' => $mainPhone,
            'tickets_count' => $ticketsCount,
            'total_price' => $pricing['total_price'],
            'original_price' => $pricing['original_price'],
            'discount_percent' => $pricing['discount_percent'],
            'discount_amount' => $pricing['discount_amount'],
            'transfer_screenshot_path' => $upload['secure_url'],
            'transfer_screenshot_public_id' => $upload['public_id'],
            'status' => 'pending',
            'reference_code' => 'SRC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
        ]);

        // 🎟️ إنشاء التذاكر
        foreach ($request->names as $i => $name) {
            \App\Models\Ticket::create([
                'booking_id' => $booking->id,
                'name' => $name,
                'phone' => $this->normalizeEgyptPhone($request->phones[$i]),
                'ticket_code' => 'TIC-'.strtoupper(Str::random(6)),
            ]);
        }

        Log::info('BOOKING_DB_TRANSACTION_COMMIT', ['request_id' => $request->request_id]);
        Log::info('BOOKING_DB_INSERT_SUCCESS', [
            'request_id' => $request->request_id,
            'booking_id' => $booking->id,
            'reference_code' => $booking->reference_code
        ]);

        return $this->redirectToThankyou($booking);
    }

    /**
     * Seat-based flow for مسرح الأنبا رويس shows. Validates the selected
     * seats, ensures none are already booked or admin-blocked, then creates
     * the booking + booking_seats rows inside a single DB transaction so that
     * the unique(show_time_id, seat_id) constraint on booking_seats blocks any
     * concurrent double-booking attempt.
     */
    private function storeSeatBased(Request $request, ShowTime $showTime)
    {
        try {
            $request->validate([
                'section' => ['required', 'in:'.Theater::SECTION_BALCONY.','.Theater::SECTION_HALL],
                'seat_ids' => ['required', 'array', 'min:1'],
                'seat_ids.*' => ['integer'],
                'names' => ['required', 'array'],
                'names.*' => ['required', 'string', 'max:255'],
                'phones' => ['required', 'array'],
                'phones.*' => ['required', 'string', 'min:8', 'max:20'],
                'payment_screenshot' => 'required|image|max:20480',
            ]);
            Log::info('BOOKING_VALIDATION_PASSED', ['request_id' => $request->request_id]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('BOOKING_VALIDATION_FAILED', [
                'request_id' => $request->request_id,
                'errors' => $e->errors()
            ]);
            throw $e;
        }

        $section = $request->input('section');
        $seatIds = array_values(array_unique(array_map('intval', $request->input('seat_ids', []))));

        if (count($request->names) !== count($seatIds)) {
            Log::warning('BOOKING_VALIDATION_FAILED', [
                'request_id' => $request->request_id,
                'reason' => 'names count mismatch'
            ]);
            return back()->withErrors([
                'general' => '❌ عدد الأسماء لا يطابق عدد المقاعد المختارة',
            ])->withInput();
        }

        $theater = Theater::anbaRuweis();
        if (! $theater) {
            Log::error('BOOKING_THEATER_NOT_FOUND', ['request_id' => $request->request_id]);
            return back()->withErrors(['general' => '❌ لم يتم إعداد المسرح بعد'])->withInput();
        }

        // All requested seats must belong to this theater + section.
        $seats = Seat::where('theater_id', $theater->id)
            ->where('section', $section)
            ->whereIn('id', $seatIds)
            ->get();

        if ($seats->count() !== count($seatIds)) {
            Log::warning('BOOKING_INVALID_SEATS_REJECTED', ['request_id' => $request->request_id]);
            return back()->withErrors([
                'general' => '❌ مقاعد غير صحيحة',
            ])->withInput();
        }

        // Reject if any of them is already taken (booked or admin-blocked).
        $unavailable = $showTime->unavailableSeatIds();
        $clashes = array_intersect($seatIds, $unavailable);
        if (! empty($clashes)) {
            Log::warning('BOOKING_SEAT_CLASH_REJECTED', [
                'request_id' => $request->request_id,
                'clashes' => $clashes
            ]);
            return back()->withErrors([
                'general' => '❌ بعض المقاعد المختارة غير متاحة، حاول مرة أخرى',
            ])->withInput();
        }

        $unitPrice = $section === Theater::SECTION_BALCONY
            ? (int) ($showTime->show->balcony_price ?? 0)
            : (int) ($showTime->show->hall_price ?? 0);

        if ($unitPrice <= 0) {
            Log::warning('BOOKING_VALIDATION_FAILED', [
                'request_id' => $request->request_id,
                'reason' => 'zero unit price'
            ]);
            return back()->withErrors([
                'general' => '❌ لم يتم تحديد سعر التذكرة لهذا القسم',
            ])->withInput();
        }

        $mainPhone = $this->normalizeEgyptPhone($request->phones[0]);
        $upload = $this->uploadPaymentScreenshot($request->file('payment_screenshot'));

        try {
            Log::info('BOOKING_DB_TRANSACTION_START', ['request_id' => $request->request_id]);
            $booking = DB::transaction(function () use ($request, $showTime, $seats, $seatIds, $unitPrice, $mainPhone, $upload) {
                $count = count($seatIds);

                // Apply the bulk-discount rules server-side. Per-seat
                // booking_seats rows continue to record the FULL
                // unit price (so admins can see what each seat
                // would have cost at list); the discount lives on
                // the parent booking row.
                $pricing = BookingPricing::calculate($unitPrice, $count);

                $booking = Booking::create([
                    'show_time_id' => $showTime->id,
                    'full_name' => $request->names[0],
                    'phone' => $mainPhone,
                    'tickets_count' => $count,
                    'total_price' => $pricing['total_price'],
                    'original_price' => $pricing['original_price'],
                    'discount_percent' => $pricing['discount_percent'],
                    'discount_amount' => $pricing['discount_amount'],
                    'transfer_screenshot_path' => $upload['secure_url'],
                    'transfer_screenshot_public_id' => $upload['public_id'],
                    'status' => 'pending',
                    'reference_code' => 'SRC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                ]);

                $rows = [];
                $seatsById = $seats->keyBy('id');
                $now = now();
                foreach ($seatIds as $sid) {
                    $seat = $seatsById[$sid];
                    $rows[] = [
                        'booking_id' => $booking->id,
                        'seat_id' => $seat->id,
                        'show_time_id' => $showTime->id,
                        'section' => $seat->section,
                        'row_letter' => $seat->row_letter,
                        'seat_number' => $seat->seat_number,
                        'price' => $unitPrice,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // unique(show_time_id, seat_id) at the DB enforces no double-booking
                BookingSeat::insert($rows);

                // Re-load the freshly-inserted booking_seats in the same
                // order they were sent in the request so each ticket can
                // be wired 1:1 to its specific seat. We keep the legacy
                // tickets fields (name/phone) untouched — only the new
                // booking_seat_id link is added (PR #70).
                $createdSeats = BookingSeat::where('booking_id', $booking->id)
                    ->orderBy('id')
                    ->get();
                $seatBySeatId = $createdSeats->keyBy('seat_id');

                foreach ($request->names as $i => $name) {
                    $sid = $seatIds[$i] ?? null;
                    $bookingSeat = $sid !== null ? ($seatBySeatId[$sid] ?? null) : null;

                    \App\Models\Ticket::create([
                        'booking_id' => $booking->id,
                        'booking_seat_id' => optional($bookingSeat)->id,
                        'name' => $name,
                        'phone' => $this->normalizeEgyptPhone($request->phones[$i]),
                        'ticket_code' => 'TIC-'.strtoupper(Str::random(6)),
                    ]);
                }

                return $booking;
            });
            Log::info('BOOKING_DB_TRANSACTION_COMMIT', ['request_id' => $request->request_id]);
            Log::info('BOOKING_DB_INSERT_SUCCESS', [
                'request_id' => $request->request_id,
                'booking_id' => $booking->id,
                'reference_code' => $booking->reference_code
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('BOOKING_DB_RACE_CONDITION', [
                'request_id' => $request->request_id,
                'message' => $e->getMessage()
            ]);
            // Race condition: another booking grabbed one of these seats first.
            return back()->withErrors([
                'general' => '❌ بعض المقاعد المختارة تم حجزها للتو، حاول مرة أخرى',
            ])->withInput();
        }

        return $this->redirectToThankyou($booking);
    }

    /**
     * GET landing page rendered after the POST-redirect-GET pattern. Same
     * view that storeManual()/storeSeatBased() used to render directly.
     *
     * Looking the booking up by reference_code keeps the URL stable and
     * shareable, matches what the customer already sees, and avoids
     * exposing sequential booking IDs. A short-lived session flash flag
     * (set by redirectToThankyou()) is the "this booking was *just*
     * submitted in this session" hint that lets us light up the
     * celebration animation on the first render only; subsequent direct
     * hits still render the page cleanly but skip the confetti.
     */
    public function thankyou(string $reference)
    {
        $booking = Booking::with(['tickets', 'showTime.show'])
            ->where('reference_code', $reference)
            ->first();

        if (! $booking) {
            abort(404);
        }

        // Bookmarking the celebratory landing page is fine for a
        // pending/approved booking — the user just sees their own
        // confirmation again. But if the booking was REJECTED by an
        // admin after the fact, the green "تم إرسال طلب الحجز بنجاح"
        // headline becomes actively misleading. Defer to the public
        // ticket-lookup page, which has the proper "not approved"
        // branch already wired up (Wave 0).
        if ($booking->status === 'rejected') {
            return redirect()->route('tickets.show', ['reference' => $reference]);
        }

        $justSubmitted = session('booking_just_submitted_ref') === $reference;

        return view('bookings.thankyou', [
            'booking' => $booking,
            'justSubmitted' => $justSubmitted,
        ]);
    }

    /**
     * Centralised PRG redirect after a successful booking POST. A 303
     * "See Other" forces the browser to follow with a GET, which both
     * fixes Safari's "Resubmit form?" prompt on refresh AND prevents
     * accidental double-submits via the browser's back button. The
     * flash flag lets the GET handler distinguish a fresh celebration
     * landing from a later direct hit on the reference URL.
     */
    private function redirectToThankyou(Booking $booking)
    {
        session()->flash('booking_just_submitted_ref', $booking->reference_code);

        return redirect()
            ->route('bookings.thankyou', ['reference' => $booking->reference_code], 303);
    }

    private function uploadPaymentScreenshot($file): array
    {
        $requestId = request('request_id');
        $startTime = microtime(true);

        try {
            $optimized = ImageOptimizer::optimize($file, 1600, 80);

            $upload = (new UploadApi)->upload($optimized, [
                'folder' => 'payments/screenshots',
            ]);

            if ($optimized !== $file->getRealPath()) {
                @unlink($optimized);
            }

            // Cloudinary returns ApiResponse (an ArrayObject subclass), not a
            // plain array. Convert so callers using $upload['secure_url'] still
            // work and the declared return type is satisfied at runtime.
            if ($upload instanceof \ArrayObject) {
                $upload = $upload->getArrayCopy();
            } else {
                $upload = is_array($upload) ? $upload : (array) $upload;
            }

            Log::info('BOOKING_CLOUDINARY_SUCCESS', [
                'request_id' => $requestId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000),
                'uploaded_url' => $upload['secure_url'] ?? null
            ]);

            return $upload;
        } catch (\Exception $e) {
            Log::error('BOOKING_CLOUDINARY_FAILED', [
                'request_id' => $requestId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function normalizeEgyptPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (preg_match('/^01[0-9]{9}$/', $phone)) {
            return '20'.substr($phone, 1);
        }

        if (preg_match('/^1[0-9]{9}$/', $phone)) {
            return '20'.$phone;
        }

        if (preg_match('/^20[0-9]{10}$/', $phone)) {
            return $phone;
        }

        throw ValidationException::withMessages([
            'phone' => 'رقم غير صحيح',
        ]);
    }
}
