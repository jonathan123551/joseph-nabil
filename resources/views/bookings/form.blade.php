@extends('layouts.app')

@section('title', __('إكمال الحجز') . ' · ' . $showTime->show->title)
@section('headMeta')
    <meta name="pt-title-i18n" content="page_title_book_form" data-suffix="{{ $showTime->show->title }}">
@endsection

@section('content')

{{-- =====================================================================
     STEP 3 — Booking form (data entry only, no seat map).
     Selected seats are read from localStorage (`booking_selection`) and
     used to:
       1. render read-only chips
       2. compute the total
       3. build hidden seat_ids[] inputs and matching names[]/phones[]
          input pairs (one card per seat)

     If localStorage is missing / mismatched / empty we send the user back
     to the seat picker. The form posts to bookings.store with the same
     contract as before — backend logic unchanged.
===================================================================== --}}

@php
    $hallPriceInt    = (int) ($hallPrice ?? 0);
    $balconyPriceInt = (int) ($balconyPrice ?? 0);
    $sectionParam    = $section ?? 'hall';
    $unitPrice       = $sectionParam === 'balcony' ? $balconyPriceInt : $hallPriceInt;
@endphp

<div class="anba-flow max-w-3xl mx-auto">
<section class="prism-fade-up"
         data-anba-form
         data-section="{{ $sectionParam }}"
         data-show-time-id="{{ (int) $showTime->id }}"
         data-unit-price="{{ $unitPrice }}"
         data-seats-url="{{ route('bookings.seats', $showTime) }}?section={{ $sectionParam }}">

    <style>
        [data-anba-form] .seat-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 8px 4px 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(16,185,129,0.22), rgba(34,211,238,0.16));
            border: 1px solid rgba(52,211,153,0.55);
            color: #d1fae5;
            font-size: 12px; font-weight: 700;
            box-shadow: 0 0 14px rgba(16,185,129,0.25), inset 0 1px 0 rgba(255,255,255,0.06);
            animation: prismFadeUp .3s var(--prism-ease) both;
        }
        [data-anba-form] .seat-chip [data-remove] {
            display: inline-flex; align-items: center; justify-content: center;
            width: 18px; height: 18px;
            border-radius: 999px;
            background: rgba(2,6,23,0.5);
            color: #fee2e2;
            font-size: 11px; font-weight: 800;
            line-height: 1;
            transition: background .15s var(--prism-ease), transform .15s var(--prism-ease);
            cursor: pointer;
        }
        [data-anba-form] .seat-chip [data-remove]:hover {
            background: rgba(244,63,94,0.6);
            transform: scale(1.08);
        }
        [data-anba-form] .add-seats-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(34,211,238,0.16), rgba(192,132,252,0.16));
            border: 1px solid rgba(129,140,248,0.45);
            color: #e0e7ff;
            font-size: 12px; font-weight: 600;
            transition: transform .15s var(--prism-ease), box-shadow .15s var(--prism-ease);
            min-height: 40px;
        }
        [data-anba-form] .add-seats-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px -4px rgba(129,140,248,0.5);
        }
        [data-anba-form] .step-list {
            counter-reset: step;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        [data-anba-form] .step-list li {
            counter-increment: step;
            position: relative;
            padding-right: 32px;
            font-size: 12.5px;
            line-height: 1.7;
            color: var(--prism-text-2);
            margin-bottom: 8px;
        }
        [data-anba-form] .step-list li::before {
            content: counter(step);
            position: absolute;
            right: 0; top: 1px;
            width: 24px; height: 24px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
            border: 1px solid rgba(129,140,248,0.55);
            color: #e0e7ff;
            font-size: 11px; font-weight: 800;
            display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 0 10px rgba(129,140,248,0.18);
        }
        [data-anba-form] .attendee-card {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 12px;
            padding: 14px;
            border-radius: 16px;
            background: rgba(255,255,255,0.035);
            border: 1px solid var(--prism-border);
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease), box-shadow .2s var(--prism-ease);
        }
        [data-anba-form] .attendee-card:focus-within {
            border-color: rgba(129,140,248,0.55);
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 0 3px rgba(129,140,248,0.10);
        }
        [data-anba-form] .attendee-card .seat-pill {
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(180deg, rgba(16,185,129,0.30), rgba(16,185,129,0.15));
            border: 1px solid rgba(52,211,153,0.6);
            color: #ecfdf5;
            font-weight: 800; font-size: 13px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(16,185,129,0.30), inset 0 1px 0 rgba(255,255,255,0.06);
            min-height: 56px;
        }
        [data-anba-form] .field-stack { display: flex; flex-direction: column; gap: 10px; }
        [data-anba-form] .field-label {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 11px; font-weight: 600;
            color: var(--prism-text-3);
            letter-spacing: .04em;
            margin-bottom: 4px;
        }
        [data-anba-form] .field-label .req {
            color: rgba(251,113,133,0.85);
            font-size: 10px;
            font-weight: 700;
        }
        [data-anba-form] .field-input {
            width: 100%;
            background: rgba(8, 10, 20, 0.7);
            border: 1px solid var(--prism-border);
            color: var(--prism-text);
            border-radius: 12px;
            padding: 12px 14px;
            /* 16px+ keeps iOS Safari from auto-zooming on focus */
            font-size: 16px;
            line-height: 1.3;
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease), box-shadow .2s var(--prism-ease);
            min-height: 48px;
        }
        [data-anba-form] .field-input::placeholder { color: var(--prism-text-4); }
        [data-anba-form] .field-input:focus {
            border-color: rgba(129,140,248,0.6);
            outline: none;
            background: rgba(8,10,20,0.9);
            box-shadow: 0 0 0 3px rgba(129,140,248,0.14);
        }
        [data-anba-form] .field-input.is-invalid {
            border-color: rgba(251,113,133,0.85) !important;
            background: rgba(251,113,133,0.06);
            box-shadow: 0 0 0 3px rgba(251,113,133,0.18);
            animation: anbaShake .35s var(--prism-ease) both;
        }
        [data-anba-form] .file-zone.is-invalid {
            border-color: rgba(251,113,133,0.85) !important;
            background: rgba(251,113,133,0.06);
            box-shadow: 0 0 0 3px rgba(251,113,133,0.18);
            animation: anbaShake .35s var(--prism-ease) both;
            border-radius: 12px;
            padding: 6px;
        }
        /* ===== Lightweight, mobile-first screenshot picker =====
           Single tappable card that morphs between an empty state
           ("tap to upload") and a filled state (thumbnail + filename +
           size + "tap to change"). The underlying native input stays
           in the DOM (hidden) so the form-data POST is unchanged.
           NO desktop drag/drop, NO clipboard paste, NO multi-file —
           per the user direction, this is purely a lightweight polish
           pass over the existing flow. */
        [data-anba-form] .file-zone-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            min-height: 84px;
            background: rgba(8, 10, 20, 0.55);
            border: 1px dashed rgba(255,255,255,0.18);
            border-radius: 14px;
            cursor: pointer;
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease);
            -webkit-tap-highlight-color: transparent;
        }
        [data-anba-form] .file-zone-card:hover,
        [data-anba-form] .file-zone-card:focus-within {
            border-color: rgba(129,140,248,0.6);
            background: rgba(8,10,20,0.75);
        }
        [data-anba-form] .file-zone-empty {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }
        [data-anba-form] .file-zone-emoji {
            font-size: 22px;
            line-height: 1;
            flex: 0 0 auto;
        }
        [data-anba-form] .file-zone-cta {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--prism-text);
            line-height: 1.3;
        }
        [data-anba-form] .file-zone-hint {
            display: block;
            font-size: 11px;
            color: var(--prism-text-3);
            margin-top: 2px;
            line-height: 1.3;
        }
        [data-anba-form] .file-zone-filled {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }
        [data-anba-form] .file-zone-thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            flex: 0 0 auto;
            border: 1px solid var(--prism-border);
        }
        [data-anba-form] .file-zone-meta {
            flex: 1 1 auto;
            min-width: 0;
        }
        [data-anba-form] .file-zone-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--prism-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        [data-anba-form] .file-zone-size {
            font-size: 11px;
            color: var(--prism-text-3);
            margin-top: 2px;
        }
        [data-anba-form] .file-zone-change {
            font-size: 12px;
            font-weight: 700;
            color: var(--prism-indigo, #818cf8);
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(129,140,248,0.12);
            flex: 0 0 auto;
            white-space: nowrap;
        }
        :root[data-pt-theme="light"] [data-anba-form] .file-zone-card {
            background: #fff;
            border-color: rgba(0,0,0,0.16);
        }
        :root[data-pt-theme="light"] [data-anba-form] .file-zone-card:hover,
        :root[data-pt-theme="light"] [data-anba-form] .file-zone-card:focus-within {
            background: #fff;
            border-color: rgba(99,102,241,0.55);
        }
        :root[data-pt-theme="light"] [data-anba-form] .file-zone-change {
            color: #4338ca;
            background: rgba(99,102,241,0.10);
        }
        @keyframes anbaShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        @media (prefers-reduced-motion: reduce) {
            [data-anba-form] .field-input.is-invalid,
            [data-anba-form] .file-zone.is-invalid { animation: none; }
        }
        /* compact gold summary chip — replaces the old heavier total-bar.
           Sits inline next to the section heading so the customer sees
           "X seats · Y EGP" at a glance without a dedicated band. */
        [data-anba-form] .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(251,191,36,0.12), rgba(251,191,36,0.04));
            border: 1px solid rgba(251,191,36,0.30);
            color: #fef3c7;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .01em;
            white-space: nowrap;
        }
        [data-anba-form] .summary-pill .seats-count { color: var(--prism-text); font-weight: 800; }
        [data-anba-form] .summary-pill .amount { color: var(--prism-gold); font-weight: 800; }
        [data-anba-form] .summary-pill .dot {
            width: 3px; height: 3px; border-radius: 999px;
            background: rgba(254,243,199,0.4);
        }

        /* Visible payment-instructions block — simple, lightweight, always
           expanded. Single glass shell with a one-line heading and the
           wallet / InstaPay copy rows underneath. No toggle, no accordion. */
        [data-anba-form] .pay-block {
            background: rgba(255,255,255,0.025);
            border: 1px solid var(--prism-border);
            border-radius: 16px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        [data-anba-form] .pay-block .pay-head {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        [data-anba-form] .pay-block .pay-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px;
            border-radius: 9px;
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
            border: 1px solid rgba(129,140,248,0.4);
            font-size: 14px;
            flex-shrink: 0;
        }
        [data-anba-form] .pay-block .pay-title {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--prism-text);
            line-height: 1.3;
        }
        [data-anba-form] .pay-block .pay-amount {
            color: var(--prism-gold);
            font-weight: 800;
        }
        [data-anba-form] .pay-block .pay-rows {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        [data-anba-form] .pay-block .pay-row {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            border-radius: 12px;
            padding: 10px 12px;
        }
        [data-anba-form] .pay-block .pay-row-label {
            display: block;
            font-size: 10px;
            color: var(--prism-text-3);
            margin-bottom: 4px;
        }

        /* Single-shell attendee container — Stripe-checkout simplicity:
           cards stack inside one glass shell with a thin divider between
           them, instead of repeating per-card outlines that add noise. */
        [data-anba-form] .attendee-stack {
            display: flex;
            flex-direction: column;
            background: rgba(255,255,255,0.025);
            border: 1px solid var(--prism-border);
            border-radius: 16px;
            overflow: hidden;
        }
        [data-anba-form] .attendee-stack .attendee-card {
            background: transparent;
            border: 0;
            border-radius: 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        [data-anba-form] .attendee-stack .attendee-card:last-child { border-bottom: 0; }
        [data-anba-form] .attendee-stack .attendee-card:focus-within {
            background: rgba(129,140,248,0.06);
            box-shadow: none;
        }

        /* Quiet "we'll send the ticket within 24h" reassurance line —
           replaces the heavy ordered list that used to dominate the page. */
        [data-anba-form] .reassurance {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255,255,255,0.025);
            border: 1px solid var(--prism-border);
            font-size: 12px;
            line-height: 1.55;
            color: var(--prism-text-2);
        }
        [data-anba-form] .reassurance .reassurance-icon {
            flex-shrink: 0;
            width: 22px; height: 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
            border: 1px solid rgba(129,140,248,0.4);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 11px;
        }
        [data-anba-form] .reassurance b { color: var(--prism-text); font-weight: 700; }

        /* ===== Sticky checkout dock (mobile + desktop) =====
           Native CSS sticky pins to viewport bottom while the booking flow
           is in view, then naturally settles at the end of its parent
           wrapper (the .anba-flow div) above the footer.
           - No JS scroll math → no jitter on iOS Safari URL-bar collapse.
           - The browser handles the fixed→settled transition on the
             compositor, no main-thread work, no layout thrash. */
        .anba-flow {
            /* Sticky context. Width matches the section it wraps. */
            position: relative;
        }
        .anba-dock {
            position: -webkit-sticky;
            position: sticky;
            bottom: 0;
            z-index: 60;
            padding: 10px 12px;
            padding-bottom: max(10px, env(safe-area-inset-bottom));
            pointer-events: none;
        }
        .anba-dock-inner {
            pointer-events: auto;
            display: flex; align-items: center; gap: 12px;
            margin: 0 auto;
            max-width: 760px;
            padding: 12px 14px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(8,10,20,0.78), rgba(5,6,13,0.94));
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            border: 1px solid rgba(129,140,248,0.30);
            box-shadow:
                0 18px 50px -18px rgba(2,6,23,0.85),
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 0 32px -8px rgba(129,140,248,0.20);
        }
        :root[data-pt-theme="light"] .anba-dock-inner {
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(247,245,238,0.96));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                0 18px 50px -18px rgba(15,23,42,0.30),
                0 0 0 1px rgba(15,23,42,0.04) inset;
        }
        .anba-dock-summary { flex: 1 1 auto; min-width: 0; line-height: 1.25; }
        .anba-dock-eyebrow {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .18em;
            color: var(--prism-text-3);
        }
        .anba-dock-amount {
            font-size: 16px; font-weight: 800;
            color: var(--prism-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .anba-dock-amount .gold { color: var(--prism-gold); }
        .anba-dock-cta {
            flex-shrink: 0;
            min-height: 48px;
            padding: 12px 22px;
            font-size: 14px;
            font-weight: 800;
            border-radius: 14px;
        }
        .anba-dock-hint {
            display: none;
            margin-top: 6px;
            font-size: 11px;
            color: rgba(251,113,133,0.95);
            font-weight: 600;
        }
        .anba-dock.has-error .anba-dock-hint { display: block; }
        @media (min-width: 640px) {
            .anba-dock { padding: 14px 16px; padding-bottom: max(14px, env(safe-area-inset-bottom)); }
            .anba-dock-inner { padding: 14px 18px; }
            .anba-dock-amount { font-size: 18px; }
            .anba-dock-cta { padding: 14px 28px; font-size: 15px; }
        }
        /* breathing room between last form input and the natural
           position of the sticky dock when it settles. */
        [data-anba-form] .form-spacer-dock { height: 24px; }
        @media (min-width: 640px) {
            [data-anba-form] .form-spacer-dock { height: 32px; }
        }

        /* Step indicator (re-used) */
        [data-anba-form] .step-indicator {
            display: flex; align-items: center; gap: 10px;
            font-size: 11px; color: var(--prism-text-3);
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        [data-anba-form] .step-indicator .dot {
            width: 22px; height: 22px;
            border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            color: var(--prism-text-4);
        }
        [data-anba-form] .step-indicator .dot.done,
        [data-anba-form] .step-indicator .dot.cur {
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
            border-color: rgba(129,140,248,0.6);
            color: #e0e7ff;
        }
        [data-anba-form] .step-indicator .line { flex: 1; height: 1px; background: linear-gradient(90deg, rgba(129,140,248,0.35), rgba(255,255,255,0.04)); }

        /* =====================================================================
           LIGHT THEME — Anba booking form.
           The dark slate field backgrounds and translucent-white card
           backgrounds become invisible / harsh on cream, so we redo every
           surface here with light glass + crisper borders + warmer shadows
           while preserving the cinematic premium accents (neon gradient
           seat chips, gold summary pill, indigo seat dots).
        ===================================================================== */
        :root[data-pt-theme="light"] [data-anba-form] .seat-chip {
            background: linear-gradient(135deg, rgba(4,120,87,0.14), rgba(8,145,178,0.10));
            border-color: rgba(4,120,87,0.45);
            color: #064e3b;
            box-shadow: 0 0 12px rgba(4,120,87,0.14), inset 0 1px 0 rgba(255,255,255,0.6);
        }
        :root[data-pt-theme="light"] [data-anba-form] .seat-chip [data-remove] {
            background: rgba(15,23,42,0.06);
            color: #7f1d1d;
        }
        :root[data-pt-theme="light"] [data-anba-form] .seat-chip [data-remove]:hover {
            background: rgba(190,18,60,0.85);
            color: #fff5f5;
        }
        :root[data-pt-theme="light"] [data-anba-form] .add-seats-btn {
            background: linear-gradient(135deg, rgba(8,145,178,0.12), rgba(124,58,237,0.14));
            border-color: rgba(79,70,229,0.40);
            color: #312e81;
        }
        :root[data-pt-theme="light"] [data-anba-form] .step-list li {
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] [data-anba-form] .step-list li::before {
            background: linear-gradient(135deg, rgba(8,145,178,0.16), rgba(124,58,237,0.18));
            border-color: rgba(79,70,229,0.55);
            color: #312e81;
            box-shadow: 0 0 10px rgba(79,70,229,0.12);
        }
        :root[data-pt-theme="light"] [data-anba-form] .attendee-card {
            background: rgba(255,255,255,0.70);
            border-color: rgba(15,23,42,0.12);
        }
        :root[data-pt-theme="light"] [data-anba-form] .attendee-card:focus-within {
            border-color: rgba(79,70,229,0.55);
            background: rgba(255,255,255,0.92);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.12);
        }
        :root[data-pt-theme="light"] [data-anba-form] .attendee-card .seat-pill {
            background: linear-gradient(180deg, rgba(4,120,87,0.22), rgba(4,120,87,0.10));
            border-color: rgba(4,120,87,0.50);
            color: #064e3b;
            box-shadow: 0 0 10px rgba(4,120,87,0.16), inset 0 1px 0 rgba(255,255,255,0.6);
        }
        :root[data-pt-theme="light"] [data-anba-form] .field-input {
            background: #ffffff;
            border-color: rgba(15,23,42,0.16);
            color: var(--prism-text);
        }
        :root[data-pt-theme="light"] [data-anba-form] .field-input:focus {
            border-color: rgba(79,70,229,0.55);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.14);
        }
        :root[data-pt-theme="light"] [data-anba-form] .field-input.is-invalid {
            background: rgba(190,18,60,0.06);
            border-color: rgba(190,18,60,0.65) !important;
            box-shadow: 0 0 0 3px rgba(190,18,60,0.16);
        }
        :root[data-pt-theme="light"] [data-anba-form] .field-input::placeholder {
            color: rgba(15,23,42,0.40);
        }
        :root[data-pt-theme="light"] [data-anba-form] .summary-pill {
            background: linear-gradient(135deg, rgba(180,83,9,0.10), rgba(180,83,9,0.04));
            border-color: rgba(180,83,9,0.32);
            color: #78350f;
        }
        :root[data-pt-theme="light"] [data-anba-form] .summary-pill .seats-count { color: #0f172a; }
        :root[data-pt-theme="light"] [data-anba-form] .summary-pill .amount { color: #b45309; }
        :root[data-pt-theme="light"] [data-anba-form] .summary-pill .dot { background: rgba(180,83,9,0.45); }
        :root[data-pt-theme="light"] [data-anba-form] .pay-block {
            background: rgba(255,255,255,0.72);
            border-color: rgba(15,23,42,0.14);
        }
        :root[data-pt-theme="light"] [data-anba-form] .pay-block .pay-icon {
            background: linear-gradient(135deg, rgba(8,145,178,0.14), rgba(124,58,237,0.16));
            border-color: rgba(79,70,229,0.40);
        }
        :root[data-pt-theme="light"] [data-anba-form] .pay-block .pay-amount { color: #b45309; }
        :root[data-pt-theme="light"] [data-anba-form] .pay-block .pay-row {
            background: rgba(15,23,42,0.04);
            border-color: rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] [data-anba-form] .attendee-stack {
            background: rgba(255,255,255,0.70);
            border-color: rgba(15,23,42,0.12);
        }
        :root[data-pt-theme="light"] [data-anba-form] .attendee-stack .attendee-card {
            border-bottom-color: rgba(15,23,42,0.08);
        }
        :root[data-pt-theme="light"] [data-anba-form] .attendee-stack .attendee-card:focus-within {
            background: rgba(79,70,229,0.06);
        }
        :root[data-pt-theme="light"] [data-anba-form] .reassurance {
            background: rgba(255,255,255,0.70);
            border-color: rgba(15,23,42,0.12);
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] [data-anba-form] .reassurance .reassurance-icon {
            background: linear-gradient(135deg, rgba(8,145,178,0.14), rgba(124,58,237,0.16));
            border-color: rgba(79,70,229,0.40);
        }
        :root[data-pt-theme="light"] [data-anba-form] .step-indicator .dot {
            background: rgba(15,23,42,0.05);
            border-color: rgba(15,23,42,0.14);
            color: var(--prism-text-4);
        }
        :root[data-pt-theme="light"] [data-anba-form] .step-indicator .dot.done,
        :root[data-pt-theme="light"] [data-anba-form] .step-indicator .dot.cur {
            background: linear-gradient(135deg, rgba(8,145,178,0.16), rgba(124,58,237,0.18));
            border-color: rgba(79,70,229,0.55);
            color: #312e81;
        }
        :root[data-pt-theme="light"] [data-anba-form] .step-indicator .line {
            background: linear-gradient(90deg, rgba(79,70,229,0.40), rgba(15,23,42,0.06));
        }
    </style>

    <div class="space-y-5">

        {{-- step indicator --}}
        <div class="step-indicator">
            <span class="dot done">✓</span>
            <span data-i18n="step_section">القسم</span>
            <span class="line"></span>
            <span class="dot done">✓</span>
            <span data-i18n="step_seat">المقعد</span>
            <span class="line"></span>
            <span class="dot cur">3</span>
            <span data-i18n="step_confirm">التأكيد</span>
        </div>

        {{-- show details --}}
        <div class="prism-glass p-5 sm:p-6 space-y-2">
            <h1 class="prism-headline text-base sm:text-lg flex items-center gap-2"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                🎭 {{ $showTime->show->title }}
            </h1>
            <div class="text-[12px] text-[color:var(--prism-text-2)] flex items-center gap-2 flex-wrap">
                <span class="prism-pill">📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</span>
                <span class="prism-pill">⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</span>
                <span class="prism-pill prism-pill-neon">
                    🎟️ {{ $unitPrice }} <span data-i18n="seat_per_seat">جنيه / مقعد</span> ·
                    @if ($sectionParam === 'balcony')
                        <span data-i18n="section_balcony">البلكون</span>
                    @else
                        <span data-i18n="section_hall">الصالة</span>
                    @endif
                </span>
            </div>
        </div>

        {{-- selected seats summary — compact pill at top, chips inline,
             "edit seats" demoted to a tertiary link. --}}
        <div class="prism-glass p-5 sm:p-6 space-y-3">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <h2 class="prism-headline text-sm sm:text-base"
                    style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;"
                    data-i18n="seat_selected_label">
                    المقاعد المختارة
                </h2>
                <span class="summary-pill" aria-live="polite">
                    <span class="seats-count" data-form-summary-count>0</span>
                    <span data-i18n="seat_chip_seat">مقعد</span>
                    <span class="dot" aria-hidden="true"></span>
                    <span class="amount" data-form-total>0</span>
                    <span>EGP</span>
                </span>
            </div>

            <div data-form-chips class="flex flex-wrap gap-1.5 min-h-[44px] p-2 rounded-xl bg-black/40 border border-[color:var(--prism-border)]">
                <span class="text-[11px] text-[color:var(--prism-text-4)]" data-empty-msg data-i18n="form_loading_seats">جارٍ تحميل المقاعد المختارة...</span>
            </div>

            <div class="flex items-center justify-between gap-2 flex-wrap">
                <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed flex-1 min-w-0" data-i18n="form_chips_hint_short">
                    اضغط × لإلغاء اختيار مقعد
                </p>
                <a href="{{ route('bookings.seats', $showTime) }}?section={{ $sectionParam }}"
                   class="text-[11px] font-semibold text-[color:var(--prism-text-2)] hover:text-[color:var(--prism-text)] underline-offset-2 hover:underline transition"
                   data-i18n="form_add_edit_seats_short">
                    إضافة / تعديل المقاعد ←
                </a>
            </div>
        </div>

        {{-- payment info — visible, simple, always-expanded block. One
             clear instruction line + the wallet / InstaPay copy rows. --}}
        @if (!empty($transferWallet) || !empty($transferInsta))
            <div class="pay-block">
                <div class="pay-head">
                    <span class="pay-icon" aria-hidden="true">💳</span>
                    <span class="pay-title">
                        <span data-i18n="form_pay_instruction_a">حوّل</span>
                        <span class="pay-amount"><span data-form-total-inline>0</span> <span data-i18n="shows_egp">جنيه</span></span>
                        <span data-i18n="form_pay_instruction_b">على InstaPay أو المحفظة</span>
                    </span>
                </div>
                <div class="pay-rows">
                    @if (!empty($transferWallet))
                        <div class="pay-row">
                            <span class="pay-row-label" data-i18n="pay_wallet">📱 محفظة</span>
                            <button type="button"
                                    class="prism-copyable w-full justify-between text-sm tracking-wide"
                                    data-pt-copy="{{ $transferWallet }}"
                                    data-i18n-attr="aria-label:copy_aria"
                                    aria-label="نسخ">
                                <span dir="ltr">{{ $transferWallet }}</span>
                                <span class="copy-icon" aria-hidden="true">⧉</span>
                            </button>
                        </div>
                    @endif
                    @if (!empty($transferInsta))
                        <div class="pay-row">
                            <span class="pay-row-label" data-i18n="pay_insta">⚡ InstaPay</span>
                            <button type="button"
                                    class="prism-copyable w-full justify-between text-sm tracking-wide"
                                    data-pt-copy="{{ $transferInsta }}"
                                    data-i18n-attr="aria-label:copy_aria"
                                    aria-label="نسخ">
                                <span dir="ltr">{{ $transferInsta }}</span>
                                <span class="copy-icon" aria-hidden="true">⧉</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- the actual form --}}
        <div class="prism-glass p-5 sm:p-6 space-y-5">

            @if ($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/40 text-rose-200 text-xs rounded-xl p-3">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bookings.store', $showTime) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  id="anbaFinalForm"
                  class="space-y-5"
                  novalidate
                  data-pt-confirm='{"tone":"warn","i18nKeys":{"title":"form_confirm_modal_title","body":"form_confirm_modal_body","okLabel":"form_confirm_ok","cancelLabel":"form_confirm_cancel"},"title":"تأكيد الحجز","body":"هتقدم طلب الحجز للمراجعة. لما يتأكد، هتوصلك التذكرة على واتساب.","okLabel":"تأكيد","cancelLabel":"إلغاء","okVariant":"emerald"}'>
                @csrf
                <input type="hidden" name="section" value="{{ $sectionParam }}">

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <h3 class="text-[13px] font-bold text-[color:var(--prism-text)] flex items-center gap-2">
                            <span aria-hidden="true">👥</span>
                            <span data-i18n="form_attendees_title">بيانات الحضور</span>
                        </h3>
                        <span class="text-[10px] text-[color:var(--prism-text-3)]" data-i18n="form_attendees_hint">اكتب اسم ورقم واتساب لكل مقعد</span>
                    </div>

                    {{-- attendee cards rendered into here from localStorage —
                         single shell + dividers (Stripe-checkout simplicity) --}}
                    <div data-form-attendees class="attendee-stack"></div>
                </div>

                <div class="space-y-2">
                    <label for="anbaScreenshotFinal" class="field-label" style="margin-bottom:0;">
                        <span class="flex items-center gap-2 text-[12px] font-semibold text-[color:var(--prism-text)]">
                            <span aria-hidden="true">📸</span>
                            <span data-i18n="form_screenshot">إيصال التحويل</span>
                        </span>
                        <span class="req" data-i18n="form_required">مطلوب</span>
                    </label>
                    {{-- Mobile-first screenshot picker. The <label> wraps a
                         hidden native file input so the entire card is the
                         tap target (better Fitts's-law affordance than the
                         old "tiny button + tiny filename" pattern). After a
                         file is picked, the JS swaps the empty state for a
                         filled state with a thumbnail + filename + size +
                         a "تغيير/Change" cue; tapping the card again opens
                         the picker so they can replace it. --}}
                    <div data-screenshot-zone>
                        <label for="anbaScreenshotFinal" class="file-zone-card" data-screenshot-card>
                            <div data-screenshot-empty class="file-zone-empty">
                                <span class="file-zone-emoji" aria-hidden="true">📸</span>
                                <div>
                                    <span class="file-zone-cta" data-i18n="form_screenshot_tap_upload">اضغط لرفع صورة إيصال التحويل</span>
                                    <small class="file-zone-hint" data-i18n="form_screenshot_hint">JPG / PNG · حد أقصى 20MB</small>
                                </div>
                            </div>
                            <div data-screenshot-filled class="file-zone-filled" hidden>
                                <img data-screenshot-thumb class="file-zone-thumb" alt="" decoding="async">
                                <div class="file-zone-meta">
                                    <div data-screenshot-name class="file-zone-name"></div>
                                    <div data-screenshot-size class="file-zone-size"></div>
                                </div>
                                <span class="file-zone-change" data-i18n="form_screenshot_replace">تغيير</span>
                            </div>
                            <input type="file"
                                   name="payment_screenshot"
                                   id="anbaScreenshotFinal"
                                   accept="image/*"
                                   hidden>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        {{-- single muted reassurance line — replaces the heavy 4-step
             ordered list. Tells the customer what to expect after submit. --}}
        <div class="reassurance">
            <span class="reassurance-icon" aria-hidden="true">⏱</span>
            <span>
                <span data-i18n="form_reassurance_a">هنراجع طلبك ونرسل التذكرة على واتساب خلال</span>
                <b data-i18n="form_step4_24h">24 ساعة</b>
                <span data-i18n="form_reassurance_b">كحد أقصى.</span>
            </span>
        </div>

        <div class="form-spacer-dock" data-anba-dock-anchor></div>
    </div>
</section>

{{-- Sticky checkout dock — lives OUTSIDE the prism-fade-up section so the
     transformed ancestor doesn't interfere with sticky bounds. Pinned to
     viewport bottom while the user scrolls through the .anba-flow wrapper,
     settles naturally at the end of the booking flow above the footer. --}}
<div class="anba-dock" data-anba-dock role="region" data-i18n-attr="aria-label:form_dock_aria" aria-label="ملخص الحجز">
    <div class="anba-dock-inner">
        <div class="anba-dock-summary">
            <div class="anba-dock-eyebrow" data-i18n="seat_total">الإجمالي</div>
            <div class="anba-dock-amount">
                <span data-form-mobile-count>0</span> <span data-i18n="seat_chip_seat">مقعد</span> ·
                <span class="gold"><span data-form-mobile-total>0</span> EGP</span>
            </div>
            <div class="anba-dock-hint" data-form-dock-hint data-i18n="form_dock_hint">اكمل الحقول المطلوبة</div>
        </div>
        <button type="submit"
                form="anbaFinalForm"
                data-form-mobile-submit
                class="prism-btn-gold prism-ripple anba-dock-cta">
            <span data-i18n="form_confirm_btn">تأكيد الحجز</span>
            <span aria-hidden="true">✓</span>
        </button>
    </div>
</div>
</div>{{-- /.anba-flow --}}

<script>
(function () {
    const root = document.querySelector('[data-anba-form]');
    if (!root) return;

    const showTimeId = parseInt(root.dataset.showTimeId || '0', 10);
    const sectionParam = root.dataset.section || 'hall';
    const unitPrice = parseInt(root.dataset.unitPrice || '0', 10);
    const seatsUrl = root.dataset.seatsUrl;

    const chipsBox    = root.querySelector('[data-form-chips]');
    const totalEl     = root.querySelector('[data-form-total]');
    const summaryCnt  = root.querySelector('[data-form-summary-count]');
    const attendees   = root.querySelector('[data-form-attendees]');
    const screenshot  = root.querySelector('#anbaScreenshotFinal');
    const screenshotZone = root.querySelector('[data-screenshot-zone]');
    const form        = document.querySelector('#anbaFinalForm');
    const dock        = document.querySelector('[data-anba-dock]');

    // No portal / no scroll math: the dock now uses native CSS sticky
    // (see .anba-dock styles above). The browser handles pin→settle on
    // the compositor with no main-thread work — zero jitter.

    const dockHint    = document.querySelector('[data-form-dock-hint]');
    const mobileCount = document.querySelector('[data-form-mobile-count]');
    const mobileTotal = document.querySelector('[data-form-mobile-total]');

    let isSubmitting = false;

    // ----- read selection from localStorage -----
    let stored = null;
    try {
        stored = JSON.parse(localStorage.getItem('booking_selection') || 'null');
    } catch (e) { stored = null; }

    if (!stored
        || stored.showTimeId !== showTimeId
        || stored.section !== sectionParam
        || !Array.isArray(stored.seats)
        || stored.seats.length === 0) {
        // Nothing valid to work with — back to seat picker.
        window.location.replace(seatsUrl);
        return;
    }

    let seats = stored.seats.filter(s => typeof s.id === 'number' && s.label);

    const totalInlineEl = root.querySelector('[data-form-total-inline]');
    const emptyMsg      = root.querySelector('[data-empty-msg]');

    // ----- render chips (with × delete) -----
    // Tiny i18n shim — wraps `window.PT_T(key, vars)` and adds a
    // fallback string so the placeholder renders cleanly before the
    // dictionary is hydrated. `vars` are forwarded for `{n}`-style
    // substitution and applied to the fallback too when PT_T misses.
    function tt(key, fallback, vars) {
        if (window.PT_T) {
            const s = window.PT_T(key, vars);
            if (s !== key) return s;
        }
        let s = fallback != null ? String(fallback) : key;
        if (vars && typeof s === 'string') {
            s = s.replace(/\{(\w+)\}/g, (m, k) => (vars[k] !== undefined ? vars[k] : m));
        }
        return s;
    }

    function renderChips() {
        chipsBox.innerHTML = '';
        if (seats.length === 0) {
            const span = document.createElement('span');
            span.className = 'text-[11px] text-[color:var(--prism-text-4)]';
            span.textContent = tt('form_chips_empty', 'لم يعد هناك مقاعد مختارة');
            chipsBox.appendChild(span);
            return;
        }
        seats.forEach(s => {
            const chip = document.createElement('span');
            chip.className = 'seat-chip';
            const removeAria = tt('form_chip_remove_aria', 'إلغاء {label}', { label: s.label });
            chip.innerHTML = `
                <span>${escapeHtml(s.label)}</span>
                <button type="button" data-remove="${s.id}" aria-label="${escapeAttr(removeAria)}">✕</button>
            `;
            chipsBox.appendChild(chip);
        });
    }

    // ----- render attendee cards (one per seat, with hidden seat_ids[]) -----
    // Cached values are preserved across re-renders so removing a chip
    // does not wipe what the user already typed for the remaining seats.
    function renderAttendees() {
        const cached = {};
        attendees.querySelectorAll('.attendee-card').forEach(card => {
            const sid = card.dataset.seatId;
            const nameInput  = card.querySelector('input[name="names[]"]');
            const phoneInput = card.querySelector('input[name="phones[]"]');
            cached[sid] = {
                name:  nameInput  ? nameInput.value  : '',
                phone: phoneInput ? phoneInput.value : '',
            };
        });

        attendees.innerHTML = '';
        seats.forEach((s, i) => {
            const wrap = document.createElement('div');
            wrap.className = 'attendee-card';
            wrap.dataset.seatId = s.id;
            const nameId  = `anba-name-${s.id}`;
            const phoneId = `anba-phone-${s.id}`;
            const nameLbl   = tt('form_name_label',  'الاسم');
            const phoneLbl  = tt('form_phone_label', 'رقم واتساب');
            const reqLbl    = tt('form_required',    'مطلوب');
            const namePh    = tt('book_form_name_ph', 'اسم الشخص {n}', { n: i + 1 });
            wrap.innerHTML = `
                <div class="seat-pill">${escapeHtml(s.label)}</div>
                <div class="field-stack">
                    <input type="hidden" name="seat_ids[]" value="${s.id}">
                    <div>
                        <label for="${nameId}" class="field-label">
                            <span>${escapeHtml(nameLbl)}</span>
                            <span class="req">${escapeHtml(reqLbl)}</span>
                        </label>
                        <input type="text"
                               id="${nameId}"
                               name="names[]"
                               placeholder="${escapeAttr(namePh)}"
                               class="field-input"
                               autocomplete="name"
                               autocapitalize="words"
                               spellcheck="false"
                               enterkeyhint="next"
                               value="${escapeAttr(cached[s.id]?.name || '')}">
                    </div>
                    <div>
                        <label for="${phoneId}" class="field-label">
                            <span>${escapeHtml(phoneLbl)}</span>
                            <span class="req">${escapeHtml(reqLbl)}</span>
                        </label>
                        <input type="tel"
                               id="${phoneId}"
                               name="phones[]"
                               placeholder="01xxxxxxxxx"
                               class="field-input"
                               inputmode="tel"
                               autocomplete="tel"
                               dir="ltr"
                               enterkeyhint="next"
                               value="${escapeAttr(cached[s.id]?.phone || '')}">
                    </div>
                </div>
            `;
            attendees.appendChild(wrap);
        });
    }

    // Re-render attendee cards + chips when language toggles so AR/EN
    // labels stay in sync — `applyLang()` in layouts/app.blade.php
    // dispatches `pt:langchange` on `document` after rewriting the dict.
    //
    // Focus + caret position are captured before the rebuild and
    // restored afterwards, so toggling the language while typing on
    // Android Chrome doesn't drop the input or collapse Gboard.
    // (Same-language calls are already a no-op upstream so this path
    // only fires on a genuine AR↔EN toggle, but the restoration is
    // cheap and keeps the experience seamless regardless.)
    document.addEventListener('pt:langchange', () => {
        const active = document.activeElement;
        let focusId = null, selStart = null, selEnd = null;
        if (active && attendees.contains(active) && active.id) {
            focusId = active.id;
            try { selStart = active.selectionStart; selEnd = active.selectionEnd; } catch (_) {}
        }
        renderChips();
        renderAttendees();
        if (focusId) {
            const next = document.getElementById(focusId);
            if (next) {
                try { next.focus({ preventScroll: true }); } catch (_) { try { next.focus(); } catch (_) {} }
                if (selStart != null && selEnd != null) {
                    try { next.setSelectionRange(selStart, selEnd); } catch (_) {}
                }
            }
        }
    });

    // Clear invalid styling on any input/edit so the user gets immediate
    // visual feedback that the issue was addressed.
    attendees.addEventListener('input', (e) => {
        const t = e.target;
        if (t && t.classList && t.classList.contains('is-invalid')) {
            t.classList.remove('is-invalid');
        }
        if (dock && dock.classList.contains('has-error')) {
            // soft-clear hint when user is fixing things
            const stillBad = form.querySelector('.is-invalid');
            if (!stillBad) dock.classList.remove('has-error');
        }
    });

    function escapeHtml(v) {
        return String(v).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }
    function escapeAttr(v) {
        return String(v).replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function totals() {
        const n = seats.length;
        return { count: n, total: n * unitPrice };
    }

    function paintTotals() {
        const t = totals();
        const totalStr = t.total.toLocaleString('en-US');
        totalEl.textContent       = totalStr;
        if (summaryCnt)   summaryCnt.textContent   = t.count;
        if (mobileCount)  mobileCount.textContent  = t.count;
        if (mobileTotal)  mobileTotal.textContent  = totalStr;
        if (totalInlineEl) totalInlineEl.textContent = totalStr;
    }

    // Returns the first invalid field in DOM order (or null).
    // Order: names/phones per seat (top→bottom), then payment screenshot.
    function firstInvalid() {
        const fields = attendees.querySelectorAll('input[name="names[]"], input[name="phones[]"]');
        for (let i = 0; i < fields.length; i++) {
            if (!fields[i].value.trim()) return fields[i];
        }
        if (!screenshot.files || screenshot.files.length === 0) return screenshot;
        return null;
    }

    function highlightInvalid(el) {
        if (!el) return;
        if (el === screenshot) {
            if (screenshotZone) screenshotZone.classList.add('file-zone', 'is-invalid');
        } else {
            el.classList.add('is-invalid');
        }
    }

    function clearAllInvalid() {
        form.querySelectorAll('.is-invalid').forEach(n => n.classList.remove('is-invalid'));
        if (dock) dock.classList.remove('has-error');
    }

    function guideToInvalid(el) {
        highlightInvalid(el);
        if (dock) dock.classList.add('has-error');
        // Smooth scroll into view (centered) — accounts for floating dock height.
        const target = (el === screenshot && screenshotZone) ? screenshotZone : el;
        try {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (_) {
            target.scrollIntoView();
        }
        // Focus after a tick so the smooth-scroll on iOS isn't interrupted.
        setTimeout(() => {
            try { el.focus({ preventScroll: true }); } catch (_) { el.focus(); }
        }, 250);
    }

    // ----- screenshot preview -----
    // Mobile-first picker: empty card morphs into a thumbnail + filename +
    // size + "تغيير" cue once a file is chosen. Reads the file via a
    // FileReader so the preview works fully client-side (no Cloudinary
    // round-trip). Caps preview rendering at 5MB just to keep memory
    // bounded on older iPhones — the file itself can still be 20MB
    // (Laravel validates server-side); we just skip the FileReader for
    // huge files and show a generic icon instead. Object URL would be
    // even cheaper but iOS Safari < 15 had blob-URL quirks.
    const shotEmpty   = root.querySelector('[data-screenshot-empty]');
    const shotFilled  = root.querySelector('[data-screenshot-filled]');
    const shotThumb   = root.querySelector('[data-screenshot-thumb]');
    const shotName    = root.querySelector('[data-screenshot-name]');
    const shotSize    = root.querySelector('[data-screenshot-size]');

    function formatFileSize(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) return '';
        const KB = 1024, MB = KB * 1024;
        if (bytes >= MB) return (bytes / MB).toFixed(bytes >= 10 * MB ? 0 : 1) + ' MB';
        if (bytes >= KB) return Math.round(bytes / KB) + ' KB';
        return bytes + ' B';
    }

    function setScreenshotEmpty() {
        if (shotEmpty)  shotEmpty.hidden = false;
        if (shotFilled) shotFilled.hidden = true;
        if (shotThumb)  shotThumb.removeAttribute('src');
        if (shotName)   shotName.textContent = '';
        if (shotSize)   shotSize.textContent = '';
    }

    function setScreenshotFilled(file) {
        if (!file) return setScreenshotEmpty();
        if (shotName) shotName.textContent = file.name || '';
        if (shotSize) shotSize.textContent = formatFileSize(file.size);
        if (shotEmpty)  shotEmpty.hidden = true;
        if (shotFilled) shotFilled.hidden = false;
        // Skip the FileReader for very large or non-image files. The
        // .file-zone-thumb background colour gives a soft placeholder
        // in that fallback case, so the layout never collapses.
        if (!shotThumb || !file.type || !file.type.startsWith('image/')) return;
        if (file.size > 5 * 1024 * 1024) return; // ~5 MB preview cap
        const reader = new FileReader();
        reader.onload = (ev) => {
            try { shotThumb.src = ev.target.result; } catch (_) {}
        };
        reader.readAsDataURL(file);
    }

    screenshot.addEventListener('change', () => {
        if (screenshotZone) screenshotZone.classList.remove('is-invalid');
        if (dock && dock.classList.contains('has-error')) {
            const stillBad = form.querySelector('.is-invalid');
            if (!stillBad) dock.classList.remove('has-error');
        }
        const file = screenshot.files && screenshot.files[0];
        if (file) setScreenshotFilled(file);
        else      setScreenshotEmpty();
    });

    // iOS bfcache restoration may leave the input with a stale file
    // reference whose FileList is empty (Safari quirk) — sync the
    // preview UI to whatever the input *actually* holds at restore time.
    window.addEventListener('pageshow', () => {
        const file = screenshot && screenshot.files && screenshot.files[0];
        if (file) setScreenshotFilled(file);
        else      setScreenshotEmpty();
    });

    // ----- chip × delete handler -----
    function persistSeats() {
        try {
            localStorage.setItem('booking_selection', JSON.stringify({
                showTimeId,
                section: sectionParam,
                unitPrice,
                seats,
                savedAt: Date.now(),
            }));
        } catch (e) { /* ignore */ }
    }

    chipsBox.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove]');
        if (!btn) return;
        const id = parseInt(btn.dataset.remove, 10);
        seats = seats.filter(s => s.id !== id);

        if (seats.length === 0) {
            // No seats left — clear and bounce back to the picker.
            try { localStorage.removeItem('booking_selection'); } catch (e) {}
            window.location.replace(seatsUrl);
            return;
        }

        persistSeats();
        renderChips();
        renderAttendees();
        paintTotals();
    });

    // Smart-validation runs BEFORE the layout-level pt-confirm modal handler
    // (this listener is registered first because this script is in the page
    // body, while the pt-confirm handler is registered at the end of the
    // layout). If we find a missing field we stopImmediatePropagation so
    // the confirm modal does not appear for an invalid form.
    form.addEventListener('submit', (e) => {
        if (isSubmitting) { e.preventDefault(); return false; }

        if (seats.length === 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert(tt('form_at_least_one', '❌ من فضلك اختر مقعد واحد على الأقل'));
            window.location.replace(seatsUrl);
            return false;
        }

        clearAllInvalid();
        const bad = firstInvalid();
        if (bad) {
            e.preventDefault();
            e.stopImmediatePropagation();
            guideToInvalid(bad);
            return false;
        }

        isSubmitting = true;
        // Disable + show inline spinner on every submit button bound to
        // this form (currently just the dock CTA, but the selector is
        // future-proof). The is-loading class drives a CSS spinner via
        // the layout — we don't replace innerText so the `data-i18n`
        // span keeps re-translating correctly on language toggle.
        const submitBtns = document.querySelectorAll(
            '[data-form-mobile-submit], button[form="anbaFinalForm"], #anbaFinalForm button[type="submit"]'
        );
        submitBtns.forEach((btn) => {
            btn.disabled = true;
            btn.classList.add('is-loading');
            btn.setAttribute('aria-busy', 'true');
        });
        // Selection successfully sent — clear so refresh / back doesn't
        // resurrect an old payload. (If the server returns validation
        // errors the user is bounced back to this same page; the form
        // is then driven by old() seat_ids[] hidden values, but we don't
        // currently re-store from server. The chips/attendees will be
        // empty in that edge case. To keep UX safe we DON'T clear if
        // the user navigates back without submitting.)
        try { localStorage.removeItem('booking_selection'); } catch (e) {}
    });

    // iOS / Safari back-forward cache restores the page WITH the
    // submit-disabled state, leaving the customer stuck looking at a
    // greyed-out button. `pageshow` with persisted=true means the page
    // came out of the bfcache → reset the guard so the form is usable
    // again. (Modern browsers only fire this when bfcache hits.)
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        isSubmitting = false;
        document.querySelectorAll(
            '[data-form-mobile-submit], button[form="anbaFinalForm"], #anbaFinalForm button[type="submit"]'
        ).forEach((btn) => {
            btn.disabled = false;
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
        });
    });

    // ----- init -----
    renderChips();
    renderAttendees();
    paintTotals();
})();
</script>

@endsection
