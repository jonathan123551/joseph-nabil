@extends('layouts.app')

@section('title', 'إدارة المقاعد - ' . $showTime->show->title)

@php
    $bookedSet  = collect($bookedSeatIds)->flip();
    $blockedSet = collect($blockedSeatIds)->flip();
    $toggleUrlTemplate = route('admin.show-times.seats.toggle', [$showTime, '__SEAT__']);
@endphp

@section('content')
{{--
    Premium admin seat-management UI.

    Visually mirrors the customer seat-picker experience: same glass canvas
    feel, pinch-to-zoom + pan, mobile onboarding hint, floating action dock,
    and Prism center-screen toast. Backend stays identical — each pending
    toggle still POSTs to admin.show-times.seats.toggle one seat at a time.
--}}
<section
    class="admin-seat-root prism-fade-up"
    data-admin-seat-root
    data-toggle-url="{{ $toggleUrlTemplate }}"
    data-csrf="{{ csrf_token() }}">

    {{-- Header --}}
    <div class="prism-glass prism-glow-border p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                Seats Management
            </span>
            <h1 class="prism-headline text-xl sm:text-2xl">
                <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    إدارة المقاعد
                </span>
            </h1>
            <p class="text-xs text-[color:var(--prism-text-3)]">
                {{ $showTime->show->title }} ·
                {{ $showTime->date->format('d/m/Y') }} ·
                {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}
            </p>
        </div>

        <a href="{{ route('admin.shows.times.index', $showTime->show) }}" class="prism-btn-ghost text-xs">
            <span aria-hidden="true">→</span>
            رجوع
        </a>
    </div>

    <div class="prism-glass p-3.5 text-xs text-[color:var(--prism-text-2)] leading-relaxed prism-fade-up">
        اضغط على المقاعد لتحديدها — في الأسفل اضغط <strong>حفظ</strong> لتطبيق التغييرات.
        المقاعد المحجوزة من العملاء (وردية) لا يمكن تعديلها من هنا — ارفض الحجز أولًا.
    </div>

    {{-- Stage marker --}}
    <div class="text-center prism-fade-up">
        <div class="inline-block px-6 py-1.5 rounded-full text-[11px] tracking-widest"
             style="background: linear-gradient(135deg, rgba(34,211,238,0.14), rgba(192,132,252,0.14));
                    border: 1px solid var(--prism-border-strong);
                    color: #e0e7ff;
                    box-shadow: 0 0 18px rgba(129,140,248,0.25), 0 0 36px rgba(34,211,238,0.10);">
            STAGE
        </div>
    </div>

    {{-- Legend --}}
    <div class="legend-row prism-fade-up">
        <span class="legend-chip"><span class="legend-swatch sw-available"></span>متاح</span>
        <span class="legend-chip"><span class="legend-swatch sw-blocked"></span>محجوب (إدارة)</span>
        <span class="legend-chip"><span class="legend-swatch sw-pending"></span>تغيير مؤجَّل</span>
        <span class="legend-chip"><span class="legend-swatch sw-booked"></span>محجوز (عميل)</span>
    </div>

    {{-- ===================== Seat map (pan + zoom) ===================== --}}
    <section class="seat-mapwrap prism-fade-up">
        <div class="seat-topbar">
            <span class="seat-title">◆ خريطة القاعة</span>
            <div class="zoom-bar" role="group" aria-label="تكبير وتصغير">
                <button type="button" class="zoom-btn" data-zoom="-1" aria-label="تصغير">−</button>
                <button type="button" class="zoom-btn" data-zoom="0"  aria-label="إعادة">⟳</button>
                <button type="button" class="zoom-btn" data-zoom="1"  aria-label="تكبير">+</button>
            </div>
        </div>

        <div class="seat-scroller" data-seat-scroller>
            <div class="seat-stage" data-seat-stage>
                @foreach(['balcony' => 'بلكون', 'hall' => 'صالة'] as $section => $label)
                    <div class="section-block">
                        <h3 class="section-title">{{ $label }}</h3>
                        <div class="rows">
                            @foreach($seatsByRow[$section] ?? [] as $rowLetter => $sides)
                                <div class="row-line">
                                    <span class="row-label">{{ $rowLetter }}</span>
                                    @foreach(['left' => 'side-left', 'center' => 'side-center', 'right' => 'side-right'] as $side => $sideClass)
                                        <span class="side {{ $sideClass }}">
                                            @foreach($sides[$side] as $seat)
                                                @php
                                                    $isBooked  = $bookedSet->has($seat->id);
                                                    $isBlocked = $blockedSet->has($seat->id);
                                                    $state = $isBooked ? 'booked' : ($isBlocked ? 'blocked' : 'available');
                                                @endphp
                                                <button type="button"
                                                        class="seat-btn"
                                                        data-seat-id="{{ $seat->id }}"
                                                        data-seat-label="{{ $rowLetter }}{{ $seat->seat_number }}"
                                                        data-seat-state="{{ $state }}"
                                                        @if($isBooked) disabled aria-disabled="true" @endif
                                                        title="{{ $rowLetter }}{{ $seat->seat_number }}">
                                                    <span class="seat-num">{{ $seat->seat_number }}</span>
                                                </button>
                                            @endforeach
                                        </span>
                                    @endforeach
                                    <span class="row-label">{{ $rowLetter }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pinch & pan onboarding hint (mobile only).
                 Stays visible until the admin actually interacts with the
                 map, then fades out. Lightweight and pointer-event-passthrough. --}}
            <div class="gesture-hint" data-gesture-hint role="status" aria-live="polite">
                <div class="hint-card">
                    <div class="hint-icon" aria-hidden="true">
                        <svg viewBox="0 0 64 44" xmlns="http://www.w3.org/2000/svg">
                            <circle class="pinch-finger a" cx="22" cy="22" r="6"/>
                            <circle class="pinch-finger b" cx="42" cy="22" r="6"/>
                        </svg>
                    </div>
                    <div class="hint-text">استخدم إصبعين للتكبير والتحريك</div>
                    <div class="hint-sub">Pinch &amp; pan</div>
                </div>
            </div>

            {{-- Floating zoom FAB (mobile primary path). --}}
            <div class="canvas-fab" aria-hidden="false">
                <button type="button" class="fab-btn" data-zoom="1"  aria-label="تكبير">+</button>
                <button type="button" class="fab-btn" data-zoom="0"  aria-label="احتواء">⤢</button>
                <button type="button" class="fab-btn" data-zoom="-1" aria-label="تصغير">−</button>
            </div>
        </div>

        <p class="seat-help">
            اسحب للتنقل · قرّب بإصبعين أو بضغطة مزدوجة · اضغط مقعد لتحديده
        </p>
    </section>

</section>

{{-- ===================== Floating action dock ===================== --}}
<div class="pt-action-bar pt-bar-admin" id="adminSeatsBar" role="region" aria-label="حفظ تغييرات المقاعد">
    <div class="pt-action-bar-inner">
        <div class="pt-bar-summary">
            <span class="pt-bar-label">تغييرات معلَّقة على المقاعد</span>
            <span class="pt-bar-meta" data-seats-summary>اختر مقعدًا أو أكثر للحجب أو التفعيل.</span>
            <span class="pt-bar-meta-row">
                <span class="pt-bar-chip"><span aria-hidden="true">🚫</span> <span data-block-count>0</span></span>
                <span class="pt-bar-chip pt-bar-chip-gold"><span aria-hidden="true">↺</span> <span data-unblock-count>0</span></span>
            </span>
        </div>
        <div class="pt-bar-actions">
            <button type="button" class="prism-btn-ghost pt-bar-btn" data-seats-clear>
                <span aria-hidden="true">×</span> تراجع
            </button>
            <button type="button" class="prism-btn-emerald pt-bar-btn" data-seats-save>
                <span aria-hidden="true">✔</span>
                <span>حفظ التغييرات (<span data-pending-count>0</span>)</span>
            </button>
        </div>
    </div>
</div>

{{-- ===================== Premium center-screen toast ===================== --}}
<div class="pt-toast-overlay" data-pt-toast role="status" aria-live="polite" hidden>
    <div class="pt-toast-card" data-pt-toast-card>
        <div class="pt-toast-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path data-pt-toast-path d="M5 12.5 L10 17.5 L19 7"/>
            </svg>
        </div>
        <div class="pt-toast-title" data-pt-toast-title>تم حفظ التغييرات</div>
        <div class="pt-toast-msg"   data-pt-toast-msg></div>
    </div>
</div>

<style>
    /* =================================================================
       Admin seat-management — premium picker. Visual-only; backend logic
       (per-seat toggle endpoint) is unchanged.
       ================================================================= */
    [data-admin-seat-root] {
        --p-cyan:    #22d3ee;
        --p-indigo:  #818cf8;
        --p-violet:  #c084fc;
        --p-gold:    #fbbf24;
        --p-emerald: #34d399;
        --p-rose:    #fb7185;
        --p-text:    #f1f5fb;
        --p-text-2:  #c2cad8;
        --p-text-3:  #8590a6;
        --p-border:  rgba(255,255,255,0.08);
        --p-border-strong: rgba(129,140,248,0.32);
        --p-ease: cubic-bezier(.2,.7,.2,1);
        display: block;
        max-width: 64rem;
        margin-inline: auto;
        padding-bottom: 96px; /* breathing room behind sticky dock */
    }

    /* ---------- Legend ---------- */
    [data-admin-seat-root] .legend-row {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: center; gap: 12px;
        font-size: 11px; color: var(--p-text-2);
    }
    [data-admin-seat-root] .legend-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px;
        border: 1px solid var(--p-border);
        border-radius: 999px;
        background: rgba(8,10,20,0.45);
    }
    [data-admin-seat-root] .legend-swatch {
        display: inline-block; width: 12px; height: 12px;
        border-radius: 3px; border: 1px solid rgba(255,255,255,0.08);
    }
    [data-admin-seat-root] .sw-available { background: linear-gradient(180deg,#3a4256,#1a1f2e); }
    [data-admin-seat-root] .sw-blocked   { background: linear-gradient(180deg,#fbbf24,#713f12); box-shadow: 0 0 6px rgba(251,191,36,0.45); }
    [data-admin-seat-root] .sw-pending   {
        background: linear-gradient(180deg,#6ee7b7,#047857);
        box-shadow: 0 0 6px rgba(16,185,129,0.55);
    }
    [data-admin-seat-root] .sw-booked    { background: linear-gradient(180deg,#fb7185,#7f1d1d); box-shadow: 0 0 6px rgba(251,113,133,0.45); }

    /* ---------- Map wrap ---------- */
    [data-admin-seat-root] .seat-mapwrap {
        background:
            radial-gradient(ellipse 120% 60% at 50% -10%, rgba(34,211,238,0.10), transparent 60%),
            radial-gradient(ellipse 80% 50% at 50% 110%, rgba(192,132,252,0.10), transparent 60%),
            linear-gradient(180deg, rgba(13,16,28,0.55), rgba(5,6,13,0.85));
        border: 1px solid var(--p-border);
        border-radius: 22px;
        backdrop-filter: blur(18px) saturate(140%);
        -webkit-backdrop-filter: blur(18px) saturate(140%);
        padding: 14px;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.05),
            0 24px 48px -22px rgba(0,0,0,0.75);
    }
    [data-admin-seat-root] .seat-topbar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; margin-bottom: 10px;
    }
    [data-admin-seat-root] .seat-title {
        font-size: 13px; font-weight: 700;
        background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc);
        -webkit-background-clip: text; background-clip: text; color: transparent;
    }
    [data-admin-seat-root] .zoom-bar {
        display: inline-flex;
        border: 1px solid var(--p-border-strong);
        border-radius: 999px;
        overflow: hidden;
        background: rgba(8,10,20,0.65);
    }
    [data-admin-seat-root] .zoom-btn {
        appearance: none; border: 0; background: transparent;
        width: 36px; height: 36px;
        display: inline-flex; align-items: center; justify-content: center;
        color: #e0e7ff; font-weight: 700; font-size: 15px; cursor: pointer;
        transition: background .15s var(--p-ease), color .15s var(--p-ease);
    }
    [data-admin-seat-root] .zoom-btn:hover { background: rgba(129,140,248,0.16); color: #fff; }
    [data-admin-seat-root] .zoom-btn:active { transform: scale(0.95); }
    [data-admin-seat-root] .zoom-btn + .zoom-btn { border-right: 1px solid rgba(129,140,248,0.18); }

    /* ---------- Pan/zoom container ---------- */
    [data-admin-seat-root] .seat-scroller {
        position: relative;
        overflow: hidden;
        touch-action: none;
        -webkit-tap-highlight-color: transparent;
        user-select: none; -webkit-user-select: none;
        border-radius: 18px;
        background:
            radial-gradient(ellipse 90% 60% at 50% 0%, rgba(34,211,238,0.10), transparent 60%),
            radial-gradient(ellipse 60% 40% at 50% 110%, rgba(192,132,252,0.06), transparent 60%),
            linear-gradient(180deg, #06081a, #03050d);
        border: 1px solid var(--p-border);
        cursor: grab;
        height: clamp(360px, 60vh, 580px);
    }
    [data-admin-seat-root] .seat-scroller.is-gesturing { cursor: grabbing; }
    [data-admin-seat-root] .seat-scroller::before {
        content: ""; position: absolute; inset: 0;
        background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.05) 1px, transparent 0);
        background-size: 36px 36px;
        mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 0%, transparent 80%);
        -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 0%, transparent 80%);
        pointer-events: none; opacity: 0.55; z-index: 0;
    }
    [data-admin-seat-root] .seat-stage {
        position: absolute; left: 0; top: 0;
        transform-origin: 0 0; will-change: transform;
        padding: 18px 14px;
        z-index: 1;
        backface-visibility: hidden; -webkit-backface-visibility: hidden;
    }
    [data-admin-seat-root] .section-block { margin-bottom: 22px; }
    [data-admin-seat-root] .section-block:last-child { margin-bottom: 0; }
    [data-admin-seat-root] .section-title {
        font-size: 12px; font-weight: 700;
        background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc);
        -webkit-background-clip: text; background-clip: text; color: transparent;
        text-align: center; margin-bottom: 10px; letter-spacing: .14em;
    }
    [data-admin-seat-root] .row-line {
        display: flex; align-items: center; justify-content: center;
        gap: 10px; min-width: 720px; padding: 1px 0;
    }
    [data-admin-seat-root] .row-label {
        flex: 0 0 22px; text-align: center;
        font-size: 10px; color: var(--p-text-3);
        font-weight: 700;
    }
    [data-admin-seat-root] .side {
        display: inline-flex; align-items: center; gap: 3px; flex: 1 1 0;
    }
    [data-admin-seat-root] .side-left   { justify-content: flex-end;  }
    [data-admin-seat-root] .side-center { justify-content: center;    }
    [data-admin-seat-root] .side-right  { justify-content: flex-start; }

    /* ---------- Seat button ---------- */
    [data-admin-seat-root] .seat-btn {
        appearance: none; border: 1px solid rgba(180,200,230,0.18);
        width: 26px; height: 26px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 9px; font-weight: 700; color: rgba(255,255,255,0.85);
        border-radius: 7px;
        background: linear-gradient(180deg, #3a4256, #1a1f2e);
        cursor: pointer;
        transition:
            transform .14s var(--p-ease),
            box-shadow .18s var(--p-ease),
            background .18s var(--p-ease),
            border-color .18s var(--p-ease);
    }
    [data-admin-seat-root] .seat-btn .seat-num { line-height: 1; }
    [data-admin-seat-root] .seat-btn:not(:disabled):hover {
        transform: translateY(-1px);
        box-shadow: 0 0 12px rgba(129,140,248,0.55);
    }
    [data-admin-seat-root] .seat-btn:not(:disabled):active { transform: scale(0.94); }
    /* states */
    [data-admin-seat-root] .seat-btn[data-seat-state="blocked"] {
        background: linear-gradient(180deg, #fbbf24, #713f12);
        border-color: rgba(253,224,71,0.75);
        color: #fef3c7;
        box-shadow: 0 0 8px rgba(251,191,36,0.5);
    }
    [data-admin-seat-root] .seat-btn[data-seat-state="booked"] {
        background: linear-gradient(180deg, #fb7185, #7f1d1d);
        border-color: rgba(252,165,165,0.65);
        color: #fee2e2;
        cursor: not-allowed;
        opacity: 0.92;
    }
    /* pending change — emerald glow (matches customer's "selected" style
       so admins recognise the visual language) */
    [data-admin-seat-root] .seat-btn[data-pending="1"] {
        background: linear-gradient(180deg, #6ee7b7, #047857);
        border-color: rgba(209,250,229,1);
        color: #ecfdf5;
        box-shadow: 0 0 14px rgba(16,185,129,0.78);
        transform: translateY(-1px);
    }
    [data-admin-seat-root] .seat-help {
        margin-top: 10px; text-align: center;
        font-size: 11px; color: var(--p-text-3);
    }

    /* ---------- Onboarding hint ---------- */
    [data-admin-seat-root] .gesture-hint {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        pointer-events: none; opacity: 0;
        transition: opacity .35s var(--p-ease);
        z-index: 12;
    }
    [data-admin-seat-root] .gesture-hint.is-visible { opacity: 1; }
    [data-admin-seat-root] .gesture-hint.is-leaving { opacity: 0; }
    [data-admin-seat-root] .gesture-hint .hint-card {
        background: rgba(8,10,20,0.78);
        -webkit-backdrop-filter: blur(16px) saturate(160%);
        backdrop-filter: blur(16px) saturate(160%);
        border: 1px solid rgba(129,140,248,0.34);
        border-radius: 18px;
        padding: 14px 18px 12px;
        box-shadow:
            0 18px 40px -16px rgba(2,6,23,0.85),
            0 0 0 1px rgba(255,255,255,0.04) inset;
        text-align: center; max-width: 240px;
        animation: aHintIn .45s var(--p-ease) both;
    }
    [data-admin-seat-root] .gesture-hint.is-leaving .hint-card {
        animation: aHintOut .35s var(--p-ease) both;
    }
    @keyframes aHintIn {
        from { opacity: 0; transform: translateY(8px) scale(.97); }
        to   { opacity: 1; transform: translateY(0)  scale(1); }
    }
    @keyframes aHintOut {
        from { opacity: 1; transform: translateY(0)  scale(1); }
        to   { opacity: 0; transform: translateY(-6px) scale(.98); }
    }
    [data-admin-seat-root] .gesture-hint .hint-icon {
        width: 64px; height: 44px; margin: 0 auto 8px;
    }
    [data-admin-seat-root] .gesture-hint .hint-icon svg { width: 100%; height: 100%; overflow: visible; }
    [data-admin-seat-root] .gesture-hint .pinch-finger {
        fill: rgba(255,255,255,0.92);
        stroke: rgba(34,211,238,0.55);
        stroke-width: 1.4; transform-origin: center;
    }
    [data-admin-seat-root] .gesture-hint .pinch-finger.a {
        animation: aPinchA 1.8s ease-in-out infinite;
    }
    [data-admin-seat-root] .gesture-hint .pinch-finger.b {
        animation: aPinchB 1.8s ease-in-out infinite;
    }
    @keyframes aPinchA { 0%,100% { transform: translate(-2px,0); } 50% { transform: translate(-12px,0); } }
    @keyframes aPinchB { 0%,100% { transform: translate(2px,0);  } 50% { transform: translate(12px,0);  } }
    [data-admin-seat-root] .gesture-hint .hint-text {
        font-size: 13px; font-weight: 700;
        color: var(--p-text); line-height: 1.35;
    }
    [data-admin-seat-root] .gesture-hint .hint-sub {
        margin-top: 3px; font-size: 10.5px; font-weight: 600;
        color: var(--p-text-3); letter-spacing: .18em; text-transform: uppercase;
    }
    :root[data-pt-theme="light"] [data-admin-seat-root] .gesture-hint .hint-card {
        background: rgba(255,255,255,0.92);
        border-color: rgba(15,23,42,0.14);
        box-shadow:
            0 18px 40px -16px rgba(15,23,42,0.30),
            0 0 0 1px rgba(15,23,42,0.04) inset;
    }
    :root[data-pt-theme="light"] [data-admin-seat-root] .gesture-hint .pinch-finger {
        fill: rgba(15,23,42,0.82); stroke: rgba(99,102,241,0.55);
    }
    @media (min-width: 880px) {
        [data-admin-seat-root] .gesture-hint { display: none !important; }
    }
    @media (prefers-reduced-motion: reduce) {
        [data-admin-seat-root] .gesture-hint .pinch-finger.a,
        [data-admin-seat-root] .gesture-hint .pinch-finger.b { animation: none; }
        [data-admin-seat-root] .gesture-hint .hint-card,
        [data-admin-seat-root] .gesture-hint.is-leaving .hint-card { animation: none; }
    }

    /* ---------- Floating zoom FAB ---------- */
    [data-admin-seat-root] .canvas-fab {
        position: absolute;
        bottom: 14px; inset-inline-end: 14px;
        display: inline-flex; flex-direction: column; gap: 6px;
        z-index: 4; pointer-events: auto;
    }
    [data-admin-seat-root] .canvas-fab .fab-btn {
        width: 44px; height: 44px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 14px;
        background: linear-gradient(180deg, rgba(20,24,38,0.78), rgba(8,10,20,0.88));
        border: 1px solid var(--p-border-strong);
        color: #e0e7ff; font-weight: 700; font-size: 18px; cursor: pointer;
        backdrop-filter: blur(14px) saturate(160%);
        -webkit-backdrop-filter: blur(14px) saturate(160%);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.06),
            0 8px 22px -10px rgba(0,0,0,0.7),
            0 0 18px rgba(129,140,248,0.16);
        transition: transform .15s var(--p-ease), background .15s var(--p-ease), box-shadow .2s var(--p-ease);
    }
    [data-admin-seat-root] .canvas-fab .fab-btn:hover {
        background: linear-gradient(180deg, rgba(34,211,238,0.18), rgba(129,140,248,0.18));
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.08),
            0 10px 26px -10px rgba(0,0,0,0.7),
            0 0 26px rgba(129,140,248,0.32);
    }
    [data-admin-seat-root] .canvas-fab .fab-btn:active { transform: scale(0.92); }
    [data-admin-seat-root] .canvas-fab .fab-btn[data-zoom="0"] { font-size: 16px; }
    @media (prefers-reduced-motion: reduce) {
        [data-admin-seat-root] .canvas-fab .fab-btn { transition: none; }
    }
</style>

<script>
(function () {
    'use strict';

    const root = document.querySelector('[data-admin-seat-root]');
    if (!root) return;

    const scroller = root.querySelector('[data-seat-scroller]');
    const stage    = root.querySelector('[data-seat-stage]');
    const hint     = root.querySelector('[data-gesture-hint]');
    const bar      = document.getElementById('adminSeatsBar');
    const csrf     = root.dataset.csrf || '';
    const tplUrl   = root.dataset.toggleUrl || '';

    if (!scroller || !stage || !bar) return;

    /* =============== Pan / Zoom =============== */
    const MIN_SCALE = 0.55;
    const MAX_SCALE = 2.6;
    const FIT_PADDING = 16;

    const view = { scale: 1, tx: 0, ty: 0 };
    let baseFitScale = 1; // computed from stage natural size

    function applyTransform() {
        stage.style.transform = `translate3d(${view.tx}px, ${view.ty}px, 0) scale(${view.scale})`;
    }

    function clampPan() {
        const sw = scroller.clientWidth;
        const sh = scroller.clientHeight;
        const cw = stage.offsetWidth  * view.scale;
        const ch = stage.offsetHeight * view.scale;
        // Allow some overscroll past edges so the user always feels
        // there's slack; but constrain so the stage isn't lost off-screen.
        const slackX = Math.max(0, (cw - sw) / 2 + 40);
        const slackY = Math.max(0, (ch - sh) / 2 + 40);
        const cx = (sw - cw) / 2;
        const cy = (sh - ch) / 2;
        view.tx = Math.min(cx + slackX, Math.max(cx - slackX, view.tx));
        view.ty = Math.min(cy + slackY, Math.max(cy - slackY, view.ty));
    }

    function fitToScreen() {
        // Defer so layout is settled and offsetWidth/Height are correct.
        const sw = scroller.clientWidth  - FIT_PADDING * 2;
        const sh = scroller.clientHeight - FIT_PADDING * 2;
        const cw = stage.offsetWidth;
        const ch = stage.offsetHeight;
        if (cw === 0 || ch === 0) { return; }
        const fit = Math.min(sw / cw, sh / ch, 1);
        baseFitScale = Math.max(MIN_SCALE, fit);
        view.scale = baseFitScale;
        view.tx = (scroller.clientWidth  - cw * view.scale) / 2;
        view.ty = (scroller.clientHeight - ch * view.scale) / 2;
        applyTransform();
    }

    function setScale(next, anchor) {
        const ns = Math.max(MIN_SCALE, Math.min(MAX_SCALE, next));
        if (ns === view.scale) return;
        if (anchor) {
            // Keep anchor point stationary in screen space.
            const k = ns / view.scale;
            view.tx = anchor.x - (anchor.x - view.tx) * k;
            view.ty = anchor.y - (anchor.y - view.ty) * k;
        }
        view.scale = ns;
        clampPan();
        applyTransform();
    }

    /* =============== Pointer-driven gestures =============== */
    const pointers = new Map(); // pointerId → {x,y,startX,startY,wasDrag}
    let pinchPrevDist = 0;
    let pinchAnchor = null;
    let panStart = null;
    let dismissedHint = false;
    const DRAG_THRESHOLD = 6;

    function relPoint(e) {
        const r = scroller.getBoundingClientRect();
        return { x: e.clientX - r.left, y: e.clientY - r.top };
    }

    function onPointerDown(e) {
        if (e.target.closest('.canvas-fab') || e.target.closest('.zoom-bar')) return;
        scroller.setPointerCapture(e.pointerId);
        const p = relPoint(e);
        pointers.set(e.pointerId, { ...p, startX: e.clientX, startY: e.clientY, wasDrag: false });

        if (pointers.size === 1) {
            panStart = { tx: view.tx, ty: view.ty, x: p.x, y: p.y };
        } else if (pointers.size === 2) {
            const pts = [...pointers.values()];
            pinchPrevDist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            pinchAnchor = { x: (pts[0].x + pts[1].x) / 2, y: (pts[0].y + pts[1].y) / 2 };
            scroller.classList.add('is-gesturing');
        }
        dismissHint();
    }

    function onPointerMove(e) {
        const stored = pointers.get(e.pointerId);
        if (!stored) return;
        const p = relPoint(e);
        Object.assign(stored, p);
        const dx = e.clientX - stored.startX;
        const dy = e.clientY - stored.startY;
        if (Math.hypot(dx, dy) > DRAG_THRESHOLD) stored.wasDrag = true;

        if (pointers.size === 2) {
            const pts = [...pointers.values()];
            const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            if (pinchPrevDist > 0) {
                const factor = dist / pinchPrevDist;
                const mid = { x: (pts[0].x + pts[1].x) / 2, y: (pts[0].y + pts[1].y) / 2 };
                setScale(view.scale * factor, mid);
                pinchAnchor = mid;
            }
            pinchPrevDist = dist;
            return;
        }

        if (pointers.size === 1 && panStart) {
            view.tx = panStart.tx + (p.x - panStart.x);
            view.ty = panStart.ty + (p.y - panStart.y);
            if (stored.wasDrag) scroller.classList.add('is-gesturing');
            clampPan();
            applyTransform();
        }
    }

    function onPointerUp(e) {
        const stored = pointers.get(e.pointerId);
        try { scroller.releasePointerCapture(e.pointerId); } catch (_) {}
        pointers.delete(e.pointerId);
        if (pointers.size < 2) { pinchPrevDist = 0; pinchAnchor = null; }
        if (pointers.size === 0) { panStart = null; scroller.classList.remove('is-gesturing'); }

        if (stored && !stored.wasDrag) {
            // Treat as a tap on a seat.
            const target = e.target.closest('.seat-btn');
            if (target && !target.disabled) toggleSeat(target);
        }
    }

    scroller.addEventListener('pointerdown', onPointerDown);
    scroller.addEventListener('pointermove', onPointerMove);
    scroller.addEventListener('pointerup',     onPointerUp);
    scroller.addEventListener('pointercancel', onPointerUp);
    scroller.addEventListener('pointerleave',  onPointerUp);

    // Wheel zoom (ctrl/cmd + wheel = zoom; plain wheel = pan).
    scroller.addEventListener('wheel', function (e) {
        const p = relPoint(e);
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            const f = e.deltaY < 0 ? 1.12 : 1 / 1.12;
            setScale(view.scale * f, p);
        } else {
            e.preventDefault();
            view.tx -= e.deltaX;
            view.ty -= e.deltaY;
            clampPan();
            applyTransform();
        }
    }, { passive: false });

    // Double-tap to zoom in (or reset if already zoomed).
    let lastTapTs = 0;
    scroller.addEventListener('pointerup', function (e) {
        if (e.pointerType !== 'touch') return;
        const now = Date.now();
        if (now - lastTapTs < 280) {
            const p = relPoint(e);
            if (view.scale > baseFitScale + 0.05) setScale(baseFitScale, p);
            else setScale(view.scale * 1.6, p);
            lastTapTs = 0;
        } else {
            lastTapTs = now;
        }
    });

    // Buttons.
    function bindZoomButtons(scope) {
        scope.querySelectorAll('[data-zoom]').forEach(btn => {
            btn.addEventListener('click', function () {
                const dir = parseInt(btn.dataset.zoom, 10);
                if (dir === 0) { fitToScreen(); return; }
                const r = scroller.getBoundingClientRect();
                const anchor = { x: r.width / 2, y: r.height / 2 };
                setScale(view.scale * (dir > 0 ? 1.2 : 1 / 1.2), anchor);
            });
        });
    }
    bindZoomButtons(root);

    /* =============== Onboarding hint =============== */
    const HINT_KEY = 'admin_seat_hint_dismissed';
    function showHint() {
        if (!hint) return;
        if (window.matchMedia && window.matchMedia('(min-width: 880px)').matches) return;
        try { if (localStorage.getItem(HINT_KEY)) return; } catch (_) {}
        // Defer so it animates in after first paint.
        requestAnimationFrame(() => requestAnimationFrame(() => hint.classList.add('is-visible')));
    }
    function dismissHint() {
        if (dismissedHint || !hint) return;
        dismissedHint = true;
        hint.classList.add('is-leaving');
        try { localStorage.setItem(HINT_KEY, '1'); } catch (_) {}
        setTimeout(() => { hint && hint.parentNode && hint.parentNode.removeChild(hint); }, 380);
    }

    /* =============== Selection model =============== */
    /** Set<seat_id> of seats whose state will be toggled on save. */
    const pending = new Set();

    function seatBtnFor(id) {
        return root.querySelector(`.seat-btn[data-seat-id="${id}"]`);
    }

    function toggleSeat(btn) {
        if (!btn || btn.disabled) return;
        const id = btn.dataset.seatId;
        if (!id) return;
        if (pending.has(id)) {
            pending.delete(id);
            btn.removeAttribute('data-pending');
        } else {
            pending.add(id);
            btn.setAttribute('data-pending', '1');
        }
        renderBar();
    }

    function clearPending() {
        pending.forEach(id => {
            const b = seatBtnFor(id);
            if (b) b.removeAttribute('data-pending');
        });
        pending.clear();
        renderBar();
    }

    function renderBar() {
        const blockCount   = bar.querySelector('[data-block-count]');
        const unblockCount = bar.querySelector('[data-unblock-count]');
        const summary      = bar.querySelector('[data-seats-summary]');
        const total        = bar.querySelector('[data-pending-count]');
        let willBlock = 0, willUnblock = 0;
        const labels = [];
        pending.forEach(id => {
            const b = seatBtnFor(id);
            if (!b) return;
            const state = b.getAttribute('data-seat-state');
            if (state === 'blocked') willUnblock += 1;
            else willBlock += 1;
            labels.push(b.dataset.seatLabel || '');
        });
        if (blockCount)   blockCount.textContent   = willBlock;
        if (unblockCount) unblockCount.textContent = willUnblock;
        if (total)        total.textContent        = pending.size;
        if (summary) {
            if (pending.size === 0) {
                summary.textContent = 'اختر مقعدًا أو أكثر للحجب أو التفعيل.';
            } else {
                const preview = labels.slice(0, 6).join('، ');
                summary.textContent = labels.length > 6
                    ? `${preview} و${labels.length - 6} مقعد آخر`
                    : preview;
            }
        }
        bar.classList.toggle('is-on', pending.size > 0);
    }

    bar.querySelector('[data-seats-clear]').addEventListener('click', clearPending);
    bar.querySelector('[data-seats-save]') .addEventListener('click', saveChanges);

    /* =============== Save (fan-out POSTs to existing endpoint) =============== */
    let saving = false;
    async function saveChanges() {
        if (saving) return;
        if (pending.size === 0) return;
        saving = true;
        const saveBtn = bar.querySelector('[data-seats-save]');
        const cancelBtn = bar.querySelector('[data-seats-clear]');
        if (saveBtn)   saveBtn.disabled   = true;
        if (cancelBtn) cancelBtn.disabled = true;

        const ids = [...pending];
        let okCount = 0, failCount = 0;
        for (const id of ids) {
            try {
                const url = tplUrl.replace('__SEAT__', encodeURIComponent(id));
                const fd = new FormData();
                fd.append('_token', csrf);
                const resp = await fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                });
                if (resp.ok || resp.redirected) okCount += 1;
                else failCount += 1;
            } catch (_) {
                failCount += 1;
            }
        }

        showToast({
            error: failCount > 0,
            title: failCount === 0
                ? 'تم تحديث المقاعد'
                : (okCount === 0 ? 'تعذّر تحديث المقاعد' : 'تم بعض التحديثات'),
            body: failCount === 0
                ? `تم تطبيق ${okCount} تغيير${okCount === 1 ? '' : 'ات'}`
                : `${okCount} ناجح · ${failCount} فاشل`,
        });

        // Reload to pick up the fresh seat blocks. Slight delay so the
        // toast is visible long enough to read.
        setTimeout(() => { window.location.reload(); }, 1100);
    }

    /* =============== Toast =============== */
    const toastEl = document.querySelector('[data-pt-toast]');
    if (toastEl && toastEl.parentNode !== document.body) {
        document.body.appendChild(toastEl);
    }
    function showToast(opts) {
        if (!toastEl) return;
        const card  = toastEl.querySelector('[data-pt-toast-card]');
        const path  = toastEl.querySelector('[data-pt-toast-path]');
        const title = toastEl.querySelector('[data-pt-toast-title]');
        const msg   = toastEl.querySelector('[data-pt-toast-msg]');
        const PATH_OK   = 'M5 12.5 L10 17.5 L19 7';
        const PATH_FAIL = 'M6 6 L18 18 M18 6 L6 18';
        const isError = !!(opts && opts.error);
        if (card)  card.classList.toggle('is-error', isError);
        if (path)  path.setAttribute('d', isError ? PATH_FAIL : PATH_OK);
        if (title) title.textContent = (opts && opts.title) || (isError ? 'تعذّر الحفظ' : 'تم حفظ التغييرات');
        if (msg) {
            msg.textContent = (opts && opts.body) || '';
            msg.style.display = msg.textContent ? '' : 'none';
        }
        toastEl.hidden = false;
        const svgPath = card && card.querySelector('svg path');
        if (svgPath) {
            svgPath.style.animation = 'none';
            void svgPath.offsetWidth;
            svgPath.style.animation = '';
        }
        requestAnimationFrame(() => requestAnimationFrame(() => toastEl.classList.add('is-on')));
    }

    /* =============== Initial layout =============== */
    function init() {
        fitToScreen();
        showHint();
        renderBar();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    window.addEventListener('resize', fitToScreen);
})();
</script>
@endsection
