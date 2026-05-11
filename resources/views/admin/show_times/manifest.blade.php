@extends('layouts.app')

@php
    /* ========================================================================
       Seat Occupancy / Attendee Manifest — v3

       Two operator-led surfaces share this single Blade. The controller picks
       the surface for the request:

         mode=floor   mobile-first live operations  (default everywhere)
         mode=paper   A4-landscape printable sheet  (?mode=paper)

       The previous "Operations Console" desktop dashboard surface was retired
       in v3 — it added complexity without serving the actual operational need
       (find a person fast, find a seat fast, hand a printed sheet to door
       staff). Floor + Paper cover that need cleanly.

       All surfaces consume the same flat `rows` payload from
       ManifestController. The view layer does visual + interaction work;
       the controller stays pure data.
       ======================================================================== */

    $eventDate = optional($showTime->date)->format('d/m/Y');
    $eventTime = $showTime->time ? \Carbon\Carbon::parse($showTime->time)->format('g:i A') : '';
    $showTitle = optional($show)->title ?? '—';

    // Phone masking in the view so the controller payload stays unmasked
    // and the same data drives the CSV export.
    $maskPhone = function (?string $phone) use ($showFullPhone) {
        if (!$phone) return '';
        if ($showFullPhone) return $phone;
        $digits = preg_replace('/\D+/', '', $phone);
        $len = strlen($digits);
        if ($len <= 6) return $phone;
        return substr($digits, 0, 2) . str_repeat('●', max(0, $len - 6)) . substr($digits, -4);
    };

    // Group rows by section → row letter. Used by both Floor list and Paper
    // sheet. The controller already sorted by (section, row, seat#) so
    // insertion order is the operational order.
    $rowsBySectionRow = [];
    foreach ($rows as $r) {
        $rowsBySectionRow[$r['section_label_ar']][$r['row_letter']][] = $r;
    }

    // Stable booking_id → hue index so each booking gets the same colour
    // wherever it appears (card accent, paper band, sheet party rail).
    $bookingColorIndex = [];
    $bookingHueIdx = 0;
    foreach ($rows as $r) {
        if (!empty($r['booking_id']) && !isset($bookingColorIndex[$r['booking_id']])) {
            $bookingColorIndex[$r['booking_id']] = ($bookingHueIdx % 8);
            $bookingHueIdx++;
        }
    }

    $totalBooked    = $summary['approved'] + $summary['pending'];
    $checkedInCount = collect($rows)->where('is_scanned', true)->count();
    $capacity       = $summary['total'] ?: 1;
    $capacityPct    = (int) round(($totalBooked / $capacity) * 100);

    // URL helper preserves `full_phone` + `mode`. Pass `null` to remove a key.
    $url = function ($params = []) use ($showTime, $showFullPhone, $mode) {
        $base = route('admin.show-times.manifest', $showTime);
        $q    = array_merge(
            ['full_phone' => $showFullPhone ? 1 : 0, 'mode' => $mode],
            $params
        );
        $q = array_filter($q, fn ($v) => $v !== null && $v !== '');
        return $base . '?' . http_build_query($q);
    };

    $csvUrl  = route('admin.show-times.manifest.csv', $showTime)
        . '?' . http_build_query(['full_phone' => $showFullPhone ? 1 : 0]);
    $jsonUrl = route('admin.show-times.manifest.json', $showTime);

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

    // Floor list grouping. Section + row are used as quiet anchors so an
    // usher walking the hall always knows which arc/row a card belongs to.
    $floorRowsBySectionRow = [];
    foreach ($rowsBySectionRow as $sectionLabel => $byRow) {
        foreach ($byRow as $rowLetter => $seats) {
            $secApproved = 0; $secPending = 0; $secBlocked = 0;
            foreach ($seats as $s) {
                if ($s['status'] === 'approved') $secApproved++;
                elseif ($s['status'] === 'pending') $secPending++;
                elseif ($s['status'] === 'blocked') $secBlocked++;
            }
            $floorRowsBySectionRow[$sectionLabel][$rowLetter] = [
                'seats'   => $seats,
                'booked'  => $secApproved + $secPending,
                'blocked' => $secBlocked,
                'total'   => count($seats),
            ];
        }
    }

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

    // Status glyph for the paper sheet — survives photocopy + grayscale.
    $statusGlyph = [
        'approved' => '●',
        'pending'  => '◐',
        'blocked'  => '✕',
        'empty'    => '·',
    ];
@endphp

@section('title', 'مانيفست المقاعد · ' . $showTitle)

@push('styles')
<style>
    /* ====================================================================
       Manifest v3 — shared tokens
       Single calm palette. One accent. Family hues only where information
       lives (card seat label underline, paper hue band, sheet party rail).
       ==================================================================== */
    :root {
        --m-border        : var(--prism-border, rgba(255,255,255,0.10));
        --m-border-strong : rgba(255,255,255,0.18);
        --m-text          : var(--prism-text, #f3f4f6);
        --m-text-2        : var(--prism-text-2, #d1d5db);
        --m-text-3        : var(--prism-text-3, #9ca3af);
        --m-emerald       : #34d399;
        --m-amber         : #fbbf24;
        --m-rose          : #fb7185;
        --m-sky           : #38bdf8;
        --m-violet        : #a78bfa;
        --m-ease          : cubic-bezier(.22,.61,.36,1);
        --m-radius        : 18px;
        --m-radius-card   : 16px;
    }
    .manifest-root, .manifest-root * {
        font-feature-settings: "tnum" 1, "ss01" 1;
    }
    .manifest-root :focus-visible {
        outline: none;
        box-shadow: 0 0 0 2px rgba(56,189,248,0.55), 0 0 18px rgba(56,189,248,0.22);
        border-radius: 8px;
    }

    /* Booking family hues — used as a soft accent only. Never as a card
       background. Keeps cards calm; family lives in the seat label + sheet. */
    .mfst-hue-0 { --m-hue: rgba(34,211,238,0.95);  }
    .mfst-hue-1 { --m-hue: rgba(129,140,248,0.95); }
    .mfst-hue-2 { --m-hue: rgba(244,114,182,0.95); }
    .mfst-hue-3 { --m-hue: rgba(251,191,36,0.95);  }
    .mfst-hue-4 { --m-hue: rgba(52,211,153,0.95);  }
    .mfst-hue-5 { --m-hue: rgba(251,113,133,0.95); }
    .mfst-hue-6 { --m-hue: rgba(167,139,250,0.95); }
    .mfst-hue-7 { --m-hue: rgba(252,165,165,0.95); }

    /* ====================================================================
       FLOOR MODE — live operational surface, mobile-first
       ==================================================================== */
    .mfst-floor {
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding-bottom: calc(96px + env(safe-area-inset-bottom, 0px));
    }

    /* Header — calm, no dashboard noise. Title + a quiet capacity line
       and a single Paper-mode handoff. */
    .mfst-head {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 16px 4px 4px;
    }
    .mfst-head .title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 17px;
        font-weight: 700;
        letter-spacing: -.005em;
        color: var(--m-text);
        line-height: 1.2;
    }
    .mfst-head .live-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: var(--m-emerald);
        box-shadow: 0 0 12px rgba(52,211,153,0.55);
        flex-shrink: 0;
    }
    .mfst-head .meta {
        font-size: 12.5px;
        color: var(--m-text-3);
        display: flex;
        flex-wrap: wrap;
        gap: 6px 10px;
        align-items: center;
    }
    .mfst-head .meta b {
        color: var(--m-text);
        font-weight: 700;
    }
    .mfst-head .meta .sep {
        opacity: .45;
        margin: 0 -4px;
    }
    .mfst-head-actions {
        display: flex;
        gap: 8px;
        margin-top: 4px;
        flex-wrap: wrap;
    }
    .mfst-head-actions a, .mfst-head-actions button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 13px;
        min-height: 38px;
        border-radius: 999px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text-2);
        text-decoration: none;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s var(--m-ease), border-color .15s var(--m-ease), color .15s var(--m-ease);
        -webkit-tap-highlight-color: transparent;
    }
    .mfst-head-actions a:hover, .mfst-head-actions button:hover {
        background: rgba(129,140,248,0.10);
        border-color: rgba(129,140,248,0.30);
        color: var(--m-text);
    }
    .mfst-head-actions .is-primary {
        background: linear-gradient(180deg, rgba(56,189,248,0.18), rgba(56,189,248,0.10));
        border-color: rgba(56,189,248,0.45);
        color: #e0f2fe;
    }

    /* Sticky search bar — the operational heart. Lives just under the
       app header, survives Safari URL-bar collapse via sticky + the
       safe-area-aware offset. */
    .mfst-search-wrap {
        position: sticky;
        top: calc(58px + env(safe-area-inset-top, 0px));
        z-index: 22;
        margin: 0 -16px;
        padding: 8px 16px 10px;
        background: linear-gradient(180deg,
            rgba(8,9,18,0.92) 0%,
            rgba(8,9,18,0.82) 70%,
            rgba(8,9,18,0.0) 100%);
        backdrop-filter: blur(20px) saturate(150%);
        -webkit-backdrop-filter: blur(20px) saturate(150%);
    }
    .mfst-search {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 14px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.06);
        padding: 0 14px;
        min-height: 52px;
        transition: border-color .2s var(--m-ease), background .2s var(--m-ease), box-shadow .2s var(--m-ease);
    }
    .mfst-search:focus-within {
        border-color: rgba(56,189,248,0.55);
        background: rgba(56,189,248,0.06);
        box-shadow: 0 0 0 4px rgba(56,189,248,0.10);
    }
    .mfst-search .icon {
        font-size: 18px;
        color: var(--m-text-3);
        flex-shrink: 0;
    }
    .mfst-search input {
        flex: 1;
        background: transparent;
        border: 0;
        color: var(--m-text);
        font-size: 16px; /* >=16px → iOS Safari does not zoom on focus */
        font-weight: 500;
        outline: none;
        padding: 14px 0;
        min-width: 0;
    }
    .mfst-search input::placeholder {
        color: var(--m-text-3);
        font-weight: 400;
    }
    .mfst-search .clear {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 999px;
        border: 0;
        background: rgba(255,255,255,0.08);
        color: var(--m-text-2);
        font-size: 12px;
        cursor: pointer;
        flex-shrink: 0;
    }
    .mfst-search .clear:hover { background: rgba(255,255,255,0.14); color: var(--m-text); }
    .mfst-search-hint {
        margin-top: 6px;
        font-size: 11.5px;
        color: var(--m-text-3);
        min-height: 14px;
        padding-inline-start: 4px;
    }
    .mfst-search-hint.has-result {
        color: var(--m-sky);
    }

    /* Filter chips — minimal calm row. Horizontal scroll on small screens
       so we never wrap the chips into two rows. */
    .mfst-chips {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 2px 4px 6px;
        margin: 0 -4px;
    }
    .mfst-chips::-webkit-scrollbar { display: none; }
    .mfst-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 999px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text-2);
        font-size: 12.5px;
        font-weight: 600;
        white-space: nowrap;
        min-height: 36px;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .15s var(--m-ease), border-color .15s var(--m-ease), color .15s var(--m-ease);
        -webkit-tap-highlight-color: transparent;
    }
    .mfst-chip .count {
        font-size: 11px;
        font-weight: 700;
        opacity: .75;
        padding: 0 4px;
    }
    .mfst-chip[aria-pressed="true"] {
        background: var(--m-chip-bg, rgba(56,189,248,0.14));
        border-color: var(--m-chip-color, rgba(56,189,248,0.55));
        color: var(--m-text);
    }
    .mfst-chip-approved   { --m-chip-color: rgba(52,211,153,0.65);  --m-chip-bg: rgba(52,211,153,0.12);  }
    .mfst-chip-pending    { --m-chip-color: rgba(251,191,36,0.65);  --m-chip-bg: rgba(251,191,36,0.12);  }
    .mfst-chip-blocked    { --m-chip-color: rgba(251,113,133,0.65); --m-chip-bg: rgba(251,113,133,0.12); }
    .mfst-chip-checked    { --m-chip-color: rgba(56,189,248,0.65);  --m-chip-bg: rgba(56,189,248,0.12);  }

    /* Section + row anchors — quiet hairline labels so the operator never
       loses orientation, but they're never the loudest thing on screen. */
    .mfst-section {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .mfst-section + .mfst-section { margin-top: 4px; }
    .mfst-section-head {
        display: flex;
        align-items: baseline;
        gap: 8px;
        padding: 14px 4px 6px;
        position: sticky;
        top: calc(58px + 70px + env(safe-area-inset-top, 0px)); /* below the sticky search */
        z-index: 14;
        background: linear-gradient(180deg,
            rgba(8,9,18,0.92) 0%,
            rgba(8,9,18,0.70) 80%,
            rgba(8,9,18,0.0) 100%);
        backdrop-filter: blur(14px) saturate(140%);
        -webkit-backdrop-filter: blur(14px) saturate(140%);
    }
    .mfst-section-head .t {
        font-size: 12.5px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--m-text-2);
    }
    .mfst-section-head .s {
        font-size: 12px;
        color: var(--m-text-3);
        margin-inline-start: auto;
    }
    .mfst-section-head .s b {
        color: var(--m-text);
        font-weight: 700;
    }

    .mfst-rowgroup {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .mfst-rowhead {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 4px 2px;
        font-size: 11px;
        color: var(--m-text-3);
        letter-spacing: .04em;
    }
    .mfst-rowhead .r {
        font-weight: 700;
        color: var(--m-text-2);
    }
    .mfst-rowhead .b {
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg,
            var(--m-border) 0%,
            transparent 100%);
    }
    .mfst-rowhead .n {
        font-feature-settings: "tnum" 1;
        color: var(--m-text-3);
    }

    /* The card — the operational atom. Calm by default. One row tall.
       Seat label dominant on the start side, attendee name dominant in
       the centre, status dot at the end. Tap to expand details. */
    .mfst-card {
        display: grid;
        grid-template-columns: 60px 1fr auto;
        gap: 12px;
        align-items: center;
        padding: 12px 14px;
        border-radius: var(--m-radius-card);
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.03);
        cursor: pointer;
        transition: background .15s var(--m-ease), border-color .15s var(--m-ease), transform .12s var(--m-ease);
        -webkit-tap-highlight-color: transparent;
        position: relative;
        overflow: hidden;
    }
    .mfst-card:hover {
        background: rgba(255,255,255,0.05);
        border-color: var(--m-border-strong);
    }
    .mfst-card:active {
        transform: scale(0.985);
    }
    .mfst-card.is-focused {
        border-color: rgba(56,189,248,0.55);
        box-shadow: 0 0 0 2px rgba(56,189,248,0.18);
    }

    /* Seat block — the dominant "where" identifier. */
    .mfst-card .seat-block {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        position: relative;
    }
    .mfst-card .seat {
        font-size: 17px;
        font-weight: 800;
        font-feature-settings: "tnum" 1;
        letter-spacing: -.01em;
        color: var(--m-text);
        line-height: 1;
    }
    /* Hue underline below the seat label = booking family. Subtle but
       always visible so an usher can scan for a family at a glance. */
    .mfst-card[data-hue] .seat::after {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        bottom: -6px;
        width: 22px;
        height: 3px;
        border-radius: 2px;
        background: var(--m-hue, transparent);
    }
    .mfst-card .ck {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        border-radius: 999px;
        background: var(--m-emerald);
        color: #052e1c;
        font-size: 10px;
        font-weight: 900;
        margin-inline-start: 4px;
    }

    /* Body — attendee name dominant, meta line quiet. */
    .mfst-card .body {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .mfst-card .name {
        font-size: 14.5px;
        font-weight: 600;
        color: var(--m-text);
        letter-spacing: -.005em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.25;
    }
    .mfst-card .meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11.5px;
        color: var(--m-text-3);
        font-feature-settings: "tnum" 1;
        line-height: 1.2;
    }
    .mfst-card .meta .sep {
        width: 3px;
        height: 3px;
        border-radius: 50%;
        background: var(--m-text-3);
        opacity: .55;
        flex-shrink: 0;
    }
    .mfst-card .meta .phone {
        font-family: ui-monospace, monospace;
        letter-spacing: .01em;
    }
    .mfst-card .meta .ref {
        font-weight: 600;
        color: var(--m-text-2);
    }

    /* Status pill at the end. Single calm token, monochrome for "free",
       coloured only when it carries operational meaning. */
    .mfst-card .status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--m-text-3);
        white-space: nowrap;
    }
    .mfst-card .status .dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: var(--m-text-3);
    }
    .mfst-card.is-approved .status        { color: var(--m-emerald); }
    .mfst-card.is-approved .status .dot   { background: var(--m-emerald); box-shadow: 0 0 8px rgba(52,211,153,0.55); }
    .mfst-card.is-pending  .status        { color: var(--m-amber); }
    .mfst-card.is-pending  .status .dot   { background: var(--m-amber); }
    .mfst-card.is-blocked  .status        { color: var(--m-rose); }
    .mfst-card.is-blocked  .status .dot   { background: var(--m-rose); }
    .mfst-card.is-empty {
        opacity: .58;
    }
    .mfst-card.is-empty .name { color: var(--m-text-3); font-weight: 500; }

    /* No-results card */
    .mfst-empty {
        padding: 32px 16px;
        text-align: center;
        border-radius: var(--m-radius);
        border: 1px dashed var(--m-border-strong);
        background: rgba(255,255,255,0.02);
        margin: 8px 0 24px;
    }
    .mfst-empty .t {
        font-size: 15px;
        font-weight: 700;
        color: var(--m-text);
        margin-bottom: 4px;
    }
    .mfst-empty .s {
        font-size: 12.5px;
        color: var(--m-text-3);
        line-height: 1.5;
    }

    /* Floating action button — single primary action: open the scanner.
       Sits in the safe-area bottom-end of the screen for one-handed use. */
    .mfst-fab {
        position: fixed;
        bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        inset-inline-end: calc(20px + env(safe-area-inset-right, 0px));
        z-index: 28;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 22px;
        height: 56px;
        border-radius: 999px;
        background: linear-gradient(180deg, rgba(56,189,248,0.95), rgba(34,211,238,0.85));
        border: 1px solid rgba(255,255,255,0.18);
        color: #02212d;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: .01em;
        text-decoration: none;
        box-shadow:
            0 14px 32px rgba(34,211,238,0.42),
            inset 0 1px 0 rgba(255,255,255,0.45);
        transition: transform .15s var(--m-ease), box-shadow .15s var(--m-ease);
        -webkit-tap-highlight-color: transparent;
    }
    .mfst-fab:hover { transform: translateY(-2px); box-shadow: 0 18px 36px rgba(34,211,238,0.52), inset 0 1px 0 rgba(255,255,255,0.45); }
    .mfst-fab:active { transform: translateY(0) scale(.97); }
    .mfst-fab .ico {
        font-size: 17px;
        line-height: 1;
    }

    /* ====================================================================
       BOTTOM SHEET — premium attendee detail
       Slides up from the bottom. Single dialog, single accent, no chart
       handoff (there's no chart in v3).
       ==================================================================== */
    .mfst-sheet {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        pointer-events: none;
    }
    .mfst-sheet.is-open { pointer-events: auto; }
    .mfst-sheet-scrim {
        position: absolute;
        inset: 0;
        background: rgba(2,4,12,0.55);
        opacity: 0;
        transition: opacity .25s var(--m-ease);
    }
    .mfst-sheet.is-open .mfst-sheet-scrim { opacity: 1; }
    .mfst-sheet-card {
        position: relative;
        background: linear-gradient(180deg, rgba(18,22,40,0.96), rgba(12,15,30,0.98));
        border-top-left-radius: 22px;
        border-top-right-radius: 22px;
        border-top: 1px solid rgba(255,255,255,0.10);
        padding:
            14px 20px
            calc(22px + env(safe-area-inset-bottom, 0px));
        transform: translateY(100%);
        transition: transform .32s var(--m-ease);
        max-height: 88vh;
        max-height: 88dvh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        box-shadow: 0 -24px 64px rgba(0,0,0,0.55);
    }
    .mfst-sheet.is-open .mfst-sheet-card { transform: translateY(0); }
    .mfst-sheet-grip {
        width: 42px;
        height: 4px;
        border-radius: 999px;
        background: rgba(255,255,255,0.22);
        margin: 4px auto 12px;
    }
    .mfst-sheet-head {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
    }
    .mfst-sheet-id {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .mfst-sheet-id .sec {
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--m-text-3);
    }
    .mfst-sheet-id .seat {
        font-size: 26px;
        font-weight: 800;
        color: var(--m-text);
        letter-spacing: -.01em;
        font-feature-settings: "tnum" 1;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .mfst-sheet-id .seat .ck {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 999px;
        background: var(--m-emerald);
        color: #052e1c;
        font-size: 13px;
        font-weight: 900;
    }
    .mfst-sheet-close {
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 999px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text-2);
        cursor: pointer;
        font-size: 14px;
    }
    .mfst-sheet-close:hover { background: rgba(255,255,255,0.08); color: var(--m-text); }

    .mfst-sheet-name {
        font-size: 19px;
        font-weight: 700;
        color: var(--m-text);
        letter-spacing: -.005em;
        margin-bottom: 10px;
        line-height: 1.25;
    }
    .mfst-sheet-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: 16px;
    }
    .mfst-sheet-status .dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: currentColor;
    }
    .mfst-sheet-status[data-tone="approved"] { background: rgba(52,211,153,0.12);  color: var(--m-emerald); }
    .mfst-sheet-status[data-tone="pending"]  { background: rgba(251,191,36,0.12);  color: var(--m-amber); }
    .mfst-sheet-status[data-tone="blocked"]  { background: rgba(251,113,133,0.12); color: var(--m-rose); }
    .mfst-sheet-status[data-tone="empty"]    { background: rgba(255,255,255,0.05); color: var(--m-text-3); }
    .mfst-sheet-status[data-tone="scanned"]  { background: rgba(56,189,248,0.12);  color: var(--m-sky); }

    .mfst-sheet-meta {
        display: flex;
        flex-direction: column;
        gap: 0;
        border: 1px solid var(--m-border);
        border-radius: 14px;
        background: rgba(255,255,255,0.025);
        margin-bottom: 14px;
        overflow: hidden;
    }
    .mfst-sheet-meta .row {
        display: grid;
        grid-template-columns: 90px 1fr;
        gap: 12px;
        padding: 11px 14px;
        font-size: 13.5px;
    }
    .mfst-sheet-meta .row + .row {
        border-top: 1px solid var(--m-border);
    }
    .mfst-sheet-meta .label {
        color: var(--m-text-3);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .02em;
    }
    .mfst-sheet-meta .value {
        color: var(--m-text);
        font-weight: 600;
        word-break: break-word;
    }
    .mfst-sheet-meta .value.phone {
        font-family: ui-monospace, monospace;
        letter-spacing: .02em;
        font-weight: 500;
    }

    .mfst-sheet-party {
        margin-bottom: 14px;
    }
    .mfst-sheet-party .label {
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--m-text-3);
        margin-bottom: 6px;
        padding-inline-start: 4px;
    }
    .mfst-sheet-party ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
        border: 1px solid var(--m-border);
        border-radius: 14px;
        overflow: hidden;
        background: rgba(255,255,255,0.02);
    }
    .mfst-sheet-party li {
        display: grid;
        grid-template-columns: 14px 64px 1fr auto;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        font-size: 13px;
    }
    .mfst-sheet-party li + li { border-top: 1px solid var(--m-border); }
    .mfst-sheet-party li .swatch {
        width: 10px;
        height: 10px;
        border-radius: 3px;
        background: var(--m-hue, rgba(255,255,255,0.30));
    }
    .mfst-sheet-party li .lbl {
        font-weight: 800;
        color: var(--m-text);
        font-feature-settings: "tnum" 1;
    }
    .mfst-sheet-party li .nm {
        color: var(--m-text-2);
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mfst-sheet-party li .here {
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--m-sky);
        background: rgba(56,189,248,0.12);
        padding: 3px 8px;
        border-radius: 999px;
    }
    .mfst-sheet-party li.is-current { background: rgba(56,189,248,0.05); }

    .mfst-sheet-actions {
        display: flex;
        gap: 10px;
    }
    .mfst-sheet-actions a {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 48px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        letter-spacing: .005em;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text);
        -webkit-tap-highlight-color: transparent;
        transition: background .15s var(--m-ease), border-color .15s var(--m-ease);
    }
    .mfst-sheet-actions a:hover {
        background: rgba(255,255,255,0.08);
        border-color: var(--m-border-strong);
    }
    .mfst-sheet-actions a.primary {
        background: linear-gradient(180deg, rgba(56,189,248,0.95), rgba(34,211,238,0.85));
        border-color: rgba(255,255,255,0.18);
        color: #02212d;
        box-shadow: 0 10px 26px rgba(34,211,238,0.32);
    }
    .mfst-sheet-actions a.primary:hover { box-shadow: 0 14px 30px rgba(34,211,238,0.42); }

    @media (prefers-reduced-motion: reduce) {
        .mfst-sheet-card { transition: none; }
        .mfst-card { transition: none; }
        .mfst-search { transition: none; }
    }

    /* ====================================================================
       PAPER MODE — A4 sheet + mobile-only preview
       ==================================================================== */
    /* Slim chrome above the sheet */
    .mfst-paper-bar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 14px;
        padding: 12px 4px 0;
    }
    .mfst-paper-bar .crumb {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--m-text-2);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }
    .mfst-paper-bar .crumb:hover { color: var(--m-text); }
    .mfst-paper-bar .spacer { flex: 1; }
    .mfst-paper-bar a, .mfst-paper-bar button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 14px;
        min-height: 40px;
        border-radius: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text-2);
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }
    .mfst-paper-bar .primary {
        background: linear-gradient(180deg, rgba(56,189,248,0.95), rgba(34,211,238,0.85));
        border-color: rgba(255,255,255,0.18);
        color: #02212d;
        box-shadow: 0 10px 26px rgba(34,211,238,0.28);
    }

    /* Mobile preview — the part the user explicitly hates today (a
       tiny A4 table on a phone). v3 replaces it with a clean grouped
       list that's actually readable on a phone. The A4 sheet itself
       (below) is hidden on mobile and re-appears at >=820px / on print. */
    .mfst-paper-preview {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .mfst-paper-cover {
        padding: 18px 20px;
        border-radius: var(--m-radius);
        border: 1px solid var(--m-border);
        background:
            radial-gradient(140% 80% at 100% 0%, rgba(167,139,250,0.10), transparent 60%),
            rgba(255,255,255,0.025);
    }
    .mfst-paper-cover .t {
        font-size: 17px;
        font-weight: 800;
        color: var(--m-text);
        letter-spacing: -.005em;
        margin-bottom: 4px;
    }
    .mfst-paper-cover .d {
        font-size: 12.5px;
        color: var(--m-text-3);
        margin-bottom: 14px;
    }
    .mfst-paper-cover .stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0,1fr));
        gap: 8px;
    }
    .mfst-paper-cover .stats .s {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.03);
    }
    .mfst-paper-cover .stats .s b {
        font-size: 18px;
        font-weight: 800;
        color: var(--m-text);
        font-feature-settings: "tnum" 1;
        line-height: 1.1;
    }
    .mfst-paper-cover .stats .s span {
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--m-text-3);
        margin-top: 2px;
    }
    .mfst-paper-cover .stats .s.is-approved b { color: var(--m-emerald); }
    .mfst-paper-cover .stats .s.is-pending  b { color: var(--m-amber); }
    .mfst-paper-cover .stats .s.is-blocked  b { color: var(--m-rose); }
    .mfst-paper-cover .stats .s.is-checked  b { color: var(--m-sky); }

    .mfst-paper-cover .b {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 14px;
        padding: 13px 20px;
        min-height: 48px;
        border-radius: 14px;
        background: linear-gradient(180deg, rgba(56,189,248,0.95), rgba(34,211,238,0.85));
        border: 1px solid rgba(255,255,255,0.18);
        color: #02212d;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 12px 30px rgba(34,211,238,0.32);
    }

    .mfst-paper-preview .sec {
        border-radius: var(--m-radius);
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.02);
        overflow: hidden;
    }
    .mfst-paper-preview .sec-head {
        padding: 12px 16px;
        border-bottom: 1px solid var(--m-border);
        background: rgba(255,255,255,0.03);
        display: flex;
        align-items: baseline;
        gap: 10px;
        justify-content: space-between;
    }
    .mfst-paper-preview .sec-head .t {
        font-size: 13.5px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--m-text);
    }
    .mfst-paper-preview .sec-head .s {
        font-size: 12px;
        color: var(--m-text-3);
        font-feature-settings: "tnum" 1;
    }
    .mfst-paper-preview .sec-head .s b { color: var(--m-text); font-weight: 700; }

    .mfst-paper-preview .row {
        padding: 8px 16px 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--m-text-3);
        border-top: 1px solid var(--m-border);
    }
    .mfst-paper-preview .row:first-of-type { border-top: 0; }

    .mfst-paper-preview .line {
        display: grid;
        grid-template-columns: 56px 1fr auto;
        gap: 10px;
        align-items: center;
        padding: 10px 16px;
        border-top: 1px solid rgba(255,255,255,0.04);
        position: relative;
    }
    .mfst-paper-preview .line[data-hue]::before {
        content: '';
        position: absolute;
        top: 0; bottom: 0;
        inset-inline-start: 0;
        width: 3px;
        background: var(--m-hue, transparent);
    }
    .mfst-paper-preview .line .seat {
        font-size: 15.5px;
        font-weight: 800;
        font-feature-settings: "tnum" 1;
        color: var(--m-text);
    }
    .mfst-paper-preview .line .info {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .mfst-paper-preview .line .info .nm {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--m-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mfst-paper-preview .line .info .sub {
        font-size: 11.5px;
        color: var(--m-text-3);
        font-feature-settings: "tnum" 1;
    }
    .mfst-paper-preview .line .info .sub .ref {
        color: var(--m-text-2);
        font-weight: 600;
    }
    .mfst-paper-preview .line .info .sub .phone {
        font-family: ui-monospace, monospace;
        letter-spacing: .01em;
    }
    .mfst-paper-preview .line .ck {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        color: var(--m-text-3);
    }
    .mfst-paper-preview .line.is-scanned .ck {
        color: var(--m-emerald);
    }
    .mfst-paper-preview .line.is-blocked .info .nm { color: var(--m-rose); }
    .mfst-paper-preview .line.is-empty .info .nm { color: var(--m-text-3); font-style: italic; }

    /* A4 sheet — only visible on tablet+ (>=820px) and on print. The
       desktop sheet is the actual WYSIWYG preview; mobile uses the
       readable preview above instead. */
    .mfst-paper-scroll { display: none; }
    .mfst-paper { background: #fff; color: #000; }
    @media (min-width: 820px) {
        .mfst-paper-preview { display: none; }
        .mfst-paper-scroll {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 18px 0 0;
        }
        .mfst-paper {
            padding: 22px 24px 18px;
            border-radius: 14px;
            font-family: 'IBM Plex Sans Arabic', 'Space Grotesk', sans-serif;
            box-shadow: 0 12px 32px rgba(0,0,0,0.18);
        }
    }
    .mfst-paper-section { margin-bottom: 18px; }
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
    }
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
    .mfst-paper-table td.t-seat  { width: 64px; text-align: center; font-weight: 800; font-size: 12pt; }
    .mfst-paper-table td.t-name  { font-weight: 700; font-size: 10.5pt; }
    .mfst-paper-table td.t-ref   { white-space: nowrap; }
    .mfst-paper-table td.t-phone { white-space: nowrap; font-family: ui-monospace, monospace; font-size: 9pt; }
    .mfst-paper-table td.t-check {
        width: 36px;
        text-align: center;
        font-size: 15pt;
        line-height: 1;
    }
    .mfst-paper-table td.t-check .box {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 1.4px solid #000;
        border-radius: 2px;
    }
    .mfst-paper-table tr.is-blocked td.t-name,
    .mfst-paper-table tr.is-blocked td.t-glyph { color: #b91c1c; font-weight: 800; }
    .mfst-paper-table tr.is-empty td { color: #888; }
    .mfst-paper-table tr[data-hue] td.t-seat {
        position: relative;
    }
    .mfst-paper-table tr[data-hue] td.t-seat::before {
        content: '';
        position: absolute;
        left: 2px; right: 2px; bottom: 2px;
        height: 3px;
        background: var(--m-hue);
    }
    .mfst-paper-rowsumm {
        margin-top: 4px;
        font-size: 8.5pt;
        color: #444;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .mfst-paper-rowsumm strong { color: #000; font-weight: 800; }
    .mfst-paper-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 8px;
        font-size: 8.5pt;
        color: #333;
    }
    .mfst-paper-legend .g {
        font-weight: 800;
        color: #000;
        margin-inline-end: 4px;
    }

    /* ====================================================================
       LIGHT-MODE OVERRIDES — manifest is operations chrome; in light mode
       everything switches to a cream/slate palette so it doesn't read as
       a dark-mode card pasted on a light page.
       ==================================================================== */
    :root[data-pt-theme="light"] {
        --m-border        : rgba(15,23,42,0.10);
        --m-border-strong : rgba(15,23,42,0.18);
    }
    :root[data-pt-theme="light"] .mfst-head .meta b { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-head-actions a,
    :root[data-pt-theme="light"] .mfst-head-actions button {
        background: rgba(255,255,255,0.7);
        border-color: rgba(15,23,42,0.10);
        color: var(--prism-text-2);
    }
    :root[data-pt-theme="light"] .mfst-head-actions a:hover,
    :root[data-pt-theme="light"] .mfst-head-actions button:hover {
        background: rgba(79,70,229,0.08);
        border-color: rgba(79,70,229,0.30);
        color: var(--prism-text);
    }
    :root[data-pt-theme="light"] .mfst-search-wrap {
        background: linear-gradient(180deg,
            rgba(248,250,252,0.95) 0%,
            rgba(248,250,252,0.80) 70%,
            rgba(248,250,252,0.0) 100%);
    }
    :root[data-pt-theme="light"] .mfst-search {
        background: rgba(255,255,255,0.95);
        border-color: rgba(15,23,42,0.12);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.9);
    }
    :root[data-pt-theme="light"] .mfst-search:focus-within {
        border-color: rgba(8,145,178,0.55);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(8,145,178,0.10);
    }
    :root[data-pt-theme="light"] .mfst-search input { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-search .clear {
        background: rgba(15,23,42,0.06);
        color: var(--prism-text-2);
    }
    :root[data-pt-theme="light"] .mfst-chip {
        background: rgba(255,255,255,0.7);
        border-color: rgba(15,23,42,0.10);
        color: var(--prism-text-2);
    }
    :root[data-pt-theme="light"] .mfst-chip[aria-pressed="true"] { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-section-head {
        background: linear-gradient(180deg,
            rgba(248,250,252,0.92) 0%,
            rgba(248,250,252,0.60) 80%,
            rgba(248,250,252,0.0) 100%);
    }
    :root[data-pt-theme="light"] .mfst-section-head .t { color: var(--prism-text-2); }
    :root[data-pt-theme="light"] .mfst-rowhead { color: var(--prism-text-3); }
    :root[data-pt-theme="light"] .mfst-rowhead .r { color: var(--prism-text-2); }
    :root[data-pt-theme="light"] .mfst-card {
        background: rgba(255,255,255,0.95);
        border-color: rgba(15,23,42,0.10);
        box-shadow: 0 1px 0 rgba(15,23,42,0.04);
    }
    :root[data-pt-theme="light"] .mfst-card:hover {
        background: #fff;
        border-color: rgba(15,23,42,0.16);
    }
    :root[data-pt-theme="light"] .mfst-card .seat { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-card .name { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-card .meta { color: var(--prism-text-3); }
    :root[data-pt-theme="light"] .mfst-card .meta .ref { color: var(--prism-text-2); }
    :root[data-pt-theme="light"] .mfst-card.is-approved .status { color: #047857; }
    :root[data-pt-theme="light"] .mfst-card.is-pending  .status { color: #b45309; }
    :root[data-pt-theme="light"] .mfst-card.is-blocked  .status { color: #be123c; }
    :root[data-pt-theme="light"] .mfst-empty {
        background: rgba(255,255,255,0.6);
        border-color: rgba(15,23,42,0.12);
    }
    :root[data-pt-theme="light"] .mfst-empty .t { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-sheet-card {
        background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.99));
        border-top-color: rgba(15,23,42,0.10);
    }
    :root[data-pt-theme="light"] .mfst-sheet-grip { background: rgba(15,23,42,0.18); }
    :root[data-pt-theme="light"] .mfst-sheet-id .sec { color: var(--prism-text-3); }
    :root[data-pt-theme="light"] .mfst-sheet-id .seat { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-sheet-name { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-sheet-meta {
        background: #fff;
        border-color: rgba(15,23,42,0.10);
    }
    :root[data-pt-theme="light"] .mfst-sheet-meta .label { color: var(--prism-text-3); }
    :root[data-pt-theme="light"] .mfst-sheet-meta .value { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-sheet-meta .row + .row { border-top-color: rgba(15,23,42,0.10); }
    :root[data-pt-theme="light"] .mfst-sheet-party ul {
        background: #fff;
        border-color: rgba(15,23,42,0.10);
    }
    :root[data-pt-theme="light"] .mfst-sheet-party li + li { border-top-color: rgba(15,23,42,0.10); }
    :root[data-pt-theme="light"] .mfst-sheet-party li.is-current { background: rgba(8,145,178,0.06); }
    :root[data-pt-theme="light"] .mfst-sheet-actions a {
        background: #fff;
        border-color: rgba(15,23,42,0.12);
        color: var(--prism-text);
    }
    :root[data-pt-theme="light"] .mfst-paper-bar a,
    :root[data-pt-theme="light"] .mfst-paper-bar button {
        background: rgba(255,255,255,0.85);
        border-color: rgba(15,23,42,0.12);
        color: var(--prism-text-2);
    }
    :root[data-pt-theme="light"] .mfst-paper-cover {
        background:
            radial-gradient(140% 80% at 100% 0%, rgba(167,139,250,0.10), transparent 60%),
            rgba(255,255,255,0.85);
        border-color: rgba(15,23,42,0.10);
    }
    :root[data-pt-theme="light"] .mfst-paper-cover .t { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-paper-cover .stats .s {
        background: #fff;
        border-color: rgba(15,23,42,0.10);
    }
    :root[data-pt-theme="light"] .mfst-paper-cover .stats .s b { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-paper-cover .stats .s span { color: var(--prism-text-3); }
    :root[data-pt-theme="light"] .mfst-paper-preview .sec {
        background: rgba(255,255,255,0.95);
        border-color: rgba(15,23,42,0.10);
    }
    :root[data-pt-theme="light"] .mfst-paper-preview .sec-head {
        background: rgba(15,23,42,0.03);
        border-bottom-color: rgba(15,23,42,0.10);
    }
    :root[data-pt-theme="light"] .mfst-paper-preview .sec-head .t { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-paper-preview .row { color: var(--prism-text-3); border-top-color: rgba(15,23,42,0.08); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line { border-top-color: rgba(15,23,42,0.06); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line .seat { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line .info .nm { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line .info .sub { color: var(--prism-text-3); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line .info .sub .ref { color: var(--prism-text-2); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line.is-scanned .ck { color: #047857; }
    :root[data-pt-theme="light"] .mfst-paper-preview .line.is-blocked .info .nm { color: #be123c; }

    /* ====================================================================
       PRINT RULES — paper-first; only the A4 sheet survives.
       The actual A4 print output is intentionally unchanged from v2.
       ==================================================================== */
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm 10mm 14mm 10mm;
            @bottom-right {
                content: "Page " counter(page) " / " counter(pages);
                font-size: 8pt;
                color: #444;
                font-family: 'IBM Plex Sans Arabic', 'Space Grotesk', sans-serif;
            }
        }
        body, html { background: #fff !important; color: #000 !important; }
        body.has-bg::before, body.has-bg::after { display: none !important; }

        body * { visibility: hidden; }
        .mfst-paper, .mfst-paper * { visibility: visible; }
        .mfst-paper {
            position: absolute;
            inset: 0;
            padding: 0;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
            display: block !important;
            min-width: 0 !important;
        }
        .mfst-paper-scroll {
            display: block !important;
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .mfst-paper-bar, .mfst-paper-preview, .mfst-floor, .pt-no-print { display: none !important; }

        .mfst-paper-section { page-break-inside: auto; break-inside: auto; }
        .mfst-paper-section-head { page-break-after: avoid; break-after: avoid; }
        .mfst-paper-table tr     { page-break-inside: avoid; break-inside: avoid; }
        .mfst-paper-table thead  { display: table-header-group; }
        .mfst-paper-table tfoot  { display: table-footer-group; }
        .mfst-paper-table td.t-check .box {
            border: 1.5px solid #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
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
     data-checked-in-initial="{{ $checkedInCount }}"
     data-capacity="{{ $capacity }}"
     dir="rtl">

    @if ($mode === 'floor')
        {{-- ============================================================
             FLOOR MODE — live operations
             ============================================================ --}}
        <div class="mfst-floor">

            <header class="mfst-head pt-no-print">
                <div class="title">
                    <span class="live-dot" aria-hidden="true"></span>
                    <span>{{ $showTitle }}</span>
                </div>
                <div class="meta">
                    <span>{{ $eventDate }}</span>
                    @if ($eventTime)<span class="sep">·</span><span>{{ $eventTime }}</span>@endif
                    <span class="sep">·</span>
                    <span><b class="js-gauge-num">{{ $totalBooked }}</b> / {{ $capacity }} booked</span>
                    <span class="sep">·</span>
                    <span>✓ <b class="js-checked-num">{{ $checkedInCount }}</b> in</span>
                </div>
                <div class="mfst-head-actions">
                    <a href="{{ $url(['mode' => 'paper']) }}" data-i18n="mfst_paper">🖨 Paper sheet</a>
                    <a href="/admin/scanner" data-i18n="mfst_scanner">📷 Scanner</a>
                    <a href="{{ $url(['full_phone' => $showFullPhone ? 0 : 1]) }}">
                        {{ $showFullPhone ? '🙈 Mask phones' : '👁 Full phones' }}
                    </a>
                    @if ($show)
                        <a href="{{ route('admin.shows.times.index', $show) }}">← Back</a>
                    @else
                        <a href="{{ route('admin.dashboard') }}">← Back</a>
                    @endif
                </div>
            </header>

            <div class="mfst-search-wrap pt-no-print">
                <label class="mfst-search">
                    <span class="icon" aria-hidden="true">⌕</span>
                    <input type="search"
                           class="js-search-input"
                           placeholder="Search seat (A12), attendee, phone…"
                           aria-label="Search seats or attendees"
                           autocomplete="off"
                           autocapitalize="off"
                           inputmode="search">
                    <button type="button" class="clear js-search-clear" aria-label="Clear search" hidden>✕</button>
                </label>
                <div class="mfst-search-hint js-search-hint" aria-live="polite"></div>

                <div class="mfst-chips" role="tablist" aria-label="Status filter">
                    <button type="button" class="mfst-chip mfst-chip-approved js-filter-chip" data-status="approved" aria-pressed="{{ in_array('approved', $statusFilter) ? 'true' : 'false' }}">
                        <span>Booked</span><span class="count">{{ $summary['approved'] }}</span>
                    </button>
                    <button type="button" class="mfst-chip mfst-chip-pending js-filter-chip" data-status="pending" aria-pressed="{{ in_array('pending', $statusFilter) ? 'true' : 'false' }}">
                        <span>Pending</span><span class="count">{{ $summary['pending'] }}</span>
                    </button>
                    <button type="button" class="mfst-chip mfst-chip-blocked js-filter-chip" data-status="blocked" aria-pressed="{{ in_array('blocked', $statusFilter) ? 'true' : 'false' }}">
                        <span>Blocked</span><span class="count">{{ $summary['blocked'] }}</span>
                    </button>
                    <button type="button" class="mfst-chip mfst-chip-checked js-filter-chip" data-status="checked_in" aria-pressed="{{ in_array('checked_in', $statusFilter) ? 'true' : 'false' }}">
                        <span>✓ Checked-in</span><span class="count js-filter-checked-count">{{ $checkedInCount }}</span>
                    </button>
                </div>
            </div>

            <div class="mfst-list js-floor-list">
                @foreach ($floorRowsBySectionRow as $sectionLabel => $byRow)
                    @php $secStats = $floorSectionStats[$sectionLabel] ?? ['booked' => 0, 'blocked' => 0, 'total' => 0]; @endphp
                    <section class="mfst-section js-floor-section" data-section-key="{{ $sectionLabel }}">
                        <header class="mfst-section-head">
                            <span class="t">{{ $sectionLabel }}</span>
                            <span class="s"><b>{{ $secStats['booked'] }}</b> of {{ $secStats['total'] }}</span>
                        </header>
                        @foreach ($byRow as $rletter => $block)
                            <div class="mfst-rowgroup js-floor-rowgroup" data-row="{{ $rletter }}">
                                <div class="mfst-rowhead" aria-hidden="true">
                                    <span class="r">Row {{ $rletter }}</span>
                                    <span class="b"></span>
                                    <span class="n">{{ $block['booked'] }}/{{ $block['total'] }}</span>
                                </div>
                                @foreach ($block['seats'] as $s)
                                    @php
                                        $seatLabel = $s['row_letter'] . $s['seat_number'];
                                        $hue = $s['booking_id'] ? ($bookingColorIndex[$s['booking_id']] ?? null) : null;
                                        $statusLabel = match ($s['status']) {
                                            'approved' => 'Booked',
                                            'pending'  => 'Pending',
                                            'blocked'  => 'Blocked',
                                            'empty'    => 'Free',
                                            default    => $s['status'],
                                        };
                                    @endphp
                                    <article class="mfst-card is-{{ $s['status'] }} @if($s['is_scanned']) is-scanned @endif {{ $hue !== null ? 'mfst-hue-' . $hue : '' }} js-floor-card"
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
                                        <div class="seat-block">
                                            <span class="seat">{{ $seatLabel }}</span>
                                            @if ($s['is_scanned'])<span class="ck" aria-label="Checked in">✓</span>@endif
                                        </div>
                                        <div class="body">
                                            <div class="name">
                                                @if ($s['status'] === 'blocked')
                                                    Blocked seat
                                                @elseif ($s['status'] === 'empty')
                                                    Free
                                                @else
                                                    {{ $s['attendee_name'] ?: '—' }}
                                                @endif
                                            </div>
                                            <div class="meta">
                                                @if ($s['phone'])<span class="phone">{{ $maskPhone($s['phone']) }}</span>@endif
                                                @if ($s['phone'] && $s['booking_ref'])<span class="sep"></span>@endif
                                                @if ($s['booking_ref'])<span class="ref">{{ $s['booking_ref'] }}</span>@endif
                                            </div>
                                        </div>
                                        <div class="status" aria-hidden="true">
                                            <span class="dot"></span>
                                            <span>{{ $statusLabel }}</span>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endforeach
                    </section>
                @endforeach
            </div>

            <div class="mfst-empty js-floor-empty" style="display:none;">
                <div class="t">No matches</div>
                <div class="s">Try a different name, seat label (A12), or booking reference.</div>
            </div>

            <a href="/admin/scanner" class="mfst-fab pt-no-print" aria-label="Open QR scanner">
                <span class="ico" aria-hidden="true">⌒</span>
                <span>Scan</span>
            </a>

            {{-- Bottom sheet — attendee detail --}}
            <div class="mfst-sheet" aria-hidden="true" data-sheet>
                <div class="mfst-sheet-scrim js-sheet-scrim"></div>
                <div class="mfst-sheet-card" role="dialog" aria-modal="true" aria-labelledby="mfst-sheet-title">
                    <div class="mfst-sheet-grip" aria-hidden="true"></div>
                    <div class="mfst-sheet-head">
                        <div class="mfst-sheet-id">
                            <div class="sec" data-sheet-section></div>
                            <div class="seat" id="mfst-sheet-title">
                                <span data-sheet-seat>—</span>
                                <span class="ck js-sheet-ck" aria-label="Checked in" style="display:none;">✓</span>
                            </div>
                        </div>
                        <button type="button" class="mfst-sheet-close js-sheet-close" aria-label="Close detail">✕</button>
                    </div>
                    <div class="mfst-sheet-name" data-sheet-name>—</div>
                    <div class="mfst-sheet-status js-sheet-status-pill" data-tone="approved">
                        <span class="dot" aria-hidden="true"></span>
                        <span data-sheet-status>—</span>
                    </div>
                    <div class="mfst-sheet-meta">
                        <div class="row"><span class="label">Booking</span><span class="value" data-sheet-booking>—</span></div>
                        <div class="row"><span class="label">Owner</span><span class="value" data-sheet-owner>—</span></div>
                        <div class="row"><span class="label">Phone</span><span class="value phone" data-sheet-phone>—</span></div>
                        <div class="row js-sheet-scan-row" style="display:none;">
                            <span class="label">Checked in</span><span class="value" data-sheet-scan>—</span>
                        </div>
                    </div>
                    <div class="mfst-sheet-party js-sheet-party" style="display:none;">
                        <div class="label">Booking party</div>
                        <ul class="js-sheet-party-list"></ul>
                    </div>
                    <div class="mfst-sheet-actions">
                        <a class="primary js-sheet-scan" href="/admin/scanner">Scan to verify</a>
                        <a class="js-sheet-close-link" href="#" aria-label="Close">Close</a>
                    </div>
                </div>
            </div>
        </div>

    @elseif ($mode === 'paper')
        {{-- ============================================================
             PAPER MODE — mobile-readable preview + A4 print sheet
             ============================================================ --}}
        <div class="mfst-paper-bar pt-no-print">
            <a href="{{ $url(['mode' => 'floor']) }}" class="crumb" data-i18n="mfst_back_to_floor">
                ← Back to floor
            </a>
            <span class="spacer"></span>
            <a href="{{ $url(['mode' => 'paper', 'include_empty' => $includeEmpty ? 0 : 1]) }}">
                {{ $includeEmpty ? 'Hide empty' : 'Include empty' }}
            </a>
            <a href="{{ $csvUrl }}">⬇ CSV</a>
            <button type="button" class="primary js-print-now">🖨 Print sheet</button>
        </div>

        {{-- Mobile-only readable preview --}}
        <div class="mfst-paper-preview pt-no-print">
            <div class="mfst-paper-cover">
                <div class="t">{{ $showTitle }}</div>
                <div class="d">
                    {{ $eventDate }}
                    @if ($eventTime) · {{ $eventTime }} @endif
                    · A4 landscape sheet
                </div>
                <div class="stats">
                    <div class="s is-approved"><b>{{ $summary['approved'] }}</b><span>Approved</span></div>
                    <div class="s is-pending"><b>{{ $summary['pending'] }}</b><span>Pending</span></div>
                    <div class="s is-blocked"><b>{{ $summary['blocked'] }}</b><span>Blocked</span></div>
                    <div class="s is-checked"><b>{{ $checkedInCount }}</b><span>✓ in</span></div>
                </div>
                <button type="button" class="b js-print-now">🖨 Print sheet</button>
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
                <section class="sec">
                    <header class="sec-head">
                        <span class="t">{{ $sectionLabel }}</span>
                        <span class="s">
                            <b>{{ $secApproved + $secPending }}</b> attendees
                            @if ($secBlocked) · {{ $secBlocked }} blocked @endif
                            @if ($includeEmpty && $secEmpty) · {{ $secEmpty }} free @endif
                        </span>
                    </header>
                    @foreach ($byRow as $rletter => $seats)
                        <div class="row">Row {{ $rletter }}</div>
                        @foreach ($seats as $s)
                            @php
                                $seatLabel = $s['row_letter'] . $s['seat_number'];
                                $hue = $s['booking_id'] ? ($bookingColorIndex[$s['booking_id']] ?? null) : null;
                            @endphp
                            <div class="line is-{{ $s['status'] }} @if($s['is_scanned']) is-scanned @endif {{ $hue !== null ? 'mfst-hue-' . $hue : '' }}"
                                 @if($hue !== null) data-hue="{{ $hue }}" @endif>
                                <div class="seat">{{ $seatLabel }}</div>
                                <div class="info">
                                    <div class="nm">
                                        @if ($s['status'] === 'blocked')
                                            BLOCKED
                                        @elseif ($s['status'] === 'empty')
                                            Free
                                        @else
                                            {{ $s['attendee_name'] ?: '—' }}
                                        @endif
                                    </div>
                                    @if ($s['booking_ref'] || $s['phone'])
                                        <div class="sub">
                                            @if ($s['booking_ref'])<span class="ref">{{ $s['booking_ref'] }}</span>@endif
                                            @if ($s['booking_ref'] && $s['phone']) · @endif
                                            @if ($s['phone'])<span class="phone">{{ $maskPhone($s['phone']) }}</span>@endif
                                        </div>
                                    @endif
                                </div>
                                <div class="ck">{{ $s['is_scanned'] ? '✓ in' : '☐' }}</div>
                            </div>
                        @endforeach
                    @endforeach
                </section>
            @endforeach
        </div>

        {{-- Desktop / print A4 sheet --}}
        <div class="mfst-paper-scroll">
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
                                    <tr class="is-{{ $s['status'] }} {{ $hue !== null ? 'mfst-hue-' . $hue : '' }}" @if ($hue !== null) data-hue="{{ $hue }}" @endif>
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
                <span style="margin-inline-start:auto;">Printed {{ now()->format('Y-m-d H:i') }} · Phones masked except last 4 · Family band = same booking</span>
            </div>
        </div>
        </div>{{-- /.mfst-paper-scroll --}}
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const root = document.querySelector('.manifest-root');
    if (!root) return;

    const mode = root.dataset.mode || 'floor';

    /* ====================================================================
       Auto-print when ?autoprint=1 or #autoprint is present
       ==================================================================== */
    if (mode === 'paper') {
        const wantsAutoprint = window.location.hash === '#autoprint'
                            || /[?&]autoprint=1/.test(window.location.search);
        if (wantsAutoprint) {
            setTimeout(() => { try { window.print(); } catch (e) {} }, 350);
        }
        document.querySelectorAll('.js-print-now').forEach(btn => {
            btn.addEventListener('click', () => window.print());
        });
    }

    if (mode !== 'floor') return;

    /* ====================================================================
       Floor — search + filter + bottom sheet + polling
       ==================================================================== */
    let searchTerm = '';

    function getActiveStatuses() {
        return Array.from(document.querySelectorAll('.js-filter-chip[aria-pressed="true"]'))
            .map(b => b.dataset.status);
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
        document.querySelectorAll('.js-search-clear').forEach(btn => {
            btn.hidden = !q;
        });
    }
    function applyFilter() {
        applyFloorVisibility();
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

    document.querySelectorAll('.js-search-input').forEach(input => {
        input.addEventListener('input', (e) => {
            searchTerm = e.target.value;
            applyFloorVisibility();
        });
    });
    document.querySelectorAll('.js-search-clear').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.js-search-input').forEach(i => { i.value = ''; });
            searchTerm = '';
            applyFloorVisibility();
            const input = document.querySelector('.js-search-input');
            if (input) input.focus();
        });
    });
    // `/` shortcut focuses search (desktop convenience)
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
            const sheet = document.querySelector('[data-sheet].is-open');
            if (sheet) { closeSheet(); return; }
            document.querySelectorAll('.js-search-input').forEach(i => { if (i.value) i.value = ''; });
            searchTerm = '';
            applyFloorVisibility();
        }
    });

    /* ====================================================================
       Bottom sheet
       ==================================================================== */
    const sheet = document.querySelector('[data-sheet]');
    const sheetScrim = document.querySelector('.js-sheet-scrim');
    const sheetClose = document.querySelector('.js-sheet-close');
    const sheetCloseLink = document.querySelector('.js-sheet-close-link');
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
        if (d.status === 'blocked')       displayName = 'Blocked seat';
        else if (d.status === 'empty')    displayName = 'Free seat';
        else                              displayName = d.name || '—';
        $('[data-sheet-name]').textContent    = displayName;
        $('[data-sheet-status]').textContent  = d.statusEn || (d.status || '—');
        $('[data-sheet-booking]').textContent = d.bookingRef || '—';
        $('[data-sheet-owner]').textContent   = d.owner || '—';
        $('[data-sheet-phone]').textContent   = d.phone || '—';

        const statusPill = $('.js-sheet-status-pill');
        if (statusPill) {
            const tone = (d.checked === '1') ? 'scanned' : (d.status || 'approved');
            statusPill.setAttribute('data-tone', tone);
        }
        const sheetCk = $('.js-sheet-ck');
        if (sheetCk) sheetCk.style.display = (d.checked === '1') ? '' : 'none';

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

        // Party — all seats in the same booking, current one marked "Here"
        const partyWrap = $('.js-sheet-party');
        const partyList = $('.js-sheet-party-list');
        partyList.innerHTML = '';
        if (d.bookingId) {
            const partyCards = document.querySelectorAll('.js-floor-card[data-booking-id="' + d.bookingId + '"]');
            if (partyCards.length > 1) {
                const FAMILY_HUES = [
                    'rgba(34,211,238,0.85)',
                    'rgba(129,140,248,0.85)',
                    'rgba(244,114,182,0.85)',
                    'rgba(251,191,36,0.85)',
                    'rgba(52,211,153,0.85)',
                    'rgba(251,113,133,0.85)',
                    'rgba(167,139,250,0.85)',
                    'rgba(252,165,165,0.85)',
                ];
                const hueIdx = parseInt(d.hue, 10);
                const hueColor = Number.isFinite(hueIdx) ? FAMILY_HUES[hueIdx % FAMILY_HUES.length] : 'rgba(255,255,255,0.20)';
                partyCards.forEach(p => {
                    const li = document.createElement('li');
                    if (p.dataset.seat === d.seat) li.classList.add('is-current');
                    li.style.setProperty('--m-hue', hueColor);
                    const sw = document.createElement('span');
                    sw.className = 'swatch';
                    sw.setAttribute('aria-hidden', 'true');
                    const lbl = document.createElement('span');
                    lbl.className = 'lbl';
                    lbl.textContent = p.dataset.seat || '—';
                    const nm = document.createElement('span');
                    nm.className = 'nm';
                    nm.textContent = p.dataset.name || '—';
                    li.appendChild(sw);
                    li.appendChild(lbl);
                    li.appendChild(nm);
                    if (p.dataset.seat === d.seat) {
                        const here = document.createElement('span');
                        here.className = 'here';
                        here.textContent = 'Here';
                        li.appendChild(here);
                    }
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
    if (sheetCloseLink) sheetCloseLink.addEventListener('click', (e) => { e.preventDefault(); closeSheet(); });

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
       Deep-link focus — ?focus=A12 scrolls + pulses the matching card
       ==================================================================== */
    const focusSeat = root.dataset.focusSeat || '';
    if (focusSeat) {
        const target = document.querySelector('.js-floor-card[data-seat="' + focusSeat + '"]');
        if (target) {
            requestAnimationFrame(() => {
                target.scrollIntoView({ block: 'center', behavior: 'smooth' });
                target.classList.add('is-focused');
            });
        }
    }

    /* ====================================================================
       Live polling — refresh booked / ✓ count + per-seat scanned marker
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

                const booked = (data.summary.approved || 0) + (data.summary.pending || 0);
                const num = document.querySelector('.js-gauge-num');
                if (num) num.textContent = booked;

                const checkedNum = document.querySelector('.js-checked-num');
                if (checkedNum) checkedNum.textContent = String(data.checked_in || 0);

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

                const ck = document.querySelector('.js-filter-checked-count');
                if (ck) ck.textContent = String(data.checked_in || 0);
            } catch (e) {
                /* silent — polling continues */
            }
        }

        tick();
        return setInterval(tick, 10000);
    }
    startPolling();

})();
</script>
@endpush
