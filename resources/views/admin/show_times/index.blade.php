@extends('layouts.app')

@section('title', 'مواعيد العرض - ' . $show->title)

@php
    use App\Models\Show as ShowModel;
    $usesSectionPricing = $show->theater_type === ShowModel::THEATER_ANBA_RUWEIS;
    $sectionPriceLabel  = $usesSectionPricing
        ? ((int) ($show->hall_price ?? 0)) . ' / ' . ((int) ($show->balcony_price ?? 0)) . ' ج'
        : null;
@endphp
@section('content')
<section class="space-y-6">

    {{-- Header --}}
    <div class="prism-glass prism-glow-border p-5 prism-fade-up flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                <span data-i18n="adm_times_pill">Show Times</span>
            </span>
            <h1 class="prism-headline text-xl sm:text-2xl">
                <span data-i18n="adm_times_title"
                      style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    مواعيد العرض
                </span>
            </h1>
            <p class="text-xs text-[color:var(--prism-text-3)]">{{ $show->title }}</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.shows.times.create', $show) }}" class="prism-btn text-sm">
                <span data-i18n="adm_times_add">+ إضافة موعد جديد</span>
            </a>

            <a href="{{ route('admin.shows.index') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span data-i18n="adm_back">رجوع</span>
            </a>
        </div>
    </div>

    {{-- Success --}}
    @if(session('status'))
        <div class="rounded-xl px-4 py-3 text-sm prism-fade-up"
             style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
            {{ session('status') }}
        </div>
    @endif

    @if($times->isEmpty())
        <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)] prism-fade-up"
             data-i18n="adm_times_empty">
            لا توجد مواعيد لهذا العرض حتى الآن.
        </div>
    @else

        {{-- DESKTOP --}}
        <div class="hidden md:block prism-glass overflow-hidden prism-fade-up">
            <div class="overflow-x-auto">
                <table class="min-w-[720px] w-full text-sm text-[color:var(--prism-text-2)] text-center">

                    <thead style="background: rgba(255,255,255,0.04);">
                        <tr class="text-xs uppercase" style="letter-spacing:.14em; color: var(--prism-text-3);">
                            <th class="px-3 py-3 text-center" data-i18n="adm_times_col_date">التاريخ</th>
                            <th class="px-3 py-3 text-center" data-i18n="adm_times_col_time">الساعة</th>
                            <th class="px-3 py-3 text-center" data-i18n="adm_times_col_price">السعر</th>
                            <th class="px-3 py-3 text-center" data-i18n="adm_times_col_avail">المتاح / الإجمالي</th>
                            <th class="px-3 py-3 text-center" data-i18n="adm_times_col_status">الحالة</th>
                            <th class="px-3 py-3 text-center" data-i18n="adm_times_col_actions">إجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($times as $time)
                        @php
                            $reserved = $time->bookings()
                                ->whereIn('status', ['approved','pending'])
                                ->sum('tickets_count');
                            $remaining = max(0, $time->total_tickets - $reserved);
                            $isLocked  = $remaining <= 0;
                        @endphp

                        <tr style="border-top: 1px solid rgba(255,255,255,0.06); transition: background .15s ease;"
                            onmouseover="this.style.background='rgba(129,140,248,0.06)'"
                            onmouseout="this.style.background=''">

                            <td class="px-3 py-3 text-center align-middle text-[color:var(--prism-text)]">
                                {{ $time->date->format('d/m/Y') }}
                            </td>

                            <td class="px-3 py-3 text-center align-middle">
                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </td>

                            <td class="px-3 py-3" style="color: var(--prism-gold);">
                                @if ($usesSectionPricing)
                                    <span class="text-[11px]" title="صالة / بلكون">
                                        {{ $sectionPriceLabel }}
                                    </span>
                                @else
                                    {{ $time->ticket_price }} <span data-i18n="common_currency_short">ج</span>
                                @endif
                            </td>

                            <td class="px-3 py-3 text-center align-middle">
                                <span class="prism-pill">
                                    <span class="font-semibold" style="color: var(--prism-emerald);">{{ $remaining }}</span>
                                    <span class="opacity-60">/ {{ $time->total_tickets }}</span>
                                </span>
                            </td>

                            {{-- PRISM SWITCH --}}
                            <td class="px-3 py-3 text-center">
                                <form action="{{ route('admin.shows.times.toggle', [$show, $time]) }}" method="POST">
                                    @csrf
                                    @method('PATCH')

                                    <label class="cursor-pointer block w-fit mx-auto">
                                        <input type="checkbox" class="sr-only peer"
                                               onchange="this.form.submit()"
                                               {{ ($time->is_sold_out || $isLocked) ? 'checked' : '' }}
                                               {{ $isLocked ? 'disabled' : '' }}>

                                        <div class="relative flex items-center justify-between w-[120px] h-9 px-2 rounded-full transition-all duration-300"
                                             style="
                                                background: {{ ($time->is_sold_out || $isLocked) ? 'rgba(244,63,94,0.18)' : 'rgba(16,185,129,0.12)' }};
                                                border: 1px solid {{ ($time->is_sold_out || $isLocked) ? 'rgba(251,113,133,0.45)' : 'rgba(52,211,153,0.45)' }};
                                                box-shadow: {{ ($time->is_sold_out || $isLocked) ? '0 0 14px rgba(244,63,94,0.25)' : '0 0 14px rgba(52,211,153,0.25)' }};
                                                opacity: {{ $isLocked ? '0.6' : '1' }};
                                                cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }};">

                                            <div class="absolute top-1 w-7 h-7 bg-white rounded-full shadow-md transition-all duration-300
                                                        {{ ($time->is_sold_out || $isLocked) ? 'left-1' : 'left-[calc(100%-2rem)]' }}">
                                            </div>

                                            <span class="text-xs w-full text-center font-medium z-10"
                                                  data-i18n="{{ ($time->is_sold_out || $isLocked) ? 'adm_status_sold_out' : 'adm_status_available' }}"
                                                  style="color: {{ ($time->is_sold_out || $isLocked) ? '#fda4af' : '#6ee7b7' }};">
                                                {{ ($time->is_sold_out || $isLocked) ? 'Sold Out' : 'متاح' }}
                                            </span>
                                        </div>
                                    </label>
                                </form>
                            </td>

                            <td class="px-3 py-3 text-center align-middle">
                                <div class="flex justify-center items-center gap-2 flex-wrap">
                                    @if($show->theater_type === \App\Models\Show::THEATER_ANBA_RUWEIS)
                                        <a href="{{ route('admin.show-times.seats.index', $time) }}"
                                           class="px-3 py-1 rounded-full text-xs transition"
                                           data-i18n="adm_seats"
                                           style="background: rgba(251,191,36,0.14); border: 1px solid rgba(251,191,36,0.40); color: #fcd34d;"
                                           onmouseover="this.style.background='rgba(251,191,36,0.22)'; this.style.boxShadow='0 0 16px rgba(251,191,36,0.3)';"
                                           onmouseout="this.style.background='rgba(251,191,36,0.14)'; this.style.boxShadow='';">
                                            المقاعد
                                        </a>
                                    @endif

                                    {{-- Read-only seat-occupancy / attendee manifest. Available
                                         for all showtimes — for seatmap shows it includes empty
                                         seats so it doubles as a printable seating sheet, for
                                         "Other" venues it lists just the attendees. --}}
                                    <a href="{{ route('admin.show-times.manifest', $time) }}"
                                       class="px-3 py-1 rounded-full text-xs transition"
                                       title="Seat occupancy / attendee manifest"
                                       style="background: rgba(34,211,238,0.14); border: 1px solid rgba(34,211,238,0.40); color: #a5f3fc;"
                                       onmouseover="this.style.background='rgba(34,211,238,0.22)'; this.style.boxShadow='0 0 16px rgba(34,211,238,0.30)';"
                                       onmouseout="this.style.background='rgba(34,211,238,0.14)'; this.style.boxShadow='';">
                                        📋 المانيفست
                                    </a>

                                    <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                                       class="prism-btn-ghost text-xs px-3 py-1"
                                       data-i18n="adm_edit">
                                        تعديل
                                    </a>

                                    <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}" method="POST"
                                          onsubmit="return confirm((window.PT && window.PT.lang() === 'en') ? 'Are you sure you want to delete this show time?' : 'متأكد إنك عايز تحذف الموعد؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1 rounded-full text-xs transition"
                                                data-i18n="adm_delete"
                                                style="background: rgba(244,63,94,0.14); border: 1px solid rgba(251,113,133,0.40); color: #fda4af;"
                                                onmouseover="this.style.background='rgba(244,63,94,0.22)'; this.style.boxShadow='0 0 16px rgba(244,63,94,0.3)';"
                                                onmouseout="this.style.background='rgba(244,63,94,0.14)'; this.style.boxShadow='';">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MOBILE --}}
        <div class="md:hidden space-y-3 prism-stagger pt-reveal pt-reveal-stagger">

            @foreach($times as $time)
                @php
                    $reserved = $time->bookings()
                        ->whereIn('status', ['approved','pending'])
                        ->sum('tickets_count');
                    $remaining = max(0, $time->total_tickets - $reserved);
                    $isLocked  = $remaining <= 0;
                @endphp

                <div class="prism-glass p-4 space-y-3 prism-fade-up">

                    <div class="flex justify-between text-xs">
                        <span class="text-[color:var(--prism-text)]">{{ $time->date->format('d/m/Y') }}</span>
                        <span class="font-semibold" style="color: var(--prism-gold);">
                            {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 text-center text-xs gap-2">
                        <div class="rounded-lg py-2"
                             style="background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.32);">
                            <div class="text-[color:var(--prism-text-3)] text-[10px]"
                                 data-i18n="{{ $usesSectionPricing ? 'adm_times_col_price_split' : 'adm_times_col_price' }}">
                                @if ($usesSectionPricing) صالة / بلكون @else السعر @endif
                            </div>
                            <div style="color: var(--prism-gold);" class="font-semibold">
                                @if ($usesSectionPricing)
                                    {{ $sectionPriceLabel }}
                                @else
                                    {{ $time->ticket_price }} <span data-i18n="common_currency_short">ج</span>
                                @endif
                            </div>
                        </div>

                        <div class="rounded-lg py-2"
                             style="background: rgba(52,211,153,0.08); border: 1px solid rgba(52,211,153,0.32);">
                            <div class="text-[color:var(--prism-text-3)] text-[10px]" data-i18n="adm_times_col_avail_short">المتاح</div>
                            <div style="color: var(--prism-emerald);" class="font-semibold">{{ $remaining }}</div>
                        </div>

                        <div class="rounded-lg py-2"
                             style="background: rgba(255,255,255,0.04); border: 1px solid var(--prism-border);">
                            <div class="text-[color:var(--prism-text-3)] text-[10px]" data-i18n="adm_times_col_total">الإجمالي</div>
                            <div class="text-[color:var(--prism-text)] font-semibold">{{ $time->total_tickets }}</div>
                        </div>
                    </div>

                    {{-- SWITCH MOBILE --}}
                    <form action="{{ route('admin.shows.times.toggle', [$show, $time]) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <label class="cursor-pointer block w-full">
                            <input type="checkbox" class="sr-only peer"
                                   onchange="this.form.submit()"
                                   {{ ($time->is_sold_out || $isLocked) ? 'checked' : '' }}
                                   {{ $isLocked ? 'disabled' : '' }}>

                            <div class="relative flex items-center justify-between w-full h-10 px-3 rounded-full transition-all duration-300"
                                 style="
                                    background: {{ ($time->is_sold_out || $isLocked) ? 'rgba(244,63,94,0.18)' : 'rgba(16,185,129,0.12)' }};
                                    border: 1px solid {{ ($time->is_sold_out || $isLocked) ? 'rgba(251,113,133,0.45)' : 'rgba(52,211,153,0.45)' }};
                                    box-shadow: {{ ($time->is_sold_out || $isLocked) ? '0 0 14px rgba(244,63,94,0.25)' : '0 0 14px rgba(52,211,153,0.25)' }};
                                    opacity: {{ $isLocked ? '0.6' : '1' }};
                                    cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }};">

                                <div class="absolute top-1 w-7 h-7 bg-white rounded-full shadow-md transition-all duration-300
                                            {{ ($time->is_sold_out || $isLocked) ? 'left-1' : 'left-[calc(100%-2rem)]' }}">
                                </div>

                                <span class="text-xs w-full text-center font-medium z-10"
                                      data-i18n="{{ ($time->is_sold_out || $isLocked) ? 'adm_status_sold_out' : 'adm_status_available' }}"
                                      style="color: {{ ($time->is_sold_out || $isLocked) ? '#fda4af' : '#6ee7b7' }};">
                                    {{ ($time->is_sold_out || $isLocked) ? 'Sold Out' : 'متاح' }}
                                </span>
                            </div>
                        </label>
                    </form>

                    <div class="flex gap-2 flex-wrap">
                        @if($show->theater_type === \App\Models\Show::THEATER_ANBA_RUWEIS)
                            <a href="{{ route('admin.show-times.seats.index', $time) }}"
                               class="flex-1 text-center py-2 rounded-lg text-xs transition"
                               data-i18n="adm_seats"
                               style="background: rgba(251,191,36,0.14); border: 1px solid rgba(251,191,36,0.40); color: #fcd34d;">
                                المقاعد
                            </a>
                        @endif

                        {{-- Manifest link (mirror of desktop action bar) --}}
                        <a href="{{ route('admin.show-times.manifest', $time) }}"
                           class="flex-1 text-center py-2 rounded-lg text-xs transition"
                           title="Seat occupancy / attendee manifest"
                           style="background: rgba(34,211,238,0.14); border: 1px solid rgba(34,211,238,0.40); color: #a5f3fc;">
                            📋 المانيفست
                        </a>

                        <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                           class="flex-1 text-center py-2 rounded-lg text-xs transition"
                           data-i18n="adm_edit"
                           style="background: rgba(255,255,255,0.06); border: 1px solid var(--prism-border); color: var(--prism-text);">
                            تعديل
                        </a>

                        <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}" method="POST" class="flex-1"
                              onsubmit="return confirm((window.PT && window.PT.lang() === 'en') ? 'Are you sure you want to delete this show time?' : 'متأكد إنك عايز تحذف الموعد؟');">
                            @csrf
                            @method('DELETE')
                            <button class="w-full py-2 rounded-lg text-xs transition"
                                    data-i18n="adm_delete"
                                    style="background: rgba(244,63,94,0.14); border: 1px solid rgba(251,113,133,0.40); color: #fda4af;">
                                حذف
                            </button>
                        </form>
                    </div>

                </div>
            @endforeach

        </div>

    @endif

</section>
@endsection
