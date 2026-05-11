@extends('layouts.app')

@php
    use App\Models\Show as ShowModel;

    /* ========================================================================
       Seat Occupancy / Attendee Manifest — Phase 2

       Three operator-led surfaces share this single Blade. The controller
       picks the right surface for the device:

         mode=ops    desktop  — Operations Console: chart-left + detail rail.
         mode=floor  mobile   — Floor Mode: thumb-first usher card list.
         mode=paper  print    — Paper Sheet: A4-landscape, paper-first.

       All three pull from the same flat `rows` payload. The view layer
       does the visual + interaction work; the controller stays pure data.
       ======================================================================== */

    $eventDate = optional($showTime->date)->format('d/m/Y');
    $eventTime = $showTime->time ? \Carbon\Carbon::parse($showTime->time)->format('g:i A') : '';
    $showTitle = optional($show)->title ?? '—';

    // Phone masking happens in the view so the controller payload stays
    // unmasked and the same data drives the CSV export.
    $maskPhone = function (?string $phone) use ($showFullPhone) {
        if (!$phone) return '';
        if ($showFullPhone) return $phone;
        $digits = preg_replace('/\D+/', '', $phone);
        $len = strlen($digits);
        if ($len <= 6) return $phone;
        return substr($digits, 0, 2) . str_repeat('●', max(0, $len - 6)) . substr($digits, -4);
    };

    // Group rows by section → row letter for both the Operations chart and
    // the Paper sheet. PHP preserves insertion order; the controller
    // already ordered by (section, row, seat#).
    $rowsBySectionRow = [];
    foreach ($rows as $r) {
        $rowsBySectionRow[$r['section_label_ar']][$r['row_letter']][] = $r;
    }

    // Stable booking_id → hue index so each booking gets the same color
    // wherever it appears (chart ring, booking list, paper band). Capped
    // at 8 distinct hues; beyond that we cycle. Two adjacent bookings on
    // a row are extremely unlikely to land on the same hue.
    $bookingColorIndex = [];
    $bookingHueIdx = 0;
    foreach ($rows as $r) {
        if (!empty($r['booking_id']) && !isset($bookingColorIndex[$r['booking_id']])) {
            $bookingColorIndex[$r['booking_id']] = ($bookingHueIdx % 8);
            $bookingHueIdx++;
        }
    }

    $totalBooked   = $summary['approved'] + $summary['pending'];
    $checkedInCount = collect($rows)->where('is_scanned', true)->count();
    $capacity       = $summary['total'] ?: 1;
    $capacityPct    = (int) round(($totalBooked / $capacity) * 100);

    // URL helper — preserves `full_phone` + `mode` so toggles don't fight
    // the user. Pass `null` to remove a key.
    $url = function ($params = []) use ($showTime, $showFullPhone, $mode) {
        $base = route('admin.show-times.manifest', $showTime);
        $q    = array_merge(
            ['full_phone' => $showFullPhone ? 1 : 0, 'mode' => $mode],
            $params
        );
        $q = array_filter($q, fn ($v) => $v !== null && $v !== '');
        return $base . '?' . http_build_query($q);
    };

    $csvUrl = route('admin.show-times.manifest.csv', $showTime)
        . '?' . http_build_query(['full_phone' => $showFullPhone ? 1 : 0]);

    $jsonUrl = route('admin.show-times.manifest.json', $showTime);

    // Filter / search initial state for the JS-driven panels. The URL is
    // the source of truth so refreshes don't lose context.
    $statusFilterJoined = implode(',', $statusFilter);

    // Per-row blocks used by the Paper sheet (attendees-only by default).
    // Empties are filtered out unless ?include_empty=1.
    $paperRowsBySectionRow = [];
    foreach ($rowsBySectionRow as $sectionLabel => $byRow) {
        foreach ($byRow as $rowLetter => $seats) {
            $kept = array_values(array_filter($seats, function ($s) use ($includeEmpty) {
                if ($includeEmpty) return true;
                return $s['status'] !== 'empty';
            }));
            if (!empty($kept)) {
                $paperRowsBySectionRow[$sectionLabel][$rowLetter] = $kept;
            }
        }
    }

    // Status glyph for the paper sheet — survives photocopy + grayscale
    // better than letters ("OK / PEND / BLK").
    $statusGlyph = [
        'approved' => '●',
        'pending'  => '◐',
        'blocked'  => '✕',
        'empty'    => '·',
    ];

    // Status pill class for the on-screen surfaces.
    $statusPill = [
        'approved' => ['emerald', 'Approved', 'معتمد'],
        'pending'  => ['amber',   'Pending',  'انتظار'],
        'blocked'  => ['rose',    'Blocked',  'محجوب'],
        'empty'    => ['ghost',   'Empty',    'فارغ'],
    ];
@endphp

@section('title', 'مانيفست المقاعد · ' . $showTitle)

@push('styles')
<style>
    /* ====================================================================
       Manifest tokens — shared across all three surfaces
       ==================================================================== */
    :root {
        --m-bg            : var(--prism-surface, rgba(255,255,255,0.03));
        --m-border        : var(--prism-border, rgba(255,255,255,0.10));
        --m-border-strong : rgba(255,255,255,0.22);
        --m-emerald       : #34d399;
        --m-amber         : #fbbf24;
        --m-rose          : #fb7185;
        --m-sky           : #38bdf8;
        --m-violet        : #a78bfa;
        --m-text          : var(--prism-text, #f3f4f6);
        --m-text-2        : var(--prism-text-2, #d1d5db);
        --m-text-3        : var(--prism-text-3, #9ca3af);
    }

    /* Booking family hues (chart ring + paper band + floor card accent) */
    .mfst-hue-0 { --m-hue: rgba(34,211,238,0.65);  --m-hue-soft: rgba(34,211,238,0.10); }
    .mfst-hue-1 { --m-hue: rgba(129,140,248,0.65); --m-hue-soft: rgba(129,140,248,0.10); }
    .mfst-hue-2 { --m-hue: rgba(244,114,182,0.65); --m-hue-soft: rgba(244,114,182,0.10); }
    .mfst-hue-3 { --m-hue: rgba(251,191,36,0.65);  --m-hue-soft: rgba(251,191,36,0.10); }
    .mfst-hue-4 { --m-hue: rgba(52,211,153,0.65);  --m-hue-soft: rgba(52,211,153,0.10); }
    .mfst-hue-5 { --m-hue: rgba(251,113,133,0.65); --m-hue-soft: rgba(251,113,133,0.10); }
    .mfst-hue-6 { --m-hue: rgba(167,139,250,0.65); --m-hue-soft: rgba(167,139,250,0.10); }
    .mfst-hue-7 { --m-hue: rgba(252,165,165,0.65); --m-hue-soft: rgba(252,165,165,0.10); }

    /* ====================================================================
       Top bar — Operations + Floor share it; Paper hides it (.pt-no-print).
       Compact, sticky, single row with overflow menu. Replaces the bigger
       "manifest-chrome" card that used to dominate the first viewport.
       ==================================================================== */
    .mfst-topbar {
        position: sticky;
        top: calc(64px + env(safe-area-inset-top, 0px));
        z-index: 20;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        background: linear-gradient(180deg, rgba(0,0,0,0.55), rgba(0,0,0,0.35));
        border: 1px solid var(--m-border);
        border-radius: 16px;
        padding: 10px 14px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        align-items: center;
        margin-bottom: 12px;
    }
    .mfst-topbar-id { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .mfst-topbar-title { font-size: 15px; font-weight: 800; color: var(--m-text); display: flex; align-items: center; gap: 8px; }
    .mfst-topbar-title .live-dot {
        width: 8px; height: 8px; border-radius: 999px;
        background: var(--m-emerald);
        box-shadow: 0 0 8px rgba(52,211,153,0.7);
        animation: mfst-pulse-soft 2s ease-in-out infinite;
    }
    @keyframes mfst-pulse-soft { 0%,100% { opacity: 1; } 50% { opacity: .35; } }
    .mfst-topbar-meta { font-size: 11px; color: var(--m-text-3); letter-spacing: .04em; display: flex; gap: 10px; flex-wrap: wrap; }

    .mfst-topbar-actions { display: flex; gap: 8px; align-items: center; }
    .mfst-mode-switch {
        display: inline-flex;
        border: 1px solid var(--m-border);
        border-radius: 999px;
        overflow: hidden;
        background: rgba(255,255,255,0.04);
    }
    .mfst-mode-switch a {
        padding: 8px 14px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        color: var(--m-text-2);
        transition: background .15s ease, color .15s ease;
        white-space: nowrap;
    }
    .mfst-mode-switch a:hover { color: var(--m-text); background: rgba(129,140,248,0.10); }
    .mfst-mode-switch a.is-active {
        color: #fff;
        background: linear-gradient(135deg, rgba(34,211,238,0.28), rgba(167,139,250,0.28));
    }

    .mfst-overflow {
        position: relative;
    }
    .mfst-overflow-btn {
        width: 36px; height: 36px;
        border-radius: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text);
        font-size: 18px;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: background .15s ease;
    }
    .mfst-overflow-btn:hover { background: rgba(129,140,248,0.12); }
    .mfst-overflow-menu {
        position: absolute;
        top: calc(100% + 6px);
        inset-inline-end: 0;
        min-width: 220px;
        padding: 6px;
        border-radius: 14px;
        border: 1px solid var(--m-border);
        background: rgba(8,9,18,0.95);
        backdrop-filter: blur(12px);
        box-shadow: 0 16px 48px rgba(0,0,0,0.55);
        display: none;
        z-index: 30;
    }
    .mfst-overflow-menu.is-open { display: block; }
    .mfst-overflow-menu a, .mfst-overflow-menu button {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 9px 12px;
        border-radius: 10px;
        font-size: 13px;
        color: var(--m-text);
        background: transparent;
        border: 0;
        cursor: pointer;
        text-align: start;
        transition: background .12s ease;
    }
    .mfst-overflow-menu a:hover, .mfst-overflow-menu button:hover { background: rgba(129,140,248,0.10); }
    .mfst-overflow-menu hr { border: 0; border-top: 1px solid var(--m-border); margin: 4px 0; }

    /* ====================================================================
       Capacity gauge — single most important number; persistent in header
       ==================================================================== */
    .mfst-gauge {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        color: var(--m-text-2);
    }
    .mfst-gauge-bar {
        position: relative;
        width: clamp(140px, 22vw, 280px);
        height: 6px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
        overflow: hidden;
    }
    .mfst-gauge-bar .fill {
        position: absolute;
        inset-inline-start: 0; top: 0; bottom: 0;
        width: 0%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--m-emerald), var(--m-sky));
        transition: width .8s cubic-bezier(.4,0,.2,1);
    }
    .mfst-gauge-num { font-weight: 800; color: var(--m-text); font-feature-settings: "tnum" 1; }

    /* ====================================================================
       Filter strip — sticky chips just under the top bar. Status + section
       + search. URL-encoded so refresh preserves intent.
       ==================================================================== */
    .mfst-filters {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        padding: 8px 4px;
        margin-bottom: 12px;
    }
    .mfst-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.03);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .02em;
        color: var(--m-text-2);
        cursor: pointer;
        user-select: none;
        transition: background .15s ease, color .15s ease, border-color .15s ease, transform .12s ease;
    }
    .mfst-chip:hover { transform: translateY(-1px); color: var(--m-text); }
    .mfst-chip:active { transform: translateY(0); }
    .mfst-chip[aria-pressed="true"] {
        color: #fff;
        border-color: var(--m-chip-color, rgba(167,139,250,0.7));
        background: var(--m-chip-bg, rgba(167,139,250,0.18));
    }
    .mfst-chip .count { color: var(--m-text-3); font-weight: 700; }
    .mfst-chip[aria-pressed="true"] .count { color: rgba(255,255,255,0.85); }
    .mfst-chip-approved   { --m-chip-color: rgba(52,211,153,0.7);  --m-chip-bg: rgba(52,211,153,0.16);  }
    .mfst-chip-pending    { --m-chip-color: rgba(251,191,36,0.7);  --m-chip-bg: rgba(251,191,36,0.16);  }
    .mfst-chip-blocked    { --m-chip-color: rgba(251,113,133,0.7); --m-chip-bg: rgba(251,113,133,0.18); }
    .mfst-chip-checked    { --m-chip-color: rgba(56,189,248,0.7);  --m-chip-bg: rgba(56,189,248,0.18);  }
    .mfst-chip-empty      { --m-chip-color: rgba(255,255,255,0.45); --m-chip-bg: rgba(255,255,255,0.10); }

    .mfst-search {
        margin-inline-start: auto;
        flex: 1 1 240px;
        min-width: 200px;
        position: relative;
    }
    .mfst-search input {
        width: 100%;
        padding: 10px 38px 10px 14px;
        border-radius: 999px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text);
        font-size: 13px;
        outline: none;
        transition: border-color .15s ease, background .15s ease;
    }
    .mfst-search input:focus { border-color: rgba(129,140,248,0.7); background: rgba(255,255,255,0.07); }
    .mfst-search .icon {
        position: absolute;
        inset-inline-end: 12px;
        top: 50%;
        transform: translateY(-50%);
        opacity: .55;
        pointer-events: none;
    }
    .mfst-search kbd {
        position: absolute;
        inset-inline-end: 38px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid var(--m-border);
        color: var(--m-text-3);
        background: rgba(255,255,255,0.04);
    }

    /* ====================================================================
       OPERATIONS CONSOLE (mode=ops) — chart-left + detail rail-right
       ==================================================================== */
    .mfst-ops {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .mfst-ops { grid-template-columns: 1fr; }
        .mfst-ops-rail { order: 2; }
    }

    .mfst-ops-chart {
        border: 1px solid var(--m-border);
        border-radius: 18px;
        background: rgba(255,255,255,0.025);
        padding: 18px;
        min-height: 60vh;
    }

    /* Section block (Hall / Balcony) inside the chart */
    .mfst-section {
        margin-bottom: 18px;
    }
    .mfst-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }
    .mfst-section-title {
        font-size: 14px;
        font-weight: 800;
        letter-spacing: .04em;
        color: var(--m-text);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .mfst-section-sub {
        font-size: 11px;
        color: var(--m-text-3);
        font-feature-settings: "tnum" 1;
    }

    .mfst-stage {
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .35em;
        color: rgba(167,139,250,0.85);
        padding: 12px;
        border-radius: 12px;
        border: 1px dashed rgba(167,139,250,0.4);
        background: linear-gradient(180deg, rgba(167,139,250,0.06), transparent);
        margin-bottom: 14px;
    }

    /* Row of seats — letter + saturation gauge + chips */
    .mfst-row {
        display: grid;
        grid-template-columns: 56px 1fr;
        gap: 10px;
        align-items: center;
        padding: 4px 0;
        border-bottom: 1px dashed rgba(255,255,255,0.05);
    }
    .mfst-row:last-child { border-bottom: 0; }
    .mfst-row-label {
        display: flex; flex-direction: column; align-items: center;
        font-weight: 800;
        font-size: 13px;
        color: var(--m-text);
    }
    .mfst-row-label .row-letter { font-size: 14px; }
    .mfst-row-label .row-stats  { font-size: 9px; color: var(--m-text-3); margin-top: 2px; font-feature-settings: "tnum" 1; }
    .mfst-row-label .row-stats .ck { color: var(--m-emerald); }

    .mfst-row-seats {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: center;
    }

    /* Seat chip — visible by default. This is the chip that was effectively
       invisible in Phase 1. Strong border, real status fill, family hue
       ring as box-shadow inset. */
    .mfst-seat {
        --m-seat-bg: rgba(255,255,255,0.06);
        --m-seat-border: var(--m-border-strong);
        --m-seat-color: var(--m-text);
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        padding: 0 6px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        background: var(--m-seat-bg);
        border: 1px solid var(--m-seat-border);
        color: var(--m-seat-color);
        font-feature-settings: "tnum" 1;
        cursor: pointer;
        transition: transform .12s ease, border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .mfst-seat:hover { transform: translateY(-1px); border-color: rgba(255,255,255,0.5); }
    .mfst-seat:focus-visible { outline: 2px solid var(--m-sky); outline-offset: 2px; }
    .mfst-seat.is-approved {
        --m-seat-bg: rgba(52,211,153,0.18);
        --m-seat-border: rgba(52,211,153,0.65);
        --m-seat-color: #d1fae5;
    }
    .mfst-seat.is-pending {
        --m-seat-bg: rgba(251,191,36,0.16);
        --m-seat-border: rgba(251,191,36,0.65);
        --m-seat-color: #fef3c7;
    }
    .mfst-seat.is-blocked {
        --m-seat-bg: repeating-linear-gradient(45deg, rgba(251,113,133,0.18), rgba(251,113,133,0.18) 3px, rgba(0,0,0,0.18) 3px, rgba(0,0,0,0.18) 5px);
        --m-seat-border: rgba(251,113,133,0.7);
        --m-seat-color: #fecdd3;
        cursor: not-allowed;
    }
    .mfst-seat.is-empty {
        --m-seat-bg: transparent;
        --m-seat-border: rgba(255,255,255,0.12);
        --m-seat-color: var(--m-text-3);
        opacity: .8;
    }
    /* Light theme overrides — chip stays visible on a parchment background */
    [data-pt-theme="light"] .mfst-seat {
        --m-seat-bg: rgba(0,0,0,0.04);
        --m-seat-border: rgba(0,0,0,0.20);
        --m-seat-color: #1f2937;
    }
    [data-pt-theme="light"] .mfst-seat.is-approved {
        --m-seat-bg: rgba(16,185,129,0.15);
        --m-seat-border: rgba(5,150,105,0.7);
        --m-seat-color: #065f46;
    }
    [data-pt-theme="light"] .mfst-seat.is-pending {
        --m-seat-bg: rgba(245,158,11,0.15);
        --m-seat-border: rgba(217,119,6,0.7);
        --m-seat-color: #92400e;
    }
    [data-pt-theme="light"] .mfst-seat.is-blocked {
        --m-seat-bg: repeating-linear-gradient(45deg, rgba(244,63,94,0.15), rgba(244,63,94,0.15) 3px, rgba(0,0,0,0.10) 3px, rgba(0,0,0,0.10) 5px);
        --m-seat-border: rgba(225,29,72,0.7);
        --m-seat-color: #9f1239;
    }
    [data-pt-theme="light"] .mfst-seat.is-empty {
        --m-seat-border: rgba(0,0,0,0.12);
        --m-seat-color: #6b7280;
    }
    /* Family hue ring — drawn as a left-side inset shadow so it doesn't
       fight the status border. Visible on RTL too. */
    .mfst-seat[data-hue]::before {
        content: "";
        position: absolute;
        inset-block: 3px;
        inset-inline-start: 2px;
        width: 3px;
        border-radius: 2px;
        background: var(--m-hue);
    }
    /* Checked-in marker (✓) — emerald dot top-right */
    .mfst-seat[data-checked="1"]::after {
        content: "✓";
        position: absolute;
        top: -4px;
        inset-inline-end: -4px;
        width: 14px; height: 14px;
        border-radius: 999px;
        background: var(--m-emerald);
        color: #052e23;
        font-size: 9px;
        font-weight: 900;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 8px rgba(52,211,153,0.7);
    }
    .mfst-seat.is-focused {
        animation: mfst-focus-pulse 1.4s ease-out 3;
        z-index: 3;
        border-color: var(--m-sky) !important;
        box-shadow: 0 0 0 3px rgba(56,189,248,0.18), 0 0 24px rgba(56,189,248,0.45);
    }
    .mfst-seat.is-selected {
        border-color: var(--m-sky) !important;
        box-shadow: 0 0 0 2px rgba(56,189,248,0.30);
    }
    .mfst-seat.is-booking-highlight {
        box-shadow: 0 0 0 2px var(--m-hue), 0 0 18px var(--m-hue-soft);
        transform: translateY(-1px);
    }
    @keyframes mfst-focus-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(56,189,248,0.65); }
        100% { box-shadow: 0 0 0 14px rgba(56,189,248,0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .mfst-seat.is-focused { animation: none; box-shadow: 0 0 0 3px rgba(56,189,248,0.35); }
    }
    /* Hide seats filtered out by the chip strip (status-driven). The
       `data-status` attribute holds the row status; JS toggles a class
       on the chart root that disables matching seats. */
    .mfst-ops-chart.hide-empty    .mfst-seat[data-status="empty"]    { opacity: .18; pointer-events: none; }
    .mfst-ops-chart.hide-approved .mfst-seat[data-status="approved"] { opacity: .18; pointer-events: none; }
    .mfst-ops-chart.hide-pending  .mfst-seat[data-status="pending"]  { opacity: .18; pointer-events: none; }
    .mfst-ops-chart.hide-blocked  .mfst-seat[data-status="blocked"]  { opacity: .18; pointer-events: none; }
    .mfst-ops-chart.only-checked  .mfst-seat:not([data-checked="1"]) { opacity: .18; pointer-events: none; }

    /* ----- Detail rail (right) ----- */
    .mfst-rail {
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: sticky;
        top: calc(64px + env(safe-area-inset-top, 0px) + 70px);
        max-height: calc(100vh - 160px);
    }
    .mfst-rail-card {
        border: 1px solid var(--m-border);
        border-radius: 16px;
        background: rgba(255,255,255,0.03);
        padding: 14px;
    }
    .mfst-rail-empty {
        text-align: center;
        padding: 24px 14px;
        color: var(--m-text-3);
        font-size: 12px;
        border: 1px dashed var(--m-border);
        border-radius: 16px;
    }
    .mfst-rail-empty .kbd-hint {
        display: inline-flex;
        gap: 4px;
        margin-top: 8px;
        font-size: 11px;
    }
    .mfst-rail-empty .kbd-hint kbd {
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid var(--m-border);
        font-family: ui-monospace, monospace;
    }

    .mfst-seat-detail .seat-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }
    .mfst-seat-detail .seat-big {
        font-size: 24px;
        font-weight: 800;
        letter-spacing: .02em;
        color: var(--m-text);
        font-feature-settings: "tnum" 1;
    }
    .mfst-seat-detail .seat-section {
        font-size: 11px;
        color: var(--m-text-3);
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .mfst-seat-detail .seat-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--m-text);
        margin: 2px 0 6px;
        line-height: 1.25;
    }
    .mfst-seat-detail .seat-meta {
        font-size: 12px;
        color: var(--m-text-2);
        display: flex;
        flex-direction: column;
        gap: 3px;
        font-feature-settings: "tnum" 1;
    }
    .mfst-seat-detail .seat-meta .label { color: var(--m-text-3); margin-inline-end: 4px; }
    .mfst-seat-detail .seat-actions {
        margin-top: 10px;
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .mfst-seat-detail .seat-actions a, .mfst-seat-detail .seat-actions button {
        flex: 1 1 auto;
        text-align: center;
        padding: 8px 10px;
        font-size: 12px;
        border-radius: 10px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text);
        cursor: pointer;
    }
    .mfst-seat-detail .seat-actions a:hover { background: rgba(129,140,248,0.12); }

    /* Party (full booking) inside seat detail */
    .mfst-seat-detail .seat-party {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed var(--m-border);
    }
    .mfst-seat-detail .seat-party .label {
        font-size: 10px;
        color: var(--m-text-3);
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }
    .mfst-seat-detail .seat-party ul { display: flex; flex-direction: column; gap: 4px; }
    .mfst-seat-detail .seat-party li {
        display: flex; align-items: center; gap: 8px;
        font-size: 12px;
        padding: 4px 6px;
        border-radius: 8px;
        background: rgba(255,255,255,0.03);
    }
    .mfst-seat-detail .seat-party li.is-current { background: rgba(56,189,248,0.12); }
    .mfst-seat-detail .seat-party li .lbl { font-weight: 800; color: var(--m-text); }
    .mfst-seat-detail .seat-party li .nm  { color: var(--m-text-2); }

    /* Bookings list card */
    .mfst-bookings-card { padding: 0; }
    .mfst-bookings-head {
        padding: 12px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--m-border);
    }
    .mfst-bookings-head .title {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--m-text-2);
    }
    .mfst-bookings-head .count { font-size: 11px; color: var(--m-text-3); }
    .mfst-bookings-list {
        max-height: 360px;
        overflow-y: auto;
    }
    .mfst-bookings-list .row {
        display: grid;
        grid-template-columns: 16px 1fr auto;
        gap: 10px;
        align-items: center;
        padding: 10px 14px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
        cursor: pointer;
        transition: background .12s ease;
    }
    .mfst-bookings-list .row:hover { background: rgba(129,140,248,0.08); }
    .mfst-bookings-list .row.is-active { background: rgba(56,189,248,0.10); }
    .mfst-bookings-list .row:last-child { border-bottom: 0; }
    .mfst-bookings-list .dot {
        width: 8px; height: 8px; border-radius: 999px;
        background: var(--m-hue, var(--m-text-3));
        box-shadow: 0 0 8px var(--m-hue, transparent);
    }
    .mfst-bookings-list .body { min-width: 0; }
    .mfst-bookings-list .body .l1 {
        font-size: 13px; font-weight: 700; color: var(--m-text);
        white-space: nowrap; text-overflow: ellipsis; overflow: hidden;
    }
    .mfst-bookings-list .body .l2 {
        font-size: 11px; color: var(--m-text-3);
        font-feature-settings: "tnum" 1;
    }
    .mfst-bookings-list .meta {
        font-size: 11px;
        color: var(--m-text-2);
        text-align: end;
    }
    .mfst-bookings-list .meta .seats { font-weight: 800; color: var(--m-text); }

    /* ====================================================================
       FLOOR MODE (mode=floor) — mobile-first, dark-forced, thumb-first
       ==================================================================== */
    [data-mode="floor"] {
        --m-bg            : #0a0a14;
        --m-border        : rgba(255,255,255,0.10);
        --m-border-strong : rgba(255,255,255,0.22);
        --m-text          : #f3f4f6;
        --m-text-2        : #d1d5db;
        --m-text-3        : #9ca3af;
        color: var(--m-text);
    }
    [data-mode="floor"] .mfst-topbar { background: linear-gradient(180deg, rgba(0,0,0,0.85), rgba(0,0,0,0.55)); }

    .mfst-floor {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding-bottom: calc(120px + env(safe-area-inset-bottom, 0px));
        /* Force dark background regardless of theme so the screen
           doesn't blind a usher in a dim hall. */
        background: #0a0a14;
        margin: -16px -16px 0;
        padding: 12px;
        min-height: calc(100vh - 80px);
        color: #f3f4f6;
    }
    .mfst-floor-filters {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        padding: 4px 0 8px;
        margin: 0 -4px;
    }
    .mfst-floor-filters::-webkit-scrollbar { display: none; }
    .mfst-floor-filters .mfst-chip {
        scroll-snap-align: start;
        white-space: nowrap;
        font-size: 11px;
        padding: 6px 10px;
    }
    .mfst-floor-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* 80 px card — chip top-left, name top-right, action trailing */
    .mfst-card {
        min-height: 80px;
        display: grid;
        grid-template-columns: 56px 1fr auto;
        gap: 10px;
        align-items: center;
        padding: 12px;
        border-radius: 14px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: transform .12s ease, border-color .15s ease;
    }
    .mfst-card:active { transform: scale(0.99); }
    .mfst-card.is-approved { border-color: rgba(52,211,153,0.55); background: rgba(52,211,153,0.06); }
    .mfst-card.is-pending  { border-color: rgba(251,191,36,0.55); background: rgba(251,191,36,0.06); }
    .mfst-card.is-blocked  { border-color: rgba(251,113,133,0.55); background: rgba(251,113,133,0.06); }
    .mfst-card.is-empty    { border-color: rgba(255,255,255,0.10); background: rgba(255,255,255,0.02); opacity: .85; }
    .mfst-card.is-scanned  { border-color: rgba(52,211,153,0.85); background: rgba(52,211,153,0.14); }

    .mfst-card[data-hue]::before {
        content: "";
        position: absolute;
        inset-block: 12px;
        inset-inline-start: 0;
        width: 3px;
        border-radius: 2px;
        background: var(--m-hue);
    }
    .mfst-card .card-chip {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 6px 4px;
        border-radius: 12px;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--m-border);
        min-width: 56px;
    }
    .mfst-card .card-chip .seat {
        font-size: 16px;
        font-weight: 800;
        color: var(--m-text);
        font-feature-settings: "tnum" 1;
    }
    .mfst-card .card-chip .sec {
        font-size: 9px;
        color: var(--m-text-3);
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-top: 2px;
    }
    .mfst-card.is-approved .card-chip { background: rgba(52,211,153,0.15); border-color: rgba(52,211,153,0.5); }
    .mfst-card.is-pending  .card-chip { background: rgba(251,191,36,0.15); border-color: rgba(251,191,36,0.5); }
    .mfst-card.is-blocked  .card-chip { background: rgba(251,113,133,0.15); border-color: rgba(251,113,133,0.5); }
    .mfst-card.is-scanned  .card-chip { background: rgba(52,211,153,0.25); border-color: rgba(52,211,153,0.75); }

    .mfst-card .card-body { min-width: 0; }
    .mfst-card .card-name {
        font-size: 15px;
        font-weight: 700;
        color: var(--m-text);
        line-height: 1.2;
        margin-bottom: 4px;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }
    .mfst-card .card-meta {
        font-size: 11px;
        color: var(--m-text-3);
        font-feature-settings: "tnum" 1;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .mfst-card .card-meta .pill {
        padding: 1px 6px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
    }
    .mfst-card .card-meta .pill.ok    { background: rgba(52,211,153,0.18); color: #6ee7b7; }
    .mfst-card .card-meta .pill.pen   { background: rgba(251,191,36,0.18); color: #fcd34d; }
    .mfst-card .card-meta .pill.blk   { background: rgba(251,113,133,0.18); color: #fda4af; }
    .mfst-card .card-meta .pill.emp   { background: rgba(255,255,255,0.08); color: var(--m-text-3); }
    .mfst-card .card-meta .pill.ck    { background: rgba(56,189,248,0.18); color: #7dd3fc; }

    .mfst-card .card-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px; height: 44px;
        border-radius: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.06);
        color: var(--m-text);
        font-size: 16px;
    }

    .mfst-floor-empty {
        text-align: center;
        padding: 40px 12px;
        color: var(--m-text-3);
        font-size: 13px;
    }

    /* Sticky bottom search + FAB */
    .mfst-floor-dock {
        position: fixed;
        inset-inline: 0;
        bottom: 0;
        padding: 10px 12px calc(12px + env(safe-area-inset-bottom, 0px));
        background: linear-gradient(180deg, rgba(10,10,20,0.0), rgba(10,10,20,0.95) 35%);
        z-index: 25;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .mfst-floor-dock .mfst-search { flex: 1; margin-inline-start: 0; }
    .mfst-floor-dock .mfst-search input {
        background: rgba(20,22,40,0.9);
        border-color: rgba(255,255,255,0.16);
        font-size: 15px;
        padding: 14px 44px 14px 16px;
    }
    .mfst-fab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px; height: 52px;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--m-emerald), var(--m-sky));
        color: #052e23;
        font-size: 22px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 12px 32px rgba(52,211,153,0.35);
    }

    /* ====================================================================
       PAPER SHEET (mode=paper) — on-screen preview + print rules
       ==================================================================== */
    .mfst-paper {
        background: #fff;
        color: #000;
        padding: 18px;
        border-radius: 12px;
        font-family: 'IBM Plex Sans Arabic', 'Space Grotesk', sans-serif;
    }
    .mfst-paper-section {
        page-break-inside: auto;
        break-inside: auto;
        margin-bottom: 18px;
    }
    .mfst-paper-section + .mfst-paper-section { page-break-before: always; break-before: page; }
    .mfst-paper-head {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        align-items: end;
        padding-bottom: 8px;
        border-bottom: 2px solid #000;
        margin-bottom: 8px;
    }
    .mfst-paper-head .t1 { font-size: 16pt; font-weight: 800; }
    .mfst-paper-head .t2 { font-size: 10pt; color: #444; }
    .mfst-paper-head .stats { font-size: 9pt; text-align: end; line-height: 1.4; }
    .mfst-paper-head .stats strong { font-weight: 800; }
    .mfst-paper-section-head {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        padding: 4px 6px;
        background: #f3f4f6;
        border: 1px solid #000;
        font-size: 10pt;
        font-weight: 800;
        margin-bottom: 0;
    }

    /* Table */
    .mfst-paper-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5pt;
        font-feature-settings: "tnum" 1;
    }
    .mfst-paper-table th, .mfst-paper-table td {
        padding: 5px 6px;
        border: 1px solid #000;
        vertical-align: middle;
    }
    .mfst-paper-table th {
        background: #f0f0f0;
        font-size: 8.5pt;
        font-weight: 800;
        text-align: start;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .mfst-paper-table td.t-glyph { width: 22px; text-align: center; font-weight: 800; font-size: 12pt; }
    .mfst-paper-table td.t-seat  { width: 60px; text-align: center; font-weight: 800; font-size: 11pt; }
    .mfst-paper-table td.t-name  { font-weight: 600; }
    .mfst-paper-table td.t-ref   { white-space: nowrap; }
    .mfst-paper-table td.t-phone { white-space: nowrap; font-family: ui-monospace, monospace; font-size: 9pt; }
    .mfst-paper-table td.t-check { width: 28px; text-align: center; font-size: 14pt; }
    .mfst-paper-table tr.is-approved td.t-glyph { color: #047857; }
    .mfst-paper-table tr.is-pending  td.t-glyph { color: #92400e; }
    .mfst-paper-table tr.is-blocked  td.t-glyph { color: #be123c; }
    .mfst-paper-table tr.is-empty    td.t-glyph { color: #6b7280; }
    .mfst-paper-table tr.is-blocked  td.t-name  { color: #6b7280; font-style: italic; }
    .mfst-paper-table tr.is-empty    td.t-name  { color: #9ca3af; font-style: italic; }
    /* Family hue band — soft background tint behind the whole row */
    .mfst-paper-table tr[data-hue="0"] td { background: rgba(34,211,238,0.08); }
    .mfst-paper-table tr[data-hue="1"] td { background: rgba(129,140,248,0.08); }
    .mfst-paper-table tr[data-hue="2"] td { background: rgba(244,114,182,0.08); }
    .mfst-paper-table tr[data-hue="3"] td { background: rgba(251,191,36,0.10); }
    .mfst-paper-table tr[data-hue="4"] td { background: rgba(52,211,153,0.10); }
    .mfst-paper-table tr[data-hue="5"] td { background: rgba(251,113,133,0.10); }
    .mfst-paper-table tr[data-hue="6"] td { background: rgba(167,139,250,0.10); }
    .mfst-paper-table tr[data-hue="7"] td { background: rgba(252,165,165,0.10); }

    .mfst-paper-rowsumm {
        font-size: 8.5pt;
        color: #444;
        padding: 4px 8px;
        border: 1px solid #000;
        border-top: 0;
        background: #fafafa;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .mfst-paper-legend {
        margin-top: 10px;
        font-size: 8pt;
        color: #444;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        border-top: 1px dashed #000;
        padding-top: 6px;
    }
    .mfst-paper-legend .g { font-weight: 800; }

    .mfst-paper-actions { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 10px; }
    .mfst-paper-actions a, .mfst-paper-actions button {
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text);
        cursor: pointer;
    }

    /* ====================================================================
       PRINT RULES — paper-first, only the paper section survives
       ==================================================================== */
    @media print {
        @page { size: A4 landscape; margin: 10mm 10mm 14mm 10mm; }

        body, html { background: #fff !important; color: #000 !important; }
        body.has-bg::before, body.has-bg::after { display: none !important; }

        /* Hide everything except the paper area */
        body * { visibility: hidden; }
        .mfst-paper, .mfst-paper * { visibility: visible; }
        .mfst-paper { position: absolute; inset: 0; padding: 0; background: #fff !important; color: #000 !important; }

        /* Chrome */
        .mfst-topbar, .mfst-filters, .mfst-paper-actions, .pt-no-print { display: none !important; }

        /* Running header on every page (CSS Paged Media) */
        .mfst-paper-section { page-break-inside: auto; break-inside: auto; }
        .mfst-paper-section-head { page-break-after: avoid; break-after: avoid; }
        .mfst-paper-table tr     { page-break-inside: avoid; break-inside: avoid; }
        .mfst-paper-table thead  { display: table-header-group; }

        /* Force the soft hue tints to print on most desktop browsers */
        .mfst-paper-table tr[data-hue] td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endpush

@section('content')
<div class="manifest-root prism-fade-up"
     data-mode="{{ $mode }}"
     data-focus-seat="{{ $focusSeat }}"
     data-poll-url="{{ $jsonUrl }}"
     data-status-filter="{{ $statusFilterJoined }}"
     data-mobile-default-mode="floor"
     data-checked-in-initial="{{ $checkedInCount }}"
     data-capacity="{{ $capacity }}"
     dir="rtl">

    {{-- ============================================================
         Top bar — Operations + Floor share it; Paper hides it
         ============================================================ --}}
    @if ($mode !== 'paper')
    <div class="mfst-topbar pt-no-print">
        <div class="mfst-topbar-id">
            <div class="mfst-topbar-title">
                <span class="live-dot" aria-hidden="true"></span>
                <span>{{ $showTitle }}</span>
            </div>
            <div class="mfst-topbar-meta">
                <span>{{ $eventDate }}</span>
                @if ($eventTime)<span>•</span><span>{{ $eventTime }}</span>@endif
                <span>•</span>
                <span class="mfst-gauge">
                    <span class="mfst-gauge-num js-gauge-num">{{ $totalBooked }} / {{ $capacity }}</span>
                    <span class="mfst-gauge-bar" aria-hidden="true"><span class="fill js-gauge-fill" data-pct="{{ $capacityPct }}"></span></span>
                </span>
                <span>•</span>
                <span class="js-gauge-checked">✓ <strong class="js-checked-num">{{ $checkedInCount }}</strong> checked-in</span>
            </div>
        </div>
        <div class="mfst-topbar-actions">
            <nav class="mfst-mode-switch" role="tablist" aria-label="Mode">
                <a href="{{ $url(['mode' => 'ops']) }}"   role="tab" aria-selected="{{ $mode === 'ops'   ? 'true' : 'false' }}" class="{{ $mode === 'ops'   ? 'is-active' : '' }}">🎛 Operations</a>
                <a href="{{ $url(['mode' => 'floor']) }}" role="tab" aria-selected="{{ $mode === 'floor' ? 'true' : 'false' }}" class="{{ $mode === 'floor' ? 'is-active' : '' }}">📱 Floor</a>
                <a href="{{ $url(['mode' => 'paper']) }}" role="tab" aria-selected="{{ $mode === 'paper' ? 'true' : 'false' }}" class="{{ $mode === 'paper' ? 'is-active' : '' }}">🖨 Paper</a>
            </nav>
            <div class="mfst-overflow">
                <button type="button" class="mfst-overflow-btn js-overflow-toggle" aria-haspopup="true" aria-expanded="false" aria-label="More actions">⋯</button>
                <div class="mfst-overflow-menu js-overflow-menu" role="menu">
                    <a href="{{ $url(['mode' => 'paper']) }}#autoprint" data-autoprint="1">🖨 Print sheet</a>
                    <a href="{{ $csvUrl }}">⬇ Export CSV</a>
                    <hr>
                    <a href="{{ $url(['full_phone' => $showFullPhone ? 0 : 1]) }}">
                        {{ $showFullPhone ? '🙈 Mask phones' : '👁 Show full phone numbers' }}
                    </a>
                    <a href="/admin/scanner">📷 Open QR Scanner</a>
                    <hr>
                    <a href="{{ route('admin.show-times.index') }}">← Back to show-times</a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================================
         Filter strip — Operations only. Floor has its own scroller.
         ============================================================ --}}
    @if ($mode === 'ops')
    <div class="mfst-filters pt-no-print">
        <button type="button" class="mfst-chip mfst-chip-approved js-filter-chip" data-status="approved" aria-pressed="{{ in_array('approved', $statusFilter) ? 'true' : 'false' }}">
            <span class="prism-dot prism-dot-emerald" aria-hidden="true"></span>
            <span>Approved</span>
            <span class="count">{{ $summary['approved'] }}</span>
        </button>
        <button type="button" class="mfst-chip mfst-chip-pending js-filter-chip" data-status="pending" aria-pressed="{{ in_array('pending', $statusFilter) ? 'true' : 'false' }}">
            <span class="prism-dot prism-dot-amber" aria-hidden="true"></span>
            <span>Pending</span>
            <span class="count">{{ $summary['pending'] }}</span>
        </button>
        <button type="button" class="mfst-chip mfst-chip-blocked js-filter-chip" data-status="blocked" aria-pressed="{{ in_array('blocked', $statusFilter) ? 'true' : 'false' }}">
            <span class="prism-dot prism-dot-rose" aria-hidden="true"></span>
            <span>Blocked</span>
            <span class="count">{{ $summary['blocked'] }}</span>
        </button>
        <button type="button" class="mfst-chip mfst-chip-checked js-filter-chip" data-status="checked_in" aria-pressed="{{ in_array('checked_in', $statusFilter) ? 'true' : 'false' }}">
            <span class="prism-dot prism-dot-sky" aria-hidden="true"></span>
            <span>Checked-in</span>
            <span class="count js-filter-checked-count">{{ $checkedInCount }}</span>
        </button>
        <button type="button" class="mfst-chip mfst-chip-empty js-filter-chip" data-status="empty" aria-pressed="{{ in_array('empty', $statusFilter) ? 'true' : 'false' }}">
            <span class="prism-dot" style="background: rgba(255,255,255,0.35);" aria-hidden="true"></span>
            <span>Empty</span>
            <span class="count">{{ $summary['empty'] }}</span>
        </button>

        <label class="mfst-search">
            <input type="search"
                   class="js-search-input"
                   placeholder="🔍 ابحث: اسم / رقم / كود حجز / مقعد (مثال: A12)"
                   aria-label="Search seats, bookings, attendees"
                   autocomplete="off"
                   autocapitalize="off">
            <kbd>/</kbd>
            <span class="icon">🔎</span>
        </label>
    </div>
    @endif

    {{-- ============================================================
         OPERATIONS CONSOLE
         ============================================================ --}}
    @if ($mode === 'ops')
    <div class="mfst-ops">
        <section class="mfst-ops-chart js-chart" aria-label="Seating chart">
            @foreach ($rowsBySectionRow as $sectionLabel => $byRow)
                @php
                    $sectionApproved = 0; $sectionPending = 0; $sectionBlocked = 0; $sectionEmpty = 0; $sectionTotal = 0;
                    foreach ($byRow as $rletter => $seats) {
                        foreach ($seats as $s) {
                            $sectionTotal++;
                            $sectionApproved += $s['status'] === 'approved' ? 1 : 0;
                            $sectionPending  += $s['status'] === 'pending'  ? 1 : 0;
                            $sectionBlocked  += $s['status'] === 'blocked'  ? 1 : 0;
                            $sectionEmpty    += $s['status'] === 'empty'    ? 1 : 0;
                        }
                    }
                @endphp
                <div class="mfst-section">
                    <div class="mfst-section-head">
                        <div class="mfst-section-title">
                            <span>{{ $sectionLabel }}</span>
                            <span class="mfst-section-sub">
                                {{ $sectionApproved + $sectionPending }} / {{ $sectionTotal }}
                                · ✕ {{ $sectionBlocked }}
                            </span>
                        </div>
                    </div>
                    <div class="mfst-stage">🎬 STAGE · المسرح</div>
                    @foreach ($byRow as $rletter => $seats)
                        @php
                            $rowBooked   = 0; $rowChecked = 0; $rowTotal = count($seats);
                            foreach ($seats as $s) {
                                if (in_array($s['status'], ['approved', 'pending'], true)) $rowBooked++;
                                if (!empty($s['is_scanned'])) $rowChecked++;
                            }
                        @endphp
                        <div class="mfst-row">
                            <div class="mfst-row-label">
                                <span class="row-letter">{{ $rletter }}</span>
                                <span class="row-stats">
                                    {{ $rowBooked }}/{{ $rowTotal }}
                                    @if ($rowChecked > 0) <span class="ck">✓{{ $rowChecked }}</span> @endif
                                </span>
                            </div>
                            <div class="mfst-row-seats">
                                @foreach ($seats as $s)
                                    @php
                                        $seatLabel = $s['row_letter'] . $s['seat_number'];
                                        $hue = $s['booking_id'] ? ($bookingColorIndex[$s['booking_id']] ?? null) : null;
                                    @endphp
                                    <button type="button"
                                            class="mfst-seat is-{{ $s['status'] }} {{ $hue !== null ? 'mfst-hue-' . $hue : '' }} js-seat"
                                            data-seat="{{ $seatLabel }}"
                                            data-status="{{ $s['status'] }}"
                                            data-section="{{ $s['section_label_ar'] }}"
                                            data-section-en="{{ $s['section_label_en'] }}"
                                            data-row="{{ $s['row_letter'] }}"
                                            data-seat-num="{{ $s['seat_number'] }}"
                                            @if ($hue !== null) data-hue="{{ $hue }}" @endif
                                            data-booking-id="{{ $s['booking_id'] ?? '' }}"
                                            data-booking-ref="{{ $s['booking_ref'] ?? '' }}"
                                            data-name="{{ $s['attendee_name'] ?? '' }}"
                                            data-owner="{{ $s['booking_owner'] ?? '' }}"
                                            data-phone="{{ $s['phone'] ? $maskPhone($s['phone']) : '' }}"
                                            data-status-en="{{ $s['status_en'] }}"
                                            data-checked="{{ $s['is_scanned'] ? '1' : '0' }}"
                                            data-scanned-at="{{ $s['scanned_at'] ?? '' }}"
                                            data-haystack="@php echo strtolower(trim(($s['attendee_name'] ?? '') . ' ' . ($s['booking_owner'] ?? '') . ' ' . ($s['booking_ref'] ?? '') . ' ' . ($s['phone'] ?? '') . ' ' . $seatLabel . ' ' . $s['section_label_ar'])); @endphp"
                                            aria-label="Seat {{ $seatLabel }} ({{ $s['status_en'] }})">
                                        <span>{{ $s['seat_number'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </section>

        <aside class="mfst-rail" aria-label="Detail rail">
            <div class="mfst-rail-card mfst-seat-detail js-seat-detail" style="display:none;">
                <div class="seat-head">
                    <div>
                        <div class="seat-big js-detail-seat">—</div>
                        <div class="seat-section js-detail-section"></div>
                    </div>
                    <button type="button" class="prism-btn prism-btn-ghost js-detail-close" aria-label="Close detail">✕</button>
                </div>
                <div class="seat-name js-detail-name"></div>
                <div class="seat-meta">
                    <div><span class="label">Status:</span><span class="js-detail-status"></span></div>
                    <div><span class="label">Booking:</span><span class="js-detail-booking"></span></div>
                    <div><span class="label">Owner:</span><span class="js-detail-owner"></span></div>
                    <div><span class="label">Phone:</span><span class="js-detail-phone"></span></div>
                    <div class="js-detail-scan-row" style="display:none;"><span class="label">Checked in:</span><span class="js-detail-scan"></span></div>
                </div>
                <div class="seat-actions">
                    <a href="#" class="js-detail-scanlink" target="_self">📷 Scan to verify</a>
                </div>
                <div class="seat-party js-detail-party" style="display:none;">
                    <div class="label">Booking party</div>
                    <ul class="js-detail-party-list"></ul>
                </div>
            </div>

            <div class="mfst-rail-empty js-rail-empty">
                <div>Select a seat or a booking to see details.</div>
                <div class="kbd-hint">
                    <kbd>/</kbd> search · <kbd>Esc</kbd> clear
                </div>
            </div>

            <div class="mfst-rail-card mfst-bookings-card">
                <div class="mfst-bookings-head">
                    <span class="title">Bookings</span>
                    <span class="count">{{ count($bookings) }}</span>
                </div>
                <div class="mfst-bookings-list js-bookings-list">
                    @foreach ($bookings as $b)
                        @php $hue = $bookingColorIndex[$b['id']] ?? 0; @endphp
                        <div class="row mfst-hue-{{ $hue }} js-booking-row"
                             data-booking-id="{{ $b['id'] }}"
                             data-booking-ref="{{ $b['ref'] }}"
                             data-seats="{{ implode(',', $b['seat_labels']) }}"
                             data-haystack="@php echo strtolower(($b['owner'] ?? '') . ' ' . ($b['ref'] ?? '') . ' ' . ($b['phone'] ?? '')); @endphp">
                            <span class="dot" aria-hidden="true"></span>
                            <div class="body">
                                <div class="l1">{{ $b['owner'] ?? '—' }}</div>
                                <div class="l2">{{ $b['ref'] }} · {{ strtolower($b['status_en']) }}</div>
                            </div>
                            <div class="meta">
                                <div><span class="seats">{{ count($b['seats']) }}</span> seats</div>
                                @if ($b['checked_in'] > 0)
                                    <div style="color: var(--m-emerald);">✓ {{ $b['checked_in'] }}/{{ count($b['seats']) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
    @endif

    {{-- ============================================================
         FLOOR MODE
         ============================================================ --}}
    @if ($mode === 'floor')
    <div class="mfst-floor" data-floor>
        <div class="mfst-floor-filters" role="tablist" aria-label="Status filter">
            <button type="button" class="mfst-chip mfst-chip-approved js-filter-chip" data-status="approved" aria-pressed="{{ in_array('approved', $statusFilter) ? 'true' : 'false' }}">
                <span>Approved</span><span class="count">{{ $summary['approved'] }}</span>
            </button>
            <button type="button" class="mfst-chip mfst-chip-pending js-filter-chip" data-status="pending" aria-pressed="{{ in_array('pending', $statusFilter) ? 'true' : 'false' }}">
                <span>Pending</span><span class="count">{{ $summary['pending'] }}</span>
            </button>
            <button type="button" class="mfst-chip mfst-chip-blocked js-filter-chip" data-status="blocked" aria-pressed="{{ in_array('blocked', $statusFilter) ? 'true' : 'false' }}">
                <span>Blocked</span><span class="count">{{ $summary['blocked'] }}</span>
            </button>
            <button type="button" class="mfst-chip mfst-chip-checked js-filter-chip" data-status="checked_in" aria-pressed="{{ in_array('checked_in', $statusFilter) ? 'true' : 'false' }}">
                <span>✓ Checked</span><span class="count js-filter-checked-count">{{ $checkedInCount }}</span>
            </button>
            <button type="button" class="mfst-chip mfst-chip-empty js-filter-chip" data-status="empty" aria-pressed="{{ in_array('empty', $statusFilter) ? 'true' : 'false' }}">
                <span>Empty</span><span class="count">{{ $summary['empty'] }}</span>
            </button>
        </div>

        <div class="mfst-floor-list js-floor-list">
            @foreach ($rows as $s)
                @php
                    $seatLabel = $s['row_letter'] . $s['seat_number'];
                    $hue = $s['booking_id'] ? ($bookingColorIndex[$s['booking_id']] ?? null) : null;
                    $statusPillKey = $s['status'];
                @endphp
                <div class="mfst-card is-{{ $s['status'] }} @if($s['is_scanned']) is-scanned @endif {{ $hue !== null ? 'mfst-hue-' . $hue : '' }} js-floor-card"
                     data-seat="{{ $seatLabel }}"
                     data-status="{{ $s['status'] }}"
                     data-checked="{{ $s['is_scanned'] ? '1' : '0' }}"
                     @if ($hue !== null) data-hue="{{ $hue }}" @endif
                     data-haystack="@php echo strtolower(trim(($s['attendee_name'] ?? '') . ' ' . ($s['booking_owner'] ?? '') . ' ' . ($s['booking_ref'] ?? '') . ' ' . ($s['phone'] ?? '') . ' ' . $seatLabel . ' ' . $s['section_label_ar'])); @endphp"
                     role="button"
                     tabindex="0">
                    <div class="card-chip">
                        <span class="seat">{{ $seatLabel }}</span>
                        <span class="sec">{{ $s['section_label_ar'] }}</span>
                    </div>
                    <div class="card-body">
                        <div class="card-name">
                            @if ($s['status'] === 'blocked')
                                — محجوب —
                            @elseif ($s['status'] === 'empty')
                                — فارغ —
                            @else
                                {{ $s['attendee_name'] ?? '—' }}
                            @endif
                        </div>
                        <div class="card-meta">
                            @if ($s['status'] === 'approved')
                                <span class="pill ok">APPROVED</span>
                            @elseif ($s['status'] === 'pending')
                                <span class="pill pen">PENDING</span>
                            @elseif ($s['status'] === 'blocked')
                                <span class="pill blk">BLOCKED</span>
                            @else
                                <span class="pill emp">EMPTY</span>
                            @endif
                            @if ($s['is_scanned'])
                                <span class="pill ck">✓ {{ $s['scanned_at'] }}</span>
                            @endif
                            @if ($s['booking_ref'])
                                <span>{{ $s['booking_ref'] }}</span>
                            @endif
                            @if ($s['phone'])
                                <span>{{ $maskPhone($s['phone']) }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ $url(['mode' => 'ops', 'focus' => $seatLabel]) }}" class="card-action" aria-label="Show {{ $seatLabel }} on chart" data-no-card-click>↗</a>
                </div>
            @endforeach
        </div>

        <div class="mfst-floor-empty js-floor-empty" style="display:none;">
            No seats match the current filter.
        </div>

        <div class="mfst-floor-dock pt-no-print">
            <label class="mfst-search">
                <input type="search"
                       class="js-search-input"
                       placeholder="🔍 اسم / رقم / مقعد"
                       aria-label="Search"
                       autocomplete="off"
                       autocapitalize="off">
                <span class="icon">🔎</span>
            </label>
            <a href="/admin/scanner" class="mfst-fab" aria-label="Open QR scanner">📷</a>
        </div>
    </div>
    @endif

    {{-- ============================================================
         PAPER SHEET — A4 landscape
         ============================================================ --}}
    @if ($mode === 'paper')
    <div class="mfst-paper-actions pt-no-print">
        <a href="{{ $url(['mode' => 'paper', 'include_empty' => $includeEmpty ? 0 : 1]) }}">
            {{ $includeEmpty ? 'Hide empty seats' : 'Include empty seats (full roll-call)' }}
        </a>
        <a href="{{ $url(['mode' => 'ops']) }}">← Back to Operations</a>
        <button type="button" class="js-print-now">🖨 Print this sheet</button>
    </div>

    <div class="mfst-paper">
        <div class="mfst-paper-head">
            <div>
                <div class="t1">{{ $showTitle }}</div>
                <div class="t2">{{ $eventDate }} @if ($eventTime) · {{ $eventTime }} @endif</div>
            </div>
            <div class="stats">
                <strong>{{ $summary['approved'] }}</strong> Approved &nbsp;·&nbsp;
                <strong>{{ $summary['pending'] }}</strong> Pending &nbsp;·&nbsp;
                <strong>{{ $summary['blocked'] }}</strong> Blocked &nbsp;·&nbsp;
                <strong>{{ $summary['empty'] }}</strong> Empty<br>
                <strong>{{ $summary['total'] }}</strong> Total seats &nbsp;·&nbsp;
                <strong>{{ $checkedInCount }}</strong> ✓ checked-in
            </div>
        </div>

        @foreach ($paperRowsBySectionRow as $sectionLabel => $byRow)
            @php
                $secApproved = 0; $secPending = 0; $secBlocked = 0; $secEmpty = 0; $secTotal = 0;
                foreach ($byRow as $rletter => $seats) {
                    foreach ($seats as $s) {
                        $secTotal++;
                        $secApproved += $s['status'] === 'approved' ? 1 : 0;
                        $secPending  += $s['status'] === 'pending'  ? 1 : 0;
                        $secBlocked  += $s['status'] === 'blocked'  ? 1 : 0;
                        $secEmpty    += $s['status'] === 'empty'    ? 1 : 0;
                    }
                }
            @endphp
            <section class="mfst-paper-section">
                <div class="mfst-paper-section-head">
                    <span>{{ $sectionLabel }}</span>
                    <span>{{ $secApproved + $secPending }} attendees · {{ $secBlocked }} blocked @if ($includeEmpty) · {{ $secEmpty }} empty @endif</span>
                </div>
                <table class="mfst-paper-table">
                    <thead>
                        <tr>
                            <th style="width:42px;">Row</th>
                            <th style="width:22px;"></th>
                            <th style="width:60px;">Seat</th>
                            <th>Attendee</th>
                            <th>Booking</th>
                            <th style="width:160px;">Phone</th>
                            <th style="width:30px;">☐</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byRow as $rletter => $seats)
                            @foreach ($seats as $i => $s)
                                @php
                                    $seatLabel = $s['row_letter'] . $s['seat_number'];
                                    $hue = $s['booking_id'] ? ($bookingColorIndex[$s['booking_id']] ?? null) : null;
                                @endphp
                                <tr class="is-{{ $s['status'] }}" @if ($hue !== null) data-hue="{{ $hue }}" @endif>
                                    @if ($i === 0)
                                        <td rowspan="{{ count($seats) }}" style="text-align:center; font-weight:800; font-size:14pt; background:#fafafa;">{{ $rletter }}</td>
                                    @endif
                                    <td class="t-glyph">{{ $statusGlyph[$s['status']] ?? '·' }}</td>
                                    <td class="t-seat">{{ $seatLabel }}</td>
                                    <td class="t-name">
                                        @if ($s['status'] === 'blocked')
                                            BLOCKED
                                        @elseif ($s['status'] === 'empty')
                                            —
                                        @else
                                            {{ $s['attendee_name'] }}
                                            @if ($s['booking_owner'] && $s['booking_owner'] !== $s['attendee_name'])
                                                <span style="color:#666; font-weight:400; font-size:8.5pt;"> · {{ $s['booking_owner'] }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="t-ref">
                                        @if ($s['booking_ref'])
                                            {{ $s['booking_ref'] }}
                                            @if ($s['is_scanned'])
                                                <span style="color:#047857; font-weight:800;"> · ✓</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="t-phone">{{ $maskPhone($s['phone']) }}</td>
                                    <td class="t-check">☐</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
                <div class="mfst-paper-rowsumm">
                    @foreach ($byRow as $rletter => $seats)
                        @php
                            $rb = 0; $rt = count($seats);
                            foreach ($seats as $s) if (in_array($s['status'], ['approved','pending'], true)) $rb++;
                        @endphp
                        <span>Row {{ $rletter }}: <strong>{{ $rb }}</strong>/{{ $rt }}</span>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="mfst-paper-legend">
            <span><span class="g">●</span> Approved</span>
            <span><span class="g">◐</span> Pending</span>
            <span><span class="g">✕</span> Blocked</span>
            <span><span class="g">☐</span> Mark check-in by hand</span>
            <span style="margin-inline-start:auto;">Printed {{ now()->format('Y-m-d H:i') }} · Phones masked except last 4 · Family color band = same booking</span>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const root = document.querySelector('.manifest-root');
    if (!root) return;

    const mode = root.dataset.mode || 'ops';

    /* ====================================================================
       Capacity gauge — animate the fill bar to the booked-percentage
       ==================================================================== */
    function animateGauge() {
        const fill = document.querySelector('.js-gauge-fill');
        if (!fill) return;
        const pct = Math.max(0, Math.min(100, parseInt(fill.dataset.pct || '0', 10)));
        requestAnimationFrame(() => { fill.style.width = pct + '%'; });
    }
    animateGauge();

    /* ====================================================================
       Overflow menu (⋯)
       ==================================================================== */
    const overflowBtn = document.querySelector('.js-overflow-toggle');
    const overflowMenu = document.querySelector('.js-overflow-menu');
    if (overflowBtn && overflowMenu) {
        overflowBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = overflowMenu.classList.toggle('is-open');
            overflowBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', (e) => {
            if (!overflowMenu.contains(e.target) && e.target !== overflowBtn) {
                overflowMenu.classList.remove('is-open');
                overflowBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ====================================================================
       Auto-print when the user lands with #autoprint or ?autoprint=1
       ==================================================================== */
    if (mode === 'paper') {
        const wantsAutoprint = window.location.hash === '#autoprint'
                            || /[?&]autoprint=1/.test(window.location.search);
        if (wantsAutoprint) {
            setTimeout(() => { try { window.print(); } catch (e) {} }, 350);
        }
        const printBtn = document.querySelector('.js-print-now');
        if (printBtn) printBtn.addEventListener('click', () => window.print());
    }

    /* ====================================================================
       Status filter chips — toggle which statuses are visible on the
       chart (ops) or in the card list (floor). URL is updated so the
       filter state is shareable + survives refresh.
       ==================================================================== */
    function getActiveStatuses() {
        return Array.from(document.querySelectorAll('.js-filter-chip[aria-pressed="true"]'))
            .map(b => b.dataset.status);
    }
    function applyFilterToOpsChart() {
        const chart = document.querySelector('.js-chart');
        if (!chart) return;
        const active = new Set(getActiveStatuses());
        chart.classList.toggle('hide-empty',    !active.has('empty'));
        chart.classList.toggle('hide-approved', !active.has('approved'));
        chart.classList.toggle('hide-pending',  !active.has('pending'));
        chart.classList.toggle('hide-blocked',  !active.has('blocked'));
        chart.classList.toggle('only-checked',   active.has('checked_in'));
    }
    function applyFilterToFloorList() {
        const cards = document.querySelectorAll('.js-floor-card');
        if (!cards.length) return;
        const active = new Set(getActiveStatuses());
        let visible = 0;
        cards.forEach(card => {
            const status   = card.dataset.status;
            const checked  = card.dataset.checked === '1';
            const passesStatus = active.has(status);
            const passesChecked = !active.has('checked_in') || checked;
            const show = passesStatus && passesChecked;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const empty = document.querySelector('.js-floor-empty');
        if (empty) empty.style.display = visible === 0 ? '' : 'none';
        // Re-apply search filter on top of the status filter
        applySearch();
    }
    function applyFilter() {
        if (mode === 'ops') applyFilterToOpsChart();
        if (mode === 'floor') applyFilterToFloorList();
        syncFilterToUrl();
    }
    function syncFilterToUrl() {
        const active = getActiveStatuses();
        const url = new URL(window.location.href);
        if (active.length === 0) {
            url.searchParams.set('status', 'none');
        } else {
            url.searchParams.set('status', active.join(','));
        }
        window.history.replaceState({}, '', url);
    }
    document.querySelectorAll('.js-filter-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            const cur = btn.getAttribute('aria-pressed') === 'true';
            btn.setAttribute('aria-pressed', cur ? 'false' : 'true');
            applyFilter();
        });
    });
    applyFilter();

    /* ====================================================================
       Search — filters the same dataset (chart seats / floor cards) by
       a `data-haystack` blob assembled server-side.
       ==================================================================== */
    let searchTerm = '';
    function applySearch() {
        if (mode === 'ops') {
            const seats = document.querySelectorAll('.js-seat');
            const q = searchTerm.trim().toLowerCase();
            if (!q) {
                seats.forEach(s => s.style.opacity = '');
                return;
            }
            seats.forEach(s => {
                const hay = s.dataset.haystack || '';
                s.style.opacity = hay.includes(q) ? '' : '0.18';
            });
            // Also highlight matching booking rows
            document.querySelectorAll('.js-booking-row').forEach(r => {
                const hay = r.dataset.haystack || '';
                r.style.display = (!q || hay.includes(q)) ? '' : 'none';
            });
        } else if (mode === 'floor') {
            const cards = document.querySelectorAll('.js-floor-card');
            const q = searchTerm.trim().toLowerCase();
            let visible = 0;
            cards.forEach(c => {
                if (c.style.display === 'none' && !q) return; // already filtered
                const hay = c.dataset.haystack || '';
                const matchesQuery = !q || hay.includes(q);
                if (!matchesQuery) {
                    c.style.display = 'none';
                } else if (c.dataset._statusHidden !== '1') {
                    c.style.display = '';
                    visible++;
                }
            });
            const empty = document.querySelector('.js-floor-empty');
            if (empty) {
                const anyVisible = Array.from(cards).some(c => c.style.display !== 'none');
                empty.style.display = anyVisible ? 'none' : '';
            }
        }
    }
    document.querySelectorAll('.js-search-input').forEach(input => {
        input.addEventListener('input', (e) => {
            searchTerm = e.target.value;
            applySearch();
        });
    });
    // `/` keyboard shortcut → focus the first visible search input
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
            const input = document.querySelector('.js-search-input');
            if (input) {
                e.preventDefault();
                input.focus();
                input.select();
            }
        }
        if (e.key === 'Escape') {
            document.querySelectorAll('.js-search-input').forEach(i => { if (i.value) i.value = ''; });
            searchTerm = '';
            applySearch();
            const detail = document.querySelector('.js-seat-detail');
            if (detail) detail.style.display = 'none';
            const railEmpty = document.querySelector('.js-rail-empty');
            if (railEmpty) railEmpty.style.display = '';
            // Clear booking highlight
            document.querySelectorAll('.is-booking-highlight').forEach(el => el.classList.remove('is-booking-highlight'));
            document.querySelectorAll('.js-booking-row.is-active').forEach(el => el.classList.remove('is-active'));
            document.querySelectorAll('.is-selected').forEach(el => el.classList.remove('is-selected'));
        }
    });

    /* ====================================================================
       Operations — seat selection drives the detail rail
       ==================================================================== */
    function openSeatDetail(seatEl) {
        const detail = document.querySelector('.js-seat-detail');
        const empty  = document.querySelector('.js-rail-empty');
        if (!detail || !seatEl) return;

        document.querySelectorAll('.mfst-seat.is-selected').forEach(s => s.classList.remove('is-selected'));
        seatEl.classList.add('is-selected');

        detail.style.display = '';
        if (empty) empty.style.display = 'none';

        const d = seatEl.dataset;
        detail.querySelector('.js-detail-seat').textContent     = d.seat;
        detail.querySelector('.js-detail-section').textContent  = (d.section || '') + ' · ' + (d.sectionEn || '');
        detail.querySelector('.js-detail-name').textContent     = d.name || (d.status === 'blocked' ? '— محجوب —' : (d.status === 'empty' ? '— فارغ —' : '—'));
        detail.querySelector('.js-detail-status').textContent   = d.statusEn || '';
        detail.querySelector('.js-detail-booking').textContent  = d.bookingRef || '—';
        detail.querySelector('.js-detail-owner').textContent    = d.owner || '—';
        detail.querySelector('.js-detail-phone').textContent    = d.phone || '—';

        const scanRow = detail.querySelector('.js-detail-scan-row');
        if (d.checked === '1') {
            scanRow.style.display = '';
            detail.querySelector('.js-detail-scan').textContent = d.scannedAt || '—';
        } else {
            scanRow.style.display = 'none';
        }

        // Scanner deep-link
        const scanLink = detail.querySelector('.js-detail-scanlink');
        if (scanLink) {
            scanLink.href = '/admin/scanner?expect=' + encodeURIComponent(d.seat);
        }

        // Party — show all seats from the same booking
        const partyWrap = detail.querySelector('.js-detail-party');
        const partyList = detail.querySelector('.js-detail-party-list');
        if (d.bookingId) {
            const partySeats = document.querySelectorAll('.mfst-seat[data-booking-id="' + d.bookingId + '"]');
            if (partySeats.length > 1) {
                partyList.innerHTML = '';
                partySeats.forEach(p => {
                    const li = document.createElement('li');
                    if (p.dataset.seat === d.seat) li.classList.add('is-current');
                    li.innerHTML = '<span class="lbl">' + p.dataset.seat + '</span> <span class="nm">' + (p.dataset.name || '—') + '</span>';
                    partyList.appendChild(li);
                });
                partyWrap.style.display = '';
            } else {
                partyWrap.style.display = 'none';
            }
        } else {
            partyWrap.style.display = 'none';
        }
    }

    document.querySelectorAll('.js-seat').forEach(seat => {
        seat.addEventListener('click', () => openSeatDetail(seat));
    });

    const detailClose = document.querySelector('.js-detail-close');
    if (detailClose) {
        detailClose.addEventListener('click', () => {
            const detail = document.querySelector('.js-seat-detail');
            const empty  = document.querySelector('.js-rail-empty');
            if (detail) detail.style.display = 'none';
            if (empty)  empty.style.display = '';
            document.querySelectorAll('.is-selected').forEach(el => el.classList.remove('is-selected'));
        });
    }

    /* ====================================================================
       Bookings sidebar — click a row to light up its seats on the chart
       ==================================================================== */
    document.querySelectorAll('.js-booking-row').forEach(row => {
        row.addEventListener('click', () => {
            document.querySelectorAll('.js-booking-row.is-active').forEach(r => r.classList.remove('is-active'));
            row.classList.add('is-active');

            document.querySelectorAll('.is-booking-highlight').forEach(s => s.classList.remove('is-booking-highlight'));

            const seats = (row.dataset.seats || '').split(',').filter(Boolean);
            if (!seats.length) return;

            const first = document.querySelector('.mfst-seat[data-seat="' + seats[0] + '"]');
            seats.forEach(label => {
                const el = document.querySelector('.mfst-seat[data-seat="' + label + '"]');
                if (el) el.classList.add('is-booking-highlight');
            });

            if (first) {
                first.scrollIntoView({ block: 'center', behavior: 'smooth' });
                openSeatDetail(first);
            }
        });
    });

    /* ====================================================================
       Floor — tapping a card opens the seat detail (here we just scroll
       to it / pulse). The trailing ↗ button goes to ops mode.
       ==================================================================== */
    document.querySelectorAll('.js-floor-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('[data-no-card-click]')) return;
            card.classList.add('is-focused');
            setTimeout(() => card.classList.remove('is-focused'), 1500);
        });
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });
    });

    /* ====================================================================
       Initial focus — if the URL has ?focus=A12, scroll there + pulse
       ==================================================================== */
    const focusSeat = root.dataset.focusSeat || '';
    if (focusSeat) {
        const target = document.querySelector('[data-seat="' + focusSeat + '"]');
        if (target) {
            requestAnimationFrame(() => {
                target.scrollIntoView({ block: 'center', behavior: 'smooth' });
                target.classList.add('is-focused');
                if (target.classList.contains('mfst-seat')) {
                    openSeatDetail(target);
                }
            });
        }
    }

    /* ====================================================================
       Live polling — every 10 s, refresh capacity gauge + ✓ markers
       ==================================================================== */
    function startPolling() {
        const url = root.dataset.pollUrl;
        if (!url) return;
        const capacity = parseInt(root.dataset.capacity || '1', 10) || 1;

        async function tick() {
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                if (!res.ok) return;
                const data = await res.json();
                if (!data || !data.summary) return;

                // Gauge: total booked / capacity
                const booked = (data.summary.approved || 0) + (data.summary.pending || 0);
                const num = document.querySelector('.js-gauge-num');
                if (num) num.textContent = booked + ' / ' + capacity;
                const fill = document.querySelector('.js-gauge-fill');
                if (fill) {
                    const pct = Math.max(0, Math.min(100, Math.round((booked / capacity) * 100)));
                    fill.dataset.pct = String(pct);
                    fill.style.width = pct + '%';
                }
                const checkedNum = document.querySelector('.js-checked-num');
                if (checkedNum) checkedNum.textContent = String(data.checked_in || 0);

                // Per-seat ✓ markers
                const scanned = data.scanned_seats || {};
                Object.keys(scanned).forEach(label => {
                    document.querySelectorAll('[data-seat="' + label + '"]').forEach(el => {
                        if (el.dataset.checked !== '1') {
                            el.dataset.checked = '1';
                            el.classList.add('is-scanned');
                        }
                        if (!el.dataset.scannedAt && scanned[label]) {
                            el.dataset.scannedAt = scanned[label];
                        }
                    });
                });

                // Filter chip count
                const ck = document.querySelector('.js-filter-checked-count');
                if (ck) ck.textContent = String(data.checked_in || 0);
            } catch (e) {
                /* silent — polling continues */
            }
        }

        tick();
        return setInterval(tick, 10000);
    }
    if (mode !== 'paper') startPolling();

})();
</script>
@endpush
