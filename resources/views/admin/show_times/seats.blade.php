@extends('layouts.seats-fullscreen')

@section('title', 'إدارة المقاعد · ' . ($showTime->show->title ?? ''))

@section('content')
{{--
    Admin seat-management.

    Renders the EXACT same canvas-based seat picker the customer sees during
    booking (`bookings._anba_seat_picker`) — same `computeLayout()`, same
    `STEP`/`RIGHT_SHIFT_STEPS`, same pinch/pan/zoom engine — and only flips
    the click-handler / dock / save semantics into admin mode. That gives
    the admin "the real customer seat picker with admin powers" without
    forking layout or geometry code.
--}}

{{-- Premium center toast for save feedback. The picker JS finds this via
     `[data-admin-toast]` and toggles `.is-on`. Same look as Prism's
     pt-toast-overlay used elsewhere in the admin. --}}
<div class="prism-admin-toast" data-admin-toast role="status" aria-live="polite">
    <div class="pt-toast-card">
        <div class="pt-toast-icon" data-toast-icon>✓</div>
        <div class="pt-toast-text">
            <div class="pt-toast-title" data-toast-title data-i18n="adm_seats_saved">تم حفظ التغييرات</div>
            <div class="pt-toast-body"  data-toast-body></div>
        </div>
    </div>
</div>

<style>
    .prism-admin-toast {
        position: fixed;
        inset: 0;
        z-index: 70;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s cubic-bezier(.2,.7,.2,1);
    }
    .prism-admin-toast.is-on { opacity: 1; }
    .prism-admin-toast .pt-toast-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(20,24,38,0.92), rgba(8,10,20,0.92));
        border: 1px solid rgba(129,140,248,0.32);
        backdrop-filter: blur(18px) saturate(140%);
        -webkit-backdrop-filter: blur(18px) saturate(140%);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.06),
            0 24px 48px -22px rgba(0,0,0,0.85),
            0 0 24px rgba(34,211,238,0.18);
        transform: translateY(8px) scale(.96);
        transition: transform 0.25s cubic-bezier(.2,.7,.2,1);
        max-width: 420px;
    }
    .prism-admin-toast.is-on .pt-toast-card {
        transform: translateY(0) scale(1);
    }
    .prism-admin-toast .pt-toast-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 20px;
        font-weight: 700;
        background: linear-gradient(180deg, rgba(52,211,153,0.18), rgba(52,211,153,0.06));
        border: 1px solid rgba(167,243,208,0.45);
        color: #6ee7b7;
    }
    .prism-admin-toast .pt-toast-card.is-error .pt-toast-icon {
        background: linear-gradient(180deg, rgba(251,113,133,0.20), rgba(251,113,133,0.06));
        border-color: rgba(251,113,133,0.45);
        color: #fb7185;
    }
    .prism-admin-toast .pt-toast-title {
        font-size: 13px;
        font-weight: 700;
        color: #f1f5fb;
    }
    .prism-admin-toast .pt-toast-body {
        font-size: 11px;
        color: #c2cad8;
        margin-top: 2px;
    }
    .prism-admin-toast .pt-toast-body:empty { display: none; }

    /* ---- Light-mode overrides: admin seat-editor save toast ----
       Fires after every block/unblock save. The dark navy gradient
       looks pasted-in on the cream admin chrome; swap to a white-cream
       surface with neutral border, softer shadow, and dampened tonal
       backgrounds for the success / error icons. */
    :root[data-pt-theme="light"] .prism-admin-toast .pt-toast-card {
        background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.98));
        border-color: rgba(15,23,42,0.12);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.7),
            0 24px 48px -22px rgba(15,23,42,0.30),
            0 0 24px rgba(34,211,238,0.10);
    }
    :root[data-pt-theme="light"] .prism-admin-toast .pt-toast-icon {
        background: rgba(16,185,129,0.10);
        border-color: rgba(16,185,129,0.50);
        color: var(--prism-emerald);
    }
    :root[data-pt-theme="light"] .prism-admin-toast .pt-toast-card.is-error .pt-toast-icon {
        background: rgba(244,63,94,0.10);
        border-color: rgba(244,63,94,0.50);
        color: var(--prism-rose);
    }
    :root[data-pt-theme="light"] .prism-admin-toast .pt-toast-title {
        color: var(--prism-text);
    }
    :root[data-pt-theme="light"] .prism-admin-toast .pt-toast-body {
        color: var(--prism-text-2);
    }

    /* Direction hint on admin chips: gold for "will block", emerald for
       "will unblock". Customer chips are unaffected (no data-flip attr). */
    [data-anba-root][data-admin-mode="1"] .seat-chip[data-flip="block"] {
        background: linear-gradient(135deg, rgba(251,191,36,0.18), rgba(251,191,36,0.04));
        border-color: rgba(253,224,71,0.45);
        color: #fef3c7;
    }
    [data-anba-root][data-admin-mode="1"] .seat-chip[data-flip="unblock"] {
        background: linear-gradient(135deg, rgba(52,211,153,0.18), rgba(52,211,153,0.04));
        border-color: rgba(167,243,208,0.45);
        color: #d1fae5;
    }
</style>

@php
    // Admin can manage either section using the same engine.
    // Default = 'hall' to preserve existing admin URLs and flow.
    $adminSection = request('section', 'hall');
    $adminSection = in_array($adminSection, ['hall', 'balcony'], true) ? $adminSection : 'hall';
    $hasBalconySeats = isset($seatsByRow['balcony']) && !empty($seatsByRow['balcony']);
    $sectionToggleUrl = function ($s) use ($showTime) {
        // Route is registered as `admin.show-times.seats.index` in routes/web.php.
        // The dotted-`.index` suffix is required because the route lives inside
        // the `admin.` group and uses the `show-times.seats.index` resource
        // naming convention. Calling `admin.show-times.seats` (without
        // `.index`) throws "Route [admin.show-times.seats] not defined."
        return route('admin.show-times.seats.index', $showTime) . '?section=' . $s;
    };
@endphp

{{-- Compact admin section switcher (only when both sections exist).
     Sticks to the top-inline-end of the canvas chrome so it never
     overlaps the seat picker controls. --}}
@if ($hasBalconySeats)
    <style>
        .anba-admin-section-switch {
            position: fixed;
            top: 14px;
            inset-inline-start: 50%;
            transform: translateX(-50%);
            z-index: 60;
            display: inline-flex;
            border: 1px solid rgba(129,140,248,0.32);
            border-radius: 999px;
            overflow: hidden;
            background: rgba(8,10,20,0.78);
            backdrop-filter: blur(14px) saturate(140%);
            -webkit-backdrop-filter: blur(14px) saturate(140%);
            box-shadow: 0 8px 24px -10px rgba(0,0,0,0.6);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
        }
        html[dir="rtl"] .anba-admin-section-switch { transform: translateX(50%); }
        .anba-admin-section-switch a {
            padding: 8px 16px;
            color: #c2cad8;
            transition: background .15s ease, color .15s ease;
        }
        .anba-admin-section-switch a:hover { color: #fff; background: rgba(129,140,248,0.10); }
        .anba-admin-section-switch a.is-active {
            color: #fff;
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
        }
    </style>
    <div class="anba-admin-section-switch" role="tablist" aria-label="Section switcher">
        <a href="{{ $sectionToggleUrl('hall') }}"
           class="{{ $adminSection === 'hall' ? 'is-active' : '' }}"
           role="tab"
           aria-selected="{{ $adminSection === 'hall' ? 'true' : 'false' }}"
           data-i18n="section_hall">الصالة</a>
        <a href="{{ $sectionToggleUrl('balcony') }}"
           class="{{ $adminSection === 'balcony' ? 'is-active' : '' }}"
           role="tab"
           aria-selected="{{ $adminSection === 'balcony' ? 'true' : 'false' }}"
           data-i18n="section_balcony">البلكون</a>
    </div>
@endif

@include('bookings._anba_seat_picker', [
    'showTime'         => $showTime,
    'section'          => $adminSection,
    'seatsByRow'       => $seatsByRow ?? [],
    'unavailableSeats' => $bookedSeatIds  ?? [],
    'blockedSeats'     => $blockedSeatIds ?? [],
    'balconyPrice'     => 0,
    'hallPrice'        => 0,
    'transferWallet'   => '',
    'transferInsta'    => '',
    'fullscreen'       => true,
    'adminMode'        => true,
    'bulkToggleUrl'    => route('admin.show-times.seats.bulk-toggle', $showTime),
    'adminBackUrl'     => $showTime->show
        ? route('admin.shows.times.index', $showTime->show)
        : route('admin.shows.index'),
])

@endsection
