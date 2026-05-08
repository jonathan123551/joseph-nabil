@extends('layouts.app')

@section('title', 'إدارة الحجوزات')

@section('content')
@php
    /** Quick-stats strip (computed from the same collection the page already
     *  loaded — no extra queries, no controller change). Gives the admin an
     *  at-a-glance summary above the table. */
    $bkPending  = $bookings->where('status', 'pending')->count();
    $bkApproved = $bookings->where('status', 'approved')->count();
    $bkRejected = $bookings->where('status', 'rejected')->count();
    $bkTickets  = $bookings->where('status', 'approved')->sum('tickets_count');
    $bkRevenue  = (int) $bookings->where('status', 'approved')->sum('total_price');
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
        <div class="rounded-2xl px-4 py-3 text-sm prism-fade-up"
             style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
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
                        <th class="pt-rtl-text text-start" data-i18n="adm_bk_col_guest">الضيف</th>
                        <th class="pt-rtl-text text-start" data-i18n="adm_bk_col_show">العرض / الموعد</th>
                        <th class="pt-rtl-text text-start" data-i18n="adm_bk_col_status">الحالة</th>
                        <th class="text-center" data-i18n="adm_bk_col_ticket">التذكرة</th>
                        <th class="pt-rtl-text text-start" data-i18n="adm_bk_col_actions">إجراءات</th>
                        <th class="pt-rtl-text text-start" data-i18n="adm_bk_col_code">الكود</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($bookings as $booking)
                    @php
                        $dt = $booking->showTime
                            ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                            : '';
                    @endphp
                    <tr class="booking-row"
                        data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                        data-status="{{ $booking->status }}"
                        data-datetime="{{ $dt }}">

                        <td class="align-top">
                            <div>
                                <p class="font-bold text-[color:var(--prism-text)]">{{ $booking->full_name }}</p>
                                <p class="text-xs" style="color: var(--prism-gold);">
                                    🎟️ {{ $booking->tickets_count }} <span data-i18n="common_ticket_word">تذكرة</span>
                                </p>
                            </div>
                            <span class="text-[color:var(--prism-text-3)] block mb-1">{{ $booking->phone }}</span>

                            @foreach($booking->tickets as $ticket)
                                <div class="text-xs text-[color:var(--prism-text-3)] mr-2">
                                    👤 {{ $ticket->name }} - 📱 {{ $ticket->phone }}
                                </div>
                            @endforeach
                        </td>

                        <td class="align-top">
                            <span class="text-[color:var(--prism-text)]">{{ $booking->showTime->show->title ?? '-' }}</span><br>
                            <span class="text-[color:var(--prism-text-3)] text-xs">
                                {{ $booking->showTime?->date->format('d/m/Y') }}
                                • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                            </span>
                        </td>

                        <td class="align-top">
                            <span class="prism-pill {{ $booking->status==='approved' ? 'prism-pill-emerald'
                                                       : ($booking->status==='rejected' ? 'prism-pill-rose' : 'prism-pill-sky') }}">
                                {{ $booking->status }}
                            </span>
                        </td>

                        <td class="align-top text-center">
                            @php
                                $allSent = $booking->tickets->every(fn($t) => $t->whatsapp_sent);
                                $total   = $booking->tickets->count();
                                $sent    = $booking->tickets->where('whatsapp_sent', true)->count();
                            @endphp
                            <div class="flex flex-col items-center gap-1">
                                <span class="inline-block w-3 h-3 rounded-full"
                                      style="background: {{ $allSent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};
                                             box-shadow: 0 0 10px {{ $allSent ? 'rgba(52,211,153,0.7)' : 'rgba(251,113,133,0.7)' }};"></span>
                                <span class="text-[10px] text-[color:var(--prism-text-3)]">
                                    {{ $sent }}/{{ $total }}
                                </span>
                            </div>
                        </td>

                        <td class="align-top">
                            <a href="{{ route('admin.bookings.show',$booking) }}" class="prism-btn-ghost text-xs px-3 py-1.5"
                               data-i18n="adm_bk_details">
                                تفاصيل
                            </a>
                        </td>

                        <td class="align-top font-mono text-xs"
                            style="color: var(--prism-text-2);">
                            {{ $booking->reference_code }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================== MOBILE CARDS ========================== --}}
    <div class="md:hidden space-y-3 prism-stagger pt-reveal pt-reveal-stagger">
        @foreach($bookings as $booking)
            @php
                $dt = $booking->showTime
                    ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                    : '';
            @endphp

            <div class="prism-glass prism-card-hover p-4 text-xs booking-card prism-fade-up"
                 data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                 data-status="{{ $booking->status }}"
                 data-datetime="{{ $dt }}">

                <div class="flex justify-between mb-2 gap-2">
                    <div>
                        <div class="font-semibold text-sm text-[color:var(--prism-text)]">{{ $booking->full_name }}</div>

                        <div class="text-xs mb-1" style="color: var(--prism-gold);">
                            🎟️ {{ $booking->tickets_count }} <span data-i18n="common_ticket_word">تذكرة</span>
                        </div>

                        <div class="text-[color:var(--prism-text-3)]">{{ $booking->phone }}</div>

                        @foreach($booking->tickets as $ticket)
                            <div class="text-[11px] text-[color:var(--prism-text-3)]">
                                👤 {{ $ticket->name }} - 📱 {{ $ticket->phone }}
                            </div>
                        @endforeach
                    </div>
                    <span class="font-mono px-2 py-1 rounded text-[10px] h-fit"
                          style="background: rgba(255,255,255,0.05); border: 1px solid var(--prism-border); color: var(--prism-text-2);">
                        {{ $booking->reference_code }}
                    </span>
                </div>

                <div class="mb-3">
                    <span class="text-[color:var(--prism-text)]">🎭 {{ $booking->showTime->show->title ?? '-' }}</span><br>
                    <span class="text-[color:var(--prism-text-3)]">
                        🕒 {{ $booking->showTime?->date->format('d/m/Y') }}
                        • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <span class="prism-pill {{ $booking->status==='approved' ? 'prism-pill-emerald'
                                               : ($booking->status==='rejected' ? 'prism-pill-rose' : 'prism-pill-sky') }}">
                        {{ $booking->status }}
                    </span>

                    @php
                        $allSent = $booking->tickets->every(fn($t) => $t->whatsapp_sent);
                        $total   = $booking->tickets->count();
                        $sent    = $booking->tickets->where('whatsapp_sent', true)->count();
                    @endphp

                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full"
                              style="background: {{ $allSent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};
                                     box-shadow: 0 0 8px {{ $allSent ? 'rgba(52,211,153,0.7)' : 'rgba(251,113,133,0.7)' }};"></span>
                        <span class="text-[color:var(--prism-text-3)] text-[11px]">
                            {{ $sent }}/{{ $total }}
                        </span>
                    </div>

                    <a href="{{ route('admin.bookings.show',$booking) }}" class="prism-btn-ghost text-xs px-3 py-1.5"
                       data-i18n="adm_bk_details">
                        تفاصيل
                    </a>
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

    [search, dt].forEach(i=>{
        i.addEventListener('input',filter);
        i.addEventListener('change',filter);
    });
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
