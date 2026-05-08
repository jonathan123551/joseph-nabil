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
            <div class="pt-toast-title" data-toast-title>تم حفظ التغييرات</div>
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

@include('bookings._anba_seat_picker', [
    'showTime'         => $showTime,
    'section'          => 'hall',
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
    'adminBackUrl'     => route('admin.show-times.index'),
])

@endsection
