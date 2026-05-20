<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Show;
use App\Models\ShowTime;
use App\Support\ShowTimeAnalytics;
use Illuminate\Http\Request;

class ShowTimeController extends Controller
{
    // عرض كل المواعيد لعرض معيّن
    //
    // The index page doubles as a live analytics dashboard for the show,
    // so we eager-load every relation needed by ShowTimeAnalytics::compute()
    // up-front (bookings + their seats, plus admin-blocked seats for
    // seatmap shows). Without the explicit `with()` call, the page would
    // fire N+1 queries per showtime when computing revenue / occupancy /
    // section breakdowns — at ~10 showtimes × hundreds of bookings the
    // request would balloon to thousands of queries.
    public function index(Show $show)
    {
        $times = $show->showTimes()
            ->with(['bookings.seats', 'seatBlocks'])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        // Re-attach the parent show on every showtime so the analytics
        // helper can read theater_type / hall_price / balcony_price
        // without re-querying.
        $times->each(fn ($t) => $t->setRelation('show', $show));

        $analytics = $times->mapWithKeys(
            fn ($t) => [$t->id => ShowTimeAnalytics::compute($t)]
        );

        $totals = ShowTimeAnalytics::totals($analytics->all());

        return view('admin.show_times.index', compact(
            'show',
            'times',
            'analytics',
            'totals'
        ));
    }

    // فورم إضافة معاد جديد
    public function create(Show $show)
    {
        return view('admin.show_times.create', compact('show'));
    }

    // حفظ معاد جديد
    public function store(Request $request, Show $show)
{
    // For seatmap-backed shows the admin no longer types a ticket count —
    // it's derived from the theater's seat layout. Validation skips the
    // total_tickets / available_tickets rules in that case so a missing
    // input doesn't 422 the form.
    $rules = [
        'date'             => ['required', 'date'],
        'time'             => ['required'],
        'ticket_price'     => ['required', 'numeric', 'min:0'],
        'is_sold_out'      => ['nullable', 'boolean'],
    ];
    if (!$show->usesSeatMap()) {
        $rules['total_tickets']     = ['required', 'integer', 'min:1'];
        $rules['available_tickets'] = ['nullable', 'integer', 'min:0'];
    }
    $data = $request->validate($rules);

    $totalTickets = $this->resolveTotalTickets($show, $data['total_tickets'] ?? null);

    $show->showTimes()->create([
        'date'              => $data['date'],
        'time'              => $data['time'],
        'ticket_price'      => $data['ticket_price'],
        'total_tickets'     => $totalTickets,
        // available_tickets is NOT NULL in the schema — seed it so existing
        // rows (and downstream summaries) stay valid.
        'available_tickets' => $totalTickets,
        'is_sold_out'       => $request->boolean('is_sold_out'),
    ]);

    return redirect()
        ->route('admin.shows.times.index', $show)
        ->with('status', 'تم إضافة الموعد بنجاح.');
}



    // فورم تعديل معاد
    public function edit(Show $show, ShowTime $showTime)
    {
        return view('admin.show_times.edit', compact('show', 'showTime'));
    }

    // تحديث بيانات معاد
    public function update(Request $request, Show $show, ShowTime $showTime)
    {
        $rules = [
            'date'         => ['required', 'date'],
            'time'         => ['required'],
            'ticket_price' => ['required', 'numeric', 'min:0'],
            'is_sold_out'  => ['nullable'],
        ];
        if (!$show->usesSeatMap()) {
            $rules['total_tickets']     = ['required', 'integer', 'min:1'];
            $rules['available_tickets'] = ['nullable', 'integer', 'min:0'];
        }
        $data = $request->validate($rules);

        $showTime->date         = $data['date'];
        $showTime->time         = $data['time'];
        $showTime->ticket_price = $data['ticket_price'];
        $showTime->total_tickets = $this->resolveTotalTickets(
            $show,
            $data['total_tickets'] ?? $showTime->total_tickets
        );

        $showTime->is_sold_out = $request->has('is_sold_out');

        $showTime->save();

        return redirect()
            ->route('admin.shows.times.index', $show)
            ->with('status', 'تم تحديث الموعد بنجاح.');
    }
        // تحديث عدد التذاكر لمعاد معيّن من صفحة تعديل العرض
    public function updateTickets(Request $request, ShowTime $showTime)
    {
        $showTime->loadMissing('show');
        $show = $showTime->show;

        // Seatmap shows ignore the submitted value entirely and re-sync to
        // the theater seat count. Manual ("Other") shows keep the original
        // contract: required positive integer from the form.
        if ($show && $show->usesSeatMap()) {
            $cap      = $show->seatMapCapacity();
            $newTotal = (int) ($cap['total'] ?? $showTime->total_tickets);
        } else {
            $data     = $request->validate([
                'total_tickets' => ['required', 'integer', 'min:1'],
            ]);
            $newTotal = (int) $data['total_tickets'];
        }

        // التذاكر المحجوزة (pending + approved) لهذا المعاد
        $reserved = $showTime->bookings()
            ->whereIn('status', ['pending', 'approved'])
            ->sum('tickets_count');

        // نحدّث الإجمالي والمتاح
        $showTime->total_tickets = $newTotal;
        $remaining = max(0, $newTotal - $reserved);
        $showTime->is_sold_out = ($remaining <= 0);

        $showTime->save();

        return back()->with('status', 'تم تحديث عدد التذاكر لهذا الموعد ✅');
    }

    /**
     * Decide the canonical total_tickets value for a given show/showtime
     * combination. Seatmap-backed shows always derive from the theater
     * seat layout; manual shows fall through to whatever the admin typed.
     */
    private function resolveTotalTickets(Show $show, $submitted): int
    {
        if ($show->usesSeatMap()) {
            $cap = $show->seatMapCapacity();
            // Fall back to the submitted value if the theater seat layout
            // is missing (e.g. seeder hasn't run) — better than zeroing
            // out capacity and silently breaking the booking flow.
            return (int) ($cap['total'] ?? max(1, (int) $submitted));
        }

        return (int) $submitted;
    }

    // حذف معاد
    public function destroy(Show $show, ShowTime $showTime)
    {
        $showTime->delete();

        return redirect()
            ->route('admin.shows.times.index', $show)
            ->with('status', 'تم حذف الموعد.');
    }
    public function toggle(Show $show, ShowTime $showTime)
{
    // قلب الحالة
    $showTime->is_sold_out = !$showTime->is_sold_out;

    $showTime->save();

    return back()->with('status', 'تم تحديث حالة الموعد ✅');
}
}
