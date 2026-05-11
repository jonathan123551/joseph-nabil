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

    // Floor Mode list grouping — section -> row -> seats. Lets us drop
    // sticky section + row dividers between cards so an usher walking the
    // hall always knows which arc/row they're scanning, instead of staring
    // at a flat list of seat labels with no anchor.
    $floorRowsBySectionRow = [];
    foreach ($rowsBySectionRow as $sectionLabel => $byRow) {
        foreach ($byRow as $rowLetter => $seats) {
            $secApproved = 0; $secPending = 0; $secBlocked = 0;
            foreach ($seats as $s) {
                if ($s['status'] === 'approved') $secApproved++;
                elseif ($s['status'] === 'pending')  $secPending++;
                elseif ($s['status'] === 'blocked')  $secBlocked++;
            }
            $floorRowsBySectionRow[$sectionLabel][$rowLetter] = [
                'seats'   => $seats,
                'booked'  => $secApproved + $secPending,
                'blocked' => $secBlocked,
                'total'   => count($seats),
            ];
        }
    }

    // Per-section attendance summary for sticky section banners.
    $floorSectionStats = [];
    foreach ($floorRowsBySectionRow as $sectionLabel => $byRow) {
        $sb = 0; $sblk = 0; $st = 0;
        foreach ($byRow as $rowLetter => $blk) {
            $sb   += $blk['booked'];
            $sblk += $blk['blocked'];
            $st   += $blk['total'];
        }
        $floorSectionStats[$sectionLabel] = [
            'booked'  => $sb,
            'blocked' => $sblk,
            'total'   => $st,
        ];
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
       Manifest tokens — shared across all three surfaces.
       Calmer palette + a tiny motion system so every surface feels
       consistent rather than "busy enterprise dashboard".
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
        --m-ease          : cubic-bezier(.22,.61,.36,1);
        --m-radius-card   : 18px;
        --m-radius-pill   : 999px;
        --m-shadow-card   : 0 8px 24px rgba(0,0,0,0.18);
        --m-shadow-focus  : 0 0 0 2px rgba(56,189,248,0.55), 0 0 18px rgba(56,189,248,0.22);
    }
    .manifest-root, .manifest-root * {
        font-feature-settings: "tnum" 1, "ss01" 1;
    }
    .manifest-root :focus-visible {
        outline: none;
        box-shadow: var(--m-shadow-focus);
        border-radius: 8px;
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
        backdrop-filter: blur(20px) saturate(140%);
        -webkit-backdrop-filter: blur(20px) saturate(140%);
        background: linear-gradient(180deg, rgba(8,9,18,0.72), rgba(8,9,18,0.42));
        border: 1px solid var(--m-border);
        border-radius: 18px;
        padding: 12px 16px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 14px;
        align-items: center;
        margin-bottom: 14px;
        box-shadow: var(--m-shadow-card);
    }
    .mfst-topbar-id { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
    .mfst-topbar-title {
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .01em;
        color: var(--m-text);
        display: flex;
        align-items: center;
        gap: 8px;
        line-height: 1.15;
    }
    .mfst-topbar-title .live-dot {
        width: 8px; height: 8px; border-radius: 999px;
        background: var(--m-emerald);
        box-shadow: 0 0 10px rgba(52,211,153,0.75);
        animation: mfst-pulse-soft 2.4s ease-in-out infinite;
        flex: 0 0 8px;
    }
    @keyframes mfst-pulse-soft { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .45; transform: scale(.85); } }
    .mfst-topbar-meta {
        font-size: 11.5px;
        color: var(--m-text-3);
        letter-spacing: .02em;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .mfst-topbar-meta > span { white-space: nowrap; }

    .mfst-topbar-actions { display: flex; gap: 8px; align-items: center; }
    .mfst-mode-switch {
        display: inline-flex;
        border: 1px solid var(--m-border);
        border-radius: 999px;
        overflow: hidden;
        background: rgba(255,255,255,0.03);
        padding: 3px;
        gap: 2px;
    }
    .mfst-mode-switch a {
        padding: 7px 14px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .02em;
        color: var(--m-text-3);
        transition: background .2s var(--m-ease), color .2s var(--m-ease);
        white-space: nowrap;
        border-radius: 999px;
    }
    .mfst-mode-switch a:hover { color: var(--m-text); background: rgba(255,255,255,0.05); }
    .mfst-mode-switch a.is-active {
        color: #fff;
        background: linear-gradient(135deg, rgba(56,189,248,0.32), rgba(167,139,250,0.32));
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.10);
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
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        color: var(--m-text-2);
    }
    .mfst-gauge-bar {
        position: relative;
        width: clamp(120px, 18vw, 220px);
        height: 5px;
        border-radius: 999px;
        background: rgba(255,255,255,0.07);
        overflow: hidden;
    }
    .mfst-gauge-bar .fill {
        position: absolute;
        inset-inline-start: 0; top: 0; bottom: 0;
        width: 0%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--m-emerald), var(--m-sky));
        transition: width .9s var(--m-ease);
        box-shadow: 0 0 12px rgba(52,211,153,0.35);
    }
    .mfst-gauge-num { font-weight: 800; color: var(--m-text); }

    /* ====================================================================
       Filter strip — sticky chips just under the top bar. Status + section
       + search. URL-encoded so refresh preserves intent.
       ==================================================================== */
    .mfst-filters {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        padding: 6px 2px 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .mfst-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 12px;
        border-radius: 999px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.025);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .01em;
        color: var(--m-text-3);
        cursor: pointer;
        user-select: none;
        transition: background .2s var(--m-ease), color .2s var(--m-ease),
                    border-color .2s var(--m-ease), transform .15s var(--m-ease);
    }
    .mfst-chip:hover { transform: translateY(-1px); color: var(--m-text); border-color: rgba(255,255,255,0.18); }
    .mfst-chip:active { transform: translateY(0); }
    .mfst-chip[aria-pressed="true"] {
        color: var(--m-text);
        border-color: var(--m-chip-color, rgba(167,139,250,0.55));
        background: var(--m-chip-bg, rgba(167,139,250,0.14));
        box-shadow: inset 0 0 0 1px var(--m-chip-color, rgba(167,139,250,0.30));
    }
    .mfst-chip .count {
        color: var(--m-text-3);
        font-weight: 700;
        font-size: 11px;
        padding: 1px 7px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
        min-width: 18px;
        text-align: center;
    }
    .mfst-chip[aria-pressed="true"] .count {
        color: #fff;
        background: var(--m-chip-color, rgba(167,139,250,0.7));
    }
    .mfst-chip-approved   { --m-chip-color: rgba(52,211,153,0.65);  --m-chip-bg: rgba(52,211,153,0.10);  }
    .mfst-chip-pending    { --m-chip-color: rgba(251,191,36,0.65);  --m-chip-bg: rgba(251,191,36,0.10);  }
    .mfst-chip-blocked    { --m-chip-color: rgba(251,113,133,0.65); --m-chip-bg: rgba(251,113,133,0.10); }
    .mfst-chip-checked    { --m-chip-color: rgba(56,189,248,0.65);  --m-chip-bg: rgba(56,189,248,0.10);  }
    .mfst-chip-empty      { --m-chip-color: rgba(255,255,255,0.35); --m-chip-bg: rgba(255,255,255,0.05); }

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
        background: rgba(255,255,255,0.03);
        color: var(--m-text);
        font-size: 13px;
        outline: none;
        transition: border-color .2s var(--m-ease), background .2s var(--m-ease), box-shadow .2s var(--m-ease);
    }
    .mfst-search input::placeholder { color: var(--m-text-3); opacity: .7; }
    .mfst-search input:focus {
        border-color: rgba(56,189,248,0.55);
        background: rgba(255,255,255,0.06);
        box-shadow: 0 0 0 3px rgba(56,189,248,0.12);
    }
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
        transition: opacity .15s var(--m-ease);
    }
    .mfst-search .clear {
        position: absolute;
        inset-inline-end: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 26px; height: 26px;
        border-radius: 999px;
        border: 0;
        background: rgba(255,255,255,0.10);
        color: var(--m-text-2);
        font-size: 11px;
        line-height: 1;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background .15s ease, transform .12s ease;
    }
    .mfst-search .clear:hover { background: rgba(255,255,255,0.18); color: var(--m-text); }
    .mfst-search .clear:active { transform: translateY(-50%) scale(.94); }
    /* When the clear button is visible, hide the keyboard hint to
       keep the right side of the input from feeling cluttered. */
    .mfst-search:has(.clear:not([hidden])) kbd { opacity: 0; pointer-events: none; }

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
        border-radius: 20px;
        background:
            radial-gradient(120% 60% at 50% -20%, rgba(167,139,250,0.06), transparent 60%),
            rgba(255,255,255,0.018);
        padding: 20px;
        min-height: 60vh;
    }

    /* Section block (Hall / Balcony) inside the chart */
    .mfst-section {
        margin-bottom: 22px;
    }
    .mfst-section:last-child { margin-bottom: 6px; }
    .mfst-section-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .mfst-section-title {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: var(--m-text-2);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .mfst-section-sub {
        font-size: 11px;
        color: var(--m-text-3);
    }

    .mfst-stage {
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .42em;
        text-transform: uppercase;
        color: rgba(167,139,250,0.7);
        padding: 7px 12px;
        border-radius: 10px;
        border: 1px solid rgba(167,139,250,0.18);
        background: linear-gradient(180deg, rgba(167,139,250,0.05), transparent);
        margin: 0 auto 14px;
        max-width: 220px;
    }

    /* Row of seats — letter + saturation gauge + chips */
    .mfst-row {
        display: grid;
        grid-template-columns: 64px 1fr;
        gap: 12px;
        align-items: center;
        padding: 5px 0;
    }
    .mfst-row + .mfst-row { border-top: 1px solid rgba(255,255,255,0.04); }
    .mfst-row-label {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
        font-weight: 800;
        font-size: 13px;
        color: var(--m-text);
        padding-inline-start: 4px;
    }
    .mfst-row-label .row-letter {
        font-size: 16px;
        font-weight: 800;
        line-height: 1;
        color: var(--m-text);
        text-align: center;
        letter-spacing: .02em;
    }
    .mfst-row-label .row-stats {
        font-size: 9.5px;
        color: var(--m-text-3);
        margin-top: 1px;
        text-align: center;
        letter-spacing: .02em;
    }
    .mfst-row-label .row-stats .ck { color: var(--m-emerald); font-weight: 700; }
    .mfst-row-label .row-bar {
        position: relative;
        height: 3px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
        overflow: hidden;
    }
    .mfst-row-label .row-bar .fill {
        position: absolute;
        inset-inline-start: 0; top: 0; bottom: 0;
        width: var(--row-pct, 0%);
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(52,211,153,0.85), rgba(56,189,248,0.85));
    }

    .mfst-row-seats {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: center;
    }

    /* Seat chip — booked seats are bold and instantly readable; empty
       seats fade into the background so the chart reads as data, not
       noise. Family hue is a left edge accent (drawn in ::before).
       ==================================================================== */
    .mfst-seat {
        --m-seat-bg: rgba(255,255,255,0.04);
        --m-seat-border: var(--m-border-strong);
        --m-seat-color: var(--m-text);
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 30px;
        padding: 0 7px;
        border-radius: 7px;
        font-size: 11.5px;
        font-weight: 700;
        line-height: 1;
        background: var(--m-seat-bg);
        border: 1px solid var(--m-seat-border);
        color: var(--m-seat-color);
        cursor: pointer;
        transition: transform .15s var(--m-ease),
                    border-color .2s var(--m-ease),
                    box-shadow .2s var(--m-ease),
                    background .2s var(--m-ease),
                    opacity .2s var(--m-ease);
    }
    .mfst-seat:hover { transform: translateY(-1px); border-color: rgba(255,255,255,0.5); box-shadow: 0 4px 12px rgba(0,0,0,0.25); }
    .mfst-seat.is-approved {
        --m-seat-bg: rgba(52,211,153,0.16);
        --m-seat-border: rgba(52,211,153,0.55);
        --m-seat-color: #ecfdf5;
    }
    .mfst-seat.is-pending {
        --m-seat-bg: rgba(251,191,36,0.14);
        --m-seat-border: rgba(251,191,36,0.55);
        --m-seat-color: #fffbeb;
    }
    .mfst-seat.is-blocked {
        --m-seat-bg: repeating-linear-gradient(45deg, rgba(251,113,133,0.16), rgba(251,113,133,0.16) 3px, rgba(0,0,0,0.18) 3px, rgba(0,0,0,0.18) 5px);
        --m-seat-border: rgba(251,113,133,0.55);
        --m-seat-color: #ffe4e6;
        cursor: not-allowed;
    }
    .mfst-seat.is-empty {
        --m-seat-bg: transparent;
        --m-seat-border: rgba(255,255,255,0.07);
        --m-seat-color: rgba(255,255,255,0.32);
        font-weight: 500;
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
        background: rgba(255,255,255,0.02);
        padding: 16px;
        box-shadow: var(--m-shadow-card);
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
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -.005em;
        color: var(--m-text);
        line-height: 1;
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
       FLOOR MODE (mode=floor) — mobile-first, search-led, dark-forced.

       Layout rhythm:
         [topbar] → [hero search, sticky] → [chip strip, sticky just below]
         → [section banner > row mini-head > cards …]
       The search lives at the top, not the bottom, so iOS Safari's
       dynamic URL bar (which expands at the bottom edge while
       scrolling) never fights it. The Scanner FAB stays at the
       bottom-right where it's a single-thumb reach.
       ==================================================================== */
    [data-mode="floor"] {
        --m-bg            : #07070f;
        --m-border        : rgba(255,255,255,0.08);
        --m-border-strong : rgba(255,255,255,0.18);
        --m-text          : #f5f5f7;
        --m-text-2        : #d4d4d8;
        --m-text-3        : #8a8a93;
        color: var(--m-text);
    }
    [data-mode="floor"] .mfst-topbar {
        background: linear-gradient(180deg, rgba(0,0,0,0.92), rgba(0,0,0,0.65));
        border-color: rgba(255,255,255,0.06);
        padding: 10px 14px;
        border-radius: 16px;
    }

    .mfst-floor {
        display: flex;
        flex-direction: column;
        gap: 10px;
        /* Force a near-black background regardless of theme so a Floor
           Mode screen doesn't blind an usher in a dim hall. */
        background: #07070f;
        margin: -16px -16px 0;
        padding: 12px 14px calc(100px + env(safe-area-inset-bottom, 0px));
        min-height: calc(100vh - 80px);
        color: var(--m-text);
        position: relative;
    }

    /* Hero search — top-anchored, sticky just under the top bar so it
       stays within thumb reach but never collides with iOS Safari's
       dynamic chrome at the bottom of the viewport. Bigger touch
       target than the desktop search; 16px font so iOS doesn't auto-
       zoom on focus. */
    .mfst-floor-search {
        position: sticky;
        top: calc(64px + env(safe-area-inset-top, 0px) + 4px);
        z-index: 18;
        margin: 0 -8px;
        padding: 6px 8px 8px;
        background: linear-gradient(180deg, rgba(7,7,15,0.96) 70%, rgba(7,7,15,0));
        backdrop-filter: blur(10px) saturate(140%);
        -webkit-backdrop-filter: blur(10px) saturate(140%);
    }
    .mfst-floor-search .mfst-search {
        margin: 0;
        flex: 1 1 auto;
        min-width: 0;
    }
    .mfst-floor-search .mfst-search input {
        background: rgba(20,22,38,0.92);
        border-color: rgba(255,255,255,0.10);
        font-size: 16px;
        padding: 14px 46px 14px 46px;
        border-radius: 16px;
        min-height: 52px;
        color: var(--m-text);
        font-weight: 500;
        letter-spacing: .01em;
    }
    .mfst-floor-search .mfst-search input:focus {
        background: rgba(28,30,52,1);
        border-color: rgba(56,189,248,0.55);
        box-shadow: 0 0 0 3px rgba(56,189,248,0.18);
    }
    .mfst-floor-search .mfst-search .icon {
        font-size: 18px;
        inset-inline-end: auto;
        inset-inline-start: 14px;
        opacity: .55;
    }
    .mfst-floor-search .mfst-search .clear {
        position: absolute;
        inset-inline-end: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 32px; height: 32px;
        border-radius: 999px;
        border: 0;
        background: rgba(255,255,255,0.10);
        color: var(--m-text);
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background .15s ease, transform .12s ease;
    }
    .mfst-floor-search .mfst-search .clear:hover { background: rgba(255,255,255,0.16); }
    .mfst-floor-search .mfst-search .clear:active { transform: translateY(-50%) scale(.94); }
    .mfst-floor-search-hint {
        margin-top: 6px;
        padding: 0 6px;
        font-size: 11.5px;
        color: var(--m-text-3);
        letter-spacing: .02em;
        min-height: 14px;
    }
    .mfst-floor-search-hint.has-result {
        color: var(--m-sky);
    }

    /* Status chip strip — sits right below the hero search, sticky so
       it never scrolls away. Snap on x so swiping reveals all five
       chips on narrow screens. */
    .mfst-floor-filters {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        scroll-snap-type: x proximity;
        -webkit-overflow-scrolling: touch;
        padding: 4px 4px 10px;
        margin: -2px -10px 2px;
        scroll-padding-inline: 14px;
        position: sticky;
        top: calc(64px + env(safe-area-inset-top, 0px) + 4px + 78px);
        z-index: 16;
        background: linear-gradient(180deg, rgba(7,7,15,1) 60%, rgba(7,7,15,0));
    }
    .mfst-floor-filters::-webkit-scrollbar { display: none; }
    .mfst-floor-filters .mfst-chip {
        scroll-snap-align: start;
        white-space: nowrap;
        font-size: 12.5px;
        font-weight: 700;
        padding: 8px 12px;
        min-height: 36px;
        gap: 6px;
    }
    .mfst-floor-filters .mfst-chip .dot {
        width: 6px; height: 6px;
        border-radius: 999px;
        background: var(--m-chip-color, rgba(255,255,255,0.4));
        flex: 0 0 6px;
    }
    .mfst-floor-filters .mfst-chip .count {
        font-size: 10.5px;
        padding: 1px 7px;
    }
    .mfst-floor-filters .mfst-chip:first-child { margin-inline-start: 10px; }
    .mfst-floor-filters .mfst-chip:last-child  { margin-inline-end:   10px; }

    /* Card list grouped by section → row. Section + row dividers give the
       usher a constant orientation cue while scanning long lists. */
    .mfst-floor-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .mfst-floor-section { display: flex; flex-direction: column; gap: 6px; }
    .mfst-floor-section-head {
        position: sticky;
        top: calc(64px + env(safe-area-inset-top, 0px) + 4px + 78px + 56px);
        z-index: 14;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(167,139,250,0.10), rgba(255,255,255,0.02));
        border: 1px solid rgba(167,139,250,0.22);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        margin-bottom: 2px;
    }
    .mfst-floor-section-head .t {
        font-size: 14px;
        font-weight: 800;
        color: var(--m-text);
        letter-spacing: .01em;
    }
    .mfst-floor-section-head .s {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--m-text-2);
        font-feature-settings: "tnum" 1;
        padding: 2px 8px;
        border-radius: 999px;
        background: rgba(0,0,0,0.20);
        border: 1px solid rgba(255,255,255,0.08);
    }

    .mfst-floor-rowgroup {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .mfst-floor-rowgroup + .mfst-floor-rowgroup { margin-top: 4px; }
    .mfst-floor-rowhead {
        display: grid;
        grid-template-columns: 26px 1fr auto;
        gap: 10px;
        align-items: center;
        padding: 2px 8px 2px 4px;
        opacity: .82;
    }
    .mfst-floor-rowhead .r {
        font-size: 12px;
        font-weight: 800;
        color: var(--m-text-2);
        text-align: center;
        letter-spacing: .04em;
    }
    .mfst-floor-rowhead .b {
        position: relative;
        height: 3px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
        overflow: hidden;
    }
    .mfst-floor-rowhead .b .fill {
        position: absolute;
        inset-inline-start: 0; top: 0; bottom: 0;
        width: var(--row-pct, 0%);
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(52,211,153,0.85), rgba(56,189,248,0.85));
    }
    .mfst-floor-rowhead .n {
        font-size: 10.5px;
        color: var(--m-text-3);
        font-feature-settings: "tnum" 1;
        letter-spacing: .02em;
    }
    .mfst-floor-rowgroup:not(:has(.mfst-card:not([style*="display: none"]))) .mfst-floor-rowhead {
        display: none;
    }

    /* Floor card — single-line name + meta. Status accent on the
       leading edge does the heavy lifting; pills are reserved for the
       atypical cases (pending / blocked / checked-in) so an "approved"
       card feels calm. */
    .mfst-card {
        min-height: 76px;
        display: grid;
        grid-template-columns: 56px 1fr 22px;
        gap: 12px;
        align-items: center;
        padding: 10px 12px;
        border-radius: 16px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.025);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: transform .12s var(--m-ease), border-color .2s var(--m-ease), background .2s var(--m-ease);
    }
    .mfst-card::after {
        /* Status accent bar on the leading edge */
        content: "";
        position: absolute;
        inset-block: 10px;
        inset-inline-end: 0;
        width: 4px;
        border-radius: 4px;
        background: rgba(255,255,255,0.10);
        transition: background .2s var(--m-ease);
    }
    .mfst-card:active { transform: scale(0.985); }
    .mfst-card.is-approved { border-color: rgba(52,211,153,0.30); background: linear-gradient(90deg, rgba(52,211,153,0.05), rgba(255,255,255,0.018)); }
    .mfst-card.is-pending  { border-color: rgba(251,191,36,0.40); background: linear-gradient(90deg, rgba(251,191,36,0.06), rgba(255,255,255,0.018)); }
    .mfst-card.is-blocked  { border-color: rgba(251,113,133,0.40); background: linear-gradient(90deg, rgba(251,113,133,0.06), rgba(255,255,255,0.018)); }
    .mfst-card.is-empty    { border-color: rgba(255,255,255,0.06); background: rgba(255,255,255,0.012); opacity: .72; }
    .mfst-card.is-scanned  { border-color: rgba(52,211,153,0.70); background: linear-gradient(90deg, rgba(52,211,153,0.12), rgba(52,211,153,0.04)); }
    .mfst-card.is-approved::after { background: rgba(52,211,153,0.85); }
    .mfst-card.is-pending::after  { background: rgba(251,191,36,0.85); }
    .mfst-card.is-blocked::after  { background: rgba(251,113,133,0.85); }
    .mfst-card.is-scanned::after  { background: rgba(52,211,153,1);     box-shadow: 0 0 12px rgba(52,211,153,0.55); }
    .mfst-card.is-empty::after    { background: rgba(255,255,255,0.06); }
    .mfst-card.is-focused {
        border-color: var(--m-sky) !important;
        box-shadow: 0 0 0 2px rgba(56,189,248,0.30), 0 6px 22px rgba(56,189,248,0.18);
    }

    .mfst-card[data-hue]::before {
        content: "";
        position: absolute;
        inset-block: 12px;
        inset-inline-start: 0;
        width: 3px;
        border-radius: 4px;
        background: var(--m-hue);
    }
    .mfst-card .card-chip {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 6px 4px;
        border-radius: 12px;
        background: rgba(255,255,255,0.045);
        border: 1px solid rgba(255,255,255,0.08);
        min-width: 56px;
        min-height: 56px;
    }
    .mfst-card .card-chip .seat {
        font-size: 18px;
        font-weight: 800;
        color: var(--m-text);
        line-height: 1;
        letter-spacing: .01em;
        font-feature-settings: "tnum" 1;
    }
    .mfst-card .card-chip .ck {
        position: absolute;
        top: -5px;
        inset-inline-end: -5px;
        width: 18px; height: 18px;
        border-radius: 999px;
        background: var(--m-emerald);
        color: #052e23;
        font-size: 11px;
        font-weight: 900;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 0 2px rgba(7,7,15,1), 0 0 10px rgba(52,211,153,0.6);
    }
    .mfst-card.is-approved .card-chip { background: rgba(52,211,153,0.10); border-color: rgba(52,211,153,0.32); }
    .mfst-card.is-pending  .card-chip { background: rgba(251,191,36,0.14); border-color: rgba(251,191,36,0.40); }
    .mfst-card.is-blocked  .card-chip { background: rgba(251,113,133,0.14); border-color: rgba(251,113,133,0.40); }
    .mfst-card.is-scanned  .card-chip { background: rgba(52,211,153,0.20); border-color: rgba(52,211,153,0.62); }
    .mfst-card.is-empty    .card-chip .seat { color: var(--m-text-3); }

    .mfst-card .card-body { min-width: 0; }
    .mfst-card .card-name {
        font-size: 15.5px;
        font-weight: 700;
        color: var(--m-text);
        line-height: 1.25;
        margin-bottom: 4px;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
        letter-spacing: .005em;
    }
    .mfst-card .card-name .muted { color: var(--m-text-3); font-weight: 600; font-style: italic; }
    .mfst-card .card-meta {
        font-size: 11.5px;
        color: var(--m-text-3);
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        line-height: 1.3;
    }
    .mfst-card .card-meta .phone {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 11.5px;
        color: var(--m-text-2);
        letter-spacing: .02em;
    }
    .mfst-card .card-meta .ref {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 10.5px;
        color: var(--m-text-3);
        letter-spacing: .02em;
    }
    .mfst-card .card-meta .tag {
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        flex: 0 0 auto;
    }
    .mfst-card .card-meta .tag.pen { background: rgba(251,191,36,0.16); color: #fcd34d; }
    .mfst-card .card-meta .tag.blk { background: rgba(251,113,133,0.16); color: #fda4af; }
    .mfst-card .card-meta .tag.ck  { background: rgba(52,211,153,0.16); color: #6ee7b7; font-family: ui-monospace, monospace; }

    .mfst-card .card-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px; height: 22px;
        color: var(--m-text-3);
        font-size: 20px;
        font-weight: 700;
        line-height: 1;
    }
    [dir="rtl"] .mfst-card .card-action { transform: scaleX(-1); }

    .mfst-floor-empty {
        text-align: center;
        padding: 48px 20px;
        color: var(--m-text-3);
        font-size: 13px;
        border: 1px dashed rgba(255,255,255,0.10);
        border-radius: 18px;
        background: rgba(255,255,255,0.018);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .mfst-floor-empty .t { color: var(--m-text); font-weight: 700; font-size: 15px; }
    .mfst-floor-empty .s { font-size: 12.5px; color: var(--m-text-3); }

    /* Scanner FAB — fixed bottom-end, single-thumb reach. position:
       fixed (not sticky) so the safe-area + iOS chrome don't shift it
       relative to other content while scrolling. */
    .mfst-fab {
        position: fixed;
        inset-inline-end: 18px;
        bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        z-index: 30;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 60px; height: 60px;
        border-radius: 20px;
        background: linear-gradient(135deg, var(--m-emerald), var(--m-sky));
        color: #052e23;
        font-size: 26px;
        font-weight: 800;
        text-decoration: none;
        box-shadow:
            0 14px 36px rgba(52,211,153,0.45),
            0 4px 12px rgba(0,0,0,0.40),
            inset 0 1px 0 rgba(255,255,255,0.25);
        transition: transform .12s var(--m-ease), box-shadow .2s var(--m-ease);
    }
    .mfst-fab:active { transform: scale(.95); box-shadow: 0 8px 22px rgba(52,211,153,0.40); }
    .mfst-fab:focus-visible { box-shadow: 0 0 0 3px rgba(56,189,248,0.45), 0 14px 36px rgba(52,211,153,0.45); }

    /* Bottom sheet — slides up when an usher taps a card. Anchored to
       the bottom of the viewport so the most important content (seat
       label, name, scan action) lands in the thumb zone. */
    .mfst-sheet {
        position: fixed;
        inset: 0;
        z-index: 40;
        display: none;
        pointer-events: none;
    }
    .mfst-sheet.is-open { display: block; pointer-events: auto; }
    .mfst-sheet-scrim {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.55);
        opacity: 0;
        transition: opacity .2s var(--m-ease);
    }
    .mfst-sheet.is-open .mfst-sheet-scrim { opacity: 1; }
    .mfst-sheet-card {
        position: absolute;
        inset-inline: 0;
        bottom: 0;
        max-height: 86vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        background: linear-gradient(180deg, rgba(15,17,28,0.98), rgba(8,9,18,0.98));
        border-top: 1px solid var(--m-border);
        border-radius: 22px 22px 0 0;
        padding: 8px 16px calc(20px + env(safe-area-inset-bottom, 0px));
        transform: translateY(100%);
        transition: transform .26s var(--m-ease);
        box-shadow: 0 -18px 40px rgba(0,0,0,0.45);
    }
    .mfst-sheet.is-open .mfst-sheet-card { transform: translateY(0); }
    .mfst-sheet-grip {
        width: 44px; height: 4px;
        border-radius: 999px;
        background: rgba(255,255,255,0.14);
        margin: 6px auto 10px;
    }
    .mfst-sheet-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }
    .mfst-sheet-id { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .mfst-sheet-id .seat {
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -.005em;
        line-height: 1;
        color: var(--m-text);
        font-feature-settings: "tnum" 1;
    }
    .mfst-sheet-id .sec {
        font-size: 11px;
        color: var(--m-text-3);
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .mfst-sheet-close {
        width: 38px; height: 38px;
        border-radius: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text);
        font-size: 16px;
        cursor: pointer;
    }
    .mfst-sheet-close:active { transform: scale(.95); }
    .mfst-sheet-name {
        font-size: 19px;
        font-weight: 700;
        color: var(--m-text);
        margin: 4px 0 12px;
        line-height: 1.25;
        word-break: break-word;
    }
    .mfst-sheet-meta {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 12px;
        border-radius: 14px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.025);
        font-feature-settings: "tnum" 1;
    }
    .mfst-sheet-meta .row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: var(--m-text);
    }
    .mfst-sheet-meta .label {
        color: var(--m-text-3);
        font-size: 11.5px;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .mfst-sheet-meta .phone {
        font-family: ui-monospace, monospace;
        font-size: 13px;
        color: var(--m-text-2);
    }
    .mfst-sheet-party {
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 14px;
        border: 1px dashed var(--m-border);
        background: rgba(255,255,255,0.018);
    }
    .mfst-sheet-party .label {
        font-size: 10.5px;
        color: var(--m-text-3);
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }
    .mfst-sheet-party ul {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .mfst-sheet-party li {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        padding: 6px 8px;
        border-radius: 10px;
        background: rgba(255,255,255,0.03);
    }
    .mfst-sheet-party li.is-current { background: rgba(56,189,248,0.14); }
    .mfst-sheet-party li .lbl { font-weight: 800; color: var(--m-text); min-width: 36px; }
    .mfst-sheet-party li .nm  { color: var(--m-text-2); }
    .mfst-sheet-actions {
        margin-top: 14px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .mfst-sheet-actions a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 14px 12px;
        border-radius: 14px;
        font-size: 13.5px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid var(--m-border);
        color: var(--m-text);
        background: rgba(255,255,255,0.04);
        min-height: 48px;
        text-align: center;
        white-space: nowrap;
    }
    .mfst-sheet-actions a:first-child {
        background: linear-gradient(135deg, rgba(52,211,153,0.20), rgba(56,189,248,0.20));
        border-color: rgba(52,211,153,0.45);
        color: #ecfdf5;
    }
    .mfst-sheet-actions a:active { transform: scale(.98); }

    /* Tablet / wider: bring the sheet to a centered card so it doesn't
       look stretched on iPad. */
    @media (min-width: 720px) {
        .mfst-sheet-card {
            inset-inline-start: auto;
            inset-inline-end: auto;
            margin: 0 auto;
            max-width: 520px;
            border-radius: 22px 22px 22px 22px;
            bottom: 28px;
            inset-inline: 28px;
        }
        .mfst-sheet.is-open .mfst-sheet-card { transform: translateY(0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .mfst-sheet-card { transition: none; }
    }

    /* ====================================================================
       PAPER SHEET (mode=paper) — on-screen preview + print rules
       ==================================================================== */
    .mfst-paper {
        background: #fff;
        color: #000;
        padding: 22px 24px 18px;
        border-radius: 14px;
        font-family: 'IBM Plex Sans Arabic', 'Space Grotesk', sans-serif;
        box-shadow: 0 12px 32px rgba(0,0,0,0.18);
    }
    .mfst-paper-section {
        page-break-inside: auto;
        break-inside: auto;
        margin-bottom: 18px;
    }
    .mfst-paper-section + .mfst-paper-section { page-break-before: always; break-before: page; }
    .mfst-paper-section { counter-increment: page-section; }
    .mfst-paper-head {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        align-items: end;
        padding-bottom: 8px;
        border-bottom: 2px solid #000;
        margin-bottom: 8px;
    }
    .mfst-paper-head .t1 { font-size: 17pt; font-weight: 800; letter-spacing: -.005em; }
    .mfst-paper-head .t2 { font-size: 10pt; color: #555; margin-top: 2px; }
    .mfst-paper-head .stats { font-size: 9pt; text-align: end; line-height: 1.5; color: #333; }
    .mfst-paper-head .stats strong { font-weight: 800; color: #000; }
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
    .mfst-paper-table td.t-seat  { width: 64px; text-align: center; font-weight: 800; font-size: 12pt; letter-spacing: .005em; }
    .mfst-paper-table td.t-name  { font-weight: 700; font-size: 10.5pt; }
    .mfst-paper-table td.t-ref   { white-space: nowrap; }
    .mfst-paper-table td.t-phone { white-space: nowrap; font-family: ui-monospace, monospace; font-size: 9pt; }
    /* Hand-check column — a fat square box. Bigger touch target for a
       sharpie at the door, and the ink doesn't crowd adjacent rows. */
    .mfst-paper-table td.t-check {
        width: 36px;
        text-align: center;
        font-size: 15pt;
        padding: 4px 4px;
    }
    .mfst-paper-table td.t-check .box {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 1.5px solid #000;
        border-radius: 3px;
        vertical-align: middle;
    }
    .mfst-paper-table tr.is-approved td.t-glyph { color: #047857; }
    .mfst-paper-table tr.is-pending  td.t-glyph { color: #92400e; }
    .mfst-paper-table tr.is-blocked  td.t-glyph { color: #be123c; }
    .mfst-paper-table tr.is-empty    td.t-glyph { color: #6b7280; }
    .mfst-paper-table tr.is-blocked  td.t-name  { color: #6b7280; font-style: italic; }
    .mfst-paper-table tr.is-empty    td.t-name  { color: #9ca3af; font-style: italic; }
    .mfst-paper-table tr.is-blocked  td.t-check .box { background: #ddd; }
    .mfst-paper-table tr.is-empty    td.t-check .box { background: #f1f1f1; }
    /* Family hue band — a 4px stripe at the seat-column edge tints the
       row without muddying the rest of the cells on photocopies. */
    .mfst-paper-table tr[data-hue] td.t-seat { position: relative; }
    .mfst-paper-table tr[data-hue] td.t-seat::before {
        content: "";
        position: absolute;
        inset-block: 2px;
        inset-inline-start: 2px;
        width: 4px;
        border-radius: 2px;
        background: currentColor;
        opacity: .55;
    }
    .mfst-paper-table tr[data-hue="0"] td.t-seat { color: #0e7490; }
    .mfst-paper-table tr[data-hue="1"] td.t-seat { color: #4f46e5; }
    .mfst-paper-table tr[data-hue="2"] td.t-seat { color: #be185d; }
    .mfst-paper-table tr[data-hue="3"] td.t-seat { color: #92400e; }
    .mfst-paper-table tr[data-hue="4"] td.t-seat { color: #047857; }
    .mfst-paper-table tr[data-hue="5"] td.t-seat { color: #be123c; }
    .mfst-paper-table tr[data-hue="6"] td.t-seat { color: #6d28d9; }
    .mfst-paper-table tr[data-hue="7"] td.t-seat { color: #b91c1c; }
    /* Soft full-row tint for on-screen + color print; muted enough to
       remain legible on grayscale. */
    .mfst-paper-table tr[data-hue="0"] td:not(.t-check) { background: rgba(34,211,238,0.06); }
    .mfst-paper-table tr[data-hue="1"] td:not(.t-check) { background: rgba(129,140,248,0.06); }
    .mfst-paper-table tr[data-hue="2"] td:not(.t-check) { background: rgba(244,114,182,0.06); }
    .mfst-paper-table tr[data-hue="3"] td:not(.t-check) { background: rgba(251,191,36,0.07); }
    .mfst-paper-table tr[data-hue="4"] td:not(.t-check) { background: rgba(52,211,153,0.07); }
    .mfst-paper-table tr[data-hue="5"] td:not(.t-check) { background: rgba(251,113,133,0.07); }
    .mfst-paper-table tr[data-hue="6"] td:not(.t-check) { background: rgba(167,139,250,0.07); }
    .mfst-paper-table tr[data-hue="7"] td:not(.t-check) { background: rgba(252,165,165,0.07); }

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

    .mfst-paper-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }
    .mfst-paper-actions a, .mfst-paper-actions button {
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 600;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text);
        cursor: pointer;
        transition: background .2s var(--m-ease), border-color .2s var(--m-ease);
    }
    .mfst-paper-actions a:hover, .mfst-paper-actions button:hover {
        background: rgba(255,255,255,0.08);
        border-color: rgba(255,255,255,0.22);
    }

    /* ====================================================================
       PRINT RULES — paper-first, only the paper section survives. Tight
       A4 landscape margins, header rows repeated per page, page count
       in the bottom-right margin.
       ==================================================================== */
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm 10mm 14mm 10mm;
            /* Page x of y in the bottom-right; works in Chrome/Edge. */
            @bottom-right {
                content: "Page " counter(page) " / " counter(pages);
                font-size: 8pt;
                color: #444;
                font-family: 'IBM Plex Sans Arabic', 'Space Grotesk', sans-serif;
            }
        }

        body, html { background: #fff !important; color: #000 !important; }
        body.has-bg::before, body.has-bg::after { display: none !important; }

        /* Hide everything except the paper area */
        body * { visibility: hidden; }
        .mfst-paper, .mfst-paper * { visibility: visible; }
        .mfst-paper {
            position: absolute;
            inset: 0;
            padding: 0;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
        }

        /* Chrome */
        .mfst-topbar, .mfst-filters, .mfst-paper-actions, .pt-no-print { display: none !important; }

        /* Running header on every page (CSS Paged Media) */
        .mfst-paper-section { page-break-inside: auto; break-inside: auto; }
        .mfst-paper-section-head { page-break-after: avoid; break-after: avoid; }
        .mfst-paper-table tr     { page-break-inside: avoid; break-inside: avoid; }
        .mfst-paper-table thead  { display: table-header-group; }
        .mfst-paper-table tfoot  { display: table-footer-group; }

        /* Hand-check box must always print with a visible stroke even
           when the browser strips backgrounds. */
        .mfst-paper-table td.t-check .box {
            border: 1.5px solid #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Force the soft hue tints + family stripe to print on most
           desktop browsers (Chrome, Edge, Safari). */
        .mfst-paper-table tr[data-hue] td,
        .mfst-paper-table tr[data-hue] td.t-seat::before {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
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
                    @if ($show)
                        <a href="{{ route('admin.shows.times.index', $show) }}">← Back to show-times</a>
                    @else
                        <a href="{{ route('admin.dashboard') }}">← Back to dashboard</a>
                    @endif
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
            <span>Approved</span>
            <span class="count">{{ $summary['approved'] }}</span>
        </button>
        <button type="button" class="mfst-chip mfst-chip-pending js-filter-chip" data-status="pending" aria-pressed="{{ in_array('pending', $statusFilter) ? 'true' : 'false' }}">
            <span>Pending</span>
            <span class="count">{{ $summary['pending'] }}</span>
        </button>
        <button type="button" class="mfst-chip mfst-chip-blocked js-filter-chip" data-status="blocked" aria-pressed="{{ in_array('blocked', $statusFilter) ? 'true' : 'false' }}">
            <span>Blocked</span>
            <span class="count">{{ $summary['blocked'] }}</span>
        </button>
        <button type="button" class="mfst-chip mfst-chip-checked js-filter-chip" data-status="checked_in" aria-pressed="{{ in_array('checked_in', $statusFilter) ? 'true' : 'false' }}">
            <span>✓ Checked-in</span>
            <span class="count js-filter-checked-count">{{ $checkedInCount }}</span>
        </button>
        <button type="button" class="mfst-chip mfst-chip-empty js-filter-chip" data-status="empty" aria-pressed="{{ in_array('empty', $statusFilter) ? 'true' : 'false' }}">
            <span>Empty</span>
            <span class="count">{{ $summary['empty'] }}</span>
        </button>

        <label class="mfst-search">
            <input type="search"
                   class="js-search-input"
                   placeholder="ابحث: اسم · رقم مقعد (A12) · كود حجز"
                   aria-label="Search seats, bookings, attendees"
                   autocomplete="off"
                   autocapitalize="off"
                   inputmode="search">
            <kbd>/</kbd>
            <span class="icon">🔎</span>
            <button type="button" class="clear js-search-clear" aria-label="Clear search" hidden>✕</button>
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
                    <div class="mfst-stage">STAGE · المسرح</div>
                    @foreach ($byRow as $rletter => $seats)
                        @php
                            $rowBooked   = 0; $rowChecked = 0; $rowTotal = count($seats);
                            foreach ($seats as $s) {
                                if (in_array($s['status'], ['approved', 'pending'], true)) $rowBooked++;
                                if (!empty($s['is_scanned'])) $rowChecked++;
                            }
                            $rowPct = $rowTotal > 0 ? (int) round(($rowBooked / $rowTotal) * 100) : 0;
                        @endphp
                        <div class="mfst-row">
                            <div class="mfst-row-label">
                                <span class="row-letter">{{ $rletter }}</span>
                                <span class="row-bar" aria-hidden="true" style="--row-pct: {{ $rowPct }}%;"><span class="fill"></span></span>
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
                                            data-haystack="{{ strtolower(trim(($s['attendee_name'] ?? '') . ' ' . ($s['booking_owner'] ?? '') . ' ' . ($s['booking_ref'] ?? '') . ' ' . ($s['phone'] ?? '') . ' ' . $seatLabel . ' ' . $s['section_label_ar'])) }}"
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
                             data-haystack="{{ strtolower(($b['owner'] ?? '') . ' ' . ($b['ref'] ?? '') . ' ' . ($b['phone'] ?? '')) }}">
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
         FLOOR MODE — mobile-first, search-led, section-anchored.

         Layout, top to bottom:
           1. Hero search (sticky, top-anchored — iOS-friendly).
              An inline clear button keeps thumb travel short and a
              live result counter narrates how many seats match.
           2. Compact status chip row (horizontal scroll, calm at rest).
           3. Section-anchored card list ("صالة · Hall — 320/500"),
              with per-row mini-dividers so the usher always knows
              which arc/row they're on.
           4. Scanner FAB pinned to the bottom edge for one-handed
              thumb use; no bottom search dock (search sits at the
              top where iOS Safari's chrome doesn't fight it).
           5. Bottom-sheet detail when tapping a card — full attendee
              context, booking party, scan / open-on-chart actions.
         ============================================================ --}}
    @if ($mode === 'floor')
    <div class="mfst-floor" data-floor>
        <div class="mfst-floor-search pt-no-print">
            <label class="mfst-search">
                <input type="search"
                       class="js-search-input"
                       placeholder="ابحث باسم أو رقم مقعد (مثال: A12)"
                       aria-label="Search seats or attendees"
                       autocomplete="off"
                       autocapitalize="off"
                       inputmode="search">
                <span class="icon" aria-hidden="true">🔍</span>
                <button type="button" class="clear js-search-clear" aria-label="Clear search" hidden>✕</button>
            </label>
            <div class="mfst-floor-search-hint js-search-hint" aria-live="polite"></div>
        </div>

        <div class="mfst-floor-filters" role="tablist" aria-label="Status filter">
            <button type="button" class="mfst-chip mfst-chip-approved js-filter-chip" data-status="approved" aria-pressed="{{ in_array('approved', $statusFilter) ? 'true' : 'false' }}">
                <span class="dot" aria-hidden="true"></span>
                <span>Approved</span><span class="count">{{ $summary['approved'] }}</span>
            </button>
            <button type="button" class="mfst-chip mfst-chip-pending js-filter-chip" data-status="pending" aria-pressed="{{ in_array('pending', $statusFilter) ? 'true' : 'false' }}">
                <span class="dot" aria-hidden="true"></span>
                <span>Pending</span><span class="count">{{ $summary['pending'] }}</span>
            </button>
            <button type="button" class="mfst-chip mfst-chip-blocked js-filter-chip" data-status="blocked" aria-pressed="{{ in_array('blocked', $statusFilter) ? 'true' : 'false' }}">
                <span class="dot" aria-hidden="true"></span>
                <span>Blocked</span><span class="count">{{ $summary['blocked'] }}</span>
            </button>
            <button type="button" class="mfst-chip mfst-chip-checked js-filter-chip" data-status="checked_in" aria-pressed="{{ in_array('checked_in', $statusFilter) ? 'true' : 'false' }}">
                <span class="dot" aria-hidden="true"></span>
                <span>✓ Checked</span><span class="count js-filter-checked-count">{{ $checkedInCount }}</span>
            </button>
            <button type="button" class="mfst-chip mfst-chip-empty js-filter-chip" data-status="empty" aria-pressed="{{ in_array('empty', $statusFilter) ? 'true' : 'false' }}">
                <span class="dot" aria-hidden="true"></span>
                <span>Empty</span><span class="count">{{ $summary['empty'] }}</span>
            </button>
        </div>

        <div class="mfst-floor-list js-floor-list">
            @foreach ($floorRowsBySectionRow as $sectionLabel => $byRow)
                @php $secStats = $floorSectionStats[$sectionLabel] ?? ['booked' => 0, 'blocked' => 0, 'total' => 0]; @endphp
                <div class="mfst-floor-section js-floor-section" data-section-key="{{ $sectionLabel }}">
                    <div class="mfst-floor-section-head">
                        <span class="t">{{ $sectionLabel }}</span>
                        <span class="s">{{ $secStats['booked'] }}/{{ $secStats['total'] }}</span>
                    </div>
                    @foreach ($byRow as $rletter => $block)
                        <div class="mfst-floor-rowgroup js-floor-rowgroup" data-row="{{ $rletter }}">
                            <div class="mfst-floor-rowhead" aria-hidden="true">
                                <span class="r">{{ $rletter }}</span>
                                <span class="b" style="--row-pct: {{ $block['total'] > 0 ? (int) round(($block['booked'] / $block['total']) * 100) : 0 }}%;"><span class="fill"></span></span>
                                <span class="n">{{ $block['booked'] }}/{{ $block['total'] }}</span>
                            </div>
                            @foreach ($block['seats'] as $s)
                                @php
                                    $seatLabel = $s['row_letter'] . $s['seat_number'];
                                    $hue = $s['booking_id'] ? ($bookingColorIndex[$s['booking_id']] ?? null) : null;
                                @endphp
                                <div class="mfst-card is-{{ $s['status'] }} @if($s['is_scanned']) is-scanned @endif {{ $hue !== null ? 'mfst-hue-' . $hue : '' }} js-floor-card"
                                     data-seat="{{ $seatLabel }}"
                                     data-status="{{ $s['status'] }}"
                                     data-status-en="{{ $s['status_en'] }}"
                                     data-section="{{ $s['section_label_ar'] }}"
                                     data-section-en="{{ $s['section_label_en'] }}"
                                     data-row="{{ $s['row_letter'] }}"
                                     data-seat-num="{{ $s['seat_number'] }}"
                                     data-name="{{ $s['attendee_name'] ?? '' }}"
                                     data-owner="{{ $s['booking_owner'] ?? '' }}"
                                     data-phone="{{ $s['phone'] ? $maskPhone($s['phone']) : '' }}"
                                     data-booking-id="{{ $s['booking_id'] ?? '' }}"
                                     data-booking-ref="{{ $s['booking_ref'] ?? '' }}"
                                     data-checked="{{ $s['is_scanned'] ? '1' : '0' }}"
                                     data-scanned-at="{{ $s['scanned_at'] ?? '' }}"
                                     @if ($hue !== null) data-hue="{{ $hue }}" @endif
                                     data-haystack="{{ strtolower(trim(($s['attendee_name'] ?? '') . ' ' . ($s['booking_owner'] ?? '') . ' ' . ($s['booking_ref'] ?? '') . ' ' . ($s['phone'] ?? '') . ' ' . $seatLabel . ' ' . $s['section_label_ar'])) }}"
                                     role="button"
                                     tabindex="0">
                                    <div class="card-chip">
                                        <span class="seat">{{ $seatLabel }}</span>
                                        @if ($s['is_scanned'])<span class="ck" aria-label="Checked in">✓</span>@endif
                                    </div>
                                    <div class="card-body">
                                        <div class="card-name">
                                            @if ($s['status'] === 'blocked')
                                                <span class="muted">— محجوب —</span>
                                            @elseif ($s['status'] === 'empty')
                                                <span class="muted">— فارغ —</span>
                                            @else
                                                {{ $s['attendee_name'] ?? '—' }}
                                            @endif
                                        </div>
                                        <div class="card-meta">
                                            @if ($s['phone'])
                                                <span class="phone">{{ $maskPhone($s['phone']) }}</span>
                                            @endif
                                            @if ($s['booking_ref'])
                                                <span class="ref">{{ $s['booking_ref'] }}</span>
                                            @endif
                                            @if ($s['status'] === 'pending')<span class="tag pen">Pending</span>@endif
                                            @if ($s['status'] === 'blocked')<span class="tag blk">Blocked</span>@endif
                                            @if ($s['is_scanned'] && $s['scanned_at'])<span class="tag ck">{{ $s['scanned_at'] }}</span>@endif
                                        </div>
                                    </div>
                                    <span class="card-action" aria-hidden="true">›</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="mfst-floor-empty js-floor-empty" style="display:none;">
            <div class="t">No matches</div>
            <div class="s">Try a different name, seat label (A12), or booking ref.</div>
        </div>

        <a href="/admin/scanner" class="mfst-fab pt-no-print" aria-label="Open QR scanner">
            <span aria-hidden="true">📷</span>
        </a>

        {{-- Bottom-sheet — slides up when an usher taps a card. Persists
             until dismissed via close, tap-outside, or Esc. The
             "View on chart" link jumps to Operations Console with
             ?focus= so the same seat is centered on desktop. --}}
        <div class="mfst-sheet" aria-hidden="true" data-sheet>
            <div class="mfst-sheet-scrim js-sheet-scrim"></div>
            <div class="mfst-sheet-card" role="dialog" aria-modal="true" aria-labelledby="mfst-sheet-title">
                <div class="mfst-sheet-grip" aria-hidden="true"></div>
                <div class="mfst-sheet-head">
                    <div class="mfst-sheet-id">
                        <div class="seat" id="mfst-sheet-title" data-sheet-seat>—</div>
                        <div class="sec" data-sheet-section></div>
                    </div>
                    <button type="button" class="mfst-sheet-close js-sheet-close" aria-label="Close detail">✕</button>
                </div>
                <div class="mfst-sheet-name" data-sheet-name>—</div>
                <div class="mfst-sheet-meta">
                    <div class="row"><span class="label">Status</span><span data-sheet-status></span></div>
                    <div class="row"><span class="label">Booking</span><span data-sheet-booking>—</span></div>
                    <div class="row"><span class="label">Owner</span><span data-sheet-owner>—</span></div>
                    <div class="row"><span class="label">Phone</span><span class="phone" data-sheet-phone>—</span></div>
                    <div class="row js-sheet-scan-row" style="display:none;"><span class="label">Checked in</span><span data-sheet-scan></span></div>
                </div>
                <div class="mfst-sheet-party js-sheet-party" style="display:none;">
                    <div class="label">Booking party</div>
                    <ul class="js-sheet-party-list"></ul>
                </div>
                <div class="mfst-sheet-actions">
                    <a class="js-sheet-scan" href="/admin/scanner">📷 Scan to verify</a>
                    <a class="js-sheet-chart" href="#">🗺 View on chart</a>
                </div>
            </div>
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
                                    <td class="t-check"><span class="box" aria-hidden="true"></span></td>
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
       Search state — declared up here so applyFilter()'s initial call
       (which may reach applySearch via applyFilterToFloorList) doesn't
       trip the temporal dead zone.
       ==================================================================== */
    let searchTerm = '';

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
    function applyFloorVisibility() {
        const cards = document.querySelectorAll('.js-floor-card');
        if (!cards.length) return;
        const active = new Set(getActiveStatuses());
        const q = (searchTerm || '').trim().toLowerCase();
        let visible = 0;
        cards.forEach(card => {
            const status        = card.dataset.status;
            const checked       = card.dataset.checked === '1';
            const passesStatus  = active.has(status);
            const passesChecked = !active.has('checked_in') || checked;
            const hay           = card.dataset.haystack || '';
            const passesQuery   = !q || hay.includes(q);
            const show = passesStatus && passesChecked && passesQuery;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        // Collapse empty row groups + sections so the list never shows
        // a dangling section header with no cards beneath it.
        document.querySelectorAll('.js-floor-rowgroup').forEach(rg => {
            const any = rg.querySelector('.js-floor-card:not([style*="display: none"])');
            rg.style.display = any ? '' : 'none';
        });
        document.querySelectorAll('.js-floor-section').forEach(sec => {
            const any = sec.querySelector('.js-floor-rowgroup:not([style*="display: none"])');
            sec.style.display = any ? '' : 'none';
        });
        const empty = document.querySelector('.js-floor-empty');
        if (empty) empty.style.display = visible === 0 ? '' : 'none';
        const hint = document.querySelector('.js-search-hint');
        if (hint) {
            if (q) {
                hint.textContent = visible + (visible === 1 ? ' match' : ' matches');
                hint.classList.add('has-result');
            } else {
                hint.textContent = '';
                hint.classList.remove('has-result');
            }
        }
        // Keep the clear (✕) button in sync with whether the input has
        // text. Looks pointless when the field is empty.
        document.querySelectorAll('.js-search-clear').forEach(btn => {
            btn.hidden = !q;
        });
    }
    function applyFilterToFloorList() {
        applyFloorVisibility();
    }
    function applyFilter() {
        if (mode === 'ops') {
            applyFilterToOpsChart();
            applySearch();
        }
        if (mode === 'floor') applyFloorVisibility();
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
       a `data-haystack` blob assembled server-side. `searchTerm` is
       declared above so applyFilter() can call applySearch() safely.
       ==================================================================== */
    function applySearch() {
        if (mode === 'ops') {
            const seats = document.querySelectorAll('.js-seat');
            const q = searchTerm.trim().toLowerCase();
            if (!q) {
                seats.forEach(s => s.style.opacity = '');
            } else {
                seats.forEach(s => {
                    const hay = s.dataset.haystack || '';
                    s.style.opacity = hay.includes(q) ? '' : '0.18';
                });
            }
            // Also filter matching booking rows in the rail
            document.querySelectorAll('.js-booking-row').forEach(r => {
                const hay = r.dataset.haystack || '';
                r.style.display = (!q || hay.includes(q)) ? '' : 'none';
            });
            // Toggle desktop search clear button
            document.querySelectorAll('.js-search-clear').forEach(btn => {
                btn.hidden = !q;
            });
        } else if (mode === 'floor') {
            applyFloorVisibility();
        }
    }
    document.querySelectorAll('.js-search-input').forEach(input => {
        input.addEventListener('input', (e) => {
            searchTerm = e.target.value;
            applySearch();
        });
    });
    // Inline clear (✕) button beside the search input — single-tap reset
    // so an usher can pivot from "name search" to "seat search" without
    // hunting for backspace.
    document.querySelectorAll('.js-search-clear').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.js-search-input').forEach(i => { i.value = ''; });
            searchTerm = '';
            applySearch();
            const input = document.querySelector('.js-search-input');
            if (input) input.focus();
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
            // 1) close the bottom sheet first if it's open
            const sheetOpen = document.querySelector('[data-sheet].is-open');
            if (sheetOpen) {
                sheetOpen.classList.remove('is-open');
                sheetOpen.setAttribute('aria-hidden', 'true');
                document.documentElement.style.overflow = '';
                return;
            }
            // 2) otherwise reset search + ops detail
            document.querySelectorAll('.js-search-input').forEach(i => { if (i.value) i.value = ''; });
            searchTerm = '';
            applySearch();
            const detail = document.querySelector('.js-seat-detail');
            if (detail) detail.style.display = 'none';
            const railEmpty = document.querySelector('.js-rail-empty');
            if (railEmpty) railEmpty.style.display = '';
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
       Floor — tapping a card slides up the bottom sheet with the full
       attendee context. The sheet has scan + open-on-chart actions, so
       an usher never has to switch screens to confirm a seat.
       ==================================================================== */
    const sheet     = document.querySelector('[data-sheet]');
    const sheetScrim = document.querySelector('.js-sheet-scrim');
    const sheetClose = document.querySelector('.js-sheet-close');
    let lastFocusedCard = null;

    function openSheet(card) {
        if (!sheet || !card) return;
        const d = card.dataset;
        lastFocusedCard = card;

        const $ = (sel) => sheet.querySelector(sel);
        $('[data-sheet-seat]').textContent    = d.seat || '—';
        const sectionPieces = [d.section, d.sectionEn].filter(Boolean);
        $('[data-sheet-section]').textContent = sectionPieces.join(' · ');

        let displayName;
        if (d.status === 'blocked')       displayName = '— محجوب —';
        else if (d.status === 'empty')    displayName = '— فارغ —';
        else                              displayName = d.name || '—';
        $('[data-sheet-name]').textContent    = displayName;
        $('[data-sheet-status]').textContent  = d.statusEn || '—';
        $('[data-sheet-booking]').textContent = d.bookingRef || '—';
        $('[data-sheet-owner]').textContent   = d.owner || '—';
        $('[data-sheet-phone]').textContent   = d.phone || '—';

        const scanRow = $('.js-sheet-scan-row');
        if (d.checked === '1') {
            scanRow.style.display = '';
            $('[data-sheet-scan]').textContent = d.scannedAt || '—';
        } else {
            scanRow.style.display = 'none';
        }

        const scanLink = $('.js-sheet-scan');
        if (scanLink && d.seat) {
            scanLink.href = '/admin/scanner?expect=' + encodeURIComponent(d.seat);
        }
        const chartLink = $('.js-sheet-chart');
        if (chartLink && d.seat) {
            const url = new URL(window.location.href);
            url.searchParams.set('mode', 'ops');
            url.searchParams.set('focus', d.seat);
            chartLink.href = url.toString();
        }

        // Party — show all seats in the same booking with the current
        // seat highlighted, so the usher knows the rest of the family.
        const partyWrap = $('.js-sheet-party');
        const partyList = $('.js-sheet-party-list');
        partyList.innerHTML = '';
        if (d.bookingId) {
            const partyCards = document.querySelectorAll('.js-floor-card[data-booking-id="' + d.bookingId + '"]');
            if (partyCards.length > 1) {
                partyCards.forEach(p => {
                    const li = document.createElement('li');
                    if (p.dataset.seat === d.seat) li.classList.add('is-current');
                    const lbl = document.createElement('span');
                    lbl.className = 'lbl';
                    lbl.textContent = p.dataset.seat || '—';
                    const nm = document.createElement('span');
                    nm.className = 'nm';
                    nm.textContent = p.dataset.name || '—';
                    li.appendChild(lbl);
                    li.appendChild(nm);
                    partyList.appendChild(li);
                });
                partyWrap.style.display = '';
            } else {
                partyWrap.style.display = 'none';
            }
        } else {
            partyWrap.style.display = 'none';
        }

        sheet.classList.add('is-open');
        sheet.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
    }

    function closeSheet() {
        if (!sheet) return;
        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
        document.documentElement.style.overflow = '';
        if (lastFocusedCard) {
            try { lastFocusedCard.focus({ preventScroll: true }); } catch (e) {}
        }
    }

    if (sheetClose) sheetClose.addEventListener('click', closeSheet);
    if (sheetScrim) sheetScrim.addEventListener('click', closeSheet);

    document.querySelectorAll('.js-floor-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('[data-no-card-click]')) return;
            openSheet(card);
        });
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openSheet(card);
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
