@extends('layouts.app')

@section('title', 'إكمال الحجز · ' . $showTime->show->title)

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

<section class="max-w-3xl mx-auto prism-fade-up"
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
        [data-anba-form] .total-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 14px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(251,191,36,0.10), rgba(251,191,36,0.04));
            border: 1px solid rgba(251,191,36,0.32);
            color: #fef3c7;
        }
        [data-anba-form] .total-bar .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .18em;
            color: rgba(254,243,199,0.75);
        }
        [data-anba-form] .total-bar .amount {
            font-size: 18px; font-weight: 800;
            color: var(--prism-gold);
        }

        /* ===== Floating checkout dock (mobile + desktop) ===== */
        .anba-dock {
            position: fixed;
            left: 0; right: 0;
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
        /* spacer keeps the last form row above the dock at all viewports */
        [data-anba-form] .form-spacer-dock { height: 96px; }
        @media (min-width: 640px) {
            [data-anba-form] .form-spacer-dock { height: 104px; }
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
    </style>

    <div class="space-y-5">

        {{-- step indicator --}}
        <div class="step-indicator">
            <span class="dot done">✓</span>
            <span>القسم</span>
            <span class="line"></span>
            <span class="dot done">✓</span>
            <span>المقعد</span>
            <span class="line"></span>
            <span class="dot cur">3</span>
            <span>التأكيد</span>
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
                    🎟️ {{ $unitPrice }} جنيه / مقعد · {{ $sectionParam === 'balcony' ? 'البلكون' : 'الصالة' }}
                </span>
            </div>
        </div>

        {{-- selected seats summary --}}
        <div class="prism-glass p-5 sm:p-6 space-y-3">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="prism-headline text-sm sm:text-base"
                    style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    المقاعد المختارة
                </h2>
                <a href="{{ route('bookings.seats', $showTime) }}?section={{ $sectionParam }}"
                   class="add-seats-btn prism-ripple">
                    <span>＋</span>
                    <span>إضافة / تعديل المقاعد</span>
                </a>
            </div>

            <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed">
                اضغط × على أي مقعد لإلغاء اختياره، أو اضغط "إضافة / تعديل المقاعد" للرجوع لخريطة المقاعد.
            </p>

            <div data-form-chips class="flex flex-wrap gap-1.5 min-h-[44px] p-2 rounded-xl bg-black/40 border border-[color:var(--prism-border)]">
                <span class="text-[11px] text-[color:var(--prism-text-4)]" data-empty-msg>جارٍ تحميل المقاعد المختارة...</span>
            </div>

            <div class="total-bar">
                <span class="label">الإجمالي</span>
                <span><span class="amount" data-form-total>0</span> <span class="text-[10px] text-[color:var(--prism-text-3)]">EGP</span></span>
            </div>
        </div>

        {{-- booking instructions --}}
        <div class="prism-glass p-5 sm:p-6 space-y-3">
            <h3 class="prism-headline text-sm flex items-center gap-2"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                📌 خطوات إكمال الحجز
            </h3>
            <ol class="step-list">
                <li>
                    حوّل قيمة الحجز
                    (<span class="text-[color:var(--prism-gold)] font-bold"><span data-form-total-inline>0</span> جنيه</span>)
                    على المحفظة أو InstaPay الموضحة بالأسفل.
                </li>
                <li>
                    التقط صورة (Screenshot) لإيصال التحويل وارفعها في الخانة المخصصة.
                </li>
                <li>
                    اكتب اسم ورقم واتساب لكل شخص بترتيب المقاعد المحجوزة.
                </li>
                <li>
                    اضغط <span class="text-[color:var(--prism-text)] font-bold">"تأكيد الحجز"</span> —
                    هنراجع الطلب ونرسل التذاكر على رقم الواتساب خلال
                    <span class="text-[color:var(--prism-text)] font-semibold">24 ساعة</span> كحد أقصى.
                </li>
            </ol>
        </div>

        {{-- transfer info --}}
        @if (!empty($transferWallet) || !empty($transferInsta))
            <div class="prism-glass p-5 sm:p-6 space-y-2">
                <h3 class="text-[12px] font-semibold flex items-center gap-2"
                    style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    💸 ادفع قيمة الحجز على
                </h3>
                @if (!empty($transferWallet))
                    <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-xl px-3 py-2.5">
                        <p class="text-[10px] text-[color:var(--prism-text-3)] mb-0.5">📱 محفظة</p>
                        <p class="text-sm font-bold text-[color:var(--prism-text)] tracking-wide" dir="ltr">{{ $transferWallet }}</p>
                    </div>
                @endif
                @if (!empty($transferInsta))
                    <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-xl px-3 py-2.5">
                        <p class="text-[10px] text-[color:var(--prism-text-3)] mb-0.5">⚡ InstaPay</p>
                        <p class="text-sm font-bold text-[color:var(--prism-text)] tracking-wide" dir="ltr">{{ $transferInsta }}</p>
                    </div>
                @endif
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
                  data-pt-confirm='{"tone":"warn","title":"تأكيد الحجز","body":"هتقدم طلب الحجز للمراجعة. لما يتأكد، هتوصلك التذكرة على واتساب.","okLabel":"تأكيد","cancelLabel":"إلغاء","okVariant":"emerald"}'>
                @csrf
                <input type="hidden" name="section" value="{{ $sectionParam }}">

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <h3 class="text-[13px] font-bold text-[color:var(--prism-text)] flex items-center gap-2">
                            <span aria-hidden="true">👥</span>
                            بيانات الحضور
                        </h3>
                        <span class="text-[10px] text-[color:var(--prism-text-3)]">اكتب اسم ورقم واتساب لكل مقعد</span>
                    </div>

                    {{-- attendee cards rendered into here from localStorage --}}
                    <div data-form-attendees class="space-y-3"></div>
                </div>

                <div class="space-y-2">
                    <label for="anbaScreenshotFinal" class="field-label" style="margin-bottom:0;">
                        <span class="flex items-center gap-2 text-[12px] font-semibold text-[color:var(--prism-text)]">
                            <span aria-hidden="true">📸</span>
                            إيصال التحويل
                        </span>
                        <span class="req">مطلوب</span>
                    </label>
                    <div data-screenshot-zone>
                        <input type="file"
                               name="payment_screenshot"
                               id="anbaScreenshotFinal"
                               accept="image/*"
                               class="w-full text-[12px] text-[color:var(--prism-text-2)]
                                      file:bg-white/[0.06] file:text-[color:var(--prism-text)]
                                      file:border file:border-[color:var(--prism-border)]
                                      file:rounded-full file:px-4 file:py-2 file:ml-3 file:cursor-pointer
                                      file:hover:bg-white/[0.10] file:transition">
                    </div>
                </div>
            </form>
        </div>

        <div class="form-spacer-dock"></div>
    </div>

    {{-- Floating checkout dock — single source of truth for confirm CTA --}}
    <div class="anba-dock" data-anba-dock role="region" aria-label="ملخص الحجز">
        <div class="anba-dock-inner">
            <div class="anba-dock-summary">
                <div class="anba-dock-eyebrow">الإجمالي</div>
                <div class="anba-dock-amount">
                    <span data-form-mobile-count>0</span> مقعد ·
                    <span class="gold"><span data-form-mobile-total>0</span> EGP</span>
                </div>
                <div class="anba-dock-hint" data-form-dock-hint>اكمل الحقول المطلوبة</div>
            </div>
            <button type="submit"
                    form="anbaFinalForm"
                    data-form-mobile-submit
                    class="prism-btn prism-ripple anba-dock-cta">
                تأكيد الحجز
                <span aria-hidden="true">✓</span>
            </button>
        </div>
    </div>
</section>

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
    const attendees   = root.querySelector('[data-form-attendees]');
    const screenshot  = root.querySelector('#anbaScreenshotFinal');
    const screenshotZone = root.querySelector('[data-screenshot-zone]');
    const form        = document.querySelector('#anbaFinalForm');
    const dock        = document.querySelector('[data-anba-dock]');

    // The page is wrapped in a `.prism-fade-up` reveal that applies a CSS
    // transform — and a transformed ancestor creates a containing block,
    // which traps `position: fixed` elements (the dock would only stay
    // visible while the form section was on screen). Portal the dock to
    // <body> on init so it pins to the viewport for the whole journey:
    // top of page through final upload.
    if (dock && dock.parentElement !== document.body) {
        document.body.appendChild(dock);
    }

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
    function renderChips() {
        chipsBox.innerHTML = '';
        if (seats.length === 0) {
            const span = document.createElement('span');
            span.className = 'text-[11px] text-[color:var(--prism-text-4)]';
            span.textContent = 'لم يعد هناك مقاعد مختارة';
            chipsBox.appendChild(span);
            return;
        }
        seats.forEach(s => {
            const chip = document.createElement('span');
            chip.className = 'seat-chip';
            chip.innerHTML = `
                <span>${escapeHtml(s.label)}</span>
                <button type="button" data-remove="${s.id}" aria-label="إلغاء ${escapeHtml(s.label)}">✕</button>
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
            wrap.innerHTML = `
                <div class="seat-pill">${escapeHtml(s.label)}</div>
                <div class="field-stack">
                    <input type="hidden" name="seat_ids[]" value="${s.id}">
                    <div>
                        <label for="${nameId}" class="field-label">
                            <span>الاسم</span>
                            <span class="req">مطلوب</span>
                        </label>
                        <input type="text"
                               id="${nameId}"
                               name="names[]"
                               placeholder="اسم الشخص ${i + 1}"
                               class="field-input"
                               autocomplete="name"
                               autocapitalize="words"
                               spellcheck="false"
                               value="${escapeAttr(cached[s.id]?.name || '')}">
                    </div>
                    <div>
                        <label for="${phoneId}" class="field-label">
                            <span>رقم واتساب</span>
                            <span class="req">مطلوب</span>
                        </label>
                        <input type="tel"
                               id="${phoneId}"
                               name="phones[]"
                               placeholder="01xxxxxxxxx"
                               class="field-input"
                               inputmode="tel"
                               autocomplete="tel"
                               dir="ltr"
                               value="${escapeAttr(cached[s.id]?.phone || '')}">
                    </div>
                </div>
            `;
            attendees.appendChild(wrap);
        });
    }

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
        if (mobileCount) mobileCount.textContent = t.count;
        if (mobileTotal) mobileTotal.textContent = totalStr;
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

    screenshot.addEventListener('change', () => {
        if (screenshotZone) screenshotZone.classList.remove('is-invalid');
        if (dock && dock.classList.contains('has-error')) {
            const stillBad = form.querySelector('.is-invalid');
            if (!stillBad) dock.classList.remove('has-error');
        }
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
            alert('❌ من فضلك اختر مقعد واحد على الأقل');
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
        const dockBtn = document.querySelector('[data-form-mobile-submit]');
        if (dockBtn) {
            dockBtn.disabled = true;
            dockBtn.innerText = 'جارِ الإرسال...';
        }
        // Selection successfully sent — clear so refresh / back doesn't
        // resurrect an old payload. (If the server returns validation
        // errors the user is bounced back to this same page; the form
        // is then driven by old() seat_ids[] hidden values, but we don't
        // currently re-store from server. The chips/attendees will be
        // empty in that edge case. To keep UX safe we DON'T clear if
        // the user navigates back without submitting.)
        try { localStorage.removeItem('booking_selection'); } catch (e) {}
    });

    // ----- init -----
    renderChips();
    renderAttendees();
    paintTotals();
})();
</script>

@endsection
