@extends('layouts.app')

@php
    /* ========================================================================
       Seat Occupancy / Attendee Manifest — v4

       Two surfaces, no check-in vocabulary, no operational dashboard. The
       manifest answers four questions only:

         · which seats are booked
         · who booked each one
         · find a person ↔ seat fast
         · hand a clean A4 sheet to organizers

       Surfaces:
         mode=floor   live operational interface, mobile-first  (default)
         mode=paper   A4-landscape printable sheet              (?mode=paper)

       Scanner/check-in lives in its own admin section now. Anything pointing
       at the retired Operations Console (?mode=ops / ?view=grid / ?view=grouped)
       collapses to Floor in the controller so bookmarks keep working.
       ======================================================================== */

    $eventDate = optional($showTime->date)->format('d/m/Y');
    $eventTime = $showTime->time ? \Carbon\Carbon::parse($showTime->time)->format('g:i A') : '';
    $showTitle = optional($show)->title ?? '—';

    // Phones display in full — the show/hide toggle was removed per user
    // request because operators need the full number quickly during events.
    $displayPhone = function (?string $phone) {
        return $phone ?: '';
    };

    // Group rows by section → row letter. Used by both surfaces. The
    // controller already sorted by (section, row, seat#) so insertion order
    // is the operational order people walk through.
    $rowsBySectionRow = [];
    $sectionTone      = [];   // section_label_ar => 'hall' | 'balcony'
    $sectionLabelEn   = [];   // section_label_ar => 'Hall'|'Balcony'
    foreach ($rows as $r) {
        $key = $r['section_label_ar'];
        $rowsBySectionRow[$key][$r['row_letter']][] = $r;
        if (!isset($sectionTone[$key])) {
            $sectionTone[$key]    = ($r['section'] ?? 'hall') === 'balcony' ? 'balcony' : 'hall';
            $sectionLabelEn[$key] = $r['section_label_en'] ?? '';
        }
    }

    // Stable booking_id → hue index so each booking gets the same colour
    // wherever it appears (card seat underline, paper band, sheet party rail).
    $bookingColorIndex = [];
    $bookingHueIdx = 0;
    foreach ($rows as $r) {
        if (!empty($r['booking_id']) && !isset($bookingColorIndex[$r['booking_id']])) {
            $bookingColorIndex[$r['booking_id']] = ($bookingHueIdx % 8);
            $bookingHueIdx++;
        }
    }

    $totalBooked = $summary['approved'] + $summary['pending'];
    $capacity    = $summary['total'] ?: 1;

    // URL helper preserves common query keys; pass null to remove one.
    $url = function ($params = []) use ($showTime, $mode, $includeEmpty) {
        $base = route('admin.show-times.manifest', $showTime);
        $q    = array_merge(
            [
                'mode'          => $mode,
                'include_empty' => $includeEmpty ? 1 : 0,
            ],
            $params
        );
        $q = array_filter($q, fn ($v) => $v !== null && $v !== '');
        return $base . '?' . http_build_query($q);
    };

    $csvUrl = route('admin.show-times.manifest.csv', $showTime);

    // Paper-mode row blocks. Empties are filtered out unless ?include_empty=1.
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

    // Floor row groups + per-section booked totals.
    $floorRowsBySectionRow = [];
    $floorSectionStats     = [];
    foreach ($rowsBySectionRow as $sectionLabel => $byRow) {
        $sb = 0; $st = 0;
        foreach ($byRow as $rowLetter => $seats) {
            $rb = 0;
            foreach ($seats as $s) {
                if (in_array($s['status'], ['approved', 'pending'], true)) $rb++;
            }
            $floorRowsBySectionRow[$sectionLabel][$rowLetter] = [
                'seats'  => $seats,
                'booked' => $rb,
                'total'  => count($seats),
            ];
            $sb += $rb;
            $st += count($seats);
        }
        $floorSectionStats[$sectionLabel] = ['booked' => $sb, 'total' => $st];
    }

    // Status glyph for the paper sheet — readable after photocopy + grayscale.
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
       Manifest v4 — shared tokens (single calm palette + tone pair)
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

        /* Section tone pair — quiet, premium, architectural. Hall = warm
           gold accent (stage floor); Balcony = cool sky accent (upper tier). */
        --m-tone-hall            : #d4a857;
        --m-tone-hall-soft       : rgba(212,168,87,0.10);
        --m-tone-hall-edge       : rgba(212,168,87,0.55);
        --m-tone-hall-tint       : rgba(212,168,87,0.018);

        --m-tone-balcony         : #56b8d6;
        --m-tone-balcony-soft    : rgba(86,184,214,0.10);
        --m-tone-balcony-edge    : rgba(86,184,214,0.55);
        --m-tone-balcony-tint    : rgba(86,184,214,0.018);
    }
    .manifest-root, .manifest-root * {
        font-feature-settings: "tnum" 1, "ss01" 1;
    }
    .manifest-root :focus-visible {
        outline: none;
        box-shadow: 0 0 0 2px rgba(56,189,248,0.55), 0 0 18px rgba(56,189,248,0.22);
        border-radius: 8px;
    }

    /* Family hue tokens — applied only as a thin underline on the seat
       label + a thin band on the paper sheet. Never a card background. */
    .mfst-hue-0 { --m-hue: rgba(34,211,238,0.95);  }
    .mfst-hue-1 { --m-hue: rgba(129,140,248,0.95); }
    .mfst-hue-2 { --m-hue: rgba(244,114,182,0.95); }
    .mfst-hue-3 { --m-hue: rgba(251,191,36,0.95);  }
    .mfst-hue-4 { --m-hue: rgba(52,211,153,0.95);  }
    .mfst-hue-5 { --m-hue: rgba(251,113,133,0.95); }
    .mfst-hue-6 { --m-hue: rgba(167,139,250,0.95); }
    .mfst-hue-7 { --m-hue: rgba(252,165,165,0.95); }

    /* ====================================================================
       FLOOR MODE
       ==================================================================== */
    .mfst-floor {
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding-bottom: calc(28px + env(safe-area-inset-bottom, 0px));
    }

    /* Header — calm, no dashboard noise. Single live meta line. */
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

    /* Sticky search — heart of the operation. Real CSS sticky, ≥16px input,
       safe-area-aware top offset so it survives Safari URL-bar collapse. */
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
        font-size: 16px; /* ≥16px → iOS does not zoom on focus */
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
    .mfst-search-hint.has-result { color: var(--m-sky); }

    /* Controls row — soft segmented bar: status chips and the density
       toggle live inside a single neutral surface so the area reads as
       one quiet control, not four competing dashboard pills. */
    .mfst-controls {
        display: flex;
        align-items: stretch;
        gap: 8px;
        margin-top: 6px;
        flex-wrap: wrap;
    }
    .mfst-chips {
        display: inline-flex;
        gap: 2px;
        padding: 3px;
        border-radius: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.035);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        min-width: 0;
    }
    .mfst-chips::-webkit-scrollbar { display: none; }
    .mfst-chip {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 9px;
        border: 0;
        background: transparent;
        color: var(--m-text-3);
        font-size: 12.5px;
        font-weight: 600;
        letter-spacing: .005em;
        white-space: nowrap;
        min-height: 36px;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .15s var(--m-ease), color .15s var(--m-ease);
        -webkit-tap-highlight-color: transparent;
    }
    .mfst-chip::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: var(--m-chip-dot, rgba(148,163,184,0.5));
        flex-shrink: 0;
        transition: background .15s var(--m-ease), box-shadow .15s var(--m-ease);
    }
    .mfst-chip .count {
        font-size: 11px;
        font-weight: 700;
        opacity: .55;
        font-feature-settings: "tnum" 1;
    }
    .mfst-chip[aria-pressed="true"] {
        background: rgba(255,255,255,0.08);
        color: var(--m-text);
    }
    .mfst-chip[aria-pressed="true"]::before {
        background: var(--m-chip-dot, rgba(56,189,248,0.9));
        box-shadow: 0 0 0 3px rgba(255,255,255,0.04);
    }
    .mfst-chip[aria-pressed="true"] .count { opacity: 1; }
    .mfst-chip-approved { --m-chip-dot: rgba(52,211,153,0.95); }
    .mfst-chip-pending  { --m-chip-dot: rgba(251,191,36,0.95); }
    .mfst-chip-blocked  { --m-chip-dot: rgba(251,113,133,0.95); }

    /* Density toggle — same surface treatment as chips for visual unity. */
    .mfst-density {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        padding: 3px;
        gap: 2px;
        border-radius: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.035);
    }
    .mfst-density button {
        appearance: none;
        border: 0;
        background: transparent;
        color: var(--m-text-3);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .01em;
        padding: 8px 14px;
        border-radius: 9px;
        cursor: pointer;
        min-height: 30px;
        white-space: nowrap;
        -webkit-tap-highlight-color: transparent;
        transition: background .15s var(--m-ease), color .15s var(--m-ease);
    }
    .mfst-density button[aria-pressed="true"] {
        background: rgba(255,255,255,0.08);
        color: var(--m-text);
    }

    /* Section anchors — Hall / Balcony each get a quiet but distinct tone.
       Sticky under the search bar so an usher walking the hall never loses
       their place. */
    .mfst-section {
        display: flex;
        flex-direction: column;
        gap: 6px;
        --m-tone        : var(--m-tone-hall);
        --m-tone-soft   : var(--m-tone-hall-soft);
        --m-tone-edge   : var(--m-tone-hall-edge);
        --m-tone-tint   : var(--m-tone-hall-tint);
    }
    .mfst-section[data-tone="balcony"] {
        --m-tone        : var(--m-tone-balcony);
        --m-tone-soft   : var(--m-tone-balcony-soft);
        --m-tone-edge   : var(--m-tone-balcony-edge);
        --m-tone-tint   : var(--m-tone-balcony-tint);
    }
    .mfst-section + .mfst-section { margin-top: 4px; }
    .mfst-section-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 4px 8px;
        position: sticky;
        top: calc(58px + 78px + env(safe-area-inset-top, 0px));
        z-index: 14;
        background: linear-gradient(180deg,
            rgba(8,9,18,0.94) 0%,
            rgba(8,9,18,0.74) 80%,
            rgba(8,9,18,0.0) 100%);
        backdrop-filter: blur(14px) saturate(140%);
        -webkit-backdrop-filter: blur(14px) saturate(140%);
    }
    .mfst-section-head .edge {
        width: 4px;
        height: 22px;
        border-radius: 2px;
        background: var(--m-tone-edge);
        flex-shrink: 0;
    }
    .mfst-section-head .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--m-tone);
        background: var(--m-tone-soft);
        border: 1px solid var(--m-tone-edge);
    }
    .mfst-section-head .t {
        font-size: 13.5px;
        font-weight: 700;
        letter-spacing: .04em;
        color: var(--m-text);
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
        color: var(--m-tone);
    }
    .mfst-rowhead .b {
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg,
            var(--m-tone-edge) 0%,
            transparent 100%);
        opacity: .55;
    }
    .mfst-rowhead .n {
        color: var(--m-text-3);
    }

    /* Card — one-row by default. Faint section tint inherits from parent. */
    .mfst-card {
        display: grid;
        grid-template-columns: 60px 1fr auto;
        gap: 12px;
        align-items: center;
        padding: 12px 14px;
        border-radius: var(--m-radius-card);
        border: 1px solid var(--m-border);
        background: linear-gradient(180deg, var(--m-tone-tint), var(--m-tone-tint)),
                    rgba(255,255,255,0.03);
        cursor: pointer;
        transition: background .15s var(--m-ease), border-color .15s var(--m-ease), transform .12s var(--m-ease);
        -webkit-tap-highlight-color: transparent;
        position: relative;
        overflow: hidden;
    }
    .mfst-card:hover {
        background: linear-gradient(180deg, var(--m-tone-tint), var(--m-tone-tint)),
                    rgba(255,255,255,0.06);
        border-color: var(--m-border-strong);
    }
    .mfst-card:active {
        transform: scale(0.985);
    }
    .mfst-card.is-focused {
        border-color: rgba(56,189,248,0.55);
        box-shadow: 0 0 0 2px rgba(56,189,248,0.18);
    }

    .mfst-card .seat-block {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .mfst-card .seat {
        font-size: 17px;
        font-weight: 800;
        font-feature-settings: "tnum" 1;
        letter-spacing: -.01em;
        color: var(--m-text);
        line-height: 1;
        position: relative;
    }
    /* Hue underline = booking family. Always visible, never loud. */
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
        opacity: .55;
    }
    .mfst-card.is-empty .name { color: var(--m-text-3); font-weight: 500; }

    /* Density toggle drives card visibility. Default = occupied only. */
    .mfst-floor:not(.is-show-empty) .mfst-card.is-empty,
    .mfst-floor:not(.is-show-empty) .mfst-rowgroup.is-all-empty {
        display: none;
    }

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

    /* ====================================================================
       BOTTOM SHEET — attendee detail. No scan/check-in. No primary action.
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
        margin: 4px auto 14px;
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
        gap: 4px;
    }
    .mfst-sheet-id .sec {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--m-text-3);
    }
    .mfst-sheet-id .sec .pip {
        width: 8px;
        height: 8px;
        border-radius: 2px;
        background: var(--m-tone-edge, rgba(255,255,255,0.20));
    }
    .mfst-sheet-id .seat {
        font-size: 28px;
        font-weight: 800;
        color: var(--m-text);
        letter-spacing: -.01em;
        font-feature-settings: "tnum" 1;
        line-height: 1.05;
    }
    .mfst-sheet-close {
        flex-shrink: 0;
        width: 38px;
        height: 38px;
        border-radius: 999px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        color: var(--m-text-2);
        cursor: pointer;
        font-size: 14px;
        -webkit-tap-highlight-color: transparent;
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
        gap: 0;
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

    @media (prefers-reduced-motion: reduce) {
        .mfst-sheet-card, .mfst-card, .mfst-search { transition: none; }
    }

    /* ====================================================================
       PHONE-ONLY POLISH (≤640px) — calm, one-handed, low visual noise
       ==================================================================== */
    @media (max-width: 640px) {
        /* Header trims. Title is enough on small screens — the "32 / 32
           booked" line is redundant once the chips show counts below. */
        .mfst-head { padding: 14px 4px 2px; gap: 4px; }
        .mfst-head .title { font-size: 16px; }
        .mfst-head .meta { font-size: 12px; }

        /* Section heads scroll naturally on phones. Sticky offset math was
           brittle (search bar height varies with chip wrap), and a roaming
           sticky band stacked on top of a sticky search bar reads as two
           competing surfaces. Tone colour on each card carries the section
           identity through the scroll, so the head is allowed to scroll off. */
        .mfst-section-head {
            position: static;
            top: auto;
            padding: 16px 4px 6px;
            background: transparent;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }

        /* Card: drop the status text label entirely. The coloured dot at
           the right edge + the tone tint of the row already conveys status.
           Reclaiming this column gives the attendee name + phone meaningful
           breathing room on a 390px viewport. */
        .mfst-card { grid-template-columns: 52px 1fr 14px; gap: 10px; padding: 11px 12px; }
        .mfst-card .seat-block { justify-content: flex-start; }
        .mfst-card .seat { font-size: 16px; }
        .mfst-card .status { font-size: 0; gap: 0; padding: 0; }
        .mfst-card .status .dot {
            width: 10px;
            height: 10px;
        }

        /* Compact rowhead — less visual weight before each row. */
        .mfst-rowhead { padding: 4px 4px 0; font-size: 10.5px; }
        .mfst-rowhead .r { font-size: 11px; }
    }

    /* ====================================================================
       PAPER MODE — A4 sheet + mobile dossier preview
       ==================================================================== */
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
    /* On phones the bar's "Print sheet" button is redundant — the cover
       card has its own large CTA in the thumb zone. Hide the duplicate
       and keep the bar to just the navigational essentials. */
    @media (max-width: 640px) {
        .mfst-paper-bar .primary { display: none; }
        .mfst-paper-bar { gap: 6px; padding: 10px 4px 0; margin-bottom: 12px; }
        .mfst-paper-bar a, .mfst-paper-bar button {
            padding: 8px 12px;
            min-height: 38px;
            font-size: 12px;
        }
    }

    /* Mobile preview — clean dossier, replaces the cramped A4 table.
       Hidden on tablet+ in favour of the real A4 sheet. */
    .mfst-paper-preview {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .mfst-paper-cover {
        padding: 20px 22px;
        border-radius: var(--m-radius);
        border: 1px solid var(--m-border);
        background:
            radial-gradient(140% 80% at 100% 0%, rgba(167,139,250,0.10), transparent 60%),
            rgba(255,255,255,0.025);
    }
    .mfst-paper-cover .kicker {
        display: inline-block;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: var(--m-text-3);
        padding: 4px 9px;
        border-radius: 999px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.04);
        margin-bottom: 14px;
    }
    .mfst-paper-cover .t {
        font-size: 18px;
        font-weight: 800;
        color: var(--m-text);
        letter-spacing: -.005em;
        margin-bottom: 4px;
        line-height: 1.25;
    }
    .mfst-paper-cover .d {
        font-size: 12.5px;
        color: var(--m-text-3);
        margin-bottom: 16px;
    }
    .mfst-paper-cover .stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0,1fr));
        gap: 8px;
    }
    .mfst-paper-cover .stats .s {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.03);
    }
    .mfst-paper-cover .stats .s b {
        font-size: 19px;
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

    .mfst-paper-cover .b {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 18px;
        padding: 14px 22px;
        min-height: 50px;
        border-radius: 14px;
        background: linear-gradient(180deg, rgba(56,189,248,0.95), rgba(34,211,238,0.85));
        border: 1px solid rgba(255,255,255,0.18);
        color: #02212d;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 12px 30px rgba(34,211,238,0.32);
    }

    /* Per-section block — adopts Hall / Balcony tone. */
    .mfst-paper-preview .sec {
        border-radius: var(--m-radius);
        border: 1px solid var(--m-border);
        background: rgba(255,255,255,0.02);
        overflow: hidden;
        --m-tone        : var(--m-tone-hall);
        --m-tone-soft   : var(--m-tone-hall-soft);
        --m-tone-edge   : var(--m-tone-hall-edge);
        --m-tone-tint   : var(--m-tone-hall-tint);
    }
    .mfst-paper-preview .sec[data-tone="balcony"] {
        --m-tone        : var(--m-tone-balcony);
        --m-tone-soft   : var(--m-tone-balcony-soft);
        --m-tone-edge   : var(--m-tone-balcony-edge);
        --m-tone-tint   : var(--m-tone-balcony-tint);
    }
    .mfst-paper-preview .sec-head {
        padding: 14px 18px;
        border-bottom: 1px solid var(--m-border);
        background: linear-gradient(180deg, var(--m-tone-soft), transparent 90%),
                    rgba(255,255,255,0.03);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .mfst-paper-preview .sec-head .edge {
        width: 4px;
        height: 24px;
        border-radius: 2px;
        background: var(--m-tone-edge);
        flex-shrink: 0;
    }
    .mfst-paper-preview .sec-head .badge {
        display: inline-flex;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--m-tone);
        background: var(--m-tone-soft);
        border: 1px solid var(--m-tone-edge);
    }
    .mfst-paper-preview .sec-head .t {
        font-size: 14px;
        font-weight: 800;
        color: var(--m-text);
    }
    .mfst-paper-preview .sec-head .s {
        font-size: 12px;
        color: var(--m-text-3);
        margin-inline-start: auto;
        font-feature-settings: "tnum" 1;
    }
    .mfst-paper-preview .sec-head .s b { color: var(--m-text); font-weight: 700; }

    .mfst-paper-preview .row {
        padding: 10px 18px 4px;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--m-tone);
        border-top: 1px solid var(--m-border);
    }
    .mfst-paper-preview .row:first-of-type { border-top: 0; }

    .mfst-paper-preview .line {
        display: grid;
        grid-template-columns: 56px 1fr;
        gap: 12px;
        align-items: center;
        padding: 11px 18px;
        border-top: 1px solid rgba(255,255,255,0.04);
        background: var(--m-tone-tint);
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
        font-size: 16px;
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
        font-size: 14px;
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
    .mfst-paper-preview .line.is-blocked .info .nm { color: var(--m-rose); }
    .mfst-paper-preview .line.is-empty .info .nm { color: var(--m-text-3); font-style: italic; }

    /* A4 print sheet — visible at ≥820px and on print. Always min-width:0
       inside print so iOS Safari doesn't squeeze it. */
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
            padding: 22px 26px 18px;
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
    .mfst-paper-head .head-title { min-width: 0; }
    .mfst-paper-head .t1 { font-size: 17pt; font-weight: 800; letter-spacing: -.005em; }
    .mfst-paper-head .t2 { font-size: 10pt; color: #555; margin-top: 2px; }
    .mfst-paper-head .stats {
        font-size: 9pt;
        text-align: right;
        line-height: 1.5;
        color: #333;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 1px;
    }
    .mfst-paper-head .stats .line {
        display: inline-flex;
        align-items: baseline;
        gap: 6px;
        white-space: nowrap;
    }
    .mfst-paper-head .stats .dot { color: #888; padding: 0 2px; }
    .mfst-paper-head .stats strong { font-weight: 800; color: #000; }
    .mfst-paper-section-head {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        padding: 5px 8px;
        border: 1px solid #000;
        font-size: 10pt;
        font-weight: 800;
        position: relative;
    }
    .mfst-paper-section-head[data-tone="hall"]    { background: #fbf6ea; border-color: #8a6b1f; }
    .mfst-paper-section-head[data-tone="balcony"] { background: #eef6fa; border-color: #1f6a85; }
    .mfst-paper-section-head .badge {
        display: inline-block;
        font-size: 8pt;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-inline-end: 8px;
        padding: 1px 7px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid currentColor;
    }
    .mfst-paper-section-head[data-tone="hall"]    .badge { color: #8a6b1f; }
    .mfst-paper-section-head[data-tone="balcony"] .badge { color: #1f6a85; }

    .mfst-paper-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5pt;
        font-feature-settings: "tnum" 1;
    }
    .mfst-paper-table th, .mfst-paper-table td {
        padding: 5px 7px;
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
    /* Screen (≥820px) column widths via colgroup; print mode overrides these
       with mm units in the @media print block. */
    .mfst-paper-table .cg-row   { width: 42px; }
    .mfst-paper-table .cg-glyph { width: 22px; }
    .mfst-paper-table .cg-seat  { width: 64px; }
    .mfst-paper-table .cg-ref   { width: 200px; }
    .mfst-paper-table .cg-phone { width: 140px; }
    .mfst-paper-table td.t-rowletter {
        text-align: center;
        font-weight: 800;
        font-size: 14pt;
        background: #fafafa;
    }
    .mfst-paper-table td.t-glyph { width: 22px; text-align: center; font-weight: 800; font-size: 12pt; }
    .mfst-paper-table td.t-seat  { width: 64px; text-align: center; font-weight: 800; font-size: 12pt; }
    .mfst-paper-table td.t-name  { font-weight: 700; font-size: 10.5pt; }
    .mfst-paper-table td.t-name .t-owner { color: #666; font-weight: 400; font-size: 8.5pt; }
    .mfst-paper-table td.t-ref   { white-space: nowrap; }
    .mfst-paper-table td.t-phone { white-space: nowrap; font-family: ui-monospace, monospace; font-size: 9pt; }
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
       LIGHT-MODE OVERRIDES
       ==================================================================== */
    :root[data-pt-theme="light"] {
        --m-border        : rgba(15,23,42,0.10);
        --m-border-strong : rgba(15,23,42,0.18);

        --m-tone-hall            : #a26d12;
        --m-tone-hall-soft       : rgba(212,168,87,0.16);
        --m-tone-hall-edge       : rgba(162,109,18,0.55);
        --m-tone-hall-tint       : rgba(212,168,87,0.05);

        --m-tone-balcony         : #1f6a85;
        --m-tone-balcony-soft    : rgba(86,184,214,0.18);
        --m-tone-balcony-edge    : rgba(31,106,133,0.55);
        --m-tone-balcony-tint    : rgba(86,184,214,0.05);
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
    :root[data-pt-theme="light"] .mfst-chip,
    :root[data-pt-theme="light"] .mfst-density {
        background: rgba(255,255,255,0.7);
        border-color: rgba(15,23,42,0.10);
        color: var(--prism-text-2);
    }
    :root[data-pt-theme="light"] .mfst-density button[aria-pressed="true"] {
        background: rgba(15,23,42,0.08);
        color: var(--prism-text);
    }
    :root[data-pt-theme="light"] .mfst-chip[aria-pressed="true"] { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-section-head {
        background: linear-gradient(180deg,
            rgba(248,250,252,0.94) 0%,
            rgba(248,250,252,0.64) 80%,
            rgba(248,250,252,0.0) 100%);
    }
    :root[data-pt-theme="light"] .mfst-section-head .t { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-rowhead { color: var(--prism-text-3); }
    :root[data-pt-theme="light"] .mfst-card {
        background: linear-gradient(180deg, var(--m-tone-tint), var(--m-tone-tint)),
                    rgba(255,255,255,0.95);
        border-color: rgba(15,23,42,0.10);
        box-shadow: 0 1px 0 rgba(15,23,42,0.04);
    }
    :root[data-pt-theme="light"] .mfst-card:hover {
        background: linear-gradient(180deg, var(--m-tone-tint), var(--m-tone-tint)), #fff;
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
        background: linear-gradient(180deg, var(--m-tone-soft), transparent 90%),
                    rgba(15,23,42,0.03);
        border-bottom-color: rgba(15,23,42,0.10);
    }
    :root[data-pt-theme="light"] .mfst-paper-preview .sec-head .t { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-paper-preview .row { border-top-color: rgba(15,23,42,0.08); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line { border-top-color: rgba(15,23,42,0.06); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line .seat { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line .info .nm { color: var(--prism-text); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line .info .sub { color: var(--prism-text-3); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line .info .sub .ref { color: var(--prism-text-2); }
    :root[data-pt-theme="light"] .mfst-paper-preview .line.is-blocked .info .nm { color: #be123c; }

    /* ====================================================================
       PRINT RULES — A4 landscape; iPhone Safari preview-safe.
       Key iOS Safari quirks handled here:
       - position:absolute + inset:0 picks up the device viewport width,
         not the @page size, so we force explicit width in mm.
       - The page-level dir="rtl" is overridden on .mfst-paper via the
         dir="ltr" attribute on the element itself; this just reinforces it.
       - table-layout: fixed + class-based column widths in mm prevent the
         table from overflowing the page width on iOS Safari.
       ==================================================================== */
    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 12mm 8mm;
            @bottom-right {
                content: "Page " counter(page) " / " counter(pages);
                font-size: 8pt;
                color: #444;
                font-family: 'IBM Plex Sans Arabic', 'Space Grotesk', sans-serif;
            }
        }
        html, body {
            background: #fff !important;
            color: #000 !important;
            margin: 0 !important;
            padding: 0 !important;
            width: auto !important;
            overflow: visible !important;
        }
        body.has-bg::before, body.has-bg::after { display: none !important; }

        body * { visibility: hidden; }
        .mfst-paper, .mfst-paper * { visibility: visible; }
        .mfst-paper {
            position: absolute;
            top: 0;
            left: 0;
            padding: 0;
            margin: 0;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
            display: block !important;
            box-sizing: border-box;
            direction: ltr !important;
            /* A4 landscape content width = 297mm − 8mm × 2 margins = 281mm.
               Locking this prevents iOS Safari from using the device
               viewport width (e.g. 390px) as the print body width, which
               caused the table to overflow + split horizontally. */
            width: 281mm !important;
            max-width: 281mm !important;
            min-width: 0 !important;
        }
        .mfst-paper-scroll {
            display: block !important;
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important;
            min-width: 0 !important;
            width: auto !important;
        }

        /* Force the on-screen mobile preview off during print (iOS Safari
           sometimes leaves it visible behind the print preview otherwise). */
        .mfst-paper-bar, .mfst-paper-preview, .mfst-floor, .pt-no-print { display: none !important; }

        .mfst-paper-section { page-break-inside: auto; break-inside: auto; }
        .mfst-paper-section-head { page-break-after: avoid; break-after: avoid; }
        .mfst-paper-table {
            table-layout: fixed !important;
            width: 100% !important;
        }
        .mfst-paper-table tr     { page-break-inside: avoid; break-inside: avoid; }
        .mfst-paper-table thead  { display: table-header-group; }
        .mfst-paper-table tfoot  { display: table-footer-group; }
        /* Explicit mm column widths — sum to ~281mm so the row always fits.
           Auto layout was letting iOS Safari grow Booking/Phone wider than
           the page on long ref/phone strings. */
        .mfst-paper-table th.col-row, .mfst-paper-table td.t-rowletter { width: 12mm; }
        .mfst-paper-table th.col-glyph, .mfst-paper-table td.t-glyph   { width: 8mm; }
        .mfst-paper-table th.col-seat, .mfst-paper-table td.t-seat     { width: 18mm; }
        .mfst-paper-table th.col-name                                  { width: auto; }
        .mfst-paper-table th.col-ref, .mfst-paper-table td.t-ref       { width: 56mm; }
        .mfst-paper-table th.col-phone, .mfst-paper-table td.t-phone   { width: 40mm; }
        .mfst-paper-table td.t-name {
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .mfst-paper-table td.t-ref,
        .mfst-paper-table td.t-phone {
            white-space: normal !important;
            word-break: break-all;
            font-size: 8pt;
        }
        .mfst-paper-section-head[data-tone="hall"],
        .mfst-paper-section-head[data-tone="balcony"] {
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
     data-status-filter="{{ implode(',', $statusFilter) }}"
     dir="rtl">

    @if ($mode === 'floor')
        {{-- ============================================================
             FLOOR MODE — live operations
             ============================================================ --}}
        <div class="mfst-floor {{ $includeEmpty ? 'is-show-empty' : '' }} js-floor-root"
             data-include-empty="{{ $includeEmpty ? '1' : '0' }}">

            <header class="mfst-head pt-no-print">
                <div class="title">
                    <span class="live-dot" aria-hidden="true"></span>
                    <span>{{ $showTitle }}</span>
                </div>
                <div class="meta">
                    <span>{{ $eventDate }}</span>
                    @if ($eventTime)<span class="sep">·</span><span>{{ $eventTime }}</span>@endif
                    <span class="sep">·</span>
                    <span><b>{{ $totalBooked }}</b> / {{ $capacity }} booked</span>
                </div>
                <div class="mfst-head-actions">
                    <a href="{{ $url(['mode' => 'paper']) }}" class="primary">Paper sheet</a>
                    @if ($show)
                        <a href="{{ route('admin.shows.times.index', $show) }}">Back</a>
                    @else
                        <a href="{{ route('admin.dashboard') }}">Back</a>
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

                <div class="mfst-controls">
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
                    </div>
                    <div class="mfst-density" role="group" aria-label="Empty seat visibility">
                        <button type="button" class="js-density-btn" data-density="occupied" aria-pressed="{{ $includeEmpty ? 'false' : 'true' }}">Occupied</button>
                        <button type="button" class="js-density-btn" data-density="all" aria-pressed="{{ $includeEmpty ? 'true' : 'false' }}">Full hall</button>
                    </div>
                </div>
            </div>

            <div class="mfst-list js-floor-list">
                @foreach ($floorRowsBySectionRow as $sectionLabel => $byRow)
                    @php
                        $tone = $sectionTone[$sectionLabel] ?? 'hall';
                        $toneEn = $tone === 'balcony' ? 'Balcony' : 'Hall';
                        $secStats = $floorSectionStats[$sectionLabel] ?? ['booked' => 0, 'total' => 0];
                    @endphp
                    <section class="mfst-section js-floor-section" data-tone="{{ $tone }}" data-section-key="{{ $sectionLabel }}">
                        <header class="mfst-section-head">
                            <span class="edge" aria-hidden="true"></span>
                            <span class="badge">{{ strtoupper($toneEn) }}</span>
                            <span class="t">{{ $sectionLabel }}</span>
                            <span class="s"><b>{{ $secStats['booked'] }}</b> of {{ $secStats['total'] }}</span>
                        </header>
                        @foreach ($byRow as $rletter => $block)
                            @php
                                $allEmpty = true;
                                foreach ($block['seats'] as $s) {
                                    if ($s['status'] !== 'empty') { $allEmpty = false; break; }
                                }
                            @endphp
                            <div class="mfst-rowgroup js-floor-rowgroup {{ $allEmpty ? 'is-all-empty' : '' }}" data-row="{{ $rletter }}">
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
                                    <article class="mfst-card is-{{ $s['status'] }} {{ $hue !== null ? 'mfst-hue-' . $hue : '' }} js-floor-card"
                                         data-seat="{{ $seatLabel }}"
                                         data-status="{{ $s['status'] }}"
                                         data-status-en="{{ $s['status_en'] }}"
                                         data-section="{{ $s['section_label_ar'] }}"
                                         data-section-en="{{ $s['section_label_en'] }}"
                                         data-tone="{{ $tone }}"
                                         data-row="{{ $s['row_letter'] }}"
                                         data-seat-num="{{ $s['seat_number'] }}"
                                         data-name="{{ $s['attendee_name'] ?? '' }}"
                                         data-owner="{{ $s['booking_owner'] ?? '' }}"
                                         data-phone="{{ $s['phone'] ? $displayPhone($s['phone']) : '' }}"
                                         data-booking-id="{{ $s['booking_id'] ?? '' }}"
                                         data-booking-ref="{{ $s['booking_ref'] ?? '' }}"
                                         @if ($hue !== null) data-hue="{{ $hue }}" @endif
                                         data-haystack="{{ strtolower(trim(($s['attendee_name'] ?? '') . ' ' . ($s['booking_owner'] ?? '') . ' ' . ($s['booking_ref'] ?? '') . ' ' . ($s['phone'] ?? '') . ' ' . $seatLabel . ' ' . $s['section_label_ar'])) }}"
                                         role="button"
                                         tabindex="0">
                                        <div class="seat-block">
                                            <span class="seat">{{ $seatLabel }}</span>
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
                                                @if ($s['phone'])<span class="phone">{{ $displayPhone($s['phone']) }}</span>@endif
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

            {{-- Bottom sheet — attendee detail (no scan/check-in) --}}
            <div class="mfst-sheet" aria-hidden="true" data-sheet>
                <div class="mfst-sheet-scrim js-sheet-scrim"></div>
                <div class="mfst-sheet-card" role="dialog" aria-modal="true" aria-labelledby="mfst-sheet-title">
                    <div class="mfst-sheet-grip" aria-hidden="true"></div>
                    <div class="mfst-sheet-head">
                        <div class="mfst-sheet-id">
                            <div class="sec">
                                <span class="pip js-sheet-pip" aria-hidden="true"></span>
                                <span data-sheet-section></span>
                            </div>
                            <div class="seat" id="mfst-sheet-title">
                                <span data-sheet-seat>—</span>
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
                    </div>
                    <div class="mfst-sheet-party js-sheet-party" style="display:none;">
                        <div class="label">Booking party</div>
                        <ul class="js-sheet-party-list"></ul>
                    </div>
                </div>
            </div>
        </div>

    @elseif ($mode === 'paper')
        {{-- ============================================================
             PAPER MODE — mobile dossier preview + A4 print sheet
             ============================================================ --}}
        <div class="mfst-paper-bar pt-no-print">
            <a href="{{ $url(['mode' => 'floor']) }}" class="crumb">← Back to floor</a>
            <span class="spacer"></span>
            <a href="{{ $url(['mode' => 'paper', 'include_empty' => $includeEmpty ? 0 : 1]) }}">
                {{ $includeEmpty ? 'Hide empty' : 'Show empty' }}
            </a>
            <a href="{{ $csvUrl }}">⬇ CSV</a>
            <button type="button" class="primary js-print-now">🖨 Print sheet</button>
        </div>

        {{-- Mobile-only readable preview --}}
        <div class="mfst-paper-preview pt-no-print">
            <div class="mfst-paper-cover">
                <span class="kicker">Preview · prints as A4 landscape</span>
                <div class="t">{{ $showTitle }}</div>
                <div class="d">
                    {{ $eventDate }}
                    @if ($eventTime) · {{ $eventTime }} @endif
                </div>
                <div class="stats">
                    <div class="s is-approved"><b>{{ $summary['approved'] }}</b><span>Approved</span></div>
                    <div class="s is-pending"><b>{{ $summary['pending'] }}</b><span>Pending</span></div>
                    <div class="s is-blocked"><b>{{ $summary['blocked'] }}</b><span>Blocked</span></div>
                </div>
                <button type="button" class="b js-print-now">🖨 Print sheet</button>
            </div>

            @foreach ($paperRowsBySectionRow as $sectionLabel => $byRow)
                @php
                    $tone = $sectionTone[$sectionLabel] ?? 'hall';
                    $toneEn = $tone === 'balcony' ? 'Balcony' : 'Hall';
                    $secApproved = 0; $secPending = 0; $secBlocked = 0; $secEmpty = 0;
                    foreach ($byRow as $rletter => $seats) {
                        foreach ($seats as $s) {
                            $secApproved += $s['status'] === 'approved' ? 1 : 0;
                            $secPending  += $s['status'] === 'pending'  ? 1 : 0;
                            $secBlocked  += $s['status'] === 'blocked'  ? 1 : 0;
                            $secEmpty    += $s['status'] === 'empty'    ? 1 : 0;
                        }
                    }
                @endphp
                <section class="sec" data-tone="{{ $tone }}">
                    <header class="sec-head">
                        <span class="edge" aria-hidden="true"></span>
                        <span class="badge">{{ strtoupper($toneEn) }}</span>
                        <span class="t">{{ $sectionLabel }}</span>
                        <span class="s">
                            <b>{{ $secApproved + $secPending }}</b> attendees
                            @if ($secBlocked) · {{ $secBlocked }} blocked @endif
                        </span>
                    </header>
                    @foreach ($byRow as $rletter => $seats)
                        <div class="row">Row {{ $rletter }}</div>
                        @foreach ($seats as $s)
                            @php
                                $seatLabel = $s['row_letter'] . $s['seat_number'];
                                $hue = $s['booking_id'] ? ($bookingColorIndex[$s['booking_id']] ?? null) : null;
                            @endphp
                            <div class="line is-{{ $s['status'] }} {{ $hue !== null ? 'mfst-hue-' . $hue : '' }}"
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
                                            @if ($s['phone'])<span class="phone">{{ $displayPhone($s['phone']) }}</span>@endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </section>
            @endforeach
        </div>

        {{-- Desktop / print A4 sheet — forced LTR so English stats render
             correctly (the page-level dir="rtl" otherwise reorders the
             "<strong>N</strong> Label" runs into "Label · N Label · N…"). --}}
        <div class="mfst-paper-scroll">
        <div class="mfst-paper" dir="ltr">
            <div class="mfst-paper-head">
                <div class="head-title">
                    <div class="t1" dir="auto">{{ $showTitle }}</div>
                    <div class="t2">{{ $eventDate }} @if ($eventTime) · {{ $eventTime }} @endif</div>
                </div>
                <div class="stats">
                    <span class="line">
                        <strong>{{ $summary['approved'] }}</strong> Approved
                        <span class="dot">·</span>
                        <strong>{{ $summary['pending'] }}</strong> Pending
                        <span class="dot">·</span>
                        <strong>{{ $summary['blocked'] }}</strong> Blocked
                        @if ($includeEmpty)
                            <span class="dot">·</span>
                            <strong>{{ $summary['empty'] }}</strong> Empty
                        @endif
                    </span>
                    <span class="line"><strong>{{ $summary['total'] }}</strong> Total seats</span>
                </div>
            </div>

            @foreach ($paperRowsBySectionRow as $sectionLabel => $byRow)
                @php
                    $tone = $sectionTone[$sectionLabel] ?? 'hall';
                    $toneEn = $tone === 'balcony' ? 'Balcony' : 'Hall';
                    $secApproved = 0; $secPending = 0; $secBlocked = 0; $secEmpty = 0;
                    foreach ($byRow as $rletter => $seats) {
                        foreach ($seats as $s) {
                            $secApproved += $s['status'] === 'approved' ? 1 : 0;
                            $secPending  += $s['status'] === 'pending'  ? 1 : 0;
                            $secBlocked  += $s['status'] === 'blocked'  ? 1 : 0;
                            $secEmpty    += $s['status'] === 'empty'    ? 1 : 0;
                        }
                    }
                @endphp
                <section class="mfst-paper-section">
                    <div class="mfst-paper-section-head" data-tone="{{ $tone }}">
                        <span><span class="badge">{{ strtoupper($toneEn) }}</span>{{ $sectionLabel }}</span>
                        <span>{{ $secApproved + $secPending }} attendees · {{ $secBlocked }} blocked @if ($includeEmpty) · {{ $secEmpty }} empty @endif</span>
                    </div>
                    <table class="mfst-paper-table">
                        <colgroup>
                            <col class="cg-row">
                            <col class="cg-glyph">
                            <col class="cg-seat">
                            <col class="cg-name">
                            <col class="cg-ref">
                            <col class="cg-phone">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="col-row">Row</th>
                                <th class="col-glyph"></th>
                                <th class="col-seat">Seat</th>
                                <th class="col-name">Attendee</th>
                                <th class="col-ref">Booking</th>
                                <th class="col-phone">Phone</th>
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
                                            <td class="t-rowletter" rowspan="{{ count($seats) }}">{{ $rletter }}</td>
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
                                                    <span class="t-owner"> · {{ $s['booking_owner'] }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="t-ref">{{ $s['booking_ref'] ?: '—' }}</td>
                                        <td class="t-phone">{{ $displayPhone($s['phone']) }}</td>
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
                <span style="margin-inline-start:auto;">Printed {{ now()->format('Y-m-d H:i') }} · Coloured band = same booking</span>
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
       Paper — auto-print + print-button wiring
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
       Floor — search + chip filter + density toggle + bottom sheet
       ==================================================================== */
    const floorRoot = document.querySelector('.js-floor-root');
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
            const passesStatus  = active.has(status) || status === 'empty';
            const hay           = card.dataset.haystack || '';
            const passesQuery   = !q || hay.includes(q);
            const show = passesStatus && passesQuery;
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
            applyFloorVisibility();
            syncFilterToUrl();
        });
    });
    applyFloorVisibility();

    /* Density toggle (Occupied / Full hall) */
    document.querySelectorAll('.js-density-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.density;
            document.querySelectorAll('.js-density-btn').forEach(b => {
                b.setAttribute('aria-pressed', b.dataset.density === target ? 'true' : 'false');
            });
            const showEmpty = target === 'all';
            if (floorRoot) {
                floorRoot.classList.toggle('is-show-empty', showEmpty);
                floorRoot.dataset.includeEmpty = showEmpty ? '1' : '0';
            }
            const url = new URL(window.location.href);
            if (showEmpty) url.searchParams.set('include_empty', '1');
            else           url.searchParams.delete('include_empty');
            window.history.replaceState({}, '', url);
        });
    });

    /* Search */
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
       Bottom sheet — open card → fill detail. No scan/check-in.
       ==================================================================== */
    const sheet = document.querySelector('[data-sheet]');

    // Portal the sheet to <body> so position: fixed actually anchors to the
    // viewport. Without this it lives inside <main class="pt-page">, which
    // has a CSS transform creating a containing block, and the sheet ends
    // up anchored to the bottom of main (past the page fold) — the site
    // footer then bleeds through the sheet card. Same root cause + fix as
    // the checkout dock (#32), the admin action bar (#37), and the resend
    // toast (#41).
    if (sheet && sheet.parentElement !== document.body) {
        document.body.appendChild(sheet);
    }

    const sheetScrim = document.querySelector('.js-sheet-scrim');
    const sheetClose = document.querySelector('.js-sheet-close');
    let lastFocusedCard = null;

    const TONE_COLORS = {
        hall:    'rgba(212,168,87,0.55)',
        balcony: 'rgba(86,184,214,0.55)',
    };

    function openSheet(card) {
        if (!sheet || !card) return;
        const d = card.dataset;
        lastFocusedCard = card;

        const $ = (sel) => sheet.querySelector(sel);
        $('[data-sheet-seat]').textContent    = d.seat || '—';
        const sectionPieces = [d.section, d.sectionEn].filter(Boolean);
        $('[data-sheet-section]').textContent = sectionPieces.join(' · ');

        const pip = $('.js-sheet-pip');
        if (pip) pip.style.background = TONE_COLORS[d.tone] || 'rgba(255,255,255,0.20)';

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
            statusPill.setAttribute('data-tone', d.status || 'approved');
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

})();
</script>
@endpush
