<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\Show;
use App\Models\ShowTime;
use App\Support\ShowTimeAnalytics;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalShows = Show::count();
        $totalShowTimes = ShowTime::count();

        // إجمالي التذاكر الأساسية لكل المواعيد
        $totalTicketsAllTimes = ShowTime::sum('total_tickets');

        // إجمالي التذاكر المحجوزة في (pending + approved)
        $bookedPendingApproved = Booking::whereIn('status', ['pending', 'approved'])
            ->sum('tickets_count');

        // إجمالي التذاكر المعتمدة
        $totalTicketsApproved = Booking::where('status', 'approved')
            ->sum('tickets_count');

        // إجمالي الفلوس من الحجوزات المعتمدة. Blocked seats are NOT paid
        // bookings — they never contribute to revenue.
        $totalRevenue = Booking::where('status', 'approved')
            ->sum('total_price');

        // حالات الحجوزات
        $pendingBookings = Booking::where('status', 'pending')->count();
        $rejectedBookings = Booking::where('status', 'rejected')->count();

        // إحصائيات لكل ميعاد عرض لوحده
        //
        // Eager-load every relation needed by the new analytics section
        // (`bookings.seats` for hall/balcony breakdown + revenue tiles,
        // `seatBlocks` for the blocked-seats KPI on each card) in one
        // shot. The legacy table further down the page reuses these
        // same rows, so this also removes the N+1 the old loop had on
        // `blockedSeatsCount()` and the per-showtime booking queries.
        $showTimesStats = ShowTime::with(['show', 'bookings.seats', 'seatBlocks'])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        // Running total used by the platform-wide "Blocked" KPI card.
        // Computed alongside the per-showtime stats so we only walk the
        // seat_blocks table once per dashboard render.
        $totalBlockedSeats = 0;

        // Pre-compute the rich analytics payload for every showtime
        // (ShowTimeAnalytics::compute() reads from already-loaded
        // relations, so this stays query-free in the loop). The legacy
        // per-row stats live on the model alongside it so the existing
        // "Show Times" table further down keeps working untouched.
        $analytics = collect();

        foreach ($showTimesStats as $time) {
            $a = ShowTimeAnalytics::compute($time);
            $analytics->put($time->id, $a);

            $totalBlockedSeats += $a['blocked'];

            // نضيف القيم دي كخصائص على الموديل عشان نستخدمها في الـ Blade
            // (الجدول التشغيلي) — same column names as before so the legacy
            // table renders identically.
            $time->approved_tickets  = $a['approved_tickets'];
            $time->pending_tickets   = $a['pending_tickets'];
            $time->blocked_tickets   = $a['blocked'];
            $time->remaining_tickets = $a['remaining'];
            $time->revenue           = $a['approved_revenue'];
        }

        // Roll up the analytics payload into a single set of top-level
        // KPIs for the dashboard "Analytics" section header (total
        // occupancy %, total discounts, etc.).
        $analyticsTotals = ShowTimeAnalytics::totals($analytics->all());

        // التذاكر المتبقية على مستوى كل المواعيد — also subtracts blocked
        // seats so the "Remaining" KPI matches what the seat picker is
        // actually willing to sell.
        $ticketsRemaining = max(0, $totalTicketsAllTimes - $bookedPendingApproved - $totalBlockedSeats);

        // 🔹 بيانات التحويل (محفوظة في جدول settings)
        $transferWallet = Setting::get('transfer_wallet', '');
        $transferInsta = Setting::get('transfer_insta', '');

        return view('admin.dashboard', compact(
            'totalShows',
            'totalShowTimes',
            'ticketsRemaining',
            'totalTicketsApproved',
            'totalBlockedSeats',
            'totalRevenue',
            'pendingBookings',
            'rejectedBookings',
            'showTimesStats',
            'analytics',
            'analyticsTotals',
            'transferWallet',
            'transferInsta'
        ));
    }

    /**
     * تحديث بيانات التحويل (محفظة / InstaPay) — يُستدعى من الفورم في لوحة الأدمن
     */
    public function updatePayments(Request $request)
    {
        $data = $request->validate([
            'transfer_wallet' => ['nullable', 'string', 'max:100'],
            'transfer_insta' => ['nullable', 'string', 'max:100'],
        ]);

        // خزن في جدول settings بواسطة موديل Setting (شكل Setting.set متوقع)
        Setting::set('transfer_wallet', $data['transfer_wallet'] ?? '');
        Setting::set('transfer_insta', $data['transfer_insta'] ?? '');

        return back()->with('status', 'تم تحديث بيانات التحويل بنجاح ✅');
    }
}
