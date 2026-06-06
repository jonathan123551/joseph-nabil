@extends('layouts.admin')

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
        <div class="pt-alert pt-alert-success prism-fade-up">
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

                    <thead class="pt-thead-soft">
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
                            // Single source of truth — subtracts customer
                            // bookings (pending + approved) AND admin-blocked
                            // seats. The blocked count drives the small
                            // "blocked" chip next to the availability pill.
                            $blocked   = $time->blockedSeatsCount();
                            $remaining = $time->effectiveRemainingTickets();
                            $isLocked  = $remaining <= 0;
                        @endphp

                        <tr class="pt-time-row">

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
                                <div class="inline-flex items-center gap-1.5 flex-wrap justify-center">
                                    <span class="prism-pill">
                                        <span class="font-semibold" style="color: var(--prism-emerald);">{{ $remaining }}</span>
                                        <span class="opacity-60">/ {{ $time->total_tickets }}</span>
                                    </span>
                                    @if($blocked > 0)
                                        <span class="prism-pill prism-pill-rose" style="font-size:10px;"
                                              title="{{ $blocked }} blocked seats (not counted in revenue)"
                                              data-i18n-html="adm_times_blocked_chip"
                                              data-i18n-vars='{"n": {{ $blocked }}}'>
                                            🚫 {{ $blocked }} محجوب
                                        </span>
                                    @endif
                                </div>
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
                                           class="pt-action-pill pt-action-pill-gold"
                                           data-i18n="adm_seats">
                                            المقاعد
                                        </a>
                                    @endif

                                    {{-- Read-only seat-occupancy / attendee manifest. Available
                                         for all showtimes — for seatmap shows it includes empty
                                         seats so it doubles as a printable seating sheet, for
                                         "Other" venues it lists just the attendees. --}}
                                    <a href="{{ route('admin.show-times.manifest', $time) }}"
                                       class="pt-action-pill pt-action-pill-cyan"
                                       title="Seat occupancy / attendee manifest">
                                        📋 المانيفست
                                    </a>

                                    <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                                       class="pt-action-pill"
                                       data-i18n="adm_edit">
                                        تعديل
                                    </a>

                                    <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}" method="POST"
                                          onsubmit="return confirm((window.PT && window.PT.lang() === 'en') ? 'Are you sure you want to delete this show time?' : 'متأكد إنك عايز تحذف الموعد؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="pt-action-pill pt-action-pill-rose"
                                                data-i18n="adm_delete">
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
                    $blocked   = $time->blockedSeatsCount();
                    $remaining = $time->effectiveRemainingTickets();
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
                        <div class="pt-mini-card pt-mini-card-gold py-2">
                            <div class="pt-mini-card-label"
                                 data-i18n="{{ $usesSectionPricing ? 'adm_times_col_price_split' : 'adm_times_col_price' }}">
                                @if ($usesSectionPricing) صالة / بلكون @else السعر @endif
                            </div>
                            <div class="pt-mini-card-value">
                                @if ($usesSectionPricing)
                                    {{ $sectionPriceLabel }}
                                @else
                                    {{ $time->ticket_price }} <span data-i18n="common_currency_short">ج</span>
                                @endif
                            </div>
                        </div>

                        <div class="pt-mini-card pt-mini-card-emerald py-2">
                            <div class="pt-mini-card-label" data-i18n="adm_times_col_avail_short">المتاح</div>
                            <div class="pt-mini-card-value">{{ $remaining }}</div>
                        </div>

                        <div class="pt-mini-card py-2">
                            <div class="pt-mini-card-label" data-i18n="adm_times_col_total">الإجمالي</div>
                            <div class="pt-mini-card-value text-[color:var(--prism-text)]">{{ $time->total_tickets }}</div>
                        </div>
                    </div>

                    @if($blocked > 0)
                        <div class="flex items-center justify-center">
                            <span class="prism-pill prism-pill-rose" style="font-size:10px;"
                                  title="{{ $blocked }} blocked seats (not counted in revenue)"
                                  data-i18n-html="adm_times_blocked_chip"
                                  data-i18n-vars='{"n": {{ $blocked }}}'>
                                🚫 {{ $blocked }} محجوب
                            </span>
                        </div>
                    @endif

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
                               class="pt-action-pill pt-action-pill-gold flex-1"
                               data-i18n="adm_seats">
                                المقاعد
                            </a>
                        @endif

                        {{-- Manifest link (mirror of desktop action bar) --}}
                        <a href="{{ route('admin.show-times.manifest', $time) }}"
                           class="pt-action-pill pt-action-pill-cyan flex-1"
                           title="Seat occupancy / attendee manifest">
                            📋 المانيفست
                        </a>

                        <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                           class="pt-action-pill flex-1"
                           data-i18n="adm_edit">
                            تعديل
                        </a>

                        <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}" method="POST" class="flex-1"
                              onsubmit="return confirm((window.PT && window.PT.lang() === 'en') ? 'Are you sure you want to delete this show time?' : 'متأكد إنك عايز تحذف الموعد؟');">
                            @csrf
                            @method('DELETE')
                            <button class="pt-action-pill pt-action-pill-rose w-full"
                                    data-i18n="adm_delete">
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
