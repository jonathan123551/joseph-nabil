@extends('layouts.app')

@section('title', 'حجز تذاكر · ' . $showTime->show->title)
@section('headMeta')
    <meta name="pt-title-i18n" content="page_title_book_create" data-suffix="{{ $showTime->show->title }}">
@endsection

@section('content')

@if ($isAnbaRuweis ?? false)
{{-- =====================================================================
     STEP 1 — Section selection (Sala / Balcony)
     The user picks a section here, then is sent to the canvas seat picker
     page. Balcony is currently disabled (no balcony seats seeded yet).
===================================================================== --}}
<section class="max-w-3xl mx-auto prism-fade-up">

    {{-- Local tweaks (kept scoped to step 1 — picks up the global PRISM tokens).
         Premium card design: two-row internal layout (name+eyebrow row at top,
         price+CTA row at bottom) so the price is unmistakable and the call-to-
         action cue is always visible. Decorative gradient orb in the top-right
         adds cinematic depth without adding markup noise. */ --}}
    <style>
        .anba-step1 .section-btn {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
            padding: 22px 22px;
            border-radius: 22px;
            text-align: right;
            background:
                radial-gradient(120% 80% at 0% 0%, rgba(34,211,238,0.10) 0%, rgba(34,211,238,0) 55%),
                radial-gradient(120% 80% at 100% 100%, rgba(192,132,252,0.10) 0%, rgba(192,132,252,0) 55%),
                linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.72));
            border: 1px solid rgba(129,140,248,0.30);
            color: #f1f5fb;
            font-weight: 600;
            box-shadow:
                0 10px 32px -14px rgba(129,140,248,0.45),
                inset 0 1px 0 rgba(255,255,255,0.06);
            transition: transform .25s var(--prism-ease),
                        box-shadow .25s var(--prism-ease),
                        border-color .25s var(--prism-ease);
            position: relative;
            overflow: hidden;
            min-height: 132px;
        }
        @media (min-width: 640px) {
            .anba-step1 .section-btn { padding: 26px 28px; min-height: 144px; }
        }
        /* Decorative gradient orb — adds cinematic premium depth. */
        .anba-step1 .section-btn::before {
            content: "";
            position: absolute;
            top: -40%;
            right: -20%;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            background: radial-gradient(closest-side, rgba(192,132,252,0.18), rgba(192,132,252,0) 70%);
            filter: blur(2px);
            transition: transform .5s var(--prism-ease), opacity .5s var(--prism-ease);
            pointer-events: none;
        }
        .anba-step1 .section-btn::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(34,211,238,0) 0%, rgba(192,132,252,0.10) 50%, rgba(34,211,238,0) 100%);
            background-size: 240% 100%;
            background-position: 0% 0%;
            opacity: 0;
            transition: background-position .7s var(--prism-ease), opacity .25s var(--prism-ease);
            pointer-events: none;
        }
        .anba-step1 .section-btn:hover {
            transform: translateY(-2px);
            border-color: rgba(129,140,248,0.55);
            box-shadow:
                0 22px 44px -16px rgba(129,140,248,0.55),
                0 0 26px rgba(34,211,238,0.18),
                inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .anba-step1 .section-btn:hover::before { transform: translate(-15px, 15px) scale(1.12); }
        .anba-step1 .section-btn:hover::after  { opacity: 1; background-position: 100% 0%; }
        .anba-step1 .section-btn:focus-visible {
            outline: 2px solid rgba(192,132,252,0.65);
            outline-offset: 3px;
        }
        @media (prefers-reduced-motion: reduce) {
            .anba-step1 .section-btn,
            .anba-step1 .section-btn::before,
            .anba-step1 .section-btn::after { transition: none; }
            .anba-step1 .section-btn:hover { transform: none; }
            .anba-step1 .section-btn:hover::before { transform: none; }
        }

        .anba-step1 .section-btn .section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .anba-step1 .section-btn .section-name {
            display: block;
            font-size: 22px;
            line-height: 1.15;
            background: var(--prism-neon);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        @media (min-width: 640px) {
            .anba-step1 .section-btn .section-name { font-size: 26px; }
        }
        .anba-step1 .section-btn .section-eyebrow {
            display: block;
            font-size: 10px;
            margin-top: 4px;
            color: rgba(229, 231, 235, 0.55);
            font-weight: 500;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .anba-step1 .section-btn .section-tag {
            flex-shrink: 0;
            font-size: 10px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(129,140,248,0.14);
            border: 1px solid rgba(129,140,248,0.38);
            color: #c7d2fe;
            font-weight: 700;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .anba-step1 .section-btn .section-foot {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .anba-step1 .section-btn .section-price-block { line-height: 1.1; }
        .anba-step1 .section-btn .section-price {
            display: block;
            font-size: 28px;
            color: var(--prism-gold);
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        @media (min-width: 640px) {
            .anba-step1 .section-btn .section-price { font-size: 32px; }
        }
        .anba-step1 .section-btn .section-price-unit {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: rgba(229, 231, 235, 0.6);
            font-weight: 500;
        }
        .anba-step1 .section-btn .section-cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.22));
            border: 1px solid rgba(129,140,248,0.45);
            color: #e0e7ff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .02em;
            white-space: nowrap;
            transition: transform .25s var(--prism-ease), background .25s var(--prism-ease);
        }
        .anba-step1 .section-btn:hover .section-cta {
            background: linear-gradient(135deg, rgba(34,211,238,0.30), rgba(192,132,252,0.34));
            transform: translateX(-3px); /* RTL: slide left toward CTA */
        }
        .anba-step1 .section-btn .section-cta-arrow {
            font-size: 13px;
            line-height: 1;
            transition: transform .25s var(--prism-ease);
        }
        .anba-step1 .section-btn:hover .section-cta-arrow {
            transform: translateX(-2px); /* RTL */
        }
        @media (prefers-reduced-motion: reduce) {
            .anba-step1 .section-btn .section-cta,
            .anba-step1 .section-btn .section-cta-arrow { transition: none; }
            .anba-step1 .section-btn:hover .section-cta,
            .anba-step1 .section-btn:hover .section-cta-arrow { transform: none; }
        }

        /* Disabled / "soon" state — fully calm, no shimmer, no orb. */
        .anba-step1 .section-btn[disabled],
        .anba-step1 .section-btn.disabled {
            opacity: .55;
            cursor: not-allowed;
            background: linear-gradient(180deg, rgba(75,85,99,0.18), rgba(31,41,55,0.10));
            border-color: rgba(156,163,175,0.25);
            box-shadow: none;
        }
        .anba-step1 .section-btn[disabled]::before,
        .anba-step1 .section-btn.disabled::before,
        .anba-step1 .section-btn[disabled]::after,
        .anba-step1 .section-btn.disabled::after { display: none; }
        .anba-step1 .section-btn[disabled]:hover,
        .anba-step1 .section-btn.disabled:hover { transform: none; box-shadow: none; }
        .anba-step1 .section-btn .badge-soon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 999px;
            background: rgba(99,102,241,0.18);
            border: 1px solid rgba(99,102,241,0.45);
            color: #c7d2fe;
            letter-spacing: .04em;
        }

        /* Step indicator */
        .step-indicator {
            display: flex; align-items: center; gap: 10px;
            font-size: 11px; color: var(--prism-text-3);
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .step-indicator .dot {
            width: 22px; height: 22px;
            border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
            border: 1px solid rgba(129,140,248,0.6);
            color: #e0e7ff;
        }
        .step-indicator .line { flex: 1; height: 1px; background: linear-gradient(90deg, rgba(129,140,248,0.4), rgba(255,255,255,0.04)); }
        .step-indicator .dim {
            background: rgba(255,255,255,0.04);
            border-color: var(--prism-border);
            color: var(--prism-text-4);
        }

        /* "Payment instructions" accordion — collapsed by default so the
           wallet/InstaPay numbers don't dominate the page. <details> works
           without JS and is a11y-friendly. Same visual language as the
           Anba step-3 form so the booking flow feels consistent. */
        .pay-details {
            background: rgba(255,255,255,0.025);
            border: 1px solid var(--prism-border);
            border-radius: 16px;
            overflow: hidden;
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease);
        }
        .pay-details[open] {
            border-color: rgba(129,140,248,0.32);
            background: rgba(255,255,255,0.04);
        }
        .pay-details > summary {
            cursor: pointer;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            min-height: 56px;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .pay-details > summary::-webkit-details-marker { display: none; }
        .pay-details .pay-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
            border: 1px solid rgba(129,140,248,0.4);
            font-size: 16px;
            flex-shrink: 0;
        }
        .pay-details .pay-meta { flex: 1 1 auto; min-width: 0; line-height: 1.3; }
        .pay-details .pay-title {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--prism-text);
        }
        .pay-details .pay-sub {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: var(--prism-text-3);
        }
        .pay-details .pay-chev {
            font-size: 12px;
            color: var(--prism-text-3);
            transition: transform .25s var(--prism-ease);
        }
        .pay-details[open] .pay-chev { transform: rotate(180deg); }
        .pay-details .pay-body {
            padding: 0 16px 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        @media (prefers-reduced-motion: reduce) {
            .pay-details .pay-chev { transition: none; }
        }

        /* =====================================================================
           LIGHT THEME — Section picker, step indicator and payment accordion.
           Dark-only surfaces would look pasted-in on the cream background, so
           we rebuild them with light-tinted glass that still preserves the
           cinematic premium feel (gradient title, decorative orb, neon hover
           glow — just translated into a light-friendly palette).
        ===================================================================== */
        :root[data-pt-theme="light"] .anba-step1 .section-btn {
            background:
                radial-gradient(120% 80% at 0% 0%, rgba(8,145,178,0.10) 0%, rgba(8,145,178,0) 55%),
                radial-gradient(120% 80% at 100% 100%, rgba(124,58,237,0.10) 0%, rgba(124,58,237,0) 55%),
                linear-gradient(180deg, rgba(255,255,255,0.96), rgba(252,250,245,0.92));
            border: 1px solid rgba(15,23,42,0.14);
            color: #0f172a;
            box-shadow:
                0 12px 28px -16px rgba(15,23,42,0.22),
                0 2px 6px -2px rgba(15,23,42,0.08),
                inset 0 1px 0 rgba(255,255,255,0.9);
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn::before {
            background: radial-gradient(closest-side, rgba(124,58,237,0.18), rgba(124,58,237,0) 70%);
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn::after {
            background: linear-gradient(135deg, rgba(8,145,178,0) 0%, rgba(124,58,237,0.10) 50%, rgba(8,145,178,0) 100%);
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn:hover {
            border-color: rgba(79,70,229,0.45);
            box-shadow:
                0 24px 44px -18px rgba(79,70,229,0.32),
                0 0 22px rgba(8,145,178,0.12),
                inset 0 1px 0 rgba(255,255,255,0.9);
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn .section-eyebrow {
            color: rgba(15,23,42,0.55);
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn .section-tag {
            background: rgba(79,70,229,0.10);
            border-color: rgba(79,70,229,0.34);
            color: #4338ca;
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn .section-price {
            color: #b45309;
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn .section-price-unit {
            color: rgba(15,23,42,0.55);
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn .section-cta {
            background: linear-gradient(135deg, rgba(8,145,178,0.14), rgba(124,58,237,0.16));
            border-color: rgba(79,70,229,0.40);
            color: #312e81;
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn:hover .section-cta {
            background: linear-gradient(135deg, rgba(8,145,178,0.22), rgba(124,58,237,0.26));
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn[disabled],
        :root[data-pt-theme="light"] .anba-step1 .section-btn.disabled {
            background: linear-gradient(180deg, rgba(241,238,232,0.7), rgba(229,226,218,0.5));
            border-color: rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] .anba-step1 .section-btn .badge-soon {
            background: rgba(79,70,229,0.10);
            border-color: rgba(79,70,229,0.34);
            color: #4338ca;
        }
        /* Step indicator — dim dots need a visible border on cream */
        :root[data-pt-theme="light"] .step-indicator { color: var(--prism-text-3); }
        :root[data-pt-theme="light"] .step-indicator .dot {
            background: linear-gradient(135deg, rgba(8,145,178,0.16), rgba(124,58,237,0.18));
            border-color: rgba(79,70,229,0.55);
            color: #312e81;
        }
        :root[data-pt-theme="light"] .step-indicator .line {
            background: linear-gradient(90deg, rgba(79,70,229,0.45), rgba(15,23,42,0.06));
        }
        :root[data-pt-theme="light"] .step-indicator .dim {
            background: rgba(15,23,42,0.05);
            border-color: rgba(15,23,42,0.14);
            color: var(--prism-text-4);
        }
        /* Payment instructions accordion — invisible BG on cream otherwise */
        :root[data-pt-theme="light"] .pay-details {
            background: rgba(255,255,255,0.70);
            border-color: rgba(15,23,42,0.14);
        }
        :root[data-pt-theme="light"] .pay-details[open] {
            background: rgba(255,255,255,0.90);
            border-color: rgba(79,70,229,0.34);
        }
        :root[data-pt-theme="light"] .pay-details .pay-icon {
            background: linear-gradient(135deg, rgba(8,145,178,0.14), rgba(124,58,237,0.16));
            border-color: rgba(79,70,229,0.40);
        }
    </style>

    <div class="anba-step1 space-y-5">

        {{-- step indicator --}}
        <div class="step-indicator prism-fade-up">
            <span class="dot">1</span>
            <span data-i18n="step_section">القسم</span>
            <span class="line"></span>
            <span class="dot dim">2</span>
            <span data-i18n="step_seat">المقعد</span>
            <span class="line"></span>
            <span class="dot dim">3</span>
            <span data-i18n="step_confirm">التأكيد</span>
        </div>

        {{-- show header --}}
        <div class="prism-glass p-5 sm:p-6 space-y-2 text-center prism-fade-up" style="animation-delay:.05s;">
            <h1 class="prism-headline text-base sm:text-lg" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $showTime->show->title }}
            </h1>
            <div class="text-[12px] text-[color:var(--prism-text-3)] flex items-center justify-center gap-3 flex-wrap">
                <span class="prism-pill">{{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</span>
                <span class="prism-pill">{{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</span>
            </div>
        </div>

        {{-- section choice --}}
        <div class="prism-glass p-5 sm:p-6 space-y-4 prism-fade-up" style="animation-delay:.1s;">
            <div class="text-center">
                <h2 class="prism-headline text-sm sm:text-base" data-i18n="pick_section_title">اختار القسم</h2>
                <p class="text-[11px] text-[color:var(--prism-text-3)] mt-1" data-i18n="pick_section_sub">
                    حدد القسم اللي عايز تحجز فيه
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <a href="{{ route('bookings.seats', $showTime) }}?section=hall"
                   class="section-btn prism-ripple"
                   data-section="hall">
                    <span class="section-head">
                        <span>
                            <span class="section-name" data-i18n="section_hall">الصالة</span>
                            <span class="section-eyebrow" data-i18n="section_hall_en">HALL</span>
                        </span>
                        <span class="section-tag" data-i18n="section_tag_main">القسم الرئيسي</span>
                    </span>
                    <span class="section-foot">
                        <span class="section-price-block">
                            <span class="section-price">{{ $hallPrice }} <span class="text-[14px] sm:text-[16px] font-semibold opacity-80" data-i18n="shows_egp">جنيه</span></span>
                            <span class="section-price-unit" data-i18n="shows_per_seat">للمقعد الواحد</span>
                        </span>
                        <span class="section-cta">
                            <span data-i18n="section_pick_seats">اختار المقعد</span>
                            <span class="section-cta-arrow" aria-hidden="true">←</span>
                        </span>
                    </span>
                </a>

                @if ($balconyPrice > 0)
                    <a href="{{ route('bookings.seats', $showTime) }}?section=balcony"
                       class="section-btn prism-ripple"
                       data-section="balcony">
                        <span class="section-head">
                            <span>
                                <span class="section-name" data-i18n="section_balcony">البلكون</span>
                                <span class="section-eyebrow" data-i18n="section_balcony_en">BALCONY</span>
                            </span>
                            <span class="section-tag" data-i18n="section_tag_premium">مشاهدة بانورامية</span>
                        </span>
                        <span class="section-foot">
                            <span class="section-price-block">
                                <span class="section-price">{{ $balconyPrice }} <span class="text-[14px] sm:text-[16px] font-semibold opacity-80" data-i18n="shows_egp">جنيه</span></span>
                                <span class="section-price-unit" data-i18n="shows_per_seat">للمقعد الواحد</span>
                            </span>
                            <span class="section-cta">
                                <span data-i18n="section_pick_seats">اختار المقعد</span>
                                <span class="section-cta-arrow" aria-hidden="true">←</span>
                            </span>
                        </span>
                    </a>
                @else
                    <button type="button"
                            class="section-btn disabled"
                            disabled
                            aria-disabled="true"
                            data-section="balcony">
                        <span class="section-head">
                            <span>
                                <span class="section-name" data-i18n="section_balcony">البلكون</span>
                                <span class="section-eyebrow" data-i18n="section_balcony_en">BALCONY</span>
                            </span>
                            <span class="badge-soon" data-i18n="section_soon">قريبًا</span>
                        </span>
                        <span class="section-foot">
                            <span class="section-price-block">
                                <span class="section-price">—</span>
                                <span class="section-price-unit" data-i18n="section_unavailable">غير متاح حاليًا</span>
                            </span>
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>

</section>

{{-- Clear any stale selection from a previous booking attempt --}}
<script>
    try {
        const stored = JSON.parse(localStorage.getItem('booking_selection') || 'null');
        // Different show time → wipe; same show time → keep so back-button
        // navigation between steps preserves the user's selection.
        if (stored && stored.showTimeId !== {{ (int) $showTime->id }}) {
            localStorage.removeItem('booking_selection');
        }
    } catch (e) { /* ignore */ }
</script>

@else
{{-- =====================================================================
     Non-Anba-Ruweis shows: original single-page manual form (ticket count
     + names + phones + screenshot). Behaviour preserved exactly.
===================================================================== --}}
<section class="max-w-5xl mx-auto prism-fade-up">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- ======================
        | 🎭 DETAILS + PAYMENT
        ======================= --}}
        <div class="md:col-span-1 prism-glass prism-glow-border p-5 space-y-4">

            <h2 class="text-sm font-semibold prism-headline"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;"
                data-i18n="book_form_show_details">
                🎭 تفاصيل العرض
            </h2>

            <p class="text-sm text-[color:var(--prism-text)] font-medium">
                {{ $showTime->show->title }}
            </p>

            <div class="space-y-1.5 text-xs text-[color:var(--prism-text-2)]">
                <p class="flex items-center gap-2"><span>📅</span>{{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                <p class="flex items-center gap-2"><span>⏰</span>{{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                <p class="text-[color:var(--prism-gold)] font-semibold flex items-center gap-2">
                    <span>🎟️</span>{{ $showTime->ticket_price }} <span data-i18n="shows_egp">جنيه</span>
                </p>
            </div>

            {{-- payment instructions — collapsed accordion. Customer
                 expands to view wallet / InstaPay numbers. --}}
            <details class="pay-details">
                <summary>
                    <span class="pay-icon" aria-hidden="true">💳</span>
                    <span class="pay-meta">
                        <span class="pay-title" data-i18n="form_pay_title">تعليمات الدفع</span>
                        <span class="pay-sub">
                            <span data-i18n="form_pay_sub_a">حوّل</span>
                            <span class="text-[color:var(--prism-gold)] font-bold">{{ $showTime->ticket_price }} <span data-i18n="shows_egp">جنيه</span></span>
                            <span data-i18n="form_pay_sub_b">واضغط للعرض</span>
                        </span>
                    </span>
                    <span class="pay-chev" aria-hidden="true">▾</span>
                </summary>
                <div class="pay-body">
                    @if (!empty($transferWallet))
                        <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-xl p-2.5">
                            <p class="text-[10px] text-[color:var(--prism-text-3)]" data-i18n="pay_wallet">📱 محفظة</p>
                            <p class="text-sm font-bold text-[color:var(--prism-text)]" dir="ltr">{{ $transferWallet }}</p>
                        </div>
                    @endif
                    @if (!empty($transferInsta))
                        <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-xl p-2.5">
                            <p class="text-[10px] text-[color:var(--prism-text-3)]" data-i18n="pay_insta">⚡ InstaPay</p>
                            <p class="text-sm font-bold text-[color:var(--prism-text)]" dir="ltr">{{ $transferInsta }}</p>
                        </div>
                    @endif
                </div>
            </details>

        </div>

        {{-- ======================
        | 📝 FORM
        ======================= --}}
        <div class="md:col-span-2 prism-glass p-6 space-y-4">

            <h2 class="text-sm font-semibold"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;"
                data-i18n="book_step2_title">
                خطوة 2: ارفع Screenshot وكمّل البيانات
            </h2>

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
                  id="bookingForm"
                  class="space-y-4">
                @csrf

                {{-- 👥 عدد التذاكر --}}
                <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-2xl p-4 space-y-3">
                    <label class="text-xs font-semibold text-[color:var(--prism-text)]" data-i18n="book_tickets_count">
                        👥 عدد التذاكر
                    </label>

                    <div class="flex items-center gap-3">
                        <button type="button" onclick="changeCount(-1)"
                                class="w-10 h-10 rounded-full bg-white/[0.06] border border-[color:var(--prism-border)] hover:bg-white/[0.10] transition flex items-center justify-center text-lg leading-none">−</button>

                        <span id="ticketsCount" class="text-[color:var(--prism-text)] font-bold text-lg min-w-[2ch] text-center">1</span>

                        <button type="button" onclick="changeCount(1)"
                                class="w-10 h-10 rounded-full bg-white/[0.06] border border-[color:var(--prism-border)] hover:bg-white/[0.10] transition flex items-center justify-center text-lg leading-none">+</button>
                    </div>

                    <input type="hidden" name="tickets_count" id="tickets_count" value="1">
                </div>

                {{-- 👤 الأشخاص --}}
                <div id="namesContainer" class="space-y-3"></div>

                {{-- Screenshot --}}
                <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-2xl p-4 space-y-2">
                    <label class="text-xs font-semibold text-[color:var(--prism-text)]" data-i18n="book_screenshot_legacy">
                        📸 Screenshot التحويل
                    </label>

                    <input type="file"
                           name="payment_screenshot"
                           id="screenshot"
                           accept="image/*"
                           class="w-full text-xs text-[color:var(--prism-text-2)]
                                  file:bg-white/[0.06] file:text-[color:var(--prism-text)]
                                  file:border file:border-[color:var(--prism-border)]
                                  file:rounded-full file:px-4 file:py-2 file:ml-3 file:cursor-pointer
                                  file:hover:bg-white/[0.10] file:transition">
                </div>

                <button type="submit"
                        id="submitBtn"
                        disabled
                        class="prism-btn prism-ripple w-full sm:w-auto">
                    <span data-i18n="book_send_request">إرسال طلب الحجز</span>
                    <span class="pt-arrow-rtl" aria-hidden="true">←</span>
                </button>
            </form>
        </div>

    </div>
</section>
@endif


{{-- ======================
| SCRIPT (manual flow only — Anba Ruweis ships its own script in the partial)
====================== --}}
@if(!($isAnbaRuweis ?? false))
<script>

let count = 1;
// `effectiveRemainingTickets()` already subtracts both customer bookings
// (pending + approved) AND any admin-blocked seats, so the stepper can
// never offer a count the seat picker / store would refuse.
const maxTickets = {{ (int) $showTime->effectiveRemainingTickets() }};

const namesContainer = document.getElementById('namesContainer');
const ticketsInput = document.getElementById('tickets_count');
const countDisplay = document.getElementById('ticketsCount');

function escapeAttr(s) {
    return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

// Tiny i18n shim — wraps the layout's `window.PT_T(key, vars)` and
// adds a fallback string so the placeholder renders cleanly before the
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

function renderNames() {
    namesContainer.innerHTML = '';

    for (let i = 1; i <= count; i++) {
        const wrap = document.createElement('div');
        wrap.className = 'space-y-2 bg-white/[0.04] border border-[color:var(--prism-border)] rounded-xl p-3';
        const namePh  = tt('book_form_name_ph', 'اسم الشخص {n}', { n: i });
        const phonePh = tt('book_form_phone_ph', 'رقم موبايل واتساب {n}', { n: i });
        wrap.innerHTML = `
            <input type="text"
                name="names[]"
                placeholder="${escapeAttr(namePh)}"
                class="prism-input"
                autocomplete="name"
                autocapitalize="words"
                spellcheck="false"
                enterkeyhint="next"
                required>

            <input type="tel"
                name="phones[]"
                placeholder="${escapeAttr(phonePh)}"
                class="prism-input"
                inputmode="tel"
                autocomplete="tel"
                dir="ltr"
                enterkeyhint="next"
                required>
        `;
        namesContainer.appendChild(wrap);
    }
}

// Re-render placeholders when the language toggle fires so AR/EN
// switch live — `applyLang()` in layouts/app.blade.php dispatches
// `pt:langchange` on `document` after rewriting the dictionary.
//
// Focus + caret position are captured before the rebuild and
// restored afterwards so toggling the language while typing on
// Android Chrome doesn't drop the input or collapse Gboard. The
// inputs don't have unique ids (they live by index inside
// `namesContainer`), so we identify the focused one by its
// position within the container's input list.
document.addEventListener('pt:langchange', () => {
    const active = document.activeElement;
    let focusIdx = -1, selStart = null, selEnd = null;
    if (active && namesContainer.contains(active) && active.matches('input')) {
        const all = Array.from(namesContainer.querySelectorAll('input'));
        focusIdx = all.indexOf(active);
        try { selStart = active.selectionStart; selEnd = active.selectionEnd; } catch (_) {}
    }
    renderNames();
    if (focusIdx >= 0) {
        const all = Array.from(namesContainer.querySelectorAll('input'));
        const next = all[focusIdx];
        if (next) {
            try { next.focus({ preventScroll: true }); } catch (_) { try { next.focus(); } catch (_) {} }
            if (selStart != null && selEnd != null) {
                try { next.setSelectionRange(selStart, selEnd); } catch (_) {}
            }
        }
    }
});

function changeCount(val) {
    count += val;

    if (count < 1) count = 1;

    if (count > maxTickets) {
        count = maxTickets;

        alert(tt('book_no_tickets_alert', '❌ لا يوجد تذاكر متاحة، المتاح: {n}', { n: maxTickets }));
    }

    countDisplay.innerText = count;
    ticketsInput.value = count;

    renderNames();
}

renderNames();


// ===== زرار الإرسال =====
const screenshotInput = document.getElementById('screenshot');
const submitBtn = document.getElementById('submitBtn');
const bookingForm = document.getElementById('bookingForm');

let screenshotReady = false;
let isSubmitting = false;

function updateButton() {
    submitBtn.disabled = !(screenshotReady && !isSubmitting);
}

screenshotInput.addEventListener('change', () => {
    screenshotReady = screenshotInput.files.length > 0;
    updateButton();
});

bookingForm.addEventListener('submit', function (e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }

    isSubmitting = true;
    submitBtn.disabled = true;
    // Inline spinner via the layout-wide `.is-loading` class. Avoids
    // overwriting innerText so the existing `data-i18n` span keeps
    // re-translating on language toggle.
    submitBtn.classList.add('is-loading');
    submitBtn.setAttribute('aria-busy', 'true');
});

// iOS / Safari back-forward cache restores the page with the submit
// button still disabled. Reset the state when the page comes out of
// the bfcache so the customer can retry / edit.
window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    isSubmitting = false;
    submitBtn.disabled = false;
    submitBtn.classList.remove('is-loading');
    submitBtn.removeAttribute('aria-busy');
});

</script>
@endif

@endsection
