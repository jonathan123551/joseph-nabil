<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\Show;
use App\Models\ShowTime;
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
        $showTimesStats = ShowTime::with('show')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        // Running total used by the platform-wide "Blocked" KPI card.
        // Computed alongside the per-showtime stats so we only walk the
        // seat_blocks table once per dashboard render.
        $totalBlockedSeats = 0;

        foreach ($showTimesStats as $time) {
            // عدد التذاكر المعتمدة للميعاد ده
            $approved = Booking::where('show_time_id', $time->id)
                ->where('status', 'approved')
                ->sum('tickets_count');

            // عدد التذاكر pending للميعاد ده
            $pending = Booking::where('show_time_id', $time->id)
                ->where('status', 'pending')
                ->sum('tickets_count');

            // المقاعد المحجوبة (admin-only). Always 0 for non-seatmap
            // shows because there's no seat layout to block against.
            $blocked = $time->blockedSeatsCount();
            $totalBlockedSeats += $blocked;

            // التذاكر المتبقية في الميعاد ده — blocked seats are
            // operationally unavailable, so they reduce remaining
            // inventory just like booked seats do.
            $remaining = max(0, (int) $time->total_tickets - $approved - $pending - $blocked);

            // نضيف القيم دي كخصائص على الموديل عشان نستخدمها في الـ Blade
            $time->approved_tickets  = $approved;
            $time->pending_tickets   = $pending;
            $time->blocked_tickets   = $blocked;
            $time->remaining_tickets = $remaining;

            // إيرادات الميعاد ده = sum(total_price) للحجوزات المعتمدة المرتبطة
            // بالميعاد ده فقط. نفس الـ partition بتاع $totalRevenue، فمضمون
            // إن مجموع الإيرادات لكل المواعيد يساوي $totalRevenue بالظبط.
            $time->revenue = (int) Booking::where('show_time_id', $time->id)
                ->where('status', 'approved')
                ->sum('total_price');
        }

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
