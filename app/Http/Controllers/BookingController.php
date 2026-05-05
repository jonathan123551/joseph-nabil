<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Seat;
use App\Models\Setting;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Theater;
use App\Support\ImageOptimizer;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    // ================= CREATE =================
    public function create(ShowTime $showTime)
    {
        $reserved = $showTime->bookings()
            ->whereIn('status', ['approved','pending'])
            ->sum('tickets_count');

        $remaining = max(0, $showTime->total_tickets - $reserved);

        abort_if($remaining <= 0 || $showTime->is_sold_out, 404);

        $showTime->loadMissing('show');
        $isAnbaRuweis = $showTime->show && $showTime->show->theater_type === Show::THEATER_ANBA_RUWEIS;

        $payload = [
            'showTime'       => $showTime,
            'transferWallet' => Setting::get('transfer_wallet', ''),
            'transferInsta'  => Setting::get('transfer_insta', ''),
            'remaining'      => $remaining,
            'isAnbaRuweis'   => $isAnbaRuweis,
        ];

        if ($isAnbaRuweis) {
            $theater = Theater::anbaRuweis();
            $seats   = $theater
                ? $theater->seats()->orderBy('row_letter')->orderBy('group_side')->orderBy('display_order')->get()
                : collect();

            $unavailable = $showTime->unavailableSeatIds();

            $payload['theater']           = $theater;
            $payload['seatsByRow']        = $this->groupSeatsByRow($seats);
            $payload['unavailableSeats']  = $unavailable;
            $payload['balconyPrice']      = (int) ($showTime->show->balcony_price ?? 0);
            $payload['hallPrice']         = (int) ($showTime->show->hall_price ?? 0);
        }

        return view('bookings.create', $payload);
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
                    if (!isset($grouped[$section][$row][$side])) {
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
        $lockKey = 'booking_lock_' . sha1(
            $request->ip() . json_encode($request->phones ?? []) . $showTime->id
        );

        if (!Cache::add($lockKey, true, 20)) {
            return back()->withErrors([
                'general' => '⏳ الطلب قيد المعالجة بالفعل'
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
        $request->validate([
            'names' => ['required','array'],
            'names.*' => ['required','string','max:255'],
            'phones' => ['required','array'],
            'phones.*' => ['required','string','min:8','max:20'],
            'payment_screenshot' => 'required|image|max:20480',
        ]);

        // 🔥 حساب التذاكر المتاحة
        $reserved = $showTime->bookings()
            ->whereIn('status', ['approved','pending'])
            ->sum('tickets_count');

        $remaining = max(0, $showTime->total_tickets - $reserved);

        $ticketsCount = count($request->names);

        // ❌ منع الحجز لو أكتر من المتاح
        if ($ticketsCount > $remaining) {
            return back()->withErrors([
                'general' => '❌ المتاح فقط: ' . $remaining . ' تذاكر'
            ])->withInput();
        }

        // ❌ لو خلصت
        if ($remaining <= 0) {
            return back()->withErrors([
                'general' => '❌ لا توجد تذاكر متاحة'
            ])->withInput();
        }

        // 📞 أول رقم
        $mainPhone = $this->normalizeEgyptPhone($request->phones[0]);

        // ☁️ رفع الصورة
        $upload = $this->uploadPaymentScreenshot($request->file('payment_screenshot'));

        // ✅ إنشاء الحجز
        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name'    => $request->names[0],
            'phone'        => $mainPhone,
            'tickets_count'=> $ticketsCount,
            'total_price'  => $showTime->ticket_price * $ticketsCount,
            'transfer_screenshot_path' => $upload['secure_url'],
            'transfer_screenshot_public_id' => $upload['public_id'],
            'status'       => 'pending',
            'reference_code' => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
        ]);

        // 🎟️ إنشاء التذاكر
        foreach ($request->names as $i => $name) {
            \App\Models\Ticket::create([
                'booking_id' => $booking->id,
                'name'       => $name,
                'phone'      => $this->normalizeEgyptPhone($request->phones[$i]),
                'ticket_code'=> 'TIC-' . strtoupper(Str::random(6)),
            ]);
        }

        return view('bookings.thankyou', compact('booking'));
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
        $request->validate([
            'section'  => ['required', 'in:' . Theater::SECTION_BALCONY . ',' . Theater::SECTION_HALL],
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['integer'],
            'names'    => ['required', 'array'],
            'names.*'  => ['required', 'string', 'max:255'],
            'phones'   => ['required', 'array'],
            'phones.*' => ['required', 'string', 'min:8', 'max:20'],
            'payment_screenshot' => 'required|image|max:20480',
        ]);

        $section = $request->input('section');
        $seatIds = array_values(array_unique(array_map('intval', $request->input('seat_ids', []))));

        if (count($request->names) !== count($seatIds)) {
            return back()->withErrors([
                'general' => '❌ عدد الأسماء لا يطابق عدد المقاعد المختارة',
            ])->withInput();
        }

        $theater = Theater::anbaRuweis();
        if (!$theater) {
            return back()->withErrors(['general' => '❌ لم يتم إعداد المسرح بعد'])->withInput();
        }

        // All requested seats must belong to this theater + section.
        $seats = Seat::where('theater_id', $theater->id)
            ->where('section', $section)
            ->whereIn('id', $seatIds)
            ->get();

        if ($seats->count() !== count($seatIds)) {
            return back()->withErrors([
                'general' => '❌ مقاعد غير صحيحة',
            ])->withInput();
        }

        // Reject if any of them is already taken (booked or admin-blocked).
        $unavailable = $showTime->unavailableSeatIds();
        $clashes     = array_intersect($seatIds, $unavailable);
        if (!empty($clashes)) {
            return back()->withErrors([
                'general' => '❌ بعض المقاعد المختارة غير متاحة، حاول مرة أخرى',
            ])->withInput();
        }

        $unitPrice = $section === Theater::SECTION_BALCONY
            ? (int) ($showTime->show->balcony_price ?? 0)
            : (int) ($showTime->show->hall_price ?? 0);

        if ($unitPrice <= 0) {
            return back()->withErrors([
                'general' => '❌ لم يتم تحديد سعر التذكرة لهذا القسم',
            ])->withInput();
        }

        $mainPhone = $this->normalizeEgyptPhone($request->phones[0]);
        $upload    = $this->uploadPaymentScreenshot($request->file('payment_screenshot'));

        try {
            $booking = DB::transaction(function () use ($request, $showTime, $seats, $seatIds, $unitPrice, $mainPhone, $upload, $section) {
                $count = count($seatIds);

                $booking = Booking::create([
                    'show_time_id' => $showTime->id,
                    'full_name'    => $request->names[0],
                    'phone'        => $mainPhone,
                    'tickets_count'=> $count,
                    'total_price'  => $unitPrice * $count,
                    'transfer_screenshot_path' => $upload['secure_url'],
                    'transfer_screenshot_public_id' => $upload['public_id'],
                    'status'       => 'pending',
                    'reference_code' => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                ]);

                $rows = [];
                $seatsById = $seats->keyBy('id');
                $now = now();
                foreach ($seatIds as $sid) {
                    $seat = $seatsById[$sid];
                    $rows[] = [
                        'booking_id'   => $booking->id,
                        'seat_id'      => $seat->id,
                        'show_time_id' => $showTime->id,
                        'section'      => $seat->section,
                        'row_letter'   => $seat->row_letter,
                        'seat_number'  => $seat->seat_number,
                        'price'        => $unitPrice,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }

                // unique(show_time_id, seat_id) at the DB enforces no double-booking
                BookingSeat::insert($rows);

                foreach ($request->names as $i => $name) {
                    \App\Models\Ticket::create([
                        'booking_id' => $booking->id,
                        'name'       => $name,
                        'phone'      => $this->normalizeEgyptPhone($request->phones[$i]),
                        'ticket_code'=> 'TIC-' . strtoupper(Str::random(6)),
                    ]);
                }

                return $booking;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition: another booking grabbed one of these seats first.
            return back()->withErrors([
                'general' => '❌ بعض المقاعد المختارة تم حجزها للتو، حاول مرة أخرى',
            ])->withInput();
        }

        return view('bookings.thankyou', compact('booking'));
    }

    private function uploadPaymentScreenshot($file): array
    {
        $optimized = ImageOptimizer::optimize($file, 1600, 80);

        $upload = (new UploadApi())->upload($optimized, [
            'folder' => 'payments/screenshots',
        ]);

        if ($optimized !== $file->getRealPath()) {
            @unlink($optimized);
        }

        return $upload;
    }

    private function normalizeEgyptPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (preg_match('/^01[0-9]{9}$/', $phone)) {
            return '20' . substr($phone, 1);
        }

        if (preg_match('/^1[0-9]{9}$/', $phone)) {
            return '20' . $phone;
        }

        if (preg_match('/^20[0-9]{10}$/', $phone)) {
            return $phone;
        }

        throw ValidationException::withMessages([
            'phone' => 'رقم غير صحيح',
        ]);
    }
}



