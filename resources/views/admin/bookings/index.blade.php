@extends('layouts.admin')

@section('title', 'إدارة الحجوزات')

@section('content')
@php
    /** Quick-stats strip (computed from the same collection the page already
     *  loaded — no extra queries, no controller change). Gives the admin an
     *  at-a-glance summary above the table. */
    $bkPending  = $bookings->where('status', 'pending')->count();
    $bkApproved = $bookings->where('status', 'approved')->count();
    $bkRejected = $bookings->where('status', 'rejected')->count();
    $bkTickets  = (int) $bookings->where('status', 'approved')->sum('tickets_count');
    $bkRevenue  = (int) $bookings->where('status', 'approved')->sum('total_price');
    // Aggregate bulk-discount savings across approved bookings. Uses
    // the persisted discount_amount column so it always matches what
    // customers actually paid (no re-derivation from rules).
    $bkDiscountSavings = (int) $bookings
        ->where('status', 'approved')
        ->sum('discount_amount');
    $bkDiscountedCount = $bookings
        ->where('status', 'approved')
        ->filter(fn ($b) => (int) ($b->discount_percent ?? 0) > 0)
        ->count();
@endphp

<section class="space-y-5">

    {{-- ========================== HEADER ========================== --}}
    <div class="flex items-end justify-between gap-3 flex-wrap prism-fade-up">
        <div class="space-y-1">
            <span class="prism-eyebrow" data-i18n="adm_bookings_eyebrow">BOOKINGS · MANAGEMENT</span>
            <h1 class="prism-headline text-xl sm:text-2xl"
                data-i18n="adm_bookings_title"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                إدارة الحجوزات
            </h1>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
            <span aria-hidden="true" class="pt-arrow-rtl">→</span>
            <span data-i18n="adm_back_dashboard">رجوع للوحة التحكم</span>
        </a>
    </div>

    {{-- ========================== STATUS FLASH ========================== --}}
    @if(session('status'))
        <div class="pt-alert pt-alert-success prism-fade-up">
            {{ session('status') }}
        </div>
    @endif

    {{-- ========================== QUICK STATS STRIP ========================== --}}
    <div class="prism-stat-strip prism-fade-up">
        <div class="prism-stat-strip-item">
            <span class="prism-stat-strip-label" data-i18n="adm_status_pending">قيد المراجعة</span>
            <span class="prism-stat-strip-val prism-stat-strip-val-cyan">{{ $bkPending }}</span>
        </div>
        <div class="prism-stat-strip-item">
            <span class="prism-stat-strip-label" data-i18n="adm_status_approved">معتمد</span>
            <span class="prism-stat-strip-val prism-stat-strip-val-emerald">{{ $bkApproved }}</span>
        </div>
        <div class="prism-stat-strip-item">
            <span class="prism-stat-strip-label" data-i18n="adm_status_rejected">مرفوض</span>
            <span class="prism-stat-strip-val prism-stat-strip-val-rose">{{ $bkRejected }}</span>
        </div>
        <div class="prism-stat-strip-item">
            <span class="prism-stat-strip-label" data-i18n="adm_tickets_approved">تذاكر معتمدة</span>
            <span class="prism-stat-strip-val">{{ $bkTickets }}</span>
        </div>
        <div class="prism-stat-strip-item">
            <span class="prism-stat-strip-label" data-i18n="adm_revenue">Revenue</span>
            <span class="prism-stat-strip-val prism-stat-strip-val-gold">{{ number_format($bkRevenue, 0) }}<span class="opacity-60 ms-1 text-[11px] font-semibold">EGP</span></span>
        </div>
        @if($bkDiscountSavings > 0 || $bkDiscountedCount > 0)
            {{-- Bulk-discount KPI — shows total savings on approved
                 bookings + the count of bookings that qualified. --}}
            <div class="prism-stat-strip-item">
                <span class="prism-stat-strip-label" data-i18n="adm_discount_savings">خصومات مطبقة</span>
                <span class="prism-stat-strip-val prism-stat-strip-val-emerald">
                    −{{ number_format($bkDiscountSavings, 0) }}<span class="opacity-60 ms-1 text-[11px] font-semibold">EGP</span>
                    <span class="opacity-70 text-[11px] font-semibold">· {{ $bkDiscountedCount }}</span>
                </span>
            </div>
        @endif
    </div>

    {{-- ========================== FILTERS / TOOLBAR ========================== --}}
    <div class="prism-toolbar prism-toolbar-sticky prism-fade-up">
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <input id="searchInput" type="text"
                   placeholder="بحث بالاسم / الموبايل / كود الحجز"
                   data-i18n-attr="placeholder:adm_bookings_search_placeholder"
                   class="prism-input text-xs"
                   style="max-width: 320px; min-height: 38px; padding: 8px 12px;">
        </div>

        <div class="prism-toolbar-end">
            {{-- Segmented status control: hidden radios drive .prism-segment label state. --}}
            <div class="prism-segment" role="radiogroup" aria-label="Status filter">
                <input type="radio" name="statusSegment" id="seg-all" value="" checked>
                <label for="seg-all" data-i18n="adm_filter_all">كل الحالات</label>

                <input type="radio" name="statusSegment" id="seg-pending" value="pending">
                <label for="seg-pending"><span style="color: var(--prism-cyan);">●</span> <span data-i18n="adm_filter_pending">Pending</span></label>

                <input type="radio" name="statusSegment" id="seg-approved" value="approved">
                <label for="seg-approved"><span style="color: var(--prism-emerald);">●</span> <span data-i18n="adm_filter_approved">Approved</span></label>

                <input type="radio" name="statusSegment" id="seg-rejected" value="rejected">
                <label for="seg-rejected"><span style="color: var(--prism-rose);">●</span> <span data-i18n="adm_filter_rejected">Rejected</span></label>
            </div>

            <select id="dateTimeFilter" class="prism-input text-xs"
                    style="max-width: 220px; min-height: 38px; padding: 6px 10px;">
                <option value="" data-i18n="adm_filter_all_times">كل المواعيد</option>
                @foreach(
                    $bookings->map(fn($b) => $b->showTime
                        ? $b->showTime->date->format('Y-m-d').' '.$b->showTime->time
                        : null)->filter()->unique()->sort()
                    as $dt
                )
                    <option value="{{ $dt }}">
                        {{ \Carbon\Carbon::parse($dt)->format('d/m/Y • g:i A') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ========================== DESKTOP TABLE ========================== --}}
    <div class="hidden md:block prism-fade-up">
        <div class="prism-glass overflow-x-auto">
            <table class="prism-table-clean w-full">
                <thead>
                    <tr>
                        <th class="pt-rtl-text text-start" data-i18n="adm_bk_col_code">الكود</th>
                        <th class="pt-rtl-text text-start" data-i18n="adm_bk_col_guest">الضيف</th>
                        <th class="pt-rtl-text text-start" data-i18n="adm_bk_col_show">العرض / الموعد</th>
                        <th class="text-center" data-i18n="adm_bk_col_status">الحالة</th>
                        <th class="text-center" data-i18n="adm_bk_col_ticket">التذاكر</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($bookings as $booking)
                    @php
                        $dt = $booking->showTime
                            ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                            : '';
                        $total   = (int) $booking->tickets_count;
                        $sent    = (int) $booking->tickets_sent_count;
                        $allSent = $total > 0 && $sent === $total;
                        $bookingTime = $booking->created_at ? $booking->created_at->format('g:i A') : '';
                    @endphp
                    <tr class="booking-row hover:bg-[color:var(--prism-surface-hover)] cursor-pointer transition-colors duration-200"
                        onclick="window.location='{{ route('admin.bookings.show', $booking) }}'"
                        data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code.' '.$bookingTime) }}"
                        data-status="{{ $booking->status }}"
                        data-datetime="{{ $dt }}">
                        
                        <td class="align-middle font-mono text-xs font-semibold" style="color: var(--prism-text);">
                            {{ $booking->reference_code }}
                        </td>

                        <td class="align-middle">
                            <p class="font-bold text-sm text-[color:var(--prism-text)]">{{ $booking->full_name }}</p>
                            <span class="text-[color:var(--prism-text-3)] block text-xs" dir="ltr">{{ $booking->phone }}</span>
                        </td>

                        <td class="align-middle">
                            <span class="text-[color:var(--prism-text)] text-sm font-medium">{{ $booking->showTime->show->title ?? '-' }}</span><br>
                            <span class="text-[color:var(--prism-text-3)] text-xs">
                                {{ $booking->showTime?->date->format('d/m/Y') }}
                                • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                            </span>
                        </td>

                        <td class="align-middle text-center">
                            <span class="prism-pill {{ $booking->status==='approved' ? 'prism-pill-emerald'
                                                       : ($booking->status==='rejected' ? 'prism-pill-rose' : 'prism-pill-sky') }}">
                                {{ $booking->status }}
                            </span>
                            @if($bookingTime)
                            <div class="mt-1 text-[10px] text-[color:var(--prism-text-3)] font-medium" dir="ltr">
                                🕒 {{ $bookingTime }}
                            </div>
                            @endif
                        </td>

                        <td class="align-middle text-center">
                            <div class="flex flex-col items-center justify-center gap-1">
                                <div class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background: rgba(251,191,36,0.1); color: var(--prism-gold);">
                                    🎟️ {{ $total }}
                                </div>
                                @if($booking->status === 'approved')
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="inline-block w-2 h-2 rounded-full"
                                          style="background: {{ $allSent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};
                                                 box-shadow: 0 0 8px {{ $allSent ? 'rgba(52,211,153,0.7)' : 'rgba(251,113,133,0.7)' }};"></span>
                                    <span class="text-[10px] font-mono text-[color:var(--prism-text-3)]" dir="ltr">
                                        {{ $sent }}/{{ $total }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================== MOBILE CARDS ========================== --}}
    <div class="md:hidden space-y-3 prism-stagger">
        @foreach($bookings as $booking)
            @php
                $dt = $booking->showTime
                    ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                    : '';
                $total   = (int) $booking->tickets_count;
                $sent    = (int) $booking->tickets_sent_count;
                $allSent = $total > 0 && $sent === $total;
                $bookingTime = $booking->created_at ? $booking->created_at->format('g:i A') : '';
            @endphp

            <div class="prism-glass prism-card-hover p-4 text-xs booking-card prism-fade-up relative overflow-hidden"
                 data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code.' '.$bookingTime) }}"
                 data-status="{{ $booking->status }}"
                 data-datetime="{{ $dt }}">
                 
                {{-- Massive touch target stretched link --}}
                <a href="{{ route('admin.bookings.show', $booking) }}" class="absolute inset-0 z-10" aria-label="View booking {{ $booking->reference_code }}"></a>

                {{-- TOP ROW: Customer & Reference --}}
                <div class="flex justify-between items-start mb-2 gap-2 relative z-0">
                    <div class="font-bold text-sm text-[color:var(--prism-text)]">{{ $booking->full_name }}</div>
                    <span class="font-mono px-2 py-0.5 rounded text-[11px] font-semibold"
                          style="background: var(--prism-surface-soft); border: 1px solid var(--prism-border); color: var(--prism-text-2);">
                        {{ $booking->reference_code }}
                    </span>
                </div>
                
                {{-- MIDDLE ROW: Phone & Show info --}}
                <div class="mb-4 space-y-1 relative z-0 text-[11px]">
                    <div class="text-[color:var(--prism-text-3)]" dir="ltr">{{ $booking->phone }}</div>
                    <div class="flex flex-wrap items-center gap-1.5 text-[color:var(--prism-text-3)]">
                        <span class="font-medium text-[color:var(--prism-text-2)]">🎭 {{ $booking->showTime->show->title ?? '-' }}</span>
                        <span class="opacity-50">•</span>
                        <span>{{ $booking->showTime?->date->format('d/m/Y') }}</span>
                        <span class="opacity-50">•</span>
                        <span>{{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}</span>
                    </div>
                </div>

                {{-- BOTTOM ROW: Status, Booking Time, Tickets, Delivery --}}
                <div class="flex items-center justify-between gap-2 relative z-0 pt-3" style="border-top: 1px solid rgba(255,255,255,0.05);">
                    <div class="flex flex-col gap-1">
                        <span class="prism-pill {{ $booking->status==='approved' ? 'prism-pill-emerald'
                                                   : ($booking->status==='rejected' ? 'prism-pill-rose' : 'prism-pill-sky') }}">
                            {{ $booking->status }}
                        </span>
                        @if($bookingTime)
                        <span class="text-[10px] text-[color:var(--prism-text-3)] font-medium" dir="ltr">
                            🕒 {{ $bookingTime }}
                        </span>
                        @endif
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full flex items-center gap-1" style="background: rgba(251,191,36,0.1); color: var(--prism-gold);">
                            🎟️ {{ $total }}
                        </span>
                        
                        @if($booking->status === 'approved')
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full"
                                  style="background: {{ $allSent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};
                                         box-shadow: 0 0 8px {{ $allSent ? 'rgba(52,211,153,0.7)' : 'rgba(251,113,133,0.7)' }};"></span>
                            <span class="text-[color:var(--prism-text-3)] font-mono text-[11px]" dir="ltr">
                                {{ $sent }}/{{ $total }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Empty state for filters that match nothing --}}
    <div id="emptyFilterState" class="prism-glass p-6 text-center prism-fade-up" style="display:none;">
        <div class="text-sm text-[color:var(--prism-text-2)] mb-1" data-i18n="adm_bk_no_match">لا توجد حجوزات تطابق هذا الفلتر.</div>
        <button type="button" id="resetFilters" class="prism-btn-ghost text-xs mt-2 inline-flex"
                data-i18n="adm_bk_reset_filters">
            تصفير الفلاتر
        </button>
    </div>

</section>

{{-- =============== JS FILTER (logic preserved, status now driven by segmented control) =============== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('searchInput');
    const dt     = document.getElementById('dateTimeFilter');
    const statusRadios = document.querySelectorAll('input[name="statusSegment"]');
    const items  = document.querySelectorAll('.booking-row, .booking-card');
    const empty  = document.getElementById('emptyFilterState');
    const reset  = document.getElementById('resetFilters');

    function getStatus() {
        const checked = document.querySelector('input[name="statusSegment"]:checked');
        return checked ? checked.value : '';
    }

    function filter(){
        const s = search.value.toLowerCase();
        const status = getStatus();
        let visible = 0;
        items.forEach(el=>{
            const ok =
                el.dataset.search.includes(s) &&
                (!status || el.dataset.status===status) &&
                (!dt.value || el.dataset.datetime===dt.value);
            el.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        if (empty) empty.style.display = (visible === 0 && items.length > 0) ? '' : 'none';
    }

    // Debounce helper — delays execution until input pauses for `ms`.
    // Prevents excessive DOM re-filtering on every keystroke when there
    // are hundreds of booking rows / cards.
    let debounceTimer;
    function debouncedFilter() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(filter, 250);
    }

    search.addEventListener('input', debouncedFilter);
    dt.addEventListener('change', filter);
    statusRadios.forEach(r => r.addEventListener('change', filter));

    if (reset) reset.addEventListener('click', () => {
        search.value = '';
        dt.value = '';
        document.getElementById('seg-all').checked = true;
        filter();
    });
});
</script>
@endsection
