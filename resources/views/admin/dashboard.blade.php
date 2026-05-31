@extends('layouts.app')

@section('title', 'لوحة تحكم الأدمن')

@php
    use App\Models\Show as ShowModel;

    // Helpers for the analytics dashboard. Data is already pre-computed
    // by the controller via ShowTimeAnalytics — these only handle
    // presentation (formatting + ring geometry).
    $stafmt = fn ($n) => number_format((int) $n);
    $staPct = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.') . '%';

    // Single source of truth for the occupancy ring geometry so every
    // ring on the page is the same size.
    $staRingRadius = 42;
    $staRingCircumference = 2 * M_PI * $staRingRadius;
@endphp

@section('content')

{{-- Shared SVG gradient hoisted out of every card so the gradient ID
     stays unique per document (HTML rule) and the definition is shipped
     once per page instead of once per showtime. --}}
<svg width="0" height="0" style="position:absolute;" aria-hidden="true">
    <defs>
        <linearGradient id="staRingGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%"  stop-color="#34d399"/>
            <stop offset="55%" stop-color="#22d3ee"/>
            <stop offset="100%" stop-color="#a78bfa"/>
        </linearGradient>
    </defs>
</svg>

{{-- Scoped CSS for the analytics dashboard. All classes are namespaced
     under `.sta-` so they cannot collide with the operational Show Times
     page or any other PRISM card surface. Only the occupancy ring + the
     stacked-progress bar need custom rules — everything else reuses the
     existing PRISM tokens from layouts/app.blade.php. --}}
<style>
    .sta-ring-wrap {
        position: relative;
        width: 116px; height: 116px;
        flex: 0 0 auto;
    }
    .sta-ring-wrap svg {
        width: 100%; height: 100%;
        transform: rotate(-90deg);
    }
    .sta-ring-track { fill: none; stroke: rgba(255,255,255,0.08); stroke-width: 10; }
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
        text-align: center; line-height: 1;
    }
    .sta-ring-percent {
        font-family: "Space Grotesk", system-ui, sans-serif;
        font-weight: 700; font-size: 26px;
        color: var(--prism-text);
        letter-spacing: -0.02em;
    }
    .sta-ring-caption {
        margin-top: 4px;
        font-size: 10px; font-weight: 600;
        letter-spacing: 0.14em;
        color: var(--prism-text-3);
        text-transform: uppercase;
    }

    /* Stacked progress bar — approved | pending | blocked | remaining */
    .sta-stack {
        position: relative;
        height: 12px; width: 100%;
        background: rgba(255,255,255,0.05);
        border-radius: 999px; overflow: hidden;
        display: flex;
        border: 1px solid rgba(255,255,255,0.06);
    }
    .sta-stack-seg { height: 100%; transition: width .9s cubic-bezier(.25,.8,.25,1); }
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

    /* Hall / Balcony breakdown cards (Anba-priced shows only). */
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
    @media (max-width: 360px) { .sta-section-rows { grid-template-columns: 1fr; } }
    .sta-section-row-label {
        font-size: 10.5px;
        color: var(--prism-text-3);
        letter-spacing: 0.06em;
    }
    .sta-section-row-value {
        font-family: "Space Grotesk", system-ui, sans-serif;
        font-weight: 700; font-size: 15px;
        color: var(--prism-text);
        line-height: 1.1;
    }

    /* Revenue split — approved vs pending tiles. */
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

    /* Advanced metrics expandable. Native <details> so it works without JS. */
    .sta-advanced { border-top: 1px dashed var(--prism-border, rgba(255,255,255,0.10)); padding-top: 14px; margin-top: 4px; }
    .sta-advanced summary {
        cursor: pointer;
        list-style: none;
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px;
        font-size: 12px; font-weight: 600;
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

    /* Live availability chip. */
    .sta-status-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px; font-weight: 600;
        border: 1px solid;
    }
    .sta-status-chip.is-live    { background: rgba(16,185,129,0.10); color: #6ee7b7; border-color: rgba(52,211,153,0.40); }
    .sta-status-chip.is-soldout { background: rgba(244,63,94,0.10);  color: #fda4af; border-color: rgba(251,113,133,0.40); }
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

    /* Accordion Showtime Card Layout */
    .sta-card {
        border-radius: var(--prism-radius);
        overflow: hidden;
        transition: border-color 0.3s var(--prism-ease), box-shadow 0.3s var(--prism-ease);
        display: flex;
        flex-direction: column;
    }
    .sta-card.is-expanded {
        border-color: var(--prism-border-neon);
        box-shadow: 0 0 0 1px rgba(129,140,248,0.18), 0 18px 48px -20px rgba(34,211,238,0.35);
    }
    .sta-card-compact {
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.015);
        cursor: pointer;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
        transition: background 0.2s var(--prism-ease);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .sta-card-compact:hover { background: rgba(255, 255, 255, 0.04); }
    .sta-card-compact:active { background: rgba(255, 255, 255, 0.06); }
    /* Accent rail that lights up while the card is open. */
    .sta-card.is-expanded .sta-card-compact {
        background: linear-gradient(180deg, rgba(34,211,238,0.06), rgba(255,255,255,0.015));
        box-shadow: inset 3px 0 0 0 var(--prism-cyan);
    }
    .sta-card-details {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.35s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 0.25s ease-out;
    }
    .sta-card.is-expanded .sta-card-details {
        opacity: 1;
    }

    /* Compact KPI chips — scannable "ticker" row that replaces the heavy
       bordered KPI boxes. Each chip = glowing dot + bold value + tiny label. */
    .sta-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .sta-chip {
        display: inline-flex;
        align-items: baseline;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,0.035);
        border: 1px solid rgba(255,255,255,0.07);
        line-height: 1;
        white-space: nowrap;
        min-width: 0;
    }
    .sta-chip-dot {
        align-self: center;
        width: 7px; height: 7px;
        border-radius: 999px;
        flex: 0 0 auto;
        background: rgba(255,255,255,0.4);
    }
    .sta-chip-val {
        font-family: "Space Grotesk", system-ui, sans-serif;
        font-weight: 700;
        font-size: 13px;
        letter-spacing: -0.01em;
        color: var(--prism-text);
    }
    .sta-chip-unit { font-size: 10px; font-weight: 600; opacity: 0.7; }
    .sta-chip-label {
        font-size: 10px; font-weight: 600;
        letter-spacing: 0.04em;
        color: var(--prism-text-3);
    }
    .sta-chip.is-occupancy { border-color: rgba(34,211,238,0.30); background: rgba(34,211,238,0.07); }
    .sta-chip.is-occupancy .sta-chip-dot { background: var(--prism-cyan); box-shadow: 0 0 8px rgba(34,211,238,0.6); }
    .sta-chip.is-occupancy .sta-chip-val { color: var(--prism-cyan); }
    .sta-chip.is-revenue { border-color: rgba(52,211,153,0.30); background: rgba(16,185,129,0.07); }
    .sta-chip.is-revenue .sta-chip-dot { background: var(--prism-emerald); box-shadow: 0 0 8px rgba(52,211,153,0.6); }
    .sta-chip.is-revenue .sta-chip-val { color: var(--prism-emerald); }
    .sta-chip.is-remaining .sta-chip-dot { background: rgba(255,255,255,0.45); }
    .sta-chip.is-approved { border-color: rgba(52,211,153,0.18); }
    .sta-chip.is-approved .sta-chip-dot { background: #6ee7b7; box-shadow: 0 0 7px rgba(110,231,183,0.45); }

    /* Expand affordance row. */
    .sta-expand-row {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        font-size: 11px; font-weight: 700;
        color: var(--prism-text-3);
        letter-spacing: 0.04em;
        transition: color 0.2s var(--prism-ease);
    }
    .sta-card-compact:hover .sta-expand-row { color: var(--prism-text-2); }
    .sta-card.is-expanded .sta-expand-row { color: var(--prism-cyan); }
    .sta-expand-arrow { font-size: 9px; transition: transform 0.3s var(--prism-ease); }
    .sta-card.is-expanded .sta-expand-arrow { transform: rotate(180deg); }

    /* Optimized smaller chart sizing */
    .sta-ring-wrap.is-mini { width: 80px; height: 80px; }
    .sta-ring-wrap.is-mini .sta-ring-percent { font-size: 20px; }
    .sta-ring-wrap.is-mini .sta-ring-caption { font-size: 9px; margin-top: 2px; }
    .sta-stack.is-mini { height: 8px; }

    @media (prefers-reduced-motion: reduce) {
        .sta-ring-fill, .sta-stack-seg { transition: none; }
        .sta-status-chip.is-live .sta-status-dot { animation: none; }
        .sta-card-details, .sta-card { transition: none; }
    }

    @media (max-width: 640px) {
        .sta-ring-wrap { width: 96px; height: 96px; }
        .sta-ring-percent { font-size: 22px; }
        .sta-rev-value { font-size: 18px; }
        .sta-card-compact { padding: 14px 16px; }
    }
</style>

    <section class="space-y-7">

        {{-- ============================ HERO ============================ --}}
        <div class="prism-glass prism-glow-border p-5 sm:p-6 prism-fade-up
                    flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="prism-pill prism-pill-neon">
                        <span class="prism-dot prism-dot-emerald"></span>
                        <span data-i18n="adm_console_pill">Admin Console</span>
                    </span>
                    <span class="prism-eyebrow" data-i18n="adm_console_eyebrow">JOSEPH NABIL · CONTROL</span>
                </div>
                <h1 class="prism-headline text-xl sm:text-2xl">
                    <span data-i18n="adm_dashboard_title"
                          style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        لوحة تحكم الأدمن
                    </span>
                </h1>
                <p class="text-sm text-[color:var(--prism-text-2)] max-w-xl"
                   data-i18n="adm_dashboard_lede">
                    من هنا تقدر تتابع نبض العروض، الحجوزات، والتذاكر اللي طلعت للجمهور.
                </p>
            </div>

            @if(session('status'))
                <div class="prism-pill prism-pill-emerald self-start sm:self-auto">
                    <span class="prism-dot prism-dot-emerald"></span>
                    {{ session('status') }}
                </div>
            @endif
        </div>

        {{-- ============================ PRIMARY KPI + ATTENTION ============================ --}}
        {{-- Two cards on desktop: revenue (primary, gold-accented, takes 2 columns)
             and pending review (cyan attention card, deep-link to bookings list with
             status filter). Strong hierarchy: revenue dominates, pending sits beside it. --}}
        <div class="grid md:grid-cols-3 gap-3 prism-stagger pt-reveal pt-reveal-stagger">

            <div class="prism-stat is-primary md:col-span-2 prism-fade-up">
                <span class="prism-stat-label" data-i18n="adm_kpi_revenue_label">إجمالي الإيرادات المعتمدة</span>
                <span class="prism-stat-value">
                    {{ number_format($totalRevenue, 0) }}
                    <span class="text-base font-semibold opacity-80 tracking-normal" data-i18n="common_egp">جنيه</span>
                </span>
                <span class="prism-stat-caption" data-i18n-html="adm_kpi_revenue_caption">
                    محسوبة من الحجوزات اللي حالتها
                    <span style="color: var(--prism-emerald);">approved</span>
                    فقط — قيد المراجعة والمرفوضة لا تُحتسب.
                </span>
            </div>

            <a href="{{ route('admin.bookings.index') }}"
               class="prism-stat is-attention prism-fade-up"
               style="text-decoration: none;">
                <div class="flex items-center justify-between">
                    <span class="prism-stat-label" style="color: var(--prism-cyan);">
                        <span class="prism-dot prism-dot-sky" style="width:6px;height:6px;"></span>
                        <span data-i18n="adm_kpi_pending">قيد المراجعة</span>
                    </span>
                    @if($pendingBookings > 0)
                        <span class="prism-pill prism-pill-sky" style="font-size:10px;" data-i18n="adm_kpi_pending_pill">يحتاج مراجعة</span>
                    @endif
                </div>
                <span class="prism-stat-value">{{ $pendingBookings }}</span>
                <span class="prism-stat-caption flex items-center justify-between gap-2">
                    <span data-i18n="adm_kpi_pending_caption">طلبات حجز محتاجة Screenshot والاعتماد.</span>
                    <span aria-hidden="true" class="prism-quick-action-arrow pt-arrow-rtl"
                          style="width:24px;height:24px;font-size:12px;">←</span>
                </span>
            </a>
        </div>

        {{-- ============================ SECONDARY STATS ============================ --}}
        <div>
            <div class="prism-section-head">
                <span class="prism-section-title" data-i18n="adm_overview_title">المؤشرات العامة</span>
                <span class="prism-eyebrow">OVERVIEW</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 prism-stagger pt-reveal pt-reveal-stagger">

                <div class="prism-stat prism-fade-up">
                    <span class="prism-stat-label" data-i18n="adm_kpi_shows">عدد العروض</span>
                    <span class="prism-stat-value" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">{{ $totalShows }}</span>
                    <span class="prism-stat-caption" data-i18n="adm_kpi_shows_caption">العروض المسرحية المسجَّلة على السيستم.</span>
                </div>

                <div class="prism-stat prism-fade-up">
                    <span class="prism-stat-label" data-i18n="adm_kpi_showtimes">مواعيد العروض</span>
                    <span class="prism-stat-value" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">{{ $totalShowTimes }}</span>
                    <span class="prism-stat-caption" data-i18n="adm_kpi_showtimes_caption">عدد المرات اللي العروض هتتقدَّم فيها على المسرح.</span>
                </div>

                <div class="prism-stat is-positive prism-fade-up">
                    <span class="prism-stat-label" data-i18n-html="adm_kpi_approved">التذاكر <span style="color: var(--prism-emerald);">approved</span></span>
                    <span class="prism-stat-value">{{ $totalTicketsApproved }}</span>
                    <span class="prism-stat-caption" data-i18n="adm_kpi_approved_caption">تذاكر لحجوزات اتأكدت واتقبلت، وطلع لها QR.</span>
                </div>

                <div class="prism-stat is-attention prism-fade-up">
                    <span class="prism-stat-label" data-i18n="adm_kpi_remaining">التذاكر المتبقية</span>
                    <span class="prism-stat-value">{{ $ticketsRemaining }}</span>
                    <span class="prism-stat-caption" data-i18n-html="adm_kpi_remaining_caption">
                        إجمالي التذاكر ناقص الحجوزات
                        <span style="color: var(--prism-emerald);">(pending + approved)</span>
                        ناقص المحجوب.
                    </span>
                </div>

                {{-- Blocked seats are operationally unavailable (reduce
                     remaining inventory and never show up in the seat
                     picker) but are NOT paid tickets — they never appear
                     in revenue or approved-ticket totals. --}}
                <div class="prism-stat prism-fade-up col-span-2 md:col-span-1"
                     style="border-color: rgba(244,63,94,0.32); background: linear-gradient(180deg, rgba(244,63,94,0.06), rgba(244,63,94,0.02));">
                    <span class="prism-stat-label" style="color: #fda4af;">
                        <span class="prism-dot" style="background: #fb7185; box-shadow: 0 0 8px rgba(251,113,133,.55);"></span>
                        <span data-i18n="adm_kpi_blocked">المقاعد المحجوبة</span>
                    </span>
                    <span class="prism-stat-value" style="color: #fda4af;">{{ $totalBlockedSeats }}</span>
                    <span class="prism-stat-caption" data-i18n="adm_kpi_blocked_caption">
                        مقاعد محجوزة إداريًا — مش متاحة للحجز ولا بتتحسب في الإيرادات.
                    </span>
                </div>
            </div>
        </div>

        {{-- ============================ MAIN CONTROLS ============================ --}}
        <div>
            <div class="prism-section-head">
                <span class="prism-section-title" data-i18n="adm_quick_title">الإجراءات السريعة</span>
                <span class="prism-eyebrow">QUICK ACTIONS</span>
            </div>

            <div class="grid md:grid-cols-3 gap-4 prism-stagger pt-reveal pt-reveal-stagger">

                <a href="{{ route('admin.shows.index') }}" class="prism-quick-action prism-fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl">🎭</div>
                        <span class="prism-quick-action-arrow pt-arrow-rtl" aria-hidden="true">←</span>
                    </div>
                    <span class="prism-eyebrow mb-1" data-i18n="adm_quick_shows_eyebrow">إدارة العروض</span>
                    <h2 class="text-base font-semibold mt-1 mb-1 text-[color:var(--prism-text)]" data-i18n="adm_quick_shows_title">العروض المسرحية</h2>
                    <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed" data-i18n="adm_quick_shows_body">
                        إضافة عروض جديدة، تعديل التفاصيل، رفع البوسترات، وتفعيل/إخفاء العروض من الموقع.
                    </p>
                </a>

                <a href="{{ route('admin.bookings.index') }}" class="prism-quick-action prism-fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl">💳</div>
                        <span class="prism-quick-action-arrow pt-arrow-rtl" aria-hidden="true">←</span>
                    </div>
                    <span class="prism-eyebrow mb-1" data-i18n="adm_quick_bookings_eyebrow">إدارة الحجوزات</span>
                    <h2 class="text-base font-semibold mt-1 mb-1 text-[color:var(--prism-text)]" data-i18n="adm_quick_bookings_title">الحجوزات والتحويلات</h2>
                    <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed" data-i18n="adm_quick_bookings_body">
                        مراجعة طلبات الحجز، التأكد من التحويلات، واعتماد التذاكر وإرسال الـ QR للحضور.
                    </p>
                </a>

                <a href="{{ route('admin.scanner') }}" class="prism-quick-action prism-fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl">📷</div>
                        <span class="prism-quick-action-arrow pt-arrow-rtl" aria-hidden="true">←</span>
                    </div>
                    <span class="prism-eyebrow mb-1" data-i18n="adm_quick_scanner_eyebrow">على الباب</span>
                    <h2 class="text-base font-semibold mt-1 mb-1 text-[color:var(--prism-text)]" data-i18n="adm_quick_scanner_title">وضع Scan تذاكر QR</h2>
                    <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed" data-i18n="adm_quick_scanner_body">
                        افتح من موبايل المسؤول على باب المسرح، وامسح كود كل تذكرة عشان تتأكد إن الحجز صالح.
                    </p>
                </a>
            </div>
        </div>

        {{-- ============================ ANALYTICS ============================ --}}
        {{-- Cross-show analytics dashboard. Each showtime renders as its
             own rich card (occupancy ring, sold vs remaining viz,
             approved vs pending revenue split, hall/balcony breakdown for
             Anba shows, expandable advanced metrics). All numbers come
             from $analytics / $analyticsTotals which are pre-computed by
             ShowTimeAnalytics in the controller. --}}
        <section class="space-y-4 pt-reveal">

            <div class="prism-section-head">
                <span class="prism-section-title" data-i18n="adm_analytics_title">تحليلات العروض</span>
                <span class="prism-eyebrow">SHOWTIME ANALYTICS</span>
            </div>

            @if($showTimesStats->isEmpty())
                <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)]"
                     data-i18n="adm_showtimes_empty">
                    لسه مفيش مواعيد متسجلة على السيستم.
                </div>
            @else

                {{-- Two supplementary analytics KPIs that don't already
                     live in the top-level KPI grid. Average occupancy %
                     is calculated as a capacity-weighted mean across all
                     showtimes, total discounts is the sum of applied
                     bulk-discount savings across approved bookings. --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 prism-fade-up">

                    <div class="prism-stat is-attention">
                        <span class="prism-stat-label" data-i18n="adm_sta_kpi_occupancy">متوسط الإشغال</span>
                        <span class="prism-stat-value">{{ $staPct($analyticsTotals['occupancy_percent'] ?? 0) }}</span>
                        <span class="prism-stat-caption">
                            {{ $stafmt($analyticsTotals['sold'] ?? 0) }} /
                            {{ $stafmt($analyticsTotals['capacity'] ?? 0) }}
                            <span data-i18n="adm_sta_kpi_tickets_word">تذكرة</span>
                        </span>
                    </div>

                    <div class="prism-stat">
                        <span class="prism-stat-label" data-i18n="adm_sta_kpi_savings">إجمالي الخصومات</span>
                        <span class="prism-stat-value" style="color: var(--prism-gold);">{{ $stafmt($analyticsTotals['total_discount'] ?? 0) }}</span>
                        <span class="prism-stat-caption">
                            <span data-i18n="common_currency_short">ج</span>
                            · <span data-i18n="adm_sta_kpi_savings_sub">قيمة الخصومات المُطبَّقة</span>
                        </span>
                    </div>

                    <div class="prism-stat is-positive">
                        <span class="prism-stat-label" data-i18n="adm_sta_rev_approved">إيراد مؤكد</span>
                        <span class="prism-stat-value">{{ $stafmt($analyticsTotals['approved_revenue'] ?? 0) }}</span>
                        <span class="prism-stat-caption">
                            <span data-i18n="common_currency_short">ج</span>
                            · {{ $stafmt($analyticsTotals['approved_bookings'] ?? 0) }}
                            <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                        </span>
                    </div>

                    <div class="prism-stat">
                        <span class="prism-stat-label" data-i18n="adm_sta_rev_pending">إيراد معلَّق</span>
                        <span class="prism-stat-value" style="color: var(--prism-gold);">{{ $stafmt($analyticsTotals['pending_revenue'] ?? 0) }}</span>
                        <span class="prism-stat-caption">
                            <span data-i18n="common_currency_short">ج</span>
                            · {{ $stafmt($analyticsTotals['pending_bookings'] ?? 0) }}
                            <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                        </span>
                    </div>

                </div>

                {{-- Discount-tier breakdown — one card per tier the
                     platform has actually issued, branded with the
                     "خصومات العيلة" / "خصومات الكنائس" family colours.
                     Tiers with zero approved bookings are skipped so the
                     strip stays focused on real activity. --}}
                @php
                    $tierBreakdown = $analyticsTotals['tier_breakdown'] ?? [];
                @endphp
                @if(!empty($tierBreakdown))
                    <div class="prism-fade-up mt-3" data-tier-breakdown-row>
                        <div class="flex items-center justify-between mb-2 px-1">
                            <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-[color:var(--prism-text-3)]"
                                  data-i18n="adm_sta_tier_breakdown_title">
                                توزيع الخصومات حسب الفئة
                            </span>
                            <span class="text-[10px] text-[color:var(--prism-text-3)] opacity-70">
                                🎁 خصومات العيلة · ⛪ خصومات الكنائس
                            </span>
                        </div>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
                            @foreach($tierBreakdown as $percent => $tier)
                                @php
                                    $isChurchTier = ($tier['family'] ?? '') === 'church';
                                    $tierColor    = $isChurchTier ? '#c4b5fd' : '#fde68a';
                                    $tierBorder   = $isChurchTier ? 'rgba(167,139,250,0.45)' : 'rgba(251,191,36,0.45)';
                                    $tierBg       = $isChurchTier
                                        ? 'linear-gradient(135deg, rgba(124,58,237,0.10), rgba(124,58,237,0.02))'
                                        : 'linear-gradient(135deg, rgba(251,191,36,0.10), rgba(251,191,36,0.02))';
                                @endphp
                                <div class="rounded-2xl p-3 flex flex-col gap-1.5"
                                     style="border: 1px solid {{ $tierBorder }}; background: {{ $tierBg }};">
                                    <div class="flex items-center gap-2 text-[11px] font-bold"
                                         style="color: {{ $tierColor }};">
                                        <span aria-hidden="true">{{ $tier['badge'] }}</span>
                                        <span dir="ltr">-{{ (int) $tier['percent'] }}%</span>
                                        <span class="opacity-80 truncate">·</span>
                                        <span class="opacity-90 truncate">{{ $tier['label'] }}</span>
                                    </div>
                                    <div class="flex items-baseline justify-between gap-2">
                                        <span class="text-[18px] font-extrabold"
                                              style="color: {{ $tierColor }};"
                                              dir="ltr">
                                            {{ $stafmt($tier['discount_amount']) }}
                                        </span>
                                        <span class="text-[10px] text-[color:var(--prism-text-3)]">
                                            <span data-i18n="common_currency_short">ج</span>
                                            <span data-i18n="adm_sta_tier_savings_word">وفّر</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 text-[10.5px] text-[color:var(--prism-text-3)]">
                                        <span>
                                            🎟 {{ $stafmt($tier['tickets']) }}
                                            <span data-i18n="adm_sta_tier_tickets_word">تذكرة</span>
                                        </span>
                                        <span>
                                            📦 {{ $stafmt($tier['bookings']) }}
                                            <span data-i18n="adm_sta_tier_bookings_word">حجز</span>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Per-showtime analytics cards --}}
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 prism-stagger">

                @foreach($showTimesStats as $time)
                    @php
                        $a = $analytics[$time->id] ?? [];

                        // Per-card section-pricing flag: shows can have
                        // different theater types, so each card decides
                        // independently whether to show the hall/balcony
                        // breakdown.
                        $cardUsesSection = $time->show && $time->show->theater_type === ShowModel::THEATER_ANBA_RUWEIS;
                        $cardSectionLabel = $cardUsesSection
                            ? ((int) ($time->show->hall_price ?? 0)) . ' / ' . ((int) ($time->show->balcony_price ?? 0))
                            : null;

                        $occupancyPct = $a['occupancy_percent'] ?? 0;
                        $ringOffset   = $staRingCircumference * (1 - min(100, $occupancyPct) / 100);

                        $approvedPct  = $a['sold_percent']     ?? 0;
                        $pendingPct   = $a['pending_percent']  ?? 0;
                        $blockedPct   = $a['blocked_percent']  ?? 0;
                        $remainingPct = max(0, 100 - $approvedPct - $pendingPct - $blockedPct);

                        $isLocked  = ($a['remaining'] ?? 0) <= 0;
                        $isSoldOut = $time->is_sold_out || $isLocked;
                    @endphp

                    <article class="sta-card prism-glass prism-glow-border prism-fade-up" data-showtime-id="{{ $time->id }}">
                        
                        {{-- ── 1. COMPACT HEADER (Always Visible, Clickable) ─── --}}
                        {{-- Whole block is one large touch target. Shows only the
                             scannable essentials: identity, state and 4 KPI chips.
                             Everything heavier lives in the accordion below. --}}
                        <div class="sta-card-compact" role="button" tabindex="0"
                             aria-expanded="false" aria-label="{{ $time->show->title }}">

                            {{-- Identity + state --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex items-center gap-2 text-[color:var(--prism-text)]">
                                        <span aria-hidden="true">🎭</span>
                                        <span class="font-semibold text-sm sm:text-base truncate">{{ $time->show->title }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="prism-pill prism-pill-neon py-0.5 px-2 text-[10px] sm:text-[11px]">
                                            <span class="prism-dot prism-dot-emerald"></span>
                                            {{ $time->date?->format('d/m/Y') }}
                                        </span>
                                        <span class="prism-pill prism-pill-amber py-0.5 px-2 text-[10px] sm:text-[11px]">
                                            🕔 {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                                        </span>
                                    </div>
                                </div>
                                <span class="sta-status-chip {{ $isSoldOut ? 'is-soldout' : 'is-live' }} text-[10px] py-1 px-2.5 flex-shrink-0">
                                    <span class="sta-status-dot"></span>
                                    <span data-i18n="{{ $isSoldOut ? 'adm_status_sold_out' : 'adm_status_available' }}">
                                        {{ $isSoldOut ? 'Sold Out' : 'متاح' }}
                                    </span>
                                </span>
                            </div>

                            {{-- Compact KPI chips — single scannable "ticker" row. --}}
                            <div class="sta-chip-row">
                                <span class="sta-chip is-occupancy">
                                    <span class="sta-chip-dot"></span>
                                    <span class="sta-chip-val" dir="ltr">{{ (int) round($occupancyPct) }}%</span>
                                    <span class="sta-chip-label" data-i18n="adm_sta_ring_caption">إشغال</span>
                                </span>
                                <span class="sta-chip is-revenue">
                                    <span class="sta-chip-dot"></span>
                                    <span class="sta-chip-val" dir="ltr">{{ $stafmt($a['approved_revenue'] ?? 0) }}<span class="sta-chip-unit"> ج</span></span>
                                    <span class="sta-chip-label" data-i18n="adm_revenue">الإيرادات</span>
                                </span>
                                <span class="sta-chip is-remaining">
                                    <span class="sta-chip-dot"></span>
                                    <span class="sta-chip-val" dir="ltr">{{ $stafmt($a['remaining'] ?? 0) }}</span>
                                    <span class="sta-chip-label" data-i18n="adm_sta_remaining">المتبقي</span>
                                </span>
                                <span class="sta-chip is-approved">
                                    <span class="sta-chip-dot"></span>
                                    <span class="sta-chip-val" dir="ltr">{{ $stafmt($a['approved_tickets'] ?? 0) }}</span>
                                    <span class="sta-chip-label" data-i18n="adm_sta_approved">معتمد</span>
                                </span>
                            </div>

                            {{-- Expand affordance --}}
                            <div class="sta-expand-row">
                                <span class="sta-expand-arrow" aria-hidden="true">▼</span>
                                <span class="sta-expand-text" data-i18n="adm_sta_show_details">عرض التفاصيل</span>
                            </div>
                        </div>

                        {{-- ── 2. EXPANDABLE DETAILS ─── --}}
                        <div class="sta-card-details">
                            <div class="p-4 sm:p-5 pt-0 border-t border-[color:var(--prism-border)] border-dashed mt-1 space-y-5">

                                {{-- ── price (moved out of the compact header) ─── --}}
                                <p class="text-[11px] text-[color:var(--prism-text-3)] pt-4">
                                    @if ($cardUsesSection)
                                        <span data-i18n="adm_sta_price_split">صالة / بلكون</span>:
                                        <span style="color: var(--prism-gold);">{{ $cardSectionLabel }} <span data-i18n="common_currency_short">ج</span></span>
                                    @else
                                        <span data-i18n="adm_times_col_price">السعر</span>:
                                        <span style="color: var(--prism-gold);">{{ $stafmt($a['ticket_price'] ?? 0) }} <span data-i18n="common_currency_short">ج</span></span>
                                    @endif
                                </p>

                                {{-- ── occupancy ring + 4-tile breakdown ────── --}}
                                <div class="flex items-center gap-4 sm:gap-5 flex-wrap">
                                    <div class="sta-ring-wrap is-mini" aria-hidden="true">
                                        <svg viewBox="0 0 100 100">
                                            <circle class="sta-ring-track" cx="50" cy="50" r="{{ $staRingRadius }}"></circle>
                                            <circle class="sta-ring-fill"
                                                    cx="50" cy="50" r="{{ $staRingRadius }}"
                                                    stroke-dasharray="{{ $staRingCircumference }}"
                                                    stroke-dashoffset="{{ $ringOffset }}"></circle>
                                        </svg>
                                        <div class="sta-ring-center">
                                            <span class="sta-ring-percent">{{ (int) round($occupancyPct) }}%</span>
                                            <span class="sta-ring-caption" data-i18n="adm_sta_ring_caption">إشغال</span>
                                        </div>
                                    </div>
        
                                    <div class="grid grid-cols-2 gap-2 flex-1 min-w-[200px]">
                                        <div class="pt-mini-card pt-mini-card-emerald">
                                            <div class="pt-mini-card-label" data-i18n="adm_sta_approved">معتمد</div>
                                            <div class="pt-mini-card-value">{{ $stafmt($a['approved_tickets'] ?? 0) }}</div>
                                        </div>
                                        <div class="pt-mini-card pt-mini-card-gold">
                                            <div class="pt-mini-card-label" data-i18n="adm_sta_pending">قيد المراجعة</div>
                                            <div class="pt-mini-card-value">{{ $stafmt($a['pending_tickets'] ?? 0) }}</div>
                                        </div>
                                        <div class="pt-mini-card" style="border-color: rgba(251,113,133,0.32); background: rgba(244,63,94,0.06);">
                                            <div class="pt-mini-card-label" data-i18n="adm_sta_blocked">محجوب</div>
                                            <div class="pt-mini-card-value" style="color: #fda4af;">{{ $stafmt($a['blocked'] ?? 0) }}</div>
                                        </div>
                                        <div class="pt-mini-card">
                                            <div class="pt-mini-card-label" data-i18n="adm_sta_remaining">المتبقي</div>
                                            <div class="pt-mini-card-value" style="color: var(--prism-text);">
                                                {{ $stafmt($a['remaining'] ?? 0) }}
                                                <span class="text-[10px] opacity-50">/ {{ $stafmt($a['capacity'] ?? 0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        
                                {{-- ── stacked progress bar + legend ───────── --}}
                                <div>
                                    <div class="sta-stack is-mini" role="img"
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
        
                                {{-- ── revenue split: approved vs pending ──── --}}
                                <div class="sta-rev-split">
                                    <div class="sta-rev-tile is-approved">
                                        <div class="sta-rev-label" data-i18n="adm_sta_rev_approved">إيراد مؤكد</div>
                                        <div class="sta-rev-value">
                                            {{ $stafmt($a['approved_revenue'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                        <div class="sta-rev-sub">
                                            {{ $stafmt($a['approved_bookings'] ?? 0) }}
                                            <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                                            · <span data-i18n="adm_sta_avg_short">متوسط</span>
                                            {{ $stafmt($a['average_booking_value'] ?? 0) }}
                                            <span data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
        
                                    <div class="sta-rev-tile is-pending">
                                        <div class="sta-rev-label" data-i18n="adm_sta_rev_pending">إيراد معلَّق</div>
                                        <div class="sta-rev-value">
                                            {{ $stafmt($a['pending_revenue'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                        <div class="sta-rev-sub">
                                            {{ $stafmt($a['pending_bookings'] ?? 0) }}
                                            <span data-i18n="adm_sta_kpi_bookings_word">حجز</span>
                                            · <span data-i18n="adm_sta_conv_short">تحويل</span>
                                            {{ $staPct($a['conversion_percent'] ?? 0) }}
                                        </div>
                                    </div>
                                </div>
        
                                {{-- ── hall / balcony breakdown (Anba only) ─── --}}
                                @if($cardUsesSection && (($a['hall'] ?? null) || ($a['balcony'] ?? null)))
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        
                                        @php $h = $a['hall'] ?? null; @endphp
                                        @if($h)
                                            <div class="sta-section-card is-hall">
                                                <div class="sta-section-title">
                                                    <span><span data-i18n="adm_sta_section_hall">صالة</span></span>
                                                    <span class="text-[10px] opacity-80">{{ $stafmt($a['hall_price']) }} <span data-i18n="common_currency_short">ج</span></span>
                                                </div>
                                                <div class="sta-section-rows">
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_sold">تذاكر مُباعة</div>
                                                        <div class="sta-section-row-value">{{ $stafmt($h['tickets_sold']) }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_list"> الأجمالى قبل الخصومات  </div>
                                                        <div class="sta-section-row-value">{{ $stafmt($h['list_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span></div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_final">صافي الإيراد</div>
                                                        <div class="sta-section-row-value" style="color: var(--prism-emerald);">
                                                            {{ $stafmt($h['final_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_savings">الخصومات</div>
                                                        <div class="sta-section-row-value" style="color: var(--prism-gold);">
                                                            {{ $stafmt($h['discount_amount']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
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
                                                    <span class="text-[10px] opacity-80">{{ $stafmt($a['balcony_price']) }} <span data-i18n="common_currency_short">ج</span></span>
                                                </div>
                                                <div class="sta-section-rows">
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_sold">تذاكر مُباعة</div>
                                                        <div class="sta-section-row-value">{{ $stafmt($b['tickets_sold']) }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_list">  الأجمالى قبل الخصومات</div>
                                                        <div class="sta-section-row-value">{{ $stafmt($b['list_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span></div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_final">صافي الإيراد</div>
                                                        <div class="sta-section-row-value" style="color: var(--prism-emerald);">
                                                            {{ $stafmt($b['final_revenue']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="sta-section-row-label" data-i18n="adm_sta_section_savings">الخصومات</div>
                                                        <div class="sta-section-row-value" style="color: var(--prism-gold);">
                                                            {{ $stafmt($b['discount_amount']) }} <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
        
                                {{-- ── advanced expandable ──────────────────── --}}
                                <div class="sta-advanced-grid border-t border-[color:var(--prism-border)] border-dashed pt-4 mt-2">
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_discount">الخصومات المُطبَّقة</div>
                                        <div class="pt-mini-card-value" style="color: var(--prism-gold);">
                                            {{ $stafmt($a['total_discount'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_discounted_count">عدد الحجوزات المخصومة</div>
                                        <div class="pt-mini-card-value">{{ $stafmt($a['discounted_bookings'] ?? 0) }}</div>
                                    </div>
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_avg">متوسط قيمة الحجز</div>
                                        <div class="pt-mini-card-value">
                                            {{ $stafmt($a['average_booking_value'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_conv">نسبة الموافقة</div>
                                        <div class="pt-mini-card-value" style="color: var(--prism-cyan);">
                                            {{ $staPct($a['conversion_percent'] ?? 0) }}
                                        </div>
                                    </div>
                                    <div class="pt-mini-card pt-mini-card-emerald">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_approved">حجوزات معتمدة</div>
                                        <div class="pt-mini-card-value">{{ $stafmt($a['approved_bookings'] ?? 0) }}</div>
                                    </div>
                                    <div class="pt-mini-card pt-mini-card-gold">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_pending">حجوزات معلَّقة</div>
                                        <div class="pt-mini-card-value">{{ $stafmt($a['pending_bookings'] ?? 0) }}</div>
                                    </div>
                                    <div class="pt-mini-card" style="border-color: rgba(251,113,133,0.32); background: rgba(244,63,94,0.06);">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_bk_rejected">حجوزات مرفوضة</div>
                                        <div class="pt-mini-card-value" style="color: #fda4af;">{{ $stafmt($a['rejected_bookings'] ?? 0) }}</div>
                                    </div>
                                    <div class="pt-mini-card">
                                        <div class="pt-mini-card-label" data-i18n="adm_sta_adv_total_rev">الإيراد الكلي</div>
                                        <div class="pt-mini-card-value">
                                            {{ $stafmt($a['total_revenue'] ?? 0) }}
                                            <span class="text-xs opacity-70" data-i18n="common_currency_short">ج</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach

                </div>
            @endif
        </section>

        {{-- ============================ SETTINGS ============================ --}}
        {{-- Moved out of the stats grid into its own settings section so it
             reads as configuration rather than a KPI tile. --}}
        <section class="space-y-3 pt-reveal">

            <div class="prism-section-head">
                <span class="prism-section-title" data-i18n="adm_payments_title">إعدادات الدفع</span>
                <span class="prism-eyebrow" data-i18n="adm_payments_eyebrow">SETTINGS · يظهر للعميل</span>
            </div>

            <div class="prism-glass p-5 prism-fade-up max-w-2xl"
                 style="border-color: rgba(52,211,153,0.30);">

                <form action="{{ route('admin.settings.payments.update') }}" method="POST" class="space-y-3 text-sm">
                    @csrf

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="prism-eyebrow" data-i18n="adm_payments_wallet">رقم المحفظة</label>
                            <input type="text"
                                   name="transfer_wallet"
                                   value="{{ old('transfer_wallet', $transferWallet) }}"
                                   class="prism-input text-sm"
                                   placeholder="010xxxxxxxx">
                        </div>

                        <div class="space-y-1">
                            <label class="prism-eyebrow">InstaPay</label>
                            <input type="text"
                                   name="transfer_insta"
                                   value="{{ old('transfer_insta', $transferInsta) }}"
                                   class="prism-input text-sm"
                                   placeholder="name@instapay">
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-2">
                        <span class="text-[11px] text-[color:var(--prism-text-3)]"
                              data-i18n="adm_payments_hint">
                            هتظهر في صفحة الدفع للعميل عشان يحوّل عليها.
                        </span>
                        <button type="submit" class="prism-btn-emerald text-xs px-4 py-2"
                                data-i18n="adm_payments_save">
                            حفظ بيانات التحويل
                        </button>
                    </div>
                </form>
            </div>
        </section>

    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.sta-card');

    cards.forEach(card => {
        const compact = card.querySelector('.sta-card-compact');
        const details = card.querySelector('.sta-card-details');
        const text = card.querySelector('.sta-expand-text');

        const setText = (key, fallback) => {
            if (!text) return;
            text.setAttribute('data-i18n', key);
            text.textContent = window.PT_T ? window.PT_T(key) : fallback;
        };

        const toggle = () => {
            const willExpand = !card.classList.contains('is-expanded');
            card.classList.toggle('is-expanded', willExpand);
            if (compact) compact.setAttribute('aria-expanded', willExpand ? 'true' : 'false');
            if (details) details.style.maxHeight = willExpand ? details.scrollHeight + 'px' : null;
            setText(
                willExpand ? 'adm_sta_hide_details' : 'adm_sta_show_details',
                willExpand ? 'إخفاء التفاصيل' : 'عرض التفاصيل'
            );
        };

        if (!compact) return;

        compact.addEventListener('click', (e) => {
            if (e.target.closest('a') || e.target.closest('button')) return;
            toggle();
        });

        // Keyboard support for the role="button" summary.
        compact.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                e.preventDefault();
                toggle();
            }
        });

        // Keep an open card's height correct if the viewport reflows.
        window.addEventListener('resize', () => {
            if (details && card.classList.contains('is-expanded')) {
                details.style.maxHeight = details.scrollHeight + 'px';
            }
        });
    });
});
</script>
@endpush
