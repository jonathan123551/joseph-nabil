@extends('layouts.app')

@php
    use App\Models\Show as ShowModel;

    $eventDate = optional($showTime->date)->format('d/m/Y');
    $eventTime = $showTime->time ? \Carbon\Carbon::parse($showTime->time)->format('g:i A') : '';
    $showTitle = optional($show)->title ?? '—';

    // Phone masking happens at the view layer so the controller's row
    // payload stays unmasked and the same data drives the CSV export.
    $maskPhone = function (?string $phone) use ($showFullPhone) {
        if (!$phone) return '';
        if ($showFullPhone) return $phone;
        $digits = preg_replace('/\D+/', '', $phone);
        $len = strlen($digits);
        if ($len <= 6) return $phone;
        return substr($digits, 0, 2) . str_repeat('●', max(0, $len - 6)) . substr($digits, -4);
    };

    // Group rows by section → row letter so we can render banded blocks
    // on the print sheet. PHP preserves insertion order, and the
    // controller already ordered by (section, row, seat#).
    $rowsBySectionRow = [];
    foreach ($rows as $r) {
        $rowsBySectionRow[$r['section_label_ar']][$r['row_letter']][] = $r;
    }

    // Stable booking-id → color band index so each booking gets its own
    // subtle background on the print sheet ("families pop together").
    // Capped at 8 distinct hues; beyond that we cycle, which is fine
    // because no two adjacent bookings on a row are likely to land on
    // the same hue.
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

    // URL helpers — preserve current `full_phone` flag across view
    // switches so the toggle doesn't fight the user.
    $url = function ($params = []) use ($showTime, $showFullPhone) {
        $base = route('admin.show-times.manifest', $showTime);
        $q    = array_merge(['full_phone' => $showFullPhone ? 1 : 0], $params);
        return $base . '?' . http_build_query($q);
    };

    $csvUrl = route('admin.show-times.manifest.csv', $showTime)
        . '?' . http_build_query(['full_phone' => $showFullPhone ? 1 : 0]);
@endphp

@section('title', 'مانيفست المقاعد · ' . $showTitle)

@push('styles')
<style>
    /* ====================================================================
       Seat Occupancy / Attendee Manifest — Phase 1
       Optimized for printable A4-landscape hard copies AND a usable
       on-screen view. Print rules at the bottom of the file override the
       screen layout completely to produce a paper-first artifact.
       ==================================================================== */

    .manifest-shell { display: grid; gap: 16px; }

    /* Top chrome — view switcher, phone toggle, export buttons. Hidden
       on print so the printed page is pure data. */
    .manifest-chrome {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
    }
    .manifest-chrome .pt-tabs {
        display: inline-flex;
        border: 1px solid var(--prism-border);
        border-radius: 999px;
        overflow: hidden;
        background: rgba(255,255,255,0.03);
    }
    .manifest-chrome .pt-tabs a {
        padding: 8px 14px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        color: var(--prism-text-2);
        transition: background .15s ease, color .15s ease;
    }
    .manifest-chrome .pt-tabs a:hover { color: var(--prism-text); background: rgba(129,140,248,0.08); }
    .manifest-chrome .pt-tabs a.is-active {
        color: #fff;
        background: linear-gradient(135deg, rgba(34,211,238,0.22), rgba(192,132,252,0.22));
    }

    /* Summary stats card — visible on both screen and print so the
       printed manifest has a header summary the head usher can lean on. */
    .manifest-stats {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
    }
    @media (max-width: 720px) { .manifest-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .manifest-stat {
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid var(--prism-border);
        background: rgba(255,255,255,0.03);
        text-align: center;
    }
    .manifest-stat .v { font-size: 18px; font-weight: 800; font-feature-settings: "tnum" 1; }
    .manifest-stat .l { font-size: 10px; letter-spacing: .12em; text-transform: uppercase; color: var(--prism-text-3); margin-top: 2px; }
    .manifest-stat.approved .v { color: var(--prism-emerald); }
    .manifest-stat.pending .v  { color: #fcd34d; }
    .manifest-stat.blocked .v  { color: #fda4af; }
    .manifest-stat.empty .v    { color: var(--prism-text-3); }
    .manifest-stat.total .v    { color: var(--prism-text); }

    /* Search box (usher view) */
    .manifest-search {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid var(--prism-border);
        background: rgba(255,255,255,0.03);
    }
    .manifest-search input {
        flex: 1;
        background: transparent;
        border: 0;
        outline: 0;
        color: var(--prism-text);
        font-size: 14px;
        font-weight: 600;
    }
    .manifest-search input::placeholder { color: var(--prism-text-3); }

    /* ====================================================================
       Print sheet (the main artifact). Rendered as one block per (section,
       row) so each strip prints together without breaking across columns. */
    .manifest-print {
        display: grid;
        gap: 10px;
    }
    .manifest-section {
        border: 1px solid var(--prism-border);
        border-radius: 14px;
        overflow: hidden;
        background: rgba(255,255,255,0.02);
    }
    .manifest-section h3 {
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        padding: 8px 12px;
        background: linear-gradient(135deg, rgba(34,211,238,0.10), rgba(192,132,252,0.10));
        border-bottom: 1px solid var(--prism-border);
    }
    .manifest-section.section-balcony h3 {
        background: linear-gradient(135deg, rgba(251,191,36,0.10), rgba(192,132,252,0.10));
    }
    .manifest-row-strip {
        display: grid;
        grid-template-columns: 56px 1fr;
        align-items: stretch;
        border-top: 1px solid var(--prism-border);
    }
    .manifest-row-strip:first-of-type { border-top: 0; }
    .manifest-row-label {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 800;
        letter-spacing: .08em;
        background: rgba(255,255,255,0.04);
        border-inline-end: 1px solid var(--prism-border);
        color: var(--prism-text);
    }
    .manifest-row-seats {
        display: grid;
        gap: 4px;
        padding: 6px;
        /* keep at most ~10 per row on print — 6 on narrow screens */
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
    .manifest-cell {
        display: flex;
        flex-direction: column;
        gap: 1px;
        padding: 6px 8px;
        border-radius: 8px;
        border: 1px solid var(--prism-border);
        background: rgba(255,255,255,0.02);
        font-size: 11px;
        line-height: 1.25;
        min-height: 44px;
    }
    .manifest-cell .seat-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-feature-settings: "tnum" 1;
    }
    .manifest-cell .seat-num {
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .02em;
        color: var(--prism-text);
    }
    .manifest-cell .seat-tag {
        font-size: 9px;
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .manifest-cell .seat-attendee {
        font-weight: 700;
        color: var(--prism-text);
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .manifest-cell .seat-meta {
        color: var(--prism-text-3);
        font-size: 10px;
        font-feature-settings: "tnum" 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Status accents on each cell */
    .manifest-cell.is-approved { border-color: rgba(52,211,153,0.45); }
    .manifest-cell.is-approved .seat-tag { background: rgba(52,211,153,0.16); color: #6ee7b7; }
    .manifest-cell.is-pending  { border-color: rgba(251,191,36,0.55); background: rgba(251,191,36,0.04); }
    .manifest-cell.is-pending  .seat-tag { background: rgba(251,191,36,0.18); color: #fde68a; }
    .manifest-cell.is-blocked  { border-color: rgba(244,63,94,0.55); background: rgba(244,63,94,0.04); }
    .manifest-cell.is-blocked  .seat-tag { background: rgba(244,63,94,0.18); color: #fda4af; }
    .manifest-cell.is-empty    { opacity: .55; }
    .manifest-cell.is-empty    .seat-tag { background: rgba(255,255,255,0.06); color: var(--prism-text-3); }
    .manifest-cell.is-scanned  { background: rgba(52,211,153,0.06); }
    .manifest-cell .seat-check {
        font-size: 11px;
        font-weight: 800;
        color: var(--prism-emerald);
        letter-spacing: .04em;
    }

    /* Per-booking color band so family groups visually cluster on print */
    .manifest-cell[data-hue="0"] { box-shadow: inset 3px 0 0 0 rgba(34,211,238,0.55); }
    .manifest-cell[data-hue="1"] { box-shadow: inset 3px 0 0 0 rgba(192,132,252,0.55); }
    .manifest-cell[data-hue="2"] { box-shadow: inset 3px 0 0 0 rgba(251,191,36,0.55); }
    .manifest-cell[data-hue="3"] { box-shadow: inset 3px 0 0 0 rgba(52,211,153,0.55); }
    .manifest-cell[data-hue="4"] { box-shadow: inset 3px 0 0 0 rgba(244,114,182,0.55); }
    .manifest-cell[data-hue="5"] { box-shadow: inset 3px 0 0 0 rgba(96,165,250,0.55); }
    .manifest-cell[data-hue="6"] { box-shadow: inset 3px 0 0 0 rgba(251,113,133,0.55); }
    .manifest-cell[data-hue="7"] { box-shadow: inset 3px 0 0 0 rgba(167,243,208,0.55); }
    html[dir="rtl"] .manifest-cell[data-hue]  { box-shadow: inset -3px 0 0 0 currentColor; }
    html[dir="rtl"] .manifest-cell[data-hue="0"] { box-shadow: inset -3px 0 0 0 rgba(34,211,238,0.55); }
    html[dir="rtl"] .manifest-cell[data-hue="1"] { box-shadow: inset -3px 0 0 0 rgba(192,132,252,0.55); }
    html[dir="rtl"] .manifest-cell[data-hue="2"] { box-shadow: inset -3px 0 0 0 rgba(251,191,36,0.55); }
    html[dir="rtl"] .manifest-cell[data-hue="3"] { box-shadow: inset -3px 0 0 0 rgba(52,211,153,0.55); }
    html[dir="rtl"] .manifest-cell[data-hue="4"] { box-shadow: inset -3px 0 0 0 rgba(244,114,182,0.55); }
    html[dir="rtl"] .manifest-cell[data-hue="5"] { box-shadow: inset -3px 0 0 0 rgba(96,165,250,0.55); }
    html[dir="rtl"] .manifest-cell[data-hue="6"] { box-shadow: inset -3px 0 0 0 rgba(251,113,133,0.55); }
    html[dir="rtl"] .manifest-cell[data-hue="7"] { box-shadow: inset -3px 0 0 0 rgba(167,243,208,0.55); }

    /* ====================================================================
       Usher view (mobile-first searchable single-column list) */
    .manifest-usher-list { display: grid; gap: 6px; }
    .manifest-usher-row {
        display: grid;
        grid-template-columns: 90px 1fr auto;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid var(--prism-border);
        background: rgba(255,255,255,0.03);
        min-height: 44px;
    }
    .manifest-usher-row.is-approved { border-color: rgba(52,211,153,0.35); }
    .manifest-usher-row.is-pending  { border-color: rgba(251,191,36,0.45); }
    .manifest-usher-row.is-blocked  { border-color: rgba(244,63,94,0.45); }
    .manifest-usher-row.is-empty    { opacity: .55; }
    .manifest-usher-row .seat-block { display: flex; align-items: center; gap: 6px; }
    .manifest-usher-row .seat-block .pt-seat-chip { padding: 4px 10px; font-size: 12px; }
    .manifest-usher-row .who { display: flex; flex-direction: column; min-width: 0; }
    .manifest-usher-row .who .nm { font-weight: 700; color: var(--prism-text); font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .manifest-usher-row .who .meta { font-size: 11px; color: var(--prism-text-3); font-feature-settings: "tnum" 1; }
    .manifest-usher-row .ck { font-size: 11px; font-weight: 800; letter-spacing: .04em; }
    .manifest-usher-row.is-scanned .ck { color: var(--prism-emerald); }

    .manifest-usher-empty {
        text-align: center;
        padding: 24px;
        color: var(--prism-text-3);
        font-size: 13px;
    }

    /* ====================================================================
       Grouped-by-booking view (party/family clustering)
       Each booking is a card with all its seats inside. */
    .manifest-grouped { display: grid; gap: 10px; }
    .manifest-booking-card {
        border: 1px solid var(--prism-border);
        border-radius: 14px;
        overflow: hidden;
        background: rgba(255,255,255,0.02);
    }
    .manifest-booking-card .bk-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        padding: 10px 14px;
        background: rgba(255,255,255,0.04);
        border-bottom: 1px solid var(--prism-border);
        font-size: 12px;
    }
    .manifest-booking-card .bk-head .ref { font-weight: 800; letter-spacing: .04em; color: var(--prism-text); }
    .manifest-booking-card .bk-head .owner { color: var(--prism-text-2); }
    .manifest-booking-card .bk-head .status-chip {
        font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
        padding: 2px 10px; border-radius: 999px;
    }
    .manifest-booking-card.s-pending .status-chip { background: rgba(251,191,36,0.18); color: #fde68a; border: 1px solid rgba(251,191,36,0.45); }
    .manifest-booking-card.s-approved .status-chip { background: rgba(52,211,153,0.16); color: #6ee7b7; border: 1px solid rgba(52,211,153,0.45); }
    .manifest-booking-card ul {
        margin: 0; padding: 0; list-style: none;
    }
    .manifest-booking-card li {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 10px;
        padding: 8px 14px;
        border-top: 1px solid var(--prism-border);
        font-size: 13px;
    }
    .manifest-booking-card li:first-of-type { border-top: 0; }
    .manifest-booking-card li .nm { font-weight: 700; color: var(--prism-text); }
    .manifest-booking-card li .ck { color: var(--prism-emerald); font-size: 11px; font-weight: 800; }
    .manifest-booking-card li.is-scanned { background: rgba(52,211,153,0.06); }

    /* ====================================================================
       GRID (theater map) view — spatial layout of every seat in the venue,
       grouped by section → row, color-coded by status. Reads as a real
       seating chart, not a list. Each cell shows just the seat number;
       hover/tap reveals the attendee in a popover (title attr fallback).
       The layout adapts: phones get smaller cells (overflow-x scroll per
       row), desktops/print get full-width rows. */
    .manifest-grid-wrap {
        display: grid;
        gap: 14px;
    }
    .manifest-grid-stage {
        text-align: center;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .26em;
        text-transform: uppercase;
        color: var(--prism-text-3);
        padding: 6px 10px;
        border-radius: 10px;
        border: 1px dashed var(--prism-border);
        background: rgba(255,255,255,0.02);
    }
    .manifest-grid-section {
        border: 1px solid var(--prism-border);
        border-radius: 14px;
        overflow: hidden;
        background: rgba(255,255,255,0.02);
    }
    .manifest-grid-section h3 {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        padding: 8px 12px;
        background: linear-gradient(135deg, rgba(34,211,238,0.10), rgba(192,132,252,0.10));
        border-bottom: 1px solid var(--prism-border);
    }
    .manifest-grid-section.section-balcony h3 {
        background: linear-gradient(135deg, rgba(251,191,36,0.10), rgba(192,132,252,0.10));
    }
    .manifest-grid-rows {
        display: grid;
        gap: 6px;
        padding: 10px;
    }
    .manifest-grid-row {
        display: grid;
        grid-template-columns: 32px 1fr;
        align-items: center;
        gap: 6px;
    }
    .manifest-grid-row-label {
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .04em;
        text-align: center;
        color: var(--prism-text);
        padding: 4px 0;
        border-radius: 6px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--prism-border);
    }
    .manifest-grid-seats {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
        justify-content: flex-start;
    }
    /* Center the row within the row track so wider rows still look
       balanced relative to narrower neighbors. */
    .manifest-grid-section .manifest-grid-seats { justify-content: center; }

    .manifest-grid-seat {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        padding: 0 6px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 800;
        font-feature-settings: "tnum" 1;
        line-height: 1;
        border: 1px solid var(--prism-border);
        background: rgba(255,255,255,0.04);
        color: var(--prism-text);
        cursor: default;
        transition: transform .12s ease, box-shadow .15s ease;
    }
    .manifest-grid-seat:hover,
    .manifest-grid-seat:focus {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px -8px rgba(0,0,0,0.55);
        outline: 0;
    }
    .manifest-grid-seat.is-approved {
        background: rgba(52,211,153,0.16);
        border-color: rgba(52,211,153,0.55);
        color: #ecfdf5;
    }
    .manifest-grid-seat.is-pending {
        background: rgba(251,191,36,0.20);
        border-color: rgba(251,191,36,0.60);
        color: #fff7e6;
    }
    .manifest-grid-seat.is-blocked {
        background: repeating-linear-gradient(45deg,
            rgba(244,63,94,0.25),
            rgba(244,63,94,0.25) 3px,
            rgba(244,63,94,0.08) 3px,
            rgba(244,63,94,0.08) 6px);
        border-color: rgba(244,63,94,0.55);
        color: #ffe4e6;
    }
    .manifest-grid-seat.is-empty {
        background: transparent;
        color: var(--prism-text-3);
        border-style: dashed;
        opacity: .8;
    }
    /* Focus pulse — when the page is loaded with ?focus=A12 (e.g. from
       a scanner deep-link or a usher-view chip tap), we scroll the
       matching seat into view and run this 3s glow so the operator
       can spot it instantly against a packed chart. */
    .manifest-grid-seat.is-focused {
        animation: manifest-seat-pulse 1.2s ease-in-out 0s 3;
        z-index: 5;
    }
    @keyframes manifest-seat-pulse {
        0%   { transform: translateY(-1px); box-shadow: 0 0 0 0 rgba(129,140,248,0.85), 0 0 0 0 rgba(192,132,252,0.45); }
        50%  { transform: translateY(-2px); box-shadow: 0 0 0 6px rgba(129,140,248,0.55), 0 0 24px 4px rgba(192,132,252,0.40); }
        100% { transform: translateY(-1px); box-shadow: 0 0 0 0 rgba(129,140,248,0); }
    }

    /* Usher chip behaves like a link but inherits the chip look. The
       subtle underline-on-hover hints it's tappable without breaking
       the chip's rounded silhouette. */
    .manifest-usher-chip {
        text-decoration: none;
        transition: transform .12s ease, box-shadow .15s ease;
    }
    .manifest-usher-chip:hover,
    .manifest-usher-chip:focus {
        outline: 0;
        transform: translateY(-1px);
        box-shadow: 0 6px 14px -8px rgba(0,0,0,0.55);
    }

    .manifest-grid-seat.is-scanned::after {
        content: "✓";
        position: absolute;
        top: -4px;
        inset-inline-end: -4px;
        background: var(--prism-emerald);
        color: #052e26;
        width: 14px; height: 14px;
        font-size: 9px;
        font-weight: 800;
        line-height: 14px;
        border-radius: 999px;
        box-shadow: 0 0 0 1.5px rgba(8,10,20,0.9);
    }

    /* Booking color band as a 3px left bar (matches Phase 1 print-sheet hue) */
    .manifest-grid-seat[data-hue] {
        box-shadow: inset 3px 0 0 0 transparent;
    }
    .manifest-grid-seat[data-hue="0"] { box-shadow: inset 3px 0 0 0 rgba(34,211,238,0.85); }
    .manifest-grid-seat[data-hue="1"] { box-shadow: inset 3px 0 0 0 rgba(192,132,252,0.85); }
    .manifest-grid-seat[data-hue="2"] { box-shadow: inset 3px 0 0 0 rgba(251,191,36,0.85); }
    .manifest-grid-seat[data-hue="3"] { box-shadow: inset 3px 0 0 0 rgba(52,211,153,0.85); }
    .manifest-grid-seat[data-hue="4"] { box-shadow: inset 3px 0 0 0 rgba(244,114,182,0.85); }
    .manifest-grid-seat[data-hue="5"] { box-shadow: inset 3px 0 0 0 rgba(96,165,250,0.85); }
    .manifest-grid-seat[data-hue="6"] { box-shadow: inset 3px 0 0 0 rgba(251,113,133,0.85); }
    .manifest-grid-seat[data-hue="7"] { box-shadow: inset 3px 0 0 0 rgba(167,243,208,0.85); }
    html[dir="rtl"] .manifest-grid-seat[data-hue="0"] { box-shadow: inset -3px 0 0 0 rgba(34,211,238,0.85); }
    html[dir="rtl"] .manifest-grid-seat[data-hue="1"] { box-shadow: inset -3px 0 0 0 rgba(192,132,252,0.85); }
    html[dir="rtl"] .manifest-grid-seat[data-hue="2"] { box-shadow: inset -3px 0 0 0 rgba(251,191,36,0.85); }
    html[dir="rtl"] .manifest-grid-seat[data-hue="3"] { box-shadow: inset -3px 0 0 0 rgba(52,211,153,0.85); }
    html[dir="rtl"] .manifest-grid-seat[data-hue="4"] { box-shadow: inset -3px 0 0 0 rgba(244,114,182,0.85); }
    html[dir="rtl"] .manifest-grid-seat[data-hue="5"] { box-shadow: inset -3px 0 0 0 rgba(96,165,250,0.85); }
    html[dir="rtl"] .manifest-grid-seat[data-hue="6"] { box-shadow: inset -3px 0 0 0 rgba(251,113,133,0.85); }
    html[dir="rtl"] .manifest-grid-seat[data-hue="7"] { box-shadow: inset -3px 0 0 0 rgba(167,243,208,0.85); }

    /* Active-seat popover (revealed by JS on click/focus) */
    .manifest-grid-popover {
        position: fixed;
        inset-inline-start: 50%;
        bottom: 16px;
        transform: translateX(-50%) translateY(8px);
        z-index: 60;
        max-width: 92vw;
        width: 360px;
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid rgba(129,140,248,0.45);
        background: linear-gradient(180deg, rgba(20,24,38,0.95), rgba(8,10,20,0.95));
        backdrop-filter: blur(14px) saturate(140%);
        -webkit-backdrop-filter: blur(14px) saturate(140%);
        box-shadow: 0 18px 36px -16px rgba(0,0,0,0.7);
        color: var(--prism-text);
        font-size: 12px;
        line-height: 1.4;
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
    }
    html[dir="rtl"] .manifest-grid-popover { transform: translateX(50%) translateY(8px); }
    .manifest-grid-popover.is-on {
        opacity: 1;
        pointer-events: auto;
        transform: translateX(-50%) translateY(0);
    }
    html[dir="rtl"] .manifest-grid-popover.is-on { transform: translateX(50%) translateY(0); }
    .manifest-grid-popover .pop-title {
        font-size: 14px; font-weight: 800; color: var(--prism-text);
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .manifest-grid-popover .pop-meta { color: var(--prism-text-3); margin-top: 4px; font-feature-settings: "tnum" 1; }
    .manifest-grid-popover .pop-close {
        position: absolute; top: 6px; inset-inline-end: 10px;
        font-size: 16px; line-height: 1; color: var(--prism-text-3);
        background: transparent; border: 0; cursor: pointer;
    }

    /* Legend strip (only screen) */
    .manifest-grid-legend {
        display: flex; flex-wrap: wrap; gap: 10px;
        font-size: 11px; color: var(--prism-text-3);
        padding: 8px 12px;
        border: 1px solid var(--prism-border);
        border-radius: 12px;
        background: rgba(255,255,255,0.02);
    }
    .manifest-grid-legend .lg { display: inline-flex; align-items: center; gap: 6px; }
    .manifest-grid-legend .sw {
        width: 14px; height: 14px; border-radius: 4px;
        border: 1px solid var(--prism-border);
    }
    .manifest-grid-legend .sw.is-approved { background: rgba(52,211,153,0.16); border-color: rgba(52,211,153,0.55); }
    .manifest-grid-legend .sw.is-pending  { background: rgba(251,191,36,0.20); border-color: rgba(251,191,36,0.60); }
    .manifest-grid-legend .sw.is-blocked  {
        background: repeating-linear-gradient(45deg,
            rgba(244,63,94,0.25),
            rgba(244,63,94,0.25) 3px,
            rgba(244,63,94,0.08) 3px,
            rgba(244,63,94,0.08) 6px);
        border-color: rgba(244,63,94,0.55);
    }
    .manifest-grid-legend .sw.is-empty    { background: transparent; border-style: dashed; }

    /* Mobile compaction — phones get tighter cells and a row-by-row
       horizontal scroll so a wide row doesn't break the page width. */
    @media (max-width: 720px) {
        .manifest-grid-seat { min-width: 26px; height: 26px; font-size: 10px; }
        .manifest-grid-row-label { font-size: 12px; }
        .manifest-grid-rows { padding: 8px; }
        .manifest-grid-row { grid-template-columns: 28px 1fr; }
    }

    /* ====================================================================
       LIGHT MODE — paper-first surfaces */
    :root[data-pt-theme="light"] .manifest-stat,
    :root[data-pt-theme="light"] .manifest-search,
    :root[data-pt-theme="light"] .manifest-section,
    :root[data-pt-theme="light"] .manifest-row-label,
    :root[data-pt-theme="light"] .manifest-cell,
    :root[data-pt-theme="light"] .manifest-usher-row,
    :root[data-pt-theme="light"] .manifest-booking-card,
    :root[data-pt-theme="light"] .manifest-booking-card .bk-head {
        background: #ffffff;
        border-color: rgba(15,23,42,0.14);
    }
    :root[data-pt-theme="light"] .manifest-cell .seat-num,
    :root[data-pt-theme="light"] .manifest-cell .seat-attendee,
    :root[data-pt-theme="light"] .manifest-usher-row .who .nm,
    :root[data-pt-theme="light"] .manifest-booking-card li .nm,
    :root[data-pt-theme="light"] .manifest-row-label {
        color: var(--prism-text);
    }

    /* ====================================================================
       PRINT — A4 landscape, paper-first. Strips all chrome, neutralizes
       backgrounds for ink, and force-breaks per section so each prints
       on its own page when the row count overflows. */
    @media print {
        @page { size: A4 landscape; margin: 10mm 10mm 12mm 10mm; }

        body, html { background: #fff !important; color: #000 !important; }
        body.has-bg::before, body.has-bg::after { display: none !important; }

        /* Hide everything except the manifest itself */
        body * { visibility: hidden; }
        .manifest-print-area, .manifest-print-area * { visibility: visible; }
        .manifest-print-area { position: absolute; inset: 0; padding: 0; }

        /* Hide chrome */
        .manifest-chrome, .manifest-search, .pt-no-print { display: none !important; }

        .manifest-print-title { text-align: center; font-size: 14pt; font-weight: 800; margin-bottom: 4mm; color: #000 !important; }
        .manifest-print-sub   { text-align: center; font-size: 10pt; margin-bottom: 4mm; color: #000 !important; }

        .manifest-stats { gap: 4mm; margin-bottom: 4mm; }
        .manifest-stat {
            background: #fff !important; border: 1px solid #000 !important;
        }
        .manifest-stat .v { color: #000 !important; font-size: 14pt; }
        .manifest-stat .l { color: #000 !important; font-size: 8pt; }

        .manifest-section {
            background: #fff !important;
            border: 1px solid #000 !important;
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 4mm;
        }
        .manifest-section + .manifest-section { page-break-before: auto; }
        .manifest-section h3 {
            background: #f0f0f0 !important;
            color: #000 !important;
            border-bottom: 1px solid #000 !important;
            font-size: 11pt;
        }
        .manifest-row-strip { border-top: 1px solid #000 !important; }
        .manifest-row-label {
            background: #f6f6f6 !important;
            color: #000 !important;
            border-inline-end: 1px solid #000 !important;
            font-size: 12pt;
        }
        .manifest-row-seats {
            grid-template-columns: repeat(auto-fill, minmax(56mm, 1fr));
            gap: 2mm; padding: 2mm;
        }
        .manifest-cell {
            background: #fff !important;
            border: 1px solid #000 !important;
            color: #000 !important;
            min-height: 14mm;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .manifest-cell .seat-num,
        .manifest-cell .seat-attendee {
            color: #000 !important;
        }
        .manifest-cell .seat-meta { color: #333 !important; }
        .manifest-cell .seat-tag {
            color: #000 !important;
            background: transparent !important;
            border: 1px solid #000 !important;
        }
        .manifest-cell.is-pending .seat-tag { background: repeating-linear-gradient(45deg, #fff, #fff 2px, #eee 2px, #eee 4px) !important; }
        .manifest-cell.is-blocked {
            background: repeating-linear-gradient(45deg, #fff, #fff 3px, #000 3px, #000 4px) !important;
        }
        .manifest-cell.is-blocked .seat-num,
        .manifest-cell.is-blocked .seat-tag { background: #fff !important; padding: 0 2px; }
        .manifest-cell.is-empty { opacity: 1; }
        .manifest-cell.is-empty .seat-attendee::after { content: "—"; }
        .manifest-cell.is-scanned { background: #f3fff6 !important; }
        .manifest-cell .seat-check { color: #000 !important; }

        /* Hue is drawn as a thicker left border so it survives on paper */
        .manifest-cell[data-hue]    { border-left-width: 3px !important; }
        html[dir="rtl"] .manifest-cell[data-hue] { border-left-width: 1px !important; border-right-width: 3px !important; }

        .manifest-print-footer {
            text-align: center;
            font-size: 8pt;
            color: #000 !important;
            margin-top: 4mm;
        }

        /* Grid view print — each section becomes its own A4 landscape
           page. Cells go pure outline so the chart is readable in ink. */
        .manifest-grid-section {
            background: #fff !important;
            border: 1px solid #000 !important;
            page-break-inside: avoid;
            break-inside: avoid;
            page-break-after: always;
        }
        .manifest-grid-section:last-child { page-break-after: auto; }
        .manifest-grid-section h3 {
            background: #f0f0f0 !important;
            color: #000 !important;
            border-bottom: 1px solid #000 !important;
            font-size: 11pt;
        }
        .manifest-grid-stage {
            background: #fff !important; color: #000 !important;
            border: 1px dashed #000 !important;
        }
        .manifest-grid-row-label {
            background: #f6f6f6 !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            font-size: 10pt;
        }
        .manifest-grid-seat {
            background: #fff !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            min-width: 7mm; height: 7mm; font-size: 7pt;
        }
        .manifest-grid-seat.is-approved { background: #fff !important; }
        .manifest-grid-seat.is-pending  {
            background: repeating-linear-gradient(45deg,
                #fff, #fff 1.5px, #eee 1.5px, #eee 3px) !important;
        }
        .manifest-grid-seat.is-blocked  {
            background: repeating-linear-gradient(45deg,
                #fff, #fff 1.5px, #000 1.5px, #000 2.5px) !important;
            color: #fff !important;
        }
        .manifest-grid-seat.is-empty    {
            background: #fff !important; border-style: dashed !important; color: #888 !important;
        }
        .manifest-grid-seat.is-scanned::after {
            background: #000 !important; color: #fff !important;
            box-shadow: 0 0 0 1px #fff;
        }
        .manifest-grid-seat[data-hue]   { border-left-width: 2.5px !important; }
        html[dir="rtl"] .manifest-grid-seat[data-hue] { border-left-width: 1px !important; border-right-width: 2.5px !important; }
        .manifest-grid-popover, .manifest-grid-legend { display: none !important; }
    }
</style>
@endpush

@section('content')
<section class="space-y-4 manifest-shell">

    {{-- Top chrome (screen only) --}}
    <div class="prism-glass prism-glow-border p-4 prism-fade-up pt-no-print">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <span class="prism-pill prism-pill-neon">
                    <span class="prism-dot prism-dot-emerald"></span>
                    <span>Seat Manifest</span>
                </span>
                <h1 class="prism-headline text-xl sm:text-2xl"
                    style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    {{ $showTitle }}
                </h1>
                <p class="text-xs text-[color:var(--prism-text-3)]" dir="ltr">
                    {{ $eventDate }} · {{ $eventTime }} · {{ $totalBooked }} / {{ $summary['total'] }} seats booked
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 manifest-chrome">
                <div class="pt-tabs" role="tablist" aria-label="View">
                    <a href="{{ $url(['view' => 'print']) }}"
                       class="{{ $view === 'print' ? 'is-active' : '' }}"
                       role="tab" aria-selected="{{ $view === 'print' ? 'true' : 'false' }}">
                        🖨 Print
                    </a>
                    <a href="{{ $url(['view' => 'usher']) }}"
                       class="{{ $view === 'usher' ? 'is-active' : '' }}"
                       role="tab" aria-selected="{{ $view === 'usher' ? 'true' : 'false' }}">
                        🔍 Usher
                    </a>
                    <a href="{{ $url(['view' => 'grouped']) }}"
                       class="{{ $view === 'grouped' ? 'is-active' : '' }}"
                       role="tab" aria-selected="{{ $view === 'grouped' ? 'true' : 'false' }}">
                        👥 By Booking
                    </a>
                    <a href="{{ $url(['view' => 'grid']) }}"
                       class="{{ $view === 'grid' ? 'is-active' : '' }}"
                       role="tab" aria-selected="{{ $view === 'grid' ? 'true' : 'false' }}">
                        🗺 Grid
                    </a>
                </div>

                <a href="{{ $url(['full_phone' => $showFullPhone ? 0 : 1]) }}"
                   class="prism-btn-ghost text-xs"
                   title="Toggle phone visibility">
                    {{ $showFullPhone ? '🔒 إخفاء الأرقام' : '👁 إظهار الأرقام' }}
                </a>

                <button type="button" onclick="window.print()" class="prism-btn text-xs">
                    🖨 طباعة
                </button>

                <a href="{{ $csvUrl }}" class="prism-btn-ghost text-xs">
                    ⬇ CSV
                </a>

                @if($show)
                    <a href="{{ route('admin.shows.times.index', $show) }}" class="prism-btn-ghost text-xs">
                        <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                        <span>رجوع</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== PRINT-PRIMARY VIEW ===================== --}}
    @if ($view === 'print')

        <div class="manifest-print-area">

            <div class="manifest-print-title">
                {{ $showTitle }}
            </div>
            <div class="manifest-print-sub" dir="ltr">
                {{ $eventDate }} · {{ $eventTime }}
            </div>

            {{-- Summary --}}
            <div class="manifest-stats prism-fade-up">
                <div class="manifest-stat approved">
                    <div class="v">{{ $summary['approved'] }}</div>
                    <div class="l">Approved</div>
                </div>
                <div class="manifest-stat pending">
                    <div class="v">{{ $summary['pending'] }}</div>
                    <div class="l">Pending</div>
                </div>
                <div class="manifest-stat blocked">
                    <div class="v">{{ $summary['blocked'] }}</div>
                    <div class="l">Blocked</div>
                </div>
                <div class="manifest-stat empty">
                    <div class="v">{{ $summary['empty'] }}</div>
                    <div class="l">Empty</div>
                </div>
                <div class="manifest-stat total">
                    <div class="v">{{ $summary['total'] }}</div>
                    <div class="l">Total Seats</div>
                </div>
            </div>

            <div class="manifest-print prism-fade-up">
                @forelse ($rowsBySectionRow as $sectionLabel => $byRow)
                    @php
                        $firstCell = collect($byRow)->flatten(1)->first();
                        $sectionEnum = $firstCell['section'] ?? 'hall';
                    @endphp
                    <div class="manifest-section section-{{ $sectionEnum === 'balcony' ? 'balcony' : 'hall' }}">
                        <h3>{{ $sectionLabel }} · {{ $sectionEnum === 'balcony' ? 'Balcony' : 'Hall' }}</h3>

                        @foreach ($byRow as $rowLetter => $cells)
                            <div class="manifest-row-strip">
                                <div class="manifest-row-label">{{ $rowLetter }}</div>
                                <div class="manifest-row-seats">
                                    @foreach ($cells as $c)
                                        @php
                                            $statusClass = 'is-' . $c['status'];
                                            $hue = $c['booking_id'] !== null
                                                ? ($bookingColorIndex[$c['booking_id']] ?? null)
                                                : null;
                                        @endphp
                                        <div class="manifest-cell {{ $statusClass }} {{ $c['is_scanned'] ? 'is-scanned' : '' }}"
                                             @if ($hue !== null) data-hue="{{ $hue }}" @endif>
                                            <div class="seat-head">
                                                <span class="seat-num" dir="ltr">{{ $c['row_letter'] }}{{ $c['seat_number'] }}</span>
                                                <span class="seat-tag">
                                                    @switch($c['status'])
                                                        @case('approved') OK @break
                                                        @case('pending')  PEND @break
                                                        @case('blocked')  BLK @break
                                                        @default          — @break
                                                    @endswitch
                                                </span>
                                            </div>
                                            <div class="seat-attendee">
                                                {{ $c['attendee_name'] ?: ($c['status'] === 'blocked' ? 'BLOCKED' : '') }}
                                            </div>
                                            <div class="seat-meta">
                                                @if ($c['booking_ref'])
                                                    #{{ $c['booking_ref'] }}
                                                @endif
                                                @if ($c['phone'])
                                                    · {{ $maskPhone($c['phone']) }}
                                                @endif
                                            </div>
                                            @if ($c['is_scanned'] && $c['scanned_at'])
                                                <div class="seat-check">✓ {{ $c['scanned_at'] }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)]">
                        لا يوجد مقاعد لعرضها — هذا العرض غير مرتبط بخريطة مقاعد.
                    </div>
                @endforelse
            </div>

            <div class="manifest-print-footer">
                Printed {{ now()->format('d/m/Y g:i A') }} · {{ $showFullPhone ? 'Full phone numbers' : 'Masked phones (last 4)' }}
            </div>
        </div>

    {{-- ===================== USHER (mobile search) VIEW ===================== --}}
    @elseif ($view === 'usher')

        <div class="manifest-stats prism-fade-up">
            <div class="manifest-stat approved">
                <div class="v">{{ $summary['approved'] }}</div>
                <div class="l">Approved</div>
            </div>
            <div class="manifest-stat pending">
                <div class="v">{{ $summary['pending'] }}</div>
                <div class="l">Pending</div>
            </div>
            <div class="manifest-stat blocked">
                <div class="v">{{ $summary['blocked'] }}</div>
                <div class="l">Blocked</div>
            </div>
            <div class="manifest-stat empty">
                <div class="v">{{ $summary['empty'] }}</div>
                <div class="l">Empty</div>
            </div>
            <div class="manifest-stat total">
                <div class="v">{{ $checkedInCount }}</div>
                <div class="l">Checked-In</div>
            </div>
        </div>

        <div class="manifest-search prism-fade-up">
            <span aria-hidden="true" style="font-size: 16px; opacity: .7;">🔎</span>
            <input type="search"
                   id="manifest-search-input"
                   placeholder="ابحث: اسم / رقم / كود حجز / مقعد (مثال: A12)"
                   aria-label="Search manifest"
                   autocomplete="off"
                   autocorrect="off"
                   autocapitalize="off"
                   spellcheck="false">
        </div>

        <div class="manifest-usher-list prism-fade-up" id="manifest-usher-list">
            @foreach ($rows as $r)
                @php
                    $seatLabel = $r['row_letter'] . $r['seat_number'];
                    $haystack = strtolower(implode(' ', array_filter([
                        $seatLabel,
                        $r['row_letter'],
                        (string) $r['seat_number'],
                        $r['section_label_ar'],
                        $r['section_label_en'],
                        $r['attendee_name'] ?? '',
                        $r['booking_ref'] ?? '',
                        $r['booking_owner'] ?? '',
                        $r['phone'] ?? '',
                    ])));
                @endphp
                <div class="manifest-usher-row is-{{ $r['status'] }} {{ $r['is_scanned'] ? 'is-scanned' : '' }}"
                     data-haystack="{{ $haystack }}">
                    <div class="seat-block">
                        {{-- Tapping the chip jumps to the grid view with
                             this seat pre-focused (pulse + auto-popover).
                             Blocked / empty seats also link so operators
                             can confirm spatially that the row is right. --}}
                        <a class="pt-seat-chip pt-seat-chip-{{ $r['section'] === 'balcony' ? 'balcony' : 'hall' }} manifest-usher-chip"
                           href="{{ $url(['view' => 'grid', 'focus' => $seatLabel]) }}"
                           title="عرض على الخريطة">
                            <span class="pt-seat-chip-section">{{ $r['section_label_ar'] }}</span>
                            <span class="pt-seat-chip-seat" dir="ltr">{{ $seatLabel }}</span>
                        </a>
                    </div>
                    <div class="who">
                        <span class="nm">
                            {{ $r['attendee_name'] ?: ($r['status'] === 'blocked' ? 'BLOCKED' : ($r['status'] === 'empty' ? '— فارغ —' : '—')) }}
                        </span>
                        <span class="meta" dir="ltr">
                            @if ($r['booking_ref']) #{{ $r['booking_ref'] }} @endif
                            @if ($r['phone']) · {{ $maskPhone($r['phone']) }} @endif
                            @if ($r['status'] === 'pending') · PENDING @endif
                        </span>
                    </div>
                    <div class="ck">
                        @if ($r['is_scanned']) ✓ {{ $r['scanned_at'] }} @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="manifest-usher-empty pt-no-print" id="manifest-usher-empty" hidden>
            لا توجد نتائج مطابقة.
        </div>

        <script>
            (function () {
                var input = document.getElementById('manifest-search-input');
                var list  = document.getElementById('manifest-usher-list');
                var empty = document.getElementById('manifest-usher-empty');
                if (!input || !list) return;

                function apply(q) {
                    q = (q || '').trim().toLowerCase();
                    var rows = list.querySelectorAll('.manifest-usher-row');
                    var visible = 0;
                    rows.forEach(function (r) {
                        var hay = r.dataset.haystack || '';
                        var match = !q || hay.indexOf(q) !== -1;
                        r.hidden = !match;
                        if (match) visible++;
                    });
                    if (empty) empty.hidden = (visible !== 0);
                }

                input.addEventListener('input', function () { apply(input.value); });
                input.addEventListener('search', function () { apply(input.value); });

                // Keyboard shortcut: "/" focuses search
                document.addEventListener('keydown', function (e) {
                    if (e.key === '/' && document.activeElement !== input) {
                        e.preventDefault();
                        input.focus();
                    }
                });
            })();
        </script>

    {{-- ===================== GROUPED-BY-BOOKING VIEW ===================== --}}
    @elseif ($view === 'grouped')

        <div class="manifest-stats prism-fade-up">
            <div class="manifest-stat approved">
                <div class="v">{{ $summary['approved'] }}</div>
                <div class="l">Approved</div>
            </div>
            <div class="manifest-stat pending">
                <div class="v">{{ $summary['pending'] }}</div>
                <div class="l">Pending</div>
            </div>
            <div class="manifest-stat blocked">
                <div class="v">{{ $summary['blocked'] }}</div>
                <div class="l">Blocked</div>
            </div>
            <div class="manifest-stat empty">
                <div class="v">{{ $summary['empty'] }}</div>
                <div class="l">Empty</div>
            </div>
            <div class="manifest-stat total">
                <div class="v">{{ $checkedInCount }}</div>
                <div class="l">Checked-In</div>
            </div>
        </div>

        @php
            $byBooking = [];
            foreach ($rows as $r) {
                if ($r['booking_id'] === null) continue;
                $byBooking[$r['booking_id']][] = $r;
            }
        @endphp

        <div class="manifest-grouped prism-fade-up">
            @forelse ($byBooking as $bookingId => $cells)
                @php $first = $cells[0]; @endphp
                <div class="manifest-booking-card s-{{ $first['status'] }}">
                    <div class="bk-head">
                        <span class="ref" dir="ltr">#{{ $first['booking_ref'] }}</span>
                        <span class="owner">{{ $first['booking_owner'] }}</span>
                        <span class="meta" dir="ltr">{{ $maskPhone($first['phone'] ?? '') }}</span>
                        <span class="status-chip">{{ $first['status_en'] }}</span>
                    </div>
                    <ul>
                        @foreach ($cells as $c)
                            <li class="{{ $c['is_scanned'] ? 'is-scanned' : '' }}">
                                <div class="flex items-center gap-2">
                                    <span class="pt-seat-chip pt-seat-chip-{{ $c['section'] === 'balcony' ? 'balcony' : 'hall' }}">
                                        <span class="pt-seat-chip-section">{{ $c['section_label_ar'] }}</span>
                                        <span class="pt-seat-chip-seat" dir="ltr">{{ $c['row_letter'] }}{{ $c['seat_number'] }}</span>
                                    </span>
                                    <span class="nm">{{ $c['attendee_name'] }}</span>
                                </div>
                                <span class="ck">
                                    @if ($c['is_scanned']) ✓ {{ $c['scanned_at'] }} @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)]">
                    لا توجد حجوزات لهذا الموعد بعد.
                </div>
            @endforelse
        </div>

    {{-- ===================== GRID (theater map) VIEW =====================
         Spatial layout of every physical seat in the venue grouped by
         section → row, color-coded by status. Reads as a real seating
         chart, not a list. Hover/tap a seat to see attendee details in
         the popover. For non-seatmap shows (where there's no fixed
         layout) we fall back to a notice — the grid only makes sense
         when there's a physical seat axis to anchor against. --}}
    @elseif ($view === 'grid')

        <div class="manifest-stats prism-fade-up">
            <div class="manifest-stat approved">
                <div class="v">{{ $summary['approved'] }}</div>
                <div class="l">Approved</div>
            </div>
            <div class="manifest-stat pending">
                <div class="v">{{ $summary['pending'] }}</div>
                <div class="l">Pending</div>
            </div>
            <div class="manifest-stat blocked">
                <div class="v">{{ $summary['blocked'] }}</div>
                <div class="l">Blocked</div>
            </div>
            <div class="manifest-stat empty">
                <div class="v">{{ $summary['empty'] }}</div>
                <div class="l">Empty</div>
            </div>
            <div class="manifest-stat total">
                <div class="v">{{ $checkedInCount }}</div>
                <div class="l">Checked-In</div>
            </div>
        </div>

        @if (!$usesSeatMap)
            <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)]">
                لا توجد خريطة مقاعد لهذا العرض — استخدم وضع "🔍 Usher" أو "👥 By Booking".
            </div>
        @else
            @php
                // Group rows by section then by row_letter, preserving
                // the seat-major ordering the controller produced. This
                // gives us a stable spatial layout: { section: { row: [...] } }
                $bySection = [];
                foreach ($rows as $r) {
                    $bySection[$r['section']][$r['row_letter']][] = $r;
                }
                // Hall first, balcony second (operationally what ushers
                // expect — main floor before the upper deck).
                uksort($bySection, function ($a, $b) {
                    if ($a === $b) return 0;
                    if ($a === 'hall') return -1;
                    if ($b === 'hall') return 1;
                    return strcmp($a, $b);
                });
            @endphp

            <div class="manifest-grid-legend pt-no-print prism-fade-up" role="note">
                <span class="lg"><span class="sw is-approved"></span> Approved</span>
                <span class="lg"><span class="sw is-pending"></span> Pending</span>
                <span class="lg"><span class="sw is-blocked"></span> Blocked</span>
                <span class="lg"><span class="sw is-empty"></span> Empty</span>
                <span class="lg"><span class="sw" style="background:#0f172a; border-color:#0f172a; position:relative;"><span style="position:absolute; top:-2px; right:-2px; width:8px; height:8px; background:var(--prism-emerald); border-radius:999px; box-shadow:0 0 0 1.5px #0f172a;"></span></span> Checked-In</span>
            </div>

            <div class="manifest-grid-wrap prism-fade-up">
                @foreach ($bySection as $sectionKey => $rowsByRow)
                    @php
                        $sectionLabel = $sectionKey === 'balcony' ? 'بلكون · Balcony' : 'صالة · Hall';
                        // Booked count for this section header
                        $sectionBooked = 0;
                        $sectionTotal  = 0;
                        foreach ($rowsByRow as $rowSeats) {
                            foreach ($rowSeats as $cell) {
                                $sectionTotal++;
                                if (in_array($cell['status'], ['approved', 'pending'], true)) $sectionBooked++;
                            }
                        }
                    @endphp
                    <div class="manifest-grid-section section-{{ $sectionKey === 'balcony' ? 'balcony' : 'hall' }}">
                        <h3 class="flex items-center justify-between gap-2 flex-wrap">
                            <span>{{ $sectionLabel }}</span>
                            <span class="text-[color:var(--prism-text-3)]" dir="ltr" style="font-weight: 600; letter-spacing: .04em;">
                                {{ $sectionBooked }} / {{ $sectionTotal }}
                            </span>
                        </h3>

                        <div class="manifest-grid-stage" aria-hidden="true">🎬 المسرح · STAGE</div>

                        <div class="manifest-grid-rows">
                            @foreach ($rowsByRow as $rowLetter => $rowSeats)
                                <div class="manifest-grid-row">
                                    <div class="manifest-grid-row-label" dir="ltr">{{ $rowLetter }}</div>
                                    <div class="manifest-grid-seats">
                                        @foreach ($rowSeats as $cell)
                                            @php
                                                $hue = $cell['booking_id']
                                                    ? abs(crc32((string) $cell['booking_id'])) % 8
                                                    : null;
                                                $labelBits = [];
                                                if ($cell['attendee_name']) $labelBits[] = $cell['attendee_name'];
                                                if ($cell['booking_ref']) $labelBits[] = '#' . $cell['booking_ref'];
                                                if ($cell['is_scanned'] && $cell['scanned_at']) $labelBits[] = '✓ ' . $cell['scanned_at'];
                                                if ($cell['status'] === 'blocked') $labelBits[] = 'BLOCKED';
                                                if ($cell['status'] === 'empty') $labelBits[] = 'EMPTY';
                                                $title = empty($labelBits)
                                                    ? ($cell['row_letter'] . $cell['seat_number'])
                                                    : ($cell['row_letter'] . $cell['seat_number'] . ' · ' . implode(' · ', $labelBits));
                                            @endphp
                                            <button type="button"
                                                    class="manifest-grid-seat is-{{ $cell['status'] }} {{ $cell['is_scanned'] ? 'is-scanned' : '' }}"
                                                    @if ($hue !== null) data-hue="{{ $hue }}" @endif
                                                    data-seat="{{ $cell['row_letter'] }}{{ $cell['seat_number'] }}"
                                                    data-section="{{ $cell['section_label_ar'] }}"
                                                    data-attendee="{{ $cell['attendee_name'] ?? '' }}"
                                                    data-phone="{{ $cell['phone'] ? $maskPhone($cell['phone']) : '' }}"
                                                    data-booking="{{ $cell['booking_ref'] ?? '' }}"
                                                    data-owner="{{ $cell['booking_owner'] ?? '' }}"
                                                    data-status="{{ $cell['status_en'] }}"
                                                    data-checked="{{ $cell['scanned_at'] ?? '' }}"
                                                    title="{{ $title }}"
                                                    aria-label="{{ $title }}">
                                                {{ $cell['seat_number'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Floating popover — JS toggles .is-on when a seat is clicked. --}}
            <div id="manifest-grid-popover" class="manifest-grid-popover pt-no-print" role="dialog" aria-live="polite" aria-hidden="true">
                <button type="button" class="pop-close" data-pop-close aria-label="إغلاق">✕</button>
                <div class="pop-title">
                    <span class="pt-seat-chip" data-pop-chip>
                        <span class="pt-seat-chip-section" data-pop-section>—</span>
                        <span class="pt-seat-chip-seat" dir="ltr" data-pop-seat>—</span>
                    </span>
                    <span data-pop-name>—</span>
                </div>
                <div class="pop-meta">
                    <span data-pop-status></span>
                    <span data-pop-booking></span>
                    <span data-pop-phone></span>
                    <span data-pop-checked></span>
                </div>
            </div>

            <script>
                (function () {
                    const wrap = document.querySelector('.manifest-grid-wrap');
                    const pop  = document.getElementById('manifest-grid-popover');
                    if (!wrap || !pop) return;

                    const els = {
                        chip:    pop.querySelector('[data-pop-chip]'),
                        section: pop.querySelector('[data-pop-section]'),
                        seat:    pop.querySelector('[data-pop-seat]'),
                        name:    pop.querySelector('[data-pop-name]'),
                        status:  pop.querySelector('[data-pop-status]'),
                        booking: pop.querySelector('[data-pop-booking]'),
                        phone:   pop.querySelector('[data-pop-phone]'),
                        checked: pop.querySelector('[data-pop-checked]'),
                    };
                    const close = pop.querySelector('[data-pop-close]');

                    function show(btn) {
                        const d = btn.dataset;
                        els.section.textContent = d.section || '—';
                        els.seat.textContent    = d.seat || '—';
                        els.chip.className      = 'pt-seat-chip pt-seat-chip-' + (
                            (d.section || '').includes('بلكون') ? 'balcony' : 'hall'
                        );
                        els.name.textContent    = d.attendee || '— فارغ —';
                        els.status.textContent  = d.status ? d.status : '';
                        els.booking.textContent = d.booking ? ' · #' + d.booking + (d.owner ? ' · ' + d.owner : '') : '';
                        els.phone.textContent   = d.phone   ? ' · ' + d.phone   : '';
                        els.checked.textContent = d.checked ? ' · ✓ ' + d.checked : '';
                        pop.classList.add('is-on');
                        pop.setAttribute('aria-hidden', 'false');
                    }
                    function hide() {
                        pop.classList.remove('is-on');
                        pop.setAttribute('aria-hidden', 'true');
                    }

                    wrap.addEventListener('click', function (e) {
                        const btn = e.target.closest('.manifest-grid-seat');
                        if (!btn) return;
                        show(btn);
                    });
                    close.addEventListener('click', hide);
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') hide();
                    });
                    // Dismiss when tapping anywhere outside the popover/grid
                    document.addEventListener('click', function (e) {
                        if (!pop.classList.contains('is-on')) return;
                        if (pop.contains(e.target)) return;
                        if (e.target.closest('.manifest-grid-seat')) return;
                        hide();
                    });

                    /* ?focus=A12 handling — when the manifest is loaded
                       via a deep-link (scanner result sheet, usher-view
                       chip tap), we look up the matching seat, scroll
                       it into view, pulse it, and auto-open the popover
                       so the operator confirms the right seat at a
                       glance. Seat lookup is case-insensitive and
                       resolves on data-seat="A12". */
                    try {
                        const params = new URLSearchParams(window.location.search);
                        const focus  = (params.get('focus') || '').trim().toUpperCase();
                        if (focus) {
                            const target = wrap.querySelector(
                                '.manifest-grid-seat[data-seat="' + focus + '"]'
                            );
                            if (target) {
                                // Defer to next frame so layout has settled
                                // (the section gradient + rows are heavy on
                                // first paint).
                                requestAnimationFrame(function () {
                                    target.scrollIntoView({
                                        behavior: 'smooth',
                                        block:    'center',
                                        inline:   'center',
                                    });
                                    target.classList.add('is-focused');
                                    show(target);
                                    // Remove the pulse class after the
                                    // animation finishes so it doesn't
                                    // bleed into hover states.
                                    setTimeout(function () {
                                        target.classList.remove('is-focused');
                                    }, 4200);
                                });
                            }
                        }
                    } catch (_) { /* malformed query param — ignore */ }
                })();
            </script>
        @endif

    @endif

</section>
@endsection
