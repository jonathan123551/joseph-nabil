@extends('layouts.app')

@section('title', 'مواعيد العرض - ' . $show->title)

@php
    use App\Models\Show as ShowModel;
    use App\Models\Theater;

    $usesSectionPricing = $show->theater_type === ShowModel::THEATER_ANBA_RUWEIS;
    $sectionPriceLabel  = $usesSectionPricing
        ? ((int) ($show->hall_price ?? 0)) . ' / ' . ((int) ($show->balcony_price ?? 0)) . ' ج'
        : null;

    // Helper closures kept in the template so the view stays self-contained.
    // The data is already pre-computed by ShowTimeAnalytics — these only
    // handle presentation (formatting, ring math, status copy).
    $fmt = fn ($n) => number_format((int) $n);

    // SVG ring geometry — single source of truth so every ring in the
    // page renders at the same radius / stroke. Changing one number here
    // resizes every ring consistently.
    $ringRadius = 42;
    $ringCircumference = 2 * M_PI * $ringRadius;
@endphp

@section('content')

{{-- Shared SVG gradient. Hoisted out of the per-card <svg> so we keep
     `staRingGrad` unique per document (HTML rule) and avoid re-uploading
     the same definition once per showtime. --}}
<svg width="0" height="0" style="position:absolute;" aria-hidden="true">
    <defs>
        <linearGradient id="staRingGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%"  stop-color="#34d399"/>
            <stop offset="55%" stop-color="#22d3ee"/>
            <stop offset="100%" stop-color="#a78bfa"/>
        </linearGradient>
    </defs>
</svg>

{{-- Scoped CSS for the analytics dashboard. Lives in the template so the
     redesign ships in one file — only the occupancy ring and the
     stacked-progress bar need custom rules; everything else reuses the
     PRISM tokens that already exist in layouts/app.blade.php. --}}
<style>
    .sta-ring-wrap {
        position: relative;
        width: 116px;
        height: 116px;
        flex: 0 0 auto;
    }
    .sta-ring-wrap svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }
    .sta-ring-track {
        fill: none;
        stroke: rgba(255,255,255,0.08);
        stroke-width: 10;
    }
    .sta-ring-fill {
        fill: none;
        stroke: url(#staRingGrad);
        stroke-width: 10;
        stroke-linecap: round;
        transition: stroke-dashoffset .9s cubic-bezier(.25,.8,.25,1);
    }
    .sta-ring-center {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center;
        line-height: 1;
    }
    .sta-ring-percent {
        font-family: "Space Grotesk", system-ui, sans-serif;
        font-weight: 700;
        font-size: 26px;
        color: var(--prism-text);
        letter-spacing: -0.02em;
    }
    .sta-ring-caption {
        margin-top: 4px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.14em;
        color: var(--prism-text-3);
        text-transform: uppercase;
    }

    /* Stacked progress bar — visualises approved | pending | blocked |
       remaining as horizontal segments. Uses CSS variables so the
       segment widths come straight from the analytics percentages. */
    .sta-stack {
        position: relative;
        height: 12px;
        width: 100%;
        background: rgba(255,255,255,0.05);
        border-radius: 999px;
        overflow: hidden;
        display: flex;
        border: 1px solid rgba(255,255,255,0.06);
    }
    .sta-stack-seg {
        height: 100%;
        transition: width .9s cubic-bezier(.25,.8,.25,1);
    }
    .sta-stack-seg.is-approved { background: linear-gradient(90deg, #10b981, #34d399); }
    .sta-stack-seg.is-pending  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .sta-stack-seg.is-blocked  { background: linear-gradient(90deg, #f43f5e, #fb7185); }
    .sta-stack-seg.is-remaining{ background: transparent; }

    .sta-legend {
        display: flex; flex-wrap: wrap; gap: 12px;
        font-size: 11px;
        color: var(--prism-text-3);
        margin-top: 8px;
    }
    .sta-legend-dot {
        display: inline-block;
        width: 9px; height: 9px;
        border-radius: 999px;
        margin-inline-end: 6px;
        vertical-align: middle;
    }
    .sta-legend-dot.is-approved { background: #34d399; box-shadow: 0 0 8px rgba(52,211,153,0.5); }
    .sta-legend-dot.is-pending  { background: #fbbf24; box-shadow: 0 0 8px rgba(251,191,36,0.5); }
    .sta-legend-dot.is-blocked  { background: #fb7185; box-shadow: 0 0 8px rgba(244,63,94,0.5); }
    .sta-legend-dot.is-remaining{ background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.45); }

    /* Section breakdown card (hall / balcony) — two tinted blocks that
       sit side-by-side on desktop and stack on mobile. */
    .sta-section-card {
        border-radius: 16px;
        padding: 14px 16px;
        border: 1px solid var(--prism-border, rgba(255,255,255,0.10));
        background: rgba(255,255,255,0.03);
        display: flex; flex-direction: column; gap: 10px;
        min-width: 0;
    }
    .sta-section-card.is-hall    { background: linear-gradient(180deg, rgba(251,191,36,0.10), rgba(251,191,36,0.02)); border-color: rgba(251,191,36,0.30); }
    .sta-section-card.is-balcony { background: linear-gradient(180deg, rgba(192,132,252,0.10), rgba(192,132,252,0.02)); border-color: rgba(192,132,252,0.30); }

    .sta-section-title {
        display: flex; align-items: center; justify-content: space-between;
        font-size: 12px; font-weight: 700;
        letter-spacing: 0.10em;
        text-transform: uppercase;
    }
    .sta-section-card.is-hall .sta-section-title { color: #fcd34d; }
    .sta-section-card.is-balcony .sta-section-title { color: #d8b4fe; }

    .sta-section-rows { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 14px; }
    @media (max-width: 360px) {
        .sta-section-rows { grid-template-columns: 1fr; }
    }
    .sta-section-row-label {
        font-size: 10.5px;
        color: var(--prism-text-3);
        letter-spacing: 0.06em;
    }
    .sta-section-row-value {
        font-family: "Space Grotesk", system-ui, sans-serif;
        font-weight: 700;
        font-size: 15px;
        color: var(--prism-text);
        line-height: 1.1;
    }

    /* Revenue split — approved vs pending side-by-side tiles. */
    .sta-rev-split { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .sta-rev-tile {
        border-radius: 14px;
        padding: 12px 14px;
        border: 1px solid rgba(255,255,255,0.10);
        background: rgba(255,255,255,0.03);
    }
    .sta-rev-tile.is-approved { border-color: rgba(52,211,153,0.32); background: linear-gradient(180deg, rgba(16,185,129,0.10), rgba(16,185,129,0.02)); }
    .sta-rev-tile.is-pending  { border-color: rgba(251,191,36,0.32);  background: linear-gradient(180deg, rgba(251,191,36,0.10), rgba(251,191,36,0.02)); }
    .sta-rev-label {
        font-size: 10px; font-weight: 600;
        letter-spacing: 0.14em;
        color: var(--prism-text-3);
        text-transform: uppercase;
    }
    .sta-rev-value {
        margin-top: 6px;
        font-family: "Space Grotesk", system-ui, sans-serif;
        font-weight: 700;
        font-size: clamp(18px, 4vw, 22px);
        line-height: 1.1;
    }
    .sta-rev-tile.is-approved .sta-rev-value { color: var(--prism-emerald); }
    .sta-rev-tile.is-pending  .sta-rev-value { color: var(--prism-gold); }
    .sta-rev-sub {
        margin-top: 4px;
        font-size: 11px;
        color: var(--prism-text-3);
    }

    /* Advanced metrics expandable. Uses native <details> so it Just
       Works without JS, then we restyle the marker for the dark theme. */
    .sta-advanced { border-top: 1px dashed var(--prism-border, rgba(255,255,255,0.10)); padding-top: 14px; margin-top: 4px; }
    .sta-advanced summary {
        cursor: pointer;
        list-style: none;
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px;
        font-size: 12px;
        font-weight: 600;
        color: var(--prism-text-2);
        letter-spacing: 0.06em;
        user-select: none;
    }
    .sta-advanced summary::-webkit-details-marker { display: none; }
    .sta-advanced summary::after {
        content: "▾";
        font-size: 10px;
        color: var(--prism-text-3);
        transition: transform .25s ease;
    }
    .sta-advanced[open] summary::after { transform: rotate(180deg); }
    .sta-advanced-grid {
        margin-top: 12px;
        display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px;
    }
    @media (min-width: 640px) {
        .sta-advanced-grid { grid-template-columns: repeat(4, minmax(0,1fr)); }
    }

    /* Card header chip showing the live status of the showtime. */
    .sta-status-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid;
    }
    .sta-status-chip.is-live { background: rgba(16,185,129,0.10); color: #6ee7b7; border-color: rgba(52,211,153,0.40); }
    .sta-status-chip.is-soldout { background: rgba(244,63,94,0.10); color: #fda4af; border-color: rgba(251,113,133,0.40); }
    .sta-status-chip .sta-status-dot {
        width: 7px; height: 7px; border-radius: 999px;
        background: currentColor;
        box-shadow: 0 0 8px currentColor;
    }
    .sta-status-chip.is-live .sta-status-dot { animation: staPulse 1.8s ease-in-out infinite; }
    @keyframes staPulse {
        0%, 100% { opacity: 1; }
        50%      { opacity: .35; }
    }

    /* Make ALL animations respect user motion prefs (a11y). */
    @media (prefers-reduced-motion: reduce) {
        .sta-ring-fill, .sta-stack-seg { transition: none; }
        .sta-status-chip.is-live .sta-status-dot { animation: none; }
    }

    /* Mobile-first layout: card spacing tightens slightly on phones so
       admins can scan more rows per scroll. */
    @media (max-width: 640px) {
        .sta-ring-wrap { width: 96px; height: 96px; }
        .sta-ring-percent { font-size: 22px; }
        .sta-rev-value { font-size: 18px; }
    }
</style>

<section class="space-y-5">

    {{-- ============================================
         HEADER
         ============================================ --}}
    <div class="prism-glass prism-glow-border p-5 prism-fade-up flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="space-y-1 min-w-0">
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
            <p class="text-xs text-[color:var(--prism-text-3)] truncate">{{ $show->title }}</p>
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

    {{-- Success flash --}}
    @if(session('status'))
        <div class="pt-alert pt-alert-success prism-fade-up">{{ session('status') }}</div>
    @endif

    @if($times->isEmpty())
        <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)] prism-fade-up"
             data-i18n="adm_times_empty">
            لا توجد مواعيد لهذا العرض حتى الآن.
        </div>
    @else

        {{-- ============================================
             TOP KPI STRIP — totals across all showtimes
             ============================================ --}}
        <div class="prism-fade-up grid grid-cols-2 lg:grid-cols-4 gap-3">

            <div class="prism-stat is-primary">
                <span class="prism-stat-label" data-i18n="adm_sta_kpi_showtimes">المواعيد</span>
                <span class="prism-stat-value">{{ $fmt($totals['count']) }}</span>
                <span class="prism-stat-caption" data-i18n="adm_sta_kpi_showtimes_sub">إجمالي المواعيد المتاحة</span>
            </div>

            <div class="prism-stat is-attention">
                <span class="prism-stat-label" data-i18n="adm_sta_kpi_occupancy">نسبة الإشغال</span>
                <span class="prism-stat-value">{{ rtrim(rtrim(number_format($totals['occupancy_percent'], 1), '0'), '.') }}%</span>
                <span class="prism-stat-caption">
                    {{ $fmt($totals['sold']) }} / {{ $fmt($totals['capacity']) }}
                    <span data-i18n="adm_sta_kpi_tickets_word">تذكرة</span>
                </span>
            </div>

            <div class="prism-stat is-positive">
                <span class="prism-stat-label" data-i18n="adm_sta_kpi_revenue">الإيراد المؤكد</span>
                <span class="prism-stat-value">{{ $fmt($totals['approved_revenue']) }}</span>
                <span class="prism-stat-caption">
                    <span data-i18n="common_currency_short">ج</span>
                    · {{ $fmt($totals['approved_bookings']) }}
                    <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                </span>
            </div>

            <div class="prism-stat">
                <span class="prism-stat-label" data-i18n="adm_sta_kpi_savings">إجمالي الخصومات</span>
                <span class="prism-stat-value" style="color: var(--prism-gold);">{{ $fmt($totals['total_discount']) }}</span>
                <span class="prism-stat-caption">
                    <span data-i18n="common_currency_short">ج</span>
                    · <span data-i18n="adm_sta_kpi_savings_sub">قيمة الخصومات المُطبَّقة</span>
                </span>
            </div>

        </div>

        {{-- ============================================
             SHOWTIME CARDS
             ============================================ --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 prism-stagger">

        @foreach($times as $time)
            @php
                $a = $analytics[$time->id] ?? [];

                $occupancyPct = $a['occupancy_percent'] ?? 0;
                $ringOffset   = $ringCircumference * (1 - min(100, $occupancyPct) / 100);

                $approvedPct  = $a['sold_percent']     ?? 0;
                $pendingPct   = $a['pending_percent']  ?? 0;
                $blockedPct   = $a['blocked_percent']  ?? 0;
                $remainingPct = max(0, 100 - $approvedPct - $pendingPct - $blockedPct);

                $isLocked  = ($a['remaining'] ?? 0) <= 0;
                $isSoldOut = $time->is_sold_out || $isLocked;
            @endphp

            <article class="prism-glass prism-glow-border p-5 sm:p-6 space-y-5 prism-fade-up">

                {{-- ── header ────────────────────────────────────── --}}
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="space-y-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="prism-pill prism-pill-neon" style="font-size:11px;">
                                <span class="prism-dot prism-dot-emerald"></span>
                                {{ $time->date->format('d/m/Y') }}
                            </span>
                            <span class="prism-pill prism-pill-amber" style="font-size:11px;">
                                🕔 {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-[color:var(--prism-text-3)]">
                            @if ($usesSectionPricing)
                                <span data-i18n="adm_sta_price_split">صالة / بلكون</span>:
                                <span style="color: var(--prism-gold);">{{ $sectionPriceLabel }}</span>
                            @else
                                <span data-i18n="adm_times_col_price">السعر</span>:
                                <span style="color: var(--prism-gold);">{{ $fmt($a['ticket_price'] ?? 0) }} <span data-i18n="common_currency_short">ج</span></span>
                            @endif
                        </p>
                    </div>

                    <span class="sta-status-chip {{ $isSoldOut ? 'is-soldout' : 'is-live' }}">
                        <span class="sta-status-dot"></span>
                        <span data-i18n="{{ $isSoldOut ? 'adm_status_sold_out' : 'adm_status_available' }}">
                            {{ $isSoldOut ? 'Sold Out' : 'متاح' }}
                        </span>
                    </span>
                </div>

                {{-- ── occupancy ring + 4-tile breakdown ─────────── --}}
                <div class="flex items-center gap-4 sm:gap-5 flex-wrap">

                    {{-- ring --}}
                    <div class="sta-ring-wrap" aria-hidden="true">
                        <svg viewBox="0 0 100 100">
                            <circle class="sta-ring-track" cx="50" cy="50" r="{{ $ringRadius }}"></circle>
                            <circle class="sta-ring-fill"
                                    cx="50" cy="50" r="{{ $ringRadius }}"
                                    stroke-dasharray="{{ $ringCircumference }}"
                                    stroke-dashoffset="{{ $ringOffset }}"></circle>
                        </svg>
                        <div class="sta-ring-center">
                            <span class="sta-ring-percent">{{ (int) round($occupancyPct) }}%</span>
                            <span class="sta-ring-caption" data-i18n="adm_sta_ring_caption">إشغال</span>
                        </div>
                    </div>

                    {{-- 4-tile breakdown --}}
                    <div class="grid grid-cols-2 gap-2 flex-1 min-w-[200px]">
                        <div class="pt-mini-card pt-mini-card-emerald">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_approved">معتمد</div>
                            <div class="pt-mini-card-value">{{ $fmt($a['approved_tickets'] ?? 0) }}</div>
                        </div>
                        <div class="pt-mini-card pt-mini-card-gold">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_pending">قيد المراجعة</div>
                            <div class="pt-mini-card-value">{{ $fmt($a['pending_tickets'] ?? 0) }}</div>
                        </div>
                        <div class="pt-mini-card" style="border-color: rgba(251,113,133,0.32); background: rgba(244,63,94,0.06);">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_blocked">محجوب</div>
                            <div class="pt-mini-card-value" style="color: #fda4af;">{{ $fmt($a['blocked'] ?? 0) }}</div>
                        </div>
                        <div class="pt-mini-card">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_remaining">المتبقي</div>
                            <div class="pt-mini-card-value" style="color: var(--prism-text);">
                                {{ $fmt($a['remaining'] ?? 0) }}
                                <span class="text-[10px] opacity-50">/ {{ $fmt($a['capacity'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── stacked progress bar (sold vs remaining viz) ── --}}
                <div>
                    <div class="sta-stack" role="img"
                         aria-label="Approved {{ $approvedPct }}%, pending {{ $pendingPct }}%, blocked {{ $blockedPct }}%, remaining {{ $remainingPct }}%">
                        <div class="sta-stack-seg is-approved"  style="width: {{ $approvedPct }}%;"></div>
                        <div class="sta-stack-seg is-pending"   style="width: {{ $pendingPct }}%;"></div>
                        <div class="sta-stack-seg is-blocked"   style="width: {{ $blockedPct }}%;"></div>
                        <div class="sta-stack-seg is-remaining" style="width: {{ $remainingPct }}%;"></div>
                    </div>
                    <div class="sta-legend">
                        <span><span class="sta-legend-dot is-approved"></span><span data-i18n="adm_sta_approved">معتمد</span></span>
                        <span><span class="sta-legend-dot is-pending"></span><span data-i18n="adm_sta_pending">قيد المراجعة</span></span>
                        @if(($a['blocked'] ?? 0) > 0)
                            <span><span class="sta-legend-dot is-blocked"></span><span data-i18n="adm_sta_blocked">محجوب</span></span>
                        @endif
                        <span><span class="sta-legend-dot is-remaining"></span><span data-i18n="adm_sta_remaining">المتبقي</span></span>
                    </div>
                </div>

                {{-- ── revenue split ────────────────────────────── --}}
                <div class="sta-rev-split">
                    <div class="sta-rev-tile is-approved">
                        <div class="sta-rev-label" data-i18n="adm_sta_rev_approved">إيراد مؤكد</div>
                        <div class="sta-rev-value">
                            {{ $fmt($a['approved_revenue'] ?? 0) }}
                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                        </div>
                        <div class="sta-rev-sub">
                            {{ $fmt($a['approved_bookings'] ?? 0) }}
                            <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                            · <span data-i18n="adm_sta_avg_short">متوسط</span>
                            {{ $fmt($a['average_booking_value'] ?? 0) }}
                            <span data-i18n="common_currency_short">ج</span>
                        </div>
                    </div>

                    <div class="sta-rev-tile is-pending">
                        <div class="sta-rev-label" data-i18n="adm_sta_rev_pending">إيراد معلَّق</div>
                        <div class="sta-rev-value">
                            {{ $fmt($a['pending_revenue'] ?? 0) }}
                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                        </div>
                        <div class="sta-rev-sub">
                            {{ $fmt($a['pending_bookings'] ?? 0) }}
                            <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                            · <span data-i18n="adm_sta_conv_short">تحويل</span>
                            {{ rtrim(rtrim(number_format($a['conversion_percent'] ?? 0, 1), '0'), '.') }}%
                        </div>
                    </div>
                </div>

                {{-- ── hall / balcony breakdown (Anba only) ─────── --}}
                @if($usesSectionPricing && (($a['hall'] ?? null) || ($a['balcony'] ?? null)))
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        @php $h = $a['hall'] ?? null; @endphp
                        @if($h)
                            <div class="sta-section-card is-hall">
                                <div class="sta-section-title">
                                    <span><span data-i18n="adm_sta_section_hall">صالة</span></span>
                                    <span class="text-[10px] opacity-80">{{ $fmt($a['hall_price']) }} <span data-i18n="common_currency_short">ج</span></span>
                                </div>
                                <div class="sta-section-rows">
                                    <div>
                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_sold">تذاكر مُباعة</div>
                                        <div class="sta-section-row-value">{{ $fmt($h['tickets_sold']) }}</div>
                                    </div>
                                    <div>
                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_list">قيمة قائمة الأسعار</div>
                                        <div class="sta-section-row-value">{{ $fmt($h['list_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span></div>
                                    </div>
                                    <div>
                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_final">صافي الإيراد</div>
                                        <div class="sta-section-row-value" style="color: var(--prism-emerald);">
                                            {{ $fmt($h['final_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_savings">الخصومات</div>
                                        <div class="sta-section-row-value" style="color: var(--prism-gold);">
                                            {{ $fmt($h['discount_amount']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @php $b = $a['balcony'] ?? null; @endphp
                        @if($b)
                            <div class="sta-section-card is-balcony">
                                <div class="sta-section-title">
                                    <span><span data-i18n="adm_sta_section_balcony">بلكون</span></span>
                                    <span class="text-[10px] opacity-80">{{ $fmt($a['balcony_price']) }} <span data-i18n="common_currency_short">ج</span></span>
                                </div>
                                <div class="sta-section-rows">
                                    <div>
                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_sold">تذاكر مُباعة</div>
                                        <div class="sta-section-row-value">{{ $fmt($b['tickets_sold']) }}</div>
                                    </div>
                                    <div>
                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_list">قيمة قائمة الأسعار</div>
                                        <div class="sta-section-row-value">{{ $fmt($b['list_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span></div>
                                    </div>
                                    <div>
                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_final">صافي الإيراد</div>
                                        <div class="sta-section-row-value" style="color: var(--prism-emerald);">
                                            {{ $fmt($b['final_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_savings">الخصومات</div>
                                        <div class="sta-section-row-value" style="color: var(--prism-gold);">
                                            {{ $fmt($b['discount_amount']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                @endif

                {{-- ── advanced expandable ──────────────────────── --}}
                <details class="sta-advanced">
                    <summary>
                        <span data-i18n="adm_sta_advanced_toggle">تحليلات متقدمة</span>
                    </summary>
                    <div class="sta-advanced-grid">
                        <div class="pt-mini-card">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_adv_discount">الخصومات المُطبَّقة</div>
                            <div class="pt-mini-card-value" style="color: var(--prism-gold);">
                                {{ $fmt($a['total_discount'] ?? 0) }}
                                <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                            </div>
                        </div>
                        <div class="pt-mini-card">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_adv_discounted_count">عدد الحجوزات المخصومة</div>
                            <div class="pt-mini-card-value">{{ $fmt($a['discounted_bookings'] ?? 0) }}</div>
                        </div>
                        <div class="pt-mini-card">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_adv_avg">متوسط قيمة الحجز</div>
                            <div class="pt-mini-card-value">
                                {{ $fmt($a['average_booking_value'] ?? 0) }}
                                <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                            </div>
                        </div>
                        <div class="pt-mini-card">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_adv_conv">نسبة الموافقة</div>
                            <div class="pt-mini-card-value" style="color: var(--prism-cyan);">
                                {{ rtrim(rtrim(number_format($a['conversion_percent'] ?? 0, 1), '0'), '.') }}%
                            </div>
                        </div>
                        <div class="pt-mini-card pt-mini-card-emerald">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_approved">حجوزات معتمدة</div>
                            <div class="pt-mini-card-value">{{ $fmt($a['approved_bookings'] ?? 0) }}</div>
                        </div>
                        <div class="pt-mini-card pt-mini-card-gold">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_pending">حجوزات معلَّقة</div>
                            <div class="pt-mini-card-value">{{ $fmt($a['pending_bookings'] ?? 0) }}</div>
                        </div>
                        <div class="pt-mini-card" style="border-color: rgba(251,113,133,0.32); background: rgba(244,63,94,0.06);">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_rejected">حجوزات مرفوضة</div>
                            <div class="pt-mini-card-value" style="color: #fda4af;">{{ $fmt($a['rejected_bookings'] ?? 0) }}</div>
                        </div>
                        <div class="pt-mini-card">
                            <div class="pt-mini-card-label" data-i18n="adm_sta_adv_total_rev">الإيراد الكلي</div>
                            <div class="pt-mini-card-value">
                                {{ $fmt($a['total_revenue'] ?? 0) }}
                                <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                            </div>
                        </div>
                    </div>
                </details>

                {{-- ── actions row ──────────────────────────────── --}}
                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-[var(--prism-border,rgba(255,255,255,0.08))]" style="padding-top: 14px;">

                    {{-- Sold-out toggle, identical contract to the old page --}}
                    <form action="{{ route('admin.shows.times.toggle', [$show, $time]) }}" method="POST" class="flex-1 min-w-[140px]">
                        @csrf
                        @method('PATCH')
                        <label class="cursor-pointer block w-full">
                            <input type="checkbox" class="sr-only peer"
                                   onchange="this.form.submit()"
                                   {{ $isSoldOut ? 'checked' : '' }}
                                   {{ $isLocked ? 'disabled' : '' }}>

                            <div class="relative flex items-center justify-between w-full h-9 px-2 rounded-full transition-all duration-300"
                                 style="
                                    background: {{ $isSoldOut ? 'rgba(244,63,94,0.18)' : 'rgba(16,185,129,0.12)' }};
                                    border: 1px solid {{ $isSoldOut ? 'rgba(251,113,133,0.45)' : 'rgba(52,211,153,0.45)' }};
                                    box-shadow: {{ $isSoldOut ? '0 0 14px rgba(244,63,94,0.25)' : '0 0 14px rgba(52,211,153,0.25)' }};
                                    opacity: {{ $isLocked ? '0.6' : '1' }};
                                    cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }};">
                                <div class="absolute top-1 w-7 h-7 bg-white rounded-full shadow-md transition-all duration-300
                                            {{ $isSoldOut ? 'left-1' : 'left-[calc(100%-2rem)]' }}"></div>
                                <span class="text-[11px] w-full text-center font-medium z-10"
                                      data-i18n="{{ $isSoldOut ? 'adm_status_sold_out' : 'adm_status_available' }}"
                                      style="color: {{ $isSoldOut ? '#fda4af' : '#6ee7b7' }};">
                                    {{ $isSoldOut ? 'Sold Out' : 'متاح' }}
                                </span>
                            </div>
                        </label>
                    </form>

                    @if($show->theater_type === \App\Models\Show::THEATER_ANBA_RUWEIS)
                        <a href="{{ route('admin.show-times.seats.index', $time) }}"
                           class="pt-action-pill pt-action-pill-gold"
                           data-i18n="adm_seats">المقاعد</a>
                    @endif

                    <a href="{{ route('admin.show-times.manifest', $time) }}"
                       class="pt-action-pill pt-action-pill-cyan"
                       title="Seat occupancy / attendee manifest">📋 <span data-i18n="adm_sta_manifest">المانيفست</span></a>

                    <a href="{{ route('admin.shows.times.edit', [$show, $time]) }}"
                       class="pt-action-pill"
                       data-i18n="adm_edit">تعديل</a>

                    <form action="{{ route('admin.shows.times.destroy', [$show, $time]) }}" method="POST"
                          onsubmit="return confirm((window.PT && window.PT.lang() === 'en') ? 'Are you sure you want to delete this show time?' : 'متأكد إنك عايز تحذف الموعد؟');">
                        @csrf
                        @method('DELETE')
                        <button class="pt-action-pill" style="color: #fda4af; border-color: rgba(251,113,133,0.40);"
                                data-i18n="adm_delete">حذف</button>
                    </form>
                </div>

            </article>
        @endforeach

        </div>

    @endif

</section>
@endsection
