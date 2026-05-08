{{--
    Premium cinema-style seat picker for مسرح الأنبا رويس — STEP 2 of the
    3-step booking flow. This partial only handles seat selection; the
    attendee form (names, phones, payment screenshot) lives on the next
    page (bookings.form / form.blade.php).

    On "Continue" the partial saves the selection to localStorage under
    `booking_selection` and redirects to the form page. The store contract
    is unchanged — the form page hydrates seat_ids[] from localStorage
    before posting to bookings.store.
--}}

@php
    $hallPriceInt    = (int) ($hallPrice ?? 0);
    $balconyPriceInt = (int) ($balconyPrice ?? 0);
    $sectionParam    = $section ?? 'hall';
    $unitPrice       = $sectionParam === 'balcony' ? $balconyPriceInt : $hallPriceInt;
    $hallSeats       = $seatsByRow['hall'] ?? [];
    $isFullscreen    = (bool) ($fullscreen ?? false);
    // Admin variant: same picker, same gestures, but the click semantics
    // toggle SeatBlock rows via a bulk endpoint instead of redirecting to
    // the customer form.
    $adminMode       = (bool) ($adminMode ?? false);
    $bulkToggleUrl   = $bulkToggleUrl ?? '';
    $adminBackUrl    = $adminBackUrl ?? '';
    // Produce a stable A→R order so the script doesn't have to sort.
    ksort($hallSeats);
@endphp

<div data-anba-root
     @if ($isFullscreen) data-fullscreen="1" @endif
     @if ($adminMode) data-admin-mode="1" @endif
     data-hall-price="{{ $unitPrice }}"
     data-section="{{ $sectionParam }}"
     data-show-time-id="{{ (int) $showTime->id }}"
     data-form-url="{{ $adminMode ? '' : (route('bookings.form', $showTime) . '?section=' . $sectionParam) }}"
     data-back-url="{{ $adminMode ? $adminBackUrl : route('bookings.create', $showTime) }}"
     data-bulk-toggle-url="{{ $bulkToggleUrl }}"
     data-csrf="{{ csrf_token() }}"
     data-unavailable='@json($unavailableSeats)'
     data-blocked='@json($blockedSeats ?? [])'>

    <style>
        /* =====================================================================
           PRISM seat picker — scoped to [data-anba-root]
           Visual styles only. No layout / geometry / JS logic was changed;
           computeLayout(), STEP, RIGHT_SHIFT_STEPS and the seat coordinate
           math are untouched.
           ===================================================================== */

        [data-anba-root] {
            --p-cyan:    #22d3ee;
            --p-indigo:  #818cf8;
            --p-violet:  #c084fc;
            --p-gold:    #fbbf24;
            --p-emerald: #34d399;
            --p-rose:    #fb7185;
            --p-text:    #f1f5fb;
            --p-text-2:  #c2cad8;
            --p-text-3:  #8590a6;
            --p-text-4:  #6b7385;
            --p-border:  rgba(255,255,255,0.08);
            --p-border-strong: rgba(129,140,248,0.32);
            --p-ease: cubic-bezier(.2,.7,.2,1);
        }

        [data-anba-root] .glass {
            background: linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.72));
            border: 1px solid var(--p-border);
            border-radius: 22px;
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.05),
                0 24px 48px -22px rgba(0,0,0,0.75);
        }
        [data-anba-root] .ambient {
            background:
                radial-gradient(ellipse 120% 60% at 50% -10%, rgba(34,211,238,0.10), transparent 60%),
                radial-gradient(ellipse 80% 50% at 50% 110%, rgba(192,132,252,0.10), transparent 60%),
                linear-gradient(180deg, rgba(13,16,28,0.55), rgba(5,6,13,0.85));
        }

        [data-anba-root] .zoom-bar {
            display: inline-flex;
            border: 1px solid var(--p-border-strong);
            border-radius: 999px;
            overflow: hidden;
            background: rgba(8,10,20,0.65);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 14px -6px rgba(129,140,248,0.4);
        }
        [data-anba-root] .zoom-btn {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #e0e7ff; font-weight: 700; font-size: 15px;
            transition: background .15s var(--p-ease), color .15s var(--p-ease);
        }
        [data-anba-root] .zoom-btn:hover { background: rgba(129,140,248,0.16); color: #fff; }
        [data-anba-root] .zoom-btn:active { transform: scale(0.95); }
        [data-anba-root] .zoom-btn + .zoom-btn { border-right: 1px solid rgba(129,140,248,0.18); }

        /* ===== Canvas wrapper =====
           Single GPU-composited transform layer. JS owns all gestures via
           Pointer Events (touch-action: none). The canvas is absolutely
           positioned inside the scroller and panned/zoomed via
           `transform: translate3d() scale()` only — no width/height mutation,
           no per-frame reflow. */
        [data-anba-root] .canvas-scroller {
            position: relative;
            overflow: hidden;
            touch-action: none;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            -webkit-user-select: none;
            border-radius: 18px;
            background:
                radial-gradient(ellipse 90% 60% at 50% 0%, rgba(34,211,238,0.10), transparent 60%),
                radial-gradient(ellipse 60% 40% at 50% 110%, rgba(192,132,252,0.06), transparent 60%),
                linear-gradient(180deg, #06081a, #03050d);
            border: 1px solid var(--p-border);
            cursor: grab;
        }
        [data-anba-root] .canvas-scroller.is-gesturing { cursor: grabbing; }
        [data-anba-root] .canvas-scroller::before {
            /* very subtle starfield dots */
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.05) 1px, transparent 0);
            background-size: 36px 36px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 0%, transparent 80%);
            -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 0%, transparent 80%);
            pointer-events: none;
            opacity: 0.55;
            z-index: 0;
        }

        [data-anba-root] canvas.seat-canvas {
            display: block;
            cursor: pointer;
            margin: 0;
            user-select: none;
            position: absolute;
            left: 0; top: 0;
            transform-origin: 0 0;
            will-change: transform;
            z-index: 1;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        /* ===== Floating zoom FAB (fullscreen mode) ===== */
        [data-anba-root] .canvas-fab {
            position: absolute;
            bottom: 14px;
            inset-inline-end: 14px;
            display: inline-flex;
            flex-direction: column;
            gap: 6px;
            z-index: 4;
            pointer-events: auto;
            opacity: 0;
            transform: translateY(8px) scale(.96);
            transition: opacity .25s var(--p-ease), transform .25s var(--p-ease);
        }
        [data-anba-root] .canvas-scroller .canvas-fab {
            /* fade in once the canvas is visible */
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        [data-anba-root] .canvas-fab .fab-btn {
            width: 44px; height: 44px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(20,24,38,0.78), rgba(8,10,20,0.88));
            border: 1px solid var(--p-border-strong);
            color: #e0e7ff;
            font-weight: 700; font-size: 18px;
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 8px 22px -10px rgba(0,0,0,0.7),
                0 0 18px rgba(129,140,248,0.16);
            transition: transform .15s var(--p-ease), background .15s var(--p-ease), box-shadow .2s var(--p-ease);
        }
        [data-anba-root] .canvas-fab .fab-btn:hover {
            background: linear-gradient(180deg, rgba(34,211,238,0.18), rgba(129,140,248,0.18));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.08),
                0 10px 26px -10px rgba(0,0,0,0.7),
                0 0 26px rgba(129,140,248,0.32);
        }
        [data-anba-root] .canvas-fab .fab-btn:active { transform: scale(0.92); }
        [data-anba-root] .canvas-fab .fab-btn[data-zoom="0"] {
            font-size: 16px;
        }
        @media (prefers-reduced-motion: reduce) {
            [data-anba-root] .canvas-fab,
            [data-anba-root] .canvas-fab .fab-btn { transition: none; }
        }

        /* ===== Pinch & pan onboarding hint (mobile only) =====
           Lightweight glass card centered over the seat map. Stays visible
           until the user actually interacts with the seat map (tap, pan,
           or pinch on the canvas), then fades out smoothly. Hidden on
           desktop via media query. Always `pointer-events: none` so the
           underlying gesture passes straight through. Animated icon
           respects prefers-reduced-motion. */
        [data-anba-root] .gesture-hint {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Always passthrough — the hint must never block the underlying
               canvas gestures. Touches on the seat map go straight to the
               canvas, and the hint dismisses itself based on those touches. */
            pointer-events: none;
            opacity: 0;
            transition: opacity .35s var(--p-ease);
            z-index: 12;
        }
        [data-anba-root] .gesture-hint.is-visible {
            opacity: 1;
        }
        [data-anba-root] .gesture-hint.is-leaving {
            opacity: 0;
        }
        [data-anba-root] .gesture-hint .hint-card {
            background: rgba(8,10,20,0.78);
            -webkit-backdrop-filter: blur(16px) saturate(160%);
            backdrop-filter: blur(16px) saturate(160%);
            border: 1px solid rgba(129,140,248,0.34);
            border-radius: 18px;
            padding: 14px 18px 12px;
            box-shadow:
                0 18px 40px -16px rgba(2,6,23,0.85),
                0 0 0 1px rgba(255,255,255,0.04) inset;
            text-align: center;
            max-width: 240px;
            animation: hintCardIn .45s var(--p-ease) both;
        }
        [data-anba-root] .gesture-hint.is-leaving .hint-card {
            animation: hintCardOut .35s var(--p-ease) both;
        }
        @keyframes hintCardIn {
            from { opacity: 0; transform: translateY(8px) scale(.97); }
            to   { opacity: 1; transform: translateY(0)  scale(1); }
        }
        @keyframes hintCardOut {
            from { opacity: 1; transform: translateY(0)  scale(1); }
            to   { opacity: 0; transform: translateY(-6px) scale(.98); }
        }
        [data-anba-root] .gesture-hint .hint-icon {
            width: 64px;
            height: 44px;
            margin: 0 auto 8px;
        }
        [data-anba-root] .gesture-hint .hint-icon svg {
            width: 100%;
            height: 100%;
            overflow: visible;
        }
        [data-anba-root] .gesture-hint .pinch-finger {
            fill: rgba(255,255,255,0.92);
            stroke: rgba(34,211,238,0.55);
            stroke-width: 1.4;
            transform-origin: center;
        }
        [data-anba-root] .gesture-hint .pinch-finger.a {
            animation: pinchA 1.8s ease-in-out infinite;
        }
        [data-anba-root] .gesture-hint .pinch-finger.b {
            animation: pinchB 1.8s ease-in-out infinite;
        }
        @keyframes pinchA {
            0%, 100% { transform: translate(-2px, 0); }
            50%      { transform: translate(-12px, 0); }
        }
        @keyframes pinchB {
            0%, 100% { transform: translate(2px, 0); }
            50%      { transform: translate(12px, 0); }
        }
        [data-anba-root] .gesture-hint .hint-text {
            font-size: 13px;
            font-weight: 700;
            color: var(--p-text);
            line-height: 1.35;
        }
        [data-anba-root] .gesture-hint .hint-sub {
            margin-top: 3px;
            font-size: 10.5px;
            font-weight: 600;
            color: var(--p-text-3);
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        :root[data-pt-theme="light"] [data-anba-root] .gesture-hint .hint-card {
            background: rgba(255,255,255,0.92);
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                0 18px 40px -16px rgba(15,23,42,0.30),
                0 0 0 1px rgba(15,23,42,0.04) inset;
        }
        :root[data-pt-theme="light"] [data-anba-root] .gesture-hint .pinch-finger {
            fill: rgba(15,23,42,0.82);
            stroke: rgba(99,102,241,0.55);
        }
        @media (min-width: 880px) {
            [data-anba-root] .gesture-hint { display: none !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            [data-anba-root] .gesture-hint .pinch-finger.a,
            [data-anba-root] .gesture-hint .pinch-finger.b { animation: none; }
            [data-anba-root] .gesture-hint .hint-card,
            [data-anba-root] .gesture-hint.is-leaving .hint-card { animation: none; }
        }

        /* ===== Side panel ===== */
        [data-anba-root] .seat-chip {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 6px 3px 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(16,185,129,0.22), rgba(34,211,238,0.16));
            border: 1px solid rgba(52,211,153,0.55);
            color: #d1fae5;
            font-size: 11px; font-weight: 700;
            box-shadow: 0 0 12px rgba(16,185,129,0.28), inset 0 1px 0 rgba(255,255,255,0.06);
            animation: prismFadeUp .25s var(--p-ease) both;
        }
        [data-anba-root] .seat-chip [data-remove] {
            display: inline-flex; align-items: center; justify-content: center;
            width: 16px; height: 16px;
            border-radius: 999px;
            background: rgba(2,6,23,0.5);
            color: #fee2e2;
            font-size: 10px; font-weight: 700;
            transition: background .15s var(--p-ease), transform .15s var(--p-ease);
            cursor: pointer;
        }
        [data-anba-root] .seat-chip [data-remove]:hover {
            background: rgba(244,63,94,0.6);
            transform: scale(1.08);
        }

        @keyframes prismFadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes prismGlowPulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(129,140,248,0.0); }
            50%     { box-shadow: 0 0 28px 0 rgba(129,140,248,0.45); }
        }

        [data-anba-root] .attendee-card {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 8px;
            padding: 8px;
            border-radius: 14px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--p-border);
        }
        [data-anba-root] .attendee-card .seat-pill {
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(180deg, rgba(16,185,129,0.30), rgba(16,185,129,0.15));
            border: 1px solid rgba(52,211,153,0.6);
            color: #ecfdf5;
            font-weight: 800; font-size: 12px;
            border-radius: 12px;
            box-shadow: 0 0 8px rgba(16,185,129,0.30), inset 0 1px 0 rgba(255,255,255,0.06);
        }
        [data-anba-root] .field-input {
            width: 100%;
            background: rgba(8,10,20,0.65);
            border: 1px solid var(--p-border);
            color: var(--p-text);
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 12px;
            transition: border-color .18s var(--p-ease), background .18s var(--p-ease), box-shadow .18s var(--p-ease);
        }
        [data-anba-root] .field-input:focus {
            border-color: rgba(129,140,248,0.6);
            outline: none;
            background: rgba(8,10,20,0.85);
            box-shadow: 0 0 0 3px rgba(129,140,248,0.10);
        }

        [data-anba-root] .cta-primary {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #cffafe 0%, #c7d2fe 50%, #e9d5ff 100%);
            color: #0b0e1c;
            font-weight: 700;
            border-radius: 999px;
            padding: 12px 18px;
            min-height: 46px;
            transition: transform .2s var(--p-ease), box-shadow .2s var(--p-ease), filter .2s var(--p-ease);
            box-shadow:
                0 8px 24px -8px rgba(129,140,248,0.6),
                inset 0 1px 0 rgba(255,255,255,0.6);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.4);
        }
        [data-anba-root] .cta-primary::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 30%, rgba(255,255,255,0.4) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform .8s var(--p-ease);
        }
        [data-anba-root] .cta-primary:hover:not(:disabled)::before { transform: translateX(100%); }
        [data-anba-root] .cta-primary:disabled {
            opacity: .45;
            cursor: not-allowed;
            background: linear-gradient(180deg, rgba(148,163,184,0.4), rgba(100,116,139,0.4));
            box-shadow: none;
            color: rgba(255,255,255,0.5);
        }
        [data-anba-root] .cta-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow:
                0 14px 32px -8px rgba(129,140,248,0.85),
                0 0 24px rgba(34,211,238,0.35),
                inset 0 1px 0 rgba(255,255,255,0.6);
            filter: brightness(1.05);
        }
        [data-anba-root] .cta-primary:active:not(:disabled) { transform: translateY(0); }

        /* ===== Sticky mobile CTA =====
           Same visual language as the global .pt-action-bar — glass + neon
           top edge, springy entrance. Renders on mobile when the user has
           at least one seat selected (and always in fullscreen mode). */
        [data-anba-root] .mobile-cta {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 60;
            display: flex;
            padding: 12px 14px calc(12px + env(safe-area-inset-bottom)) 14px;
            backdrop-filter: blur(22px) saturate(180%);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            background: linear-gradient(180deg, rgba(8,10,20,0.86), rgba(5,6,13,0.96));
            border-top: 1px solid rgba(129,140,248,0.38);
            align-items: center;
            gap: 12px;
            transform: translateY(140%);
            opacity: 0;
            pointer-events: none;
            transition:
                transform .48s cubic-bezier(.2, 1.2, .2, 1),
                opacity   .32s var(--p-ease);
            will-change: transform;
        }
        [data-anba-root] .mobile-cta::before {
            content: "";
            position: absolute;
            top: 0; left: 14px; right: 14px;
            height: 1px;
            background: linear-gradient(90deg,
                rgba(34,211,238,0)   0%,
                rgba(34,211,238,0.7) 14%,
                rgba(129,140,248,0.85) 50%,
                rgba(192,132,252,0.7) 86%,
                rgba(192,132,252,0)  100%);
            pointer-events: none;
        }
        @media (min-width: 1024px) {
            /* desktop side-panel mode: don't render the floating bar at
               all — the side aside carries the CTA */
            [data-anba-root]:not([data-fullscreen="1"]) .mobile-cta { display: none; }
        }
        [data-anba-root].has-selection .mobile-cta,
        [data-anba-root][data-fullscreen="1"] .mobile-cta {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        @media (prefers-reduced-motion: reduce) {
            [data-anba-root] .mobile-cta { transition: opacity .2s linear; }
        }

        /* =========================================================
           Fullscreen mode (Step 2: dedicated seat-picker page).
           Layout fills the viewport with no scrolling; the canvas
           is auto-scaled by JS to fit. The side panel is hidden;
           a sticky bottom bar carries the count + total + CTA.
           ========================================================= */
        [data-anba-root][data-fullscreen="1"] {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            width: 100%;
            padding: 0;
            margin: 0;
        }
        [data-anba-root][data-fullscreen="1"] .fs-grid {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            gap: 0;
        }
        [data-anba-root][data-fullscreen="1"] .fs-aside { display: none; }

        [data-anba-root][data-fullscreen="1"] .fs-mapwrap {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            padding: 8px 8px 0 8px;
            border-radius: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
        }
        [data-anba-root][data-fullscreen="1"] .fs-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 4px 8px;
            font-size: 11px;
            color: var(--p-text-2);
        }
        [data-anba-root][data-fullscreen="1"] .fs-topbar .fs-back {
            display: inline-flex; align-items: center;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(8,10,20,0.65);
            border: 1px solid var(--p-border-strong);
            color: var(--p-text);
            font-weight: 600;
            font-size: 12px;
            transition: all .15s var(--p-ease);
            min-height: 36px;
        }
        [data-anba-root][data-fullscreen="1"] .fs-topbar .fs-back:hover {
            background: rgba(129,140,248,0.12);
            border-color: rgba(129,140,248,0.6);
            box-shadow: 0 0 14px rgba(129,140,248,0.25);
        }
        [data-anba-root][data-fullscreen="1"] .fs-topbar .fs-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            background: linear-gradient(135deg, #22d3ee, #818cf8, #c084fc);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
        }
        [data-anba-root][data-fullscreen="1"] .canvas-scroller {
            flex: 1 1 auto;
            min-height: 0;
            border-radius: 18px;
            border: 1px solid var(--p-border);
        }
        [data-anba-root][data-fullscreen="1"] canvas.seat-canvas {
            margin: 0;
        }
        /* hide the small "scroll hint" text + hover status under the map in
           fullscreen — it's noise on a small screen. */
        [data-anba-root][data-fullscreen="1"] .fs-mapwrap > p { display: none; }

        /* In fullscreen the bar is part of the flex stack (not floating
           above scrollable content), so override fixed-positioning. The
           neon top edge + glass bg from the base rule still apply. */
        [data-anba-root][data-fullscreen="1"] .mobile-cta {
            position: relative;
            inset: auto;
            z-index: 1;
            margin: 0;
            transform: none;
            opacity: 1;
            pointer-events: auto;
            padding: 12px 12px max(12px, env(safe-area-inset-bottom)) 12px;
        }

        /* legend swatches */
        [data-anba-root] .legend-swatch {
            width: 14px; height: 14px;
            border-radius: 4px;
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.18);
        }
        [data-anba-root] .legend-swatch.avail    { background: linear-gradient(180deg,#3a4256,#1a1f2e); }
        [data-anba-root] .legend-swatch.sel      { background: linear-gradient(180deg,#34d399,#047857); border-color: rgba(167,243,208,0.85); box-shadow: 0 0 8px rgba(16,185,129,0.6); }
        [data-anba-root] .legend-swatch.booked   { background: linear-gradient(180deg,#fb7185,#7f1d1d); border-color: rgba(251,113,133,0.65); }
        [data-anba-root] .legend-swatch.admin    { background: linear-gradient(180deg,#fbbf24,#713f12); border-color: rgba(253,224,71,0.65); }

        /* legend pill */
        [data-anba-root] .legend-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--p-border);
            color: var(--p-text-2);
            font-size: 11px;
        }
    </style>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr),360px] gap-5 fs-grid">

        {{-- ===================== SEAT MAP ===================== --}}
        <section class="glass ambient p-4 sm:p-6 fs-mapwrap">
            @if ($isFullscreen)
                {{-- compact top bar shown only in fullscreen mode --}}
                <div class="fs-topbar">
                    <a href="{{ $adminMode ? $adminBackUrl : route('bookings.create', $showTime) }}" class="fs-back">
                        <span aria-hidden="true">→</span>
                        رجوع
                    </a>
                    <span class="fs-title">
                        @if ($adminMode)
                            ◆ إدارة المقاعد · {{ $showTime->show->title ?? '' }}
                        @else
                            ◆ اختار مقعدك
                        @endif
                    </span>
                    <div class="zoom-bar">
                        <button type="button" class="zoom-btn" data-zoom="-1" aria-label="تصغير">−</button>
                        <button type="button" class="zoom-btn" data-zoom="0"  aria-label="إعادة">⟳</button>
                        <button type="button" class="zoom-btn" data-zoom="1"  aria-label="تكبير">+</button>
                    </div>
                </div>
            @else
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold"
                        style="background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        ◆ خريطة المقاعد · {{ $showTime->show->title ?? 'مسرح الأنبا رويس' }}
                    </h2>
                    <div class="zoom-bar">
                        <button type="button" class="zoom-btn" data-zoom="-1" aria-label="تصغير">−</button>
                        <button type="button" class="zoom-btn" data-zoom="0"  aria-label="إعادة">⟳</button>
                        <button type="button" class="zoom-btn" data-zoom="1"  aria-label="تكبير">+</button>
                    </div>
                </div>
            @endif

            <div class="canvas-scroller" data-canvas-scroller>
                <canvas class="seat-canvas" data-seat-canvas
                        width="1400" height="700"
                        role="img"
                        aria-label="خريطة مقاعد الصالة"></canvas>

                {{-- Pinch & pan onboarding hint. Mobile-only; shown once per
                     device (localStorage). JS toggles `.is-visible` on init
                     and removes the node after dismiss. --}}
                <div class="gesture-hint" data-anba-gesture-hint role="status" aria-live="polite">
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

                @if ($isFullscreen)
                    {{-- Floating zoom controls (fullscreen mobile primary path).
                         Glass + neon, sits above the sticky CTA. --}}
                    <div class="canvas-fab" aria-hidden="false">
                        <button type="button" class="fab-btn" data-zoom="1"  aria-label="تكبير">+</button>
                        <button type="button" class="fab-btn" data-zoom="0"  aria-label="احتواء">⤢</button>
                        <button type="button" class="fab-btn" data-zoom="-1" aria-label="تصغير">−</button>
                    </div>
                @endif
            </div>

            <p class="mt-3 text-center text-[11px] text-[color:var(--p-text-3)]">
                اسحب للتنقل · قرّب بإصبعين أو بضغطة مزدوجة · المقاعد ذات الـ✕ مخصصة للإدارة
            </p>

            {{-- live status (used by canvas tooltip on hover) --}}
            <p class="text-center mt-1 text-[12px] min-h-[18px]" data-hover-status
               style="color: #c2cad8; letter-spacing: .04em;"></p>
        </section>

        {{-- ===================== SIDE PANEL ===================== --}}
        <aside class="glass p-5 lg:sticky lg:top-4 self-start space-y-5 fs-aside">

            {{-- show details --}}
            <div class="space-y-1.5">
                <h3 class="text-sm font-bold flex items-center gap-2"
                    style="background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    ◆ {{ $showTime->show->title }}
                </h3>
                <div class="text-[11px] text-[color:var(--p-text-2)] space-y-0.5">
                    <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                    <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                    @if ($adminMode)
                        <p class="font-semibold" style="color: var(--p-gold);">🛠️ وضع الإدارة</p>
                    @else
                        <p class="font-semibold" style="color: var(--p-gold);">🎟️ {{ $hallPriceInt }} جنيه / مقعد</p>
                    @endif
                </div>
            </div>

            {{-- transfer instructions — customer flow only --}}
            @if (!$adminMode && (!empty($transferWallet) || !empty($transferInsta)))
                <div class="rounded-2xl p-3 space-y-2"
                     style="background: rgba(8,10,20,0.55); border: 1px solid rgba(129,140,248,0.18);">
                    <h4 class="text-[11px] font-semibold"
                        style="background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        خطوة 1 · حوّل قيمة الحجز
                    </h4>
                    @if (!empty($transferWallet))
                        <div class="bg-white/[0.04] border border-[color:var(--p-border)] rounded-xl px-3 py-2">
                            <p class="text-[9px] text-[color:var(--p-text-3)] mb-0.5">📱 محفظة</p>
                            <p class="text-xs font-bold text-[color:var(--p-text)]" dir="ltr">{{ $transferWallet }}</p>
                        </div>
                    @endif
                    @if (!empty($transferInsta))
                        <div class="bg-white/[0.04] border border-[color:var(--p-border)] rounded-xl px-3 py-2">
                            <p class="text-[9px] text-[color:var(--p-text-3)] mb-0.5">⚡ InstaPay</p>
                            <p class="text-xs font-bold text-[color:var(--p-text)]" dir="ltr">{{ $transferInsta }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <div>
                <h3 class="text-sm font-bold mb-2"
                    style="background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    @if ($adminMode) إدارة المقاعد @else اختار مقاعدك @endif
                </h3>
                <p class="text-[11px] text-[color:var(--p-text-3)] leading-relaxed">
                    @if ($adminMode)
                        اضغط على المقاعد لتحديدها — رمادي يصبح مذهَّباً (محجوب للإدارة) والعكس صحيح. المقاعد المحجوزة من العملاء (وردية) لا يمكن تعديلها.
                    @else
                        اضغط على أي مقعد رمادي لاختياره. المقاعد ذات العلامة ✕ مخصصة للإدارة ولا يمكن حجزها.
                    @endif
                </p>
            </div>

            {{-- legend --}}
            <div class="grid grid-cols-2 gap-2 text-[11px] text-[color:var(--p-text-2)]">
                <div class="legend-pill"><span class="legend-swatch avail"></span> متاح</div>
                <div class="legend-pill"><span class="legend-swatch sel"></span> مختار</div>
                <div class="legend-pill"><span class="legend-swatch booked"></span> محجوز</div>
                <div class="legend-pill"><span class="legend-swatch admin"></span> إدارة</div>
            </div>

            {{-- selection summary (chips + total) — read-only here; the
                 form on the next page is where attendees + payment go. --}}
            <div class="space-y-3">
                <div>
                    <div class="flex items-center justify-between text-[11px] text-[color:var(--p-text-3)] mb-1">
                        <span>المقاعد المختارة</span>
                        <span data-selected-count>0</span>
                    </div>
                    <div data-selected-chips class="flex flex-wrap gap-1.5 min-h-[36px] p-2 rounded-xl"
                         style="background: rgba(8,10,20,0.55); border: 1px solid var(--p-border);">
                        <span class="text-[11px] text-[color:var(--p-text-4)]" data-empty-msg>لم تختر أي مقعد بعد</span>
                    </div>
                </div>

                @unless ($adminMode)
                    <div class="flex items-center justify-between rounded-xl px-3 py-2"
                         style="background: linear-gradient(135deg, rgba(251,191,36,0.10), rgba(251,191,36,0.04));
                                border: 1px solid rgba(251,191,36,0.32); color: #fef3c7;">
                        <span class="text-[11px] uppercase" style="letter-spacing: .18em;">الإجمالي</span>
                        <span class="text-base font-bold" style="color: var(--p-gold);">
                            <span data-total-price>0</span> <span class="text-[10px] opacity-80">EGP</span>
                        </span>
                    </div>
                @endunless

                <button type="button"
                        id="anbaContinueBtn"
                        data-continue
                        disabled
                        class="cta-primary w-full">
                    @if ($adminMode)
                        <span data-cta-label>حفظ التغييرات</span>
                        <span aria-hidden="true">✔</span>
                    @else
                        إكمال الحجز
                        <span aria-hidden="true">←</span>
                    @endif
                </button>

                <a href="{{ $adminMode ? $adminBackUrl : route('bookings.create', $showTime) }}"
                   class="block text-center text-[11px] transition"
                   style="color: var(--p-text-3);"
                   onmouseover="this.style.color='var(--p-text)'"
                   onmouseout="this.style.color='var(--p-text-3)'">
                    @if ($adminMode)
                        → رجوع لإدارة العروض
                    @else
                        → الرجوع لاختيار القسم
                    @endif
                </a>
            </div>
        </aside>
    </div>

    {{-- mobile sticky CTA --}}
    <div class="mobile-cta">
        <div class="flex-1">
            <div class="text-[10px] text-[color:var(--p-text-3)]">
                @if ($adminMode) تغييرات معلَّقة @else المختار @endif
            </div>
            <div class="text-sm font-bold text-[color:var(--p-text)]">
                <span data-mobile-count>0</span> مقعد
                @unless ($adminMode)
                    ·
                    <span style="color: var(--p-gold);"><span data-mobile-total>0</span> EGP</span>
                @endunless
            </div>
        </div>
        <button type="button" data-continue
                disabled
                class="cta-primary px-5 py-2 text-xs">
            @if ($adminMode)
                <span data-cta-label>حفظ التغييرات</span>
            @else
                إكمال الحجز
            @endif
        </button>
    </div>

    <script type="application/json" data-seat-data>
        @php
            $payload = ['hall' => []];
            foreach ($hallSeats as $row => $sides) {
                $payload['hall'][$row] = [
                    'left'   => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['left']),
                    'center' => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['center']),
                    'right'  => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['right']),
                ];
            }
        @endphp
        {!! json_encode($payload, JSON_UNESCAPED_UNICODE) !!}
    </script>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-anba-root]');
        if (!root) return;

        // ----- data wiring -----
        // Seat picker is now step 2 of a 3-step flow. The selection is
        // persisted to localStorage on "Continue" and consumed by the
        // form page (step 3) which posts to bookings.store with the same
        // contract (seat_ids[], names[], phones[], section, screenshot).
        const seatData     = JSON.parse(root.querySelector('[data-seat-data]').textContent);
        const unavailable  = new Set((JSON.parse(root.dataset.unavailable || '[]') || []).map(Number));
        const blocked      = new Set((JSON.parse(root.dataset.blocked     || '[]') || []).map(Number));
        const hallPrice    = parseInt(root.dataset.hallPrice || '0', 10);
        const sectionParam = root.dataset.section || 'hall';
        const showTimeId   = parseInt(root.dataset.showTimeId || '0', 10);
        const formUrl      = root.dataset.formUrl || '';
        const isFullscreen = root.dataset.fullscreen === '1';
        const adminMode    = root.dataset.adminMode === '1';
        const bulkToggleUrl= root.dataset.bulkToggleUrl || '';
        const csrfToken    = root.dataset.csrf || '';

        const canvas       = root.querySelector('[data-seat-canvas]');
        const scroller     = root.querySelector('[data-canvas-scroller]');
        const ctx          = canvas.getContext('2d');
        const hoverStatus  = root.querySelector('[data-hover-status]');

        const chipsBox     = root.querySelector('[data-selected-chips]');
        const countEl      = root.querySelector('[data-selected-count]');
        const totalEl      = root.querySelector('[data-total-price]');
        const continueBtns = root.querySelectorAll('[data-continue]');
        const mobileCount  = root.querySelector('[data-mobile-count]');
        const mobileTotal  = root.querySelector('[data-mobile-total]');

        // map seatId -> { row, n, isAdminOnly? }
        const seatMeta  = new Map();
        const selected  = new Map();

        // ----- transform-based pan/zoom state -----
        // The canvas geometry (DISPLAY_W × DISPLAY_H) and pixel buffer are
        // fixed. We pan/zoom by mutating `canvas.style.transform` only —
        // single GPU compositor layer, zero per-frame reflow.
        let zoomLevel    = 1;
        let panX         = 0;
        let panY         = 0;

        // Selection pop animation: seatId → { startT } during the 220 ms
        // pop. Drained by an rAF loop that triggers requestRedraw() until
        // the map empties. Honours prefers-reduced-motion.
        const selectionAnim = new Map();
        const POP_DURATION  = 220;
        let popRAF          = 0;

        const reducedMotion =
            window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // ===== Geometry constants =====
        //
        // Real-theater stepped layout. The center column is rendered as a
        // straight vertical column, perfectly centered. Each row's left
        // and right wings translate as a SOLID BLOCK by a per-row offset:
        //
        //   LEFT  wing → x  -=  offset
        //   RIGHT wing → x  +=  offset
        //
        // The offset comes from RIGHT_SHIFT_STEPS[row] (in half-seat widths,
        // multiplied by STEP). Row Q is the anchor (offset = 0); front
        // rows step further out. No interpolation, no stagger, no bias —
        // every seat in a wing moves by exactly the same amount, so the
        // wing keeps its natural SEAT_PITCH spacing.
        //
        // To find where the math runs, search the file for:
        //   ===== WING OFFSET (LEFT)  =====
        //   ===== WING OFFSET (RIGHT) =====
        const ROWS_ORDER       = ['A','B','C','D','E','F','G','H','GAP','I','J','K','L','M','N','O','P','Q','R'];
        const SEAT_W           = 22;     // seat box width  (px)
        const SEAT_H           = 20;     // seat box height (px)
        const SEAT_GAP         = 5;      // horizontal gap between adjacent seats
        const ROW_GAP          = 10;     // vertical gap between rows
        const ROW_PITCH        = SEAT_H + ROW_GAP;  // distance between row centers
        const SEAT_PITCH       = SEAT_W + SEAT_GAP; // distance between seat centers
        const AISLE_GAP        = 32;     // horizontal gap between center and each wing
        const ROW_A_GAP        = 78;     // mid-gap when row has no center (e.g. row A)

        // ===== HALF-SEAT STEP =====
        // Base unit for RIGHT_SHIFT_STEPS. "1 unit" = half a seat's WIDTH
        // (the seat itself, not including the SEAT_GAP between seats).
        // Multiplying a row's table entry by STEP gives that row's full
        // wing-translation offset in pixels.
        const STEP             = SEAT_W / 2;

        // ===== WING OFFSET STEPS =====
        // Per-row offset at the OUTERMOST seat of each wing (in HALF-SEAT
        // widths). The same value is used for both left and right wings
        // so the layout is symmetric. Inner-edge seats keep their natural
        // SEAT_PITCH spacing; only the spread of the wing grows.
        //
        // Tweak any single row here without touching layout math:
        const RIGHT_SHIFT_STEPS = {
            // Back section — Q is the anchor (0 offset).
            Q: 0,    P: 1,    O: 2,    N: 3,
            // Mid-back — micro 0.2-step progression so rows don't look
            // perfectly stacked.
            M: 3.0,  L: 3.2,  K: 3.4,  J: 3.6,  I: 4,
            // Front section — full integer steps up to C.
            H: 5,    G: 6,    F: 7,    E: 8,    D: 9, C: 10,
            // Front-most rows — micro adjustment to keep B/A distinct.
            B: 10.2, A: 11.2,
            // Row R — no center column; falls through to the default
            // anchoring (centerStartX = CX, centerWidth = 0). +1 step
            // matches P's outward offset.
            R: 1,
        };

        const STAGE_H          = 70;
        const TOP_PAD          = 28;
        const ROW_AREA_TOP     = TOP_PAD + STAGE_H + 28;  // first row baseline (y of row A center)
        const SIDE_PAD         = 36;

        // Display size (CSS pixels). Will be scaled by devicePixelRatio internally.
        // Width is wide enough to fit the front rows' full progressive offset.
        let DISPLAY_W = 1400;
        let DISPLAY_H = 700;
        let CX        = DISPLAY_W / 2;

        // ===== State =====
        // Each seat: { id, label, row, n, x, y, angle, w, h, state, isAdminOnly }
        const SEATS = [];
        let hoverIdx = -1;

        // ===== Row metadata cache =====
        // Per-row geometry used by drawRowLabel() so it can place labels
        // next to the outermost seat without recomputing the layout.
        const ROW_META = new Map();
        // ===== Layout computation =====
        function computeLayout() {
            SEATS.length = 0;
            seatMeta.clear();
            ROW_META.clear();

            const rows = seatData.hall || {};

            let visualRow = 0;

            ROWS_ORDER.forEach((letter, idx) => {
                if (letter === 'GAP') {
                    visualRow += 1.5; // مسافة زيادة
                    return;
                }
                const data = rows[letter];
                if (!data) return;

                const cL = (data.left   || []).length;
                // Row Q's center seats are admin-only management block;
                // skip rendering them so Q is two wings only (similar to A).
                const cC = (letter === 'Q') ? 0 : (data.center || []).length;
                const cR = (data.right  || []).length;

                // Per-row outermost offset (px). Inner edge gets 0,
                // outer edge gets baseOffset; everything in between is
                // interpolated linearly.
                const step       = RIGHT_SHIFT_STEPS[letter] || 0;
                const baseOffset = step * STEP;

                const rowY = ROW_AREA_TOP + visualRow * ROW_PITCH;
                visualRow++;

                // Center anchor: perfectly centered on CX.
                const centerWidth   = cC > 0 ? cC * SEAT_PITCH - SEAT_GAP : 0;
                const centerStartX  = CX - centerWidth / 2;

                // Pick the gap between the two wings:
                //   - row A → anchor to row B (its toward-stage neighbor)
                //   - row Q → anchor to row P (toward-stage neighbor)
                //   - row R → anchor to row Q (toward-stage neighbor; Q's
                //             raw data still has 9 center seats, so the
                //             effective anchor matches P's wing position)
                //   - rows w/ center → 2 × AISLE_GAP around centerStartX
                const NO_CENTER_ANCHORS = { A: 'B', Q: 'P', R: 'Q' };
                let leftEndX;
                let rightStartX;
                if (NO_CENTER_ANCHORS[letter]) {
                    // No center column on this row; anchor wings to the
                    // toward-stage neighbor's center column so the inner
                    // edges line up vertically.
                    const neighbor      = rows[NO_CENTER_ANCHORS[letter]];
                    const nbrCount      = (neighbor?.center || []).length;
                    const nbrWidth      = nbrCount > 0
                        ? nbrCount * SEAT_PITCH - SEAT_GAP
                        : 0;
                    const nbrStartX     = CX - nbrWidth / 2;
                    leftEndX    = nbrStartX - AISLE_GAP;
                    rightStartX = nbrStartX + nbrWidth + AISLE_GAP;
                } else {
                    leftEndX    = centerStartX - AISLE_GAP;
                    rightStartX = centerStartX + centerWidth + AISLE_GAP;
                }

                // ===== WING OFFSET (LEFT) =====
                // Uniform per-row shift. The whole left wing translates
                // LEFT by exactly `baseOffset` pixels — every seat moves
                // by the same amount, so SEAT_PITCH spacing inside the
                // wing is preserved. data.left is OUTER → INNER.
                if (cL > 0) {
                    const leftWingWidth = cL * SEAT_PITCH - SEAT_GAP;
                    const leftBaseX     = leftEndX - leftWingWidth;
                    for (let i = 0; i < cL; i++) {
                        const x = leftBaseX
                                + i * SEAT_PITCH
                                + SEAT_W / 2
                                - baseOffset;
                        pushSeat(data.left[i], letter, x, rowY, false);
                    }
                }

                // ===== CENTER =====   straight column, never offset.
                // Row I center is the "خاص بالإدارة" block (admin-only with X).
                for (let i = 0; i < cC; i++) {
                    const x = centerStartX + i * SEAT_PITCH + SEAT_W / 2;
                    pushSeat(data.center[i], letter, x, rowY, false);
                }

                // ===== WING OFFSET (RIGHT) =====
                // Uniform per-row shift. The whole right wing translates
                // RIGHT by exactly `baseOffset` pixels — mirror of LEFT.
                // data.right is INNER → OUTER.
                if (cR > 0) {
                    for (let i = 0; i < cR; i++) {
                        const x = rightStartX
                                + i * SEAT_PITCH
                                + SEAT_W / 2
                                + baseOffset;
                        pushSeat(data.right[i], letter, x, rowY, false);
                    }
                }

                ROW_META.set(letter, {
                    idx,
                    rowY,
                    leftEndX,
                    rightStartX,
                    cL, cC, cR,
                    baseOffset,
                });
            });
        }

        function pushSeat(seatRef, letter, x, y, isAdminOnly) {
            const meta = {
                id: seatRef.id,
                n: seatRef.n,
                row: letter,
                label: letter + seatRef.n,
                x, y,
                angle: 0,            // axis-aligned — no rotation in linear layout
                w: SEAT_W,
                h: SEAT_H,
                isAdminOnly: !!isAdminOnly
            };
            SEATS.push(meta);
            seatMeta.set(seatRef.id, meta);
        }

        // ===== Canvas sizing =====
        function fitCanvas() {
            const dpr = window.devicePixelRatio || 1;
            canvas.style.width  = DISPLAY_W + 'px';
            canvas.style.height = DISPLAY_H + 'px';
            canvas.width  = Math.floor(DISPLAY_W * dpr);
            canvas.height = Math.floor(DISPLAY_H * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0); // resets and applies scale
        }

        // ===== Drawing =====
        function getState(seat) {
            if (selected.has(seat.id))               return 'selected';
            if (seat.isAdminOnly)                    return 'admin';
            // In admin mode `unavailable` carries customer-booked only and
            // `blocked` carries the toggleable admin blocks. Distinguish
            // them so blocked seats render gold and customer-booked seats
            // render rose (and stay non-toggleable).
            if (adminMode) {
                if (unavailable.has(seat.id))        return 'booked';
                if (blocked.has(seat.id))            return 'admin';
                return 'available';
            }
            if (unavailable.has(seat.id) && blocked.has(seat.id)) return 'admin';
            if (unavailable.has(seat.id))            return 'booked';
            return 'available';
        }

        // STATE_STYLES — visual only. Colors restyled to PRISM palette
        // (neon emerald for selected; cooler slate for available; rose for
        // booked; gold for admin). Shape and seat geometry are unchanged.
        const STATE_STYLES = {
            available: {
                fill: ['#3a4256', '#1a1f2e'],
                stroke: 'rgba(180,200,230,0.18)',
                text: 'rgba(255,255,255,0.85)',
                shadow: null
            },
            selected: {
                // Stronger emerald gradient — brighter top so the seat reads
                // as glowing rather than flat-painted.
                fill: ['#6ee7b7', '#047857'],
                stroke: 'rgba(209,250,229,1)',
                text: '#ecfdf5',
                shadow: { color: 'rgba(16,185,129,0.98)', blur: 22 }
            },
            booked: {
                fill: ['#fb7185', '#7f1d1d'],
                stroke: 'rgba(252,165,165,0.65)',
                text: '#fee2e2',
                shadow: null
            },
            admin: {
                fill: ['#fbbf24', '#713f12'],
                stroke: 'rgba(253,224,71,0.75)',
                text: '#fef3c7',
                shadow: null
            }
        };

        // drawStage — visual only. Geometry (w, x, y, h, arc curve) is
        // unchanged; only the colors / gradient stops use the PRISM neon
        // palette (cyan → indigo → violet) instead of amber.
        function drawStage() {
            const w = Math.min(DISPLAY_W * 0.55, 460);
            const x = (DISPLAY_W - w) / 2;
            const y = TOP_PAD;
            const h = STAGE_H - 8;

            // ambient halo below the arc — neon glow
            const halo = ctx.createRadialGradient(DISPLAY_W/2, y + h, 10, DISPLAY_W/2, y + h, w * 0.85);
            halo.addColorStop(0,   'rgba(129,140,248,0.40)');
            halo.addColorStop(0.5, 'rgba(34,211,238,0.18)');
            halo.addColorStop(1,   'rgba(129,140,248,0)');
            ctx.fillStyle = halo;
            ctx.fillRect(0, y + h - 10, DISPLAY_W, 90);

            // arc body — neon gradient fill
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(x, y + h);
            ctx.bezierCurveTo(x + w * 0.10, y - 6, x + w * 0.90, y - 6, x + w, y + h);
            ctx.closePath();
            const grad = ctx.createLinearGradient(x, y, x + w, y + h);
            grad.addColorStop(0,   'rgba(34,211,238,0.30)');
            grad.addColorStop(0.5, 'rgba(129,140,248,0.22)');
            grad.addColorStop(1,   'rgba(192,132,252,0.18)');
            ctx.fillStyle = grad;
            ctx.fill();

            // arc border — neon stroke (with subtle glow)
            ctx.shadowColor = 'rgba(129,140,248,0.55)';
            ctx.shadowBlur  = 14;
            ctx.lineWidth   = 1.4;
            const stroke = ctx.createLinearGradient(x, 0, x + w, 0);
            stroke.addColorStop(0,   'rgba(34,211,238,0.85)');
            stroke.addColorStop(0.5, 'rgba(129,140,248,0.85)');
            stroke.addColorStop(1,   'rgba(192,132,252,0.85)');
            ctx.strokeStyle = stroke;
            ctx.stroke();
            ctx.restore();

            // stage label
            ctx.save();
            ctx.fillStyle = '#e0e7ff';
            ctx.font = '700 14px "Space Grotesk", system-ui, -apple-system, "Segoe UI", sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('المسرح', DISPLAY_W / 2, y + h / 2 - 4);
            ctx.font = '600 9px "Space Grotesk", system-ui, sans-serif';
            ctx.fillStyle = 'rgba(199,210,254,0.75)';
            ctx.fillText('S T A G E', DISPLAY_W / 2, y + h / 2 + 12);
            ctx.restore();
        }

        function drawRowLabel(letter) {
            const meta = ROW_META.get(letter);
            if (!meta) return;

            // Outermost seat positions match the wing math:
            //   left  outermost = leftBaseX + W/2 - baseOffset
            //                   = (leftEndX - leftWingWidth) + W/2 - baseOffset
            //   right outermost = rightStartX + (cR-1)*PITCH + W/2 + baseOffset
            const leftWingWidth = meta.cL > 0 ? meta.cL * SEAT_PITCH - SEAT_GAP : 0;
            const leftOuterX    = meta.leftEndX - leftWingWidth + SEAT_W / 2 - meta.baseOffset;
            const rightOuterX   = meta.rightStartX
                                + (meta.cR > 0 ? (meta.cR - 1) * SEAT_PITCH : 0)
                                + SEAT_W / 2
                                + meta.baseOffset;

            const isR = letter === 'R';
            // PRISM palette — row R highlighted in indigo, others muted.
            ctx.fillStyle = isR ? 'rgba(199,210,254,0.95)' : 'rgba(133,144,166,0.85)';
            ctx.font = '700 11px "Space Grotesk", system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(letter, leftOuterX  - 18, meta.rowY);
            ctx.fillText(letter, rightOuterX + 18, meta.rowY);
        }

        function drawSeat(seat, isHovered) {
            const state = getState(seat);
            const styles = STATE_STYLES[state];

            ctx.save();
            ctx.translate(seat.x, seat.y);
            ctx.rotate(seat.angle); // rotate to align with the arc's radial direction

            // hover lift (radial — outwards from stage)
            if (isHovered && state === 'available') {
                ctx.translate(0, -3);
            }

            // selection pop animation: 1 → 1.18 → 1 over POP_DURATION ms.
            // Driven by selectionAnim Map; falls through unchanged when not
            // animating or when the user prefers reduced motion.
            const popInfo = selectionAnim.get(seat.id);
            if (popInfo) {
                const t = Math.min(1, (performance.now() - popInfo.startT) / POP_DURATION);
                const popScale = 1 + 0.18 * Math.sin(t * Math.PI);
                ctx.scale(popScale, popScale);
            }

            // glow shadow for selected (or hovered available)
            if (styles.shadow) {
                ctx.shadowColor = styles.shadow.color;
                ctx.shadowBlur  = styles.shadow.blur;
            } else if (isHovered && state === 'available') {
                // PRISM hover glow — indigo / cyan.
                ctx.shadowColor = 'rgba(129,140,248,0.85)';
                ctx.shadowBlur  = 16;
            }

            // body — rounded rect with vertical gradient
            const w = seat.w, h = seat.h;
            const rx = 5, ry = 5;
            const grad = ctx.createLinearGradient(0, -h/2, 0, h/2);
            grad.addColorStop(0, styles.fill[0]);
            grad.addColorStop(1, styles.fill[1]);
            ctx.fillStyle = grad;
            roundedRect(ctx, -w/2, -h/2, w, h, rx, ry);
            ctx.fill();

            ctx.shadowColor = 'transparent';
            ctx.shadowBlur  = 0;

            // Top-edge specular highlight — gives the seat 3D button depth.
            // Skipped on admin (X overlay) and booked (already saturated).
            if (state === 'available' || state === 'selected') {
                ctx.save();
                const specGrad = ctx.createLinearGradient(0, -h/2, 0, -h/2 + h * 0.55);
                specGrad.addColorStop(0,
                    state === 'selected' ? 'rgba(255,255,255,0.42)'
                                         : 'rgba(255,255,255,0.16)');
                specGrad.addColorStop(1, 'rgba(255,255,255,0)');
                ctx.fillStyle = specGrad;
                roundedRect(ctx, -w/2 + 1, -h/2 + 1, w - 2, h * 0.55 - 1, rx - 1, ry - 1);
                ctx.fill();
                ctx.restore();
            }

            // Bottom shadow band — adds depth on every state.
            ctx.save();
            const bottomGrad = ctx.createLinearGradient(0, h/2 - h * 0.35, 0, h/2);
            bottomGrad.addColorStop(0, 'rgba(0,0,0,0)');
            bottomGrad.addColorStop(1, 'rgba(0,0,0,0.22)');
            ctx.fillStyle = bottomGrad;
            roundedRect(ctx, -w/2 + 1, h/2 - h * 0.35, w - 2, h * 0.35 - 1, rx - 1, ry - 1);
            ctx.fill();
            ctx.restore();

            // border
            ctx.lineWidth   = 1;
            ctx.strokeStyle = styles.stroke;
            roundedRect(ctx, -w/2, -h/2, w, h, rx, ry);
            ctx.stroke();

            // Inner ring on selected — gives the glow a crisp neon edge.
            if (state === 'selected') {
                ctx.save();
                ctx.strokeStyle = 'rgba(255,255,255,0.55)';
                ctx.lineWidth = 0.7;
                roundedRect(ctx, -w/2 + 1.6, -h/2 + 1.6, w - 3.2, h - 3.2, rx - 1, ry - 1);
                ctx.stroke();
                ctx.restore();
            }

            // label
            ctx.fillStyle = styles.text;
            ctx.font = '700 8.5px system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(seat.label, 0, 0.5);

            // admin "X" overlay
            if (state === 'admin') {
                ctx.strokeStyle = 'rgba(255,255,255,0.85)';
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.moveTo(-w/2 + 4, -h/2 + 4);
                ctx.lineTo( w/2 - 4,  h/2 - 4);
                ctx.moveTo( w/2 - 4, -h/2 + 4);
                ctx.lineTo(-w/2 + 4,  h/2 - 4);
                ctx.stroke();
            }

            ctx.restore();
        }

        function roundedRect(ctx, x, y, w, h, rx, ry) {
            ctx.beginPath();
            ctx.moveTo(x + rx, y);
            ctx.lineTo(x + w - rx, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + ry);
            ctx.lineTo(x + w, y + h - ry);
            ctx.quadraticCurveTo(x + w, y + h, x + w - rx, y + h);
            ctx.lineTo(x + rx, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - ry);
            ctx.lineTo(x, y + ry);
            ctx.quadraticCurveTo(x, y, x + rx, y);
            ctx.closePath();
        }

        function draw() {
            // background
            ctx.clearRect(0, 0, DISPLAY_W, DISPLAY_H);

            drawStage();

            // row labels
            ROWS_ORDER.forEach((letter, idx) => drawRowLabel(letter, idx));

            // seats — draw selected last so glow lands on top
            for (let i = 0; i < SEATS.length; i++) {
                if (i === hoverIdx) continue;
                if (getState(SEATS[i]) === 'selected') continue;
                drawSeat(SEATS[i], false);
            }
            for (let i = 0; i < SEATS.length; i++) {
                if (getState(SEATS[i]) === 'selected' && i !== hoverIdx) {
                    drawSeat(SEATS[i], false);
                }
            }
            if (hoverIdx >= 0) drawSeat(SEATS[hoverIdx], true);
        }

        // ===== Hit testing ====
        function pointToCanvas(evt) {
            const rect = canvas.getBoundingClientRect();
            const x = (evt.clientX - rect.left) * (DISPLAY_W / rect.width);
            const y = (evt.clientY - rect.top)  * (DISPLAY_H / rect.height);
            return { x, y };
        }

        function findSeatAt(x, y) {
            // iterate in reverse so the visually topmost (last drawn) wins
            for (let i = SEATS.length - 1; i >= 0; i--) {
                const s = SEATS[i];
                const dx = x - s.x;
                const dy = y - s.y;
                // inverse rotation
                const c = Math.cos(-s.angle), si = Math.sin(-s.angle);
                const lx = dx * c - dy * si;
                const ly = dx * si + dy * c;
                const pad = 1; // small click tolerance
                if (Math.abs(lx) <= s.w / 2 + pad && Math.abs(ly) <= s.h / 2 + pad) {
                    return i;
                }
            }
            return -1;
        }

        // ===== Interaction =====
        let rafQueued = false;
        function requestRedraw() {
            if (rafQueued) return;
            rafQueued = true;
            requestAnimationFrame(() => {
                rafQueued = false;
                draw();
            });
        }

        canvas.addEventListener('mousemove', (e) => {
            const { x, y } = pointToCanvas(e);
            const idx = findSeatAt(x, y);
            if (idx !== hoverIdx) {
                hoverIdx = idx;
                if (idx >= 0) {
                    const s = SEATS[idx];
                    const st = getState(s);
                    // Admin can also click admin/blocked seats to unblock
                    // them, so reflect that with the pointer cursor.
                    const isClickable = adminMode
                        ? (st === 'available' || st === 'selected' || st === 'admin')
                        : (st === 'available');
                    canvas.style.cursor = isClickable ? 'pointer' : 'not-allowed';
                    hoverStatus.textContent = describeSeat(s, st);
                } else {
                    canvas.style.cursor = 'default';
                    hoverStatus.textContent = '';
                }
                requestRedraw();
            }
        });

        canvas.addEventListener('mouseleave', () => {
            if (hoverIdx !== -1) {
                hoverIdx = -1;
                hoverStatus.textContent = '';
                requestRedraw();
            }
        });

        canvas.addEventListener('click', (e) => {
            const { x, y } = pointToCanvas(e);
            const idx = findSeatAt(x, y);
            if (idx < 0) return;
            const s = SEATS[idx];
            const st = getState(s);
            if (adminMode) {
                // Admin can toggle available, already-selected (pending),
                // and admin-blocked seats. Customer-booked stays locked.
                if (st === 'booked') return;
            } else {
                if (st !== 'available' && st !== 'selected') return;
            }
            toggleSeat(s);
        });

        function describeSeat(s, st) {
            switch (st) {
                case 'selected':  return s.label + ' · مختار';
                case 'booked':    return s.label + ' · محجوز';
                case 'admin':     return s.label + ' · مخصص للإدارة';
                case 'available':
                default:          return s.label + ' · متاح للحجز';
            }
        }

        function toggleSeat(s) {
            if (selected.has(s.id)) {
                selected.delete(s.id);
            } else {
                selected.set(s.id, { row: s.row, n: s.n });
            }
            triggerPop(s.id);
            renderSidePanel();
            requestRedraw();
        }

        // Selection pop driver — schedules a single rAF loop that redraws
        // the canvas until the animation map is empty. No-op under
        // prefers-reduced-motion.
        function triggerPop(seatId) {
            if (reducedMotion) return;
            selectionAnim.set(seatId, { startT: performance.now() });
            if (popRAF) return;
            const step = (now) => {
                let active = false;
                selectionAnim.forEach((info, id) => {
                    if (now - info.startT >= POP_DURATION) {
                        selectionAnim.delete(id);
                    } else {
                        active = true;
                    }
                });
                requestRedraw();
                popRAF = active ? requestAnimationFrame(step) : 0;
            };
            popRAF = requestAnimationFrame(step);
        }

        // ===== Side panel rendering (chips, attendees, total, mobile bar) =====
        function renderSidePanel() {
            const ids = Array.from(selected.keys());
            const n = ids.length;

            if (countEl)      countEl.textContent     = n;
            if (mobileCount)  mobileCount.textContent = n;
            // Price elements are absent in admin mode — null-check before
            // writing so the same renderer works for both flows.
            if (totalEl)      totalEl.textContent     = (n * hallPrice).toLocaleString('en-US');
            if (mobileTotal)  mobileTotal.textContent = (n * hallPrice).toLocaleString('en-US');
            root.classList.toggle('has-selection', n > 0);

            // chips
            chipsBox.innerHTML = '';
            if (n === 0) {
                const m = document.createElement('span');
                m.className = 'text-[11px] text-gray-500';
                m.textContent = adminMode ? 'لا توجد تغييرات معلَّقة' : 'لم تختر أي مقعد بعد';
                chipsBox.appendChild(m);
            } else {
                ids.sort((a, b) => {
                    const ma = selected.get(a), mb = selected.get(b);
                    if (ma.row !== mb.row) return ma.row < mb.row ? -1 : 1;
                    return ma.n - mb.n;
                }).forEach(id => {
                    const meta = selected.get(id);
                    const chip = document.createElement('span');
                    chip.className = 'seat-chip';
                    // In admin mode each chip carries a hint of direction
                    // (will block / will unblock) via its data-flip attr.
                    if (adminMode) {
                        const flip = blocked.has(id) ? 'unblock' : 'block';
                        chip.setAttribute('data-flip', flip);
                    }
                    chip.innerHTML = `<span>${meta.row}${meta.n}</span><button type="button" aria-label="إلغاء" data-remove="${id}">✕</button>`;
                    chipsBox.appendChild(chip);
                });
            }

            updateContinueButton();
        }

        function updateContinueButton() {
            const ready = selected.size > 0;
            continueBtns.forEach(btn => {
                btn.disabled = !ready;
                if (adminMode) {
                    const lbl = btn.querySelector('[data-cta-label]');
                    if (lbl) {
                        lbl.textContent = ready
                            ? `حفظ التغييرات (${selected.size})`
                            : 'حفظ التغييرات';
                    }
                }
            });
        }

        chipsBox.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove]');
            if (!btn) return;
            const id = parseInt(btn.dataset.remove, 10);
            const meta = seatMeta.get(id);
            if (meta) toggleSeat(meta);
        });

        // ===== Continue → save selection + redirect to form page =====
        // Persist the selection (IDs + labels for chip rendering) to
        // localStorage so the form page can hydrate without a server
        // round-trip. Server still validates seat_ids[] on POST so a
        // tampered localStorage can't bypass anything.
        function saveAndContinue() {
            if (selected.size === 0) return;
            if (adminMode) { saveAdminBulk(); return; }
            const ids = Array.from(selected.keys()).sort((a, b) => {
                const ma = selected.get(a), mb = selected.get(b);
                if (ma.row !== mb.row) return ma.row < mb.row ? -1 : 1;
                return ma.n - mb.n;
            });
            const seats = ids.map(id => {
                const meta = selected.get(id);
                return { id, label: `${meta.row}${meta.n}`, row: meta.row, n: meta.n };
            });
            try {
                localStorage.setItem('booking_selection', JSON.stringify({
                    showTimeId,
                    section: sectionParam,
                    unitPrice: hallPrice,
                    seats,
                    savedAt: Date.now(),
                }));
            } catch (e) { /* localStorage may be disabled — fall through */ }
            window.location.href = formUrl;
        }

        // ===== Admin: bulk apply pending toggles =====
        // POSTs the selected seat IDs to the bulk-toggle endpoint, then
        // patches the local `blocked` set + clears `selected`. No page
        // reload — the dock just collapses and the canvas redraws.
        let saveInFlight = false;
        function saveAdminBulk() {
            if (saveInFlight) return;
            if (selected.size === 0) return;
            if (!bulkToggleUrl) return;
            saveInFlight = true;

            // Optimistic feedback: disable + relabel CTA buttons.
            continueBtns.forEach(btn => {
                btn.disabled = true;
                const lbl = btn.querySelector('[data-cta-label]');
                if (lbl) lbl.textContent = 'جارٍ الحفظ…';
            });

            const seatIds = Array.from(selected.keys());
            fetch(bulkToggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ seat_ids: seatIds }),
            })
            .then(async (res) => {
                if (!res.ok) throw new Error('http ' + res.status);
                return res.json();
            })
            .then((data) => {
                // Authoritative blocked set from the server — replace local.
                if (Array.isArray(data.blocked_set)) {
                    blocked.clear();
                    data.blocked_set.forEach(id => blocked.add(Number(id)));
                }
                const blockedN   = (data.blocked   || []).length;
                const unblockedN = (data.unblocked || []).length;
                const rejectedN  = (data.rejected  || []).length;
                selected.clear();
                renderSidePanel();
                requestRedraw();
                showAdminToast({
                    ok: true,
                    blocked: blockedN,
                    unblocked: unblockedN,
                    rejected: rejectedN,
                });
            })
            .catch(() => {
                showAdminToast({ ok: false });
            })
            .finally(() => {
                saveInFlight = false;
                // Re-enable & re-label.
                continueBtns.forEach(btn => {
                    const lbl = btn.querySelector('[data-cta-label]');
                    if (lbl) lbl.textContent = 'حفظ التغييرات';
                    btn.disabled = selected.size === 0;
                });
            });
        }

        // Premium center toast for admin save feedback. Reuses the layout's
        // pt-toast-overlay node when present (rendered by the admin wrapper
        // view) and falls back to window.PT.toast for plain text.
        function showAdminToast(payload) {
            const overlay = document.querySelector('[data-admin-toast]');
            if (!overlay) {
                if (window.PT && window.PT.toast) {
                    window.PT.toast(payload.ok
                        ? '✅ تم حفظ التغييرات'
                        : '❌ تعذر حفظ التغييرات');
                }
                return;
            }
            const card    = overlay.querySelector('.pt-toast-card') || overlay;
            const iconEl  = overlay.querySelector('[data-toast-icon]');
            const titleEl = overlay.querySelector('[data-toast-title]');
            const bodyEl  = overlay.querySelector('[data-toast-body]');
            if (payload.ok) {
                if (iconEl)  iconEl.textContent  = '✓';
                if (titleEl) titleEl.textContent = 'تم حفظ التغييرات';
                const parts = [];
                if (payload.blocked)   parts.push(`حُجِب ${payload.blocked}`);
                if (payload.unblocked) parts.push(`فُعِّل ${payload.unblocked}`);
                if (payload.rejected)  parts.push(`تم تجاهل ${payload.rejected}`);
                if (bodyEl)  bodyEl.textContent  = parts.length
                    ? parts.join(' · ')
                    : 'لم يتغيّر شيء';
                card.classList.remove('is-error');
                card.classList.add('is-success');
            } else {
                if (iconEl)  iconEl.textContent  = '✕';
                if (titleEl) titleEl.textContent = 'تعذر حفظ التغييرات';
                if (bodyEl)  bodyEl.textContent  = 'حاول مرة أخرى من فضلك';
                card.classList.remove('is-success');
                card.classList.add('is-error');
            }
            overlay.classList.add('is-on');
            clearTimeout(overlay._t);
            overlay._t = setTimeout(() => overlay.classList.remove('is-on'), 2200);
        }

        continueBtns.forEach(btn => btn.addEventListener('click', saveAndContinue));

        // Restore prior selection (e.g. user came back from the form page).
        // Admin mode never restores — pending changes are intentionally
        // ephemeral so the admin starts each visit with a clean slate.
        try {
            if (adminMode) throw new Error('skip restore');
            const stored = JSON.parse(localStorage.getItem('booking_selection') || 'null');
            if (stored
                && stored.showTimeId === showTimeId
                && stored.section === sectionParam
                && Array.isArray(stored.seats)) {
                stored.seats.forEach(s => {
                    if (typeof s.id === 'number'
                        && !unavailable.has(s.id)
                        && !blocked.has(s.id)) {
                        selected.set(s.id, { row: s.row, n: s.n });
                    }
                });
            }
        } catch (e) { /* ignore */ }

        // ===== Zoom =====
        // Pure CSS scale — keeps the underlying canvas geometry unchanged.
        // In fullscreen mode the canvas is auto-fit to the available space
        // so that mobile users never need to scroll the seat map.
        const ZOOM_MIN = isFullscreen ? 0.18 : 0.7;
        const ZOOM_MAX = isFullscreen ? 2.5  : 1.8;

        // applyTransform — write the current pan/zoom to the canvas as a
        // single GPU-composited transform. Called from every gesture step
        // and every zoom action. Cheap enough to call at 60fps without
        // touching layout.
        function applyTransform() {
            canvas.style.transform =
                'translate3d(' + panX.toFixed(2) + 'px,' + panY.toFixed(2) + 'px,0) ' +
                'scale(' + zoomLevel.toFixed(4) + ')';
        }

        // Clamp pan so the canvas can't be flung entirely off-screen. We
        // allow a small over-pan margin so the user can scroll the very
        // edge into view comfortably; the canvas is always at least 60%
        // visible along each axis.
        function clampPan() {
            const sw = scroller.clientWidth;
            const sh = scroller.clientHeight;
            const cw = DISPLAY_W * zoomLevel;
            const ch = DISPLAY_H * zoomLevel;

            if (cw <= sw) {
                panX = (sw - cw) / 2; // center when narrower than viewport
            } else {
                const margin = sw * 0.08;
                const minX = sw - cw - margin;
                const maxX = margin;
                if (panX < minX) panX = minX;
                if (panX > maxX) panX = maxX;
            }
            if (ch <= sh) {
                panY = (sh - ch) / 2;
            } else {
                const margin = sh * 0.08;
                const minY = sh - ch - margin;
                const maxY = margin;
                if (panY < minY) panY = minY;
                if (panY > maxY) panY = maxY;
            }
        }

        // Apply a new zoom anchored at a specific screen point (so the
        // pixel under the user's finger / cursor stays under it).
        function setZoomAt(newZoom, screenX, screenY) {
            newZoom = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, newZoom));
            if (Math.abs(newZoom - zoomLevel) < 0.0005) return;
            const r = scroller.getBoundingClientRect();
            const px = screenX - r.left;
            const py = screenY - r.top;
            // canvas-local coords of the anchor before the zoom change
            const cx = (px - panX) / zoomLevel;
            const cy = (py - panY) / zoomLevel;
            zoomLevel = newZoom;
            // re-translate so anchor stays under the same screen point
            panX = px - cx * zoomLevel;
            panY = py - cy * zoomLevel;
            clampPan();
            applyTransform();
        }

        // Compute the zoom that would fit the full canvas in the scroller.
        // If that zoom would render seats below MIN_TAP_PX, fall back to
        // 1.0× and rely on user panning instead — never produce an
        // un-tappable map. Default-centers the canvas in both axes.
        const MIN_TAP_PX = 28;
        function fitToViewport() {
            const w = scroller.clientWidth;
            const h = scroller.clientHeight;
            if (w <= 0 || h <= 0) return;
            let z = Math.min(w / DISPLAY_W, h / DISPLAY_H);
            z = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, z));
            // Min tappable size floor — 6 px seats are not interactable.
            // If fit would shrink seats below MIN_TAP_PX, prefer 1.0× and
            // let the user pan. The original 'auto-fit' bug shipped a
            // ~6 px rendered seat on iPhone 12 Pro.
            if (SEAT_W * z < MIN_TAP_PX && z < 1) {
                z = Math.min(1, ZOOM_MAX);
            }
            zoomLevel = z;
            panX = (w - DISPLAY_W * zoomLevel) / 2;
            panY = (h - DISPLAY_H * zoomLevel) / 2;
            applyTransform();
        }

        // Zoom-bar buttons (top-right + floating FAB). Anchor at scroller
        // center for keyboard / button-driven zoom; this matches user
        // expectation that "+ / −" zooms toward what's currently centered.
        root.querySelectorAll('[data-zoom]').forEach(btn => {
            btn.addEventListener('click', () => {
                const dir = parseInt(btn.dataset.zoom, 10);
                if (dir === 0) {
                    fitToViewport();
                } else {
                    const r = scroller.getBoundingClientRect();
                    setZoomAt(zoomLevel * (dir > 0 ? 1.22 : 1 / 1.22),
                              r.left + r.width / 2,
                              r.top  + r.height / 2);
                }
            });
        });

        // ===== Pointer Events gesture pipeline =====
        // Single source of truth for pan + pinch — Pointer Events unify
        // mouse, touch, and stylus. `touch-action: none` on the scroller
        // (set in CSS) means the browser never tries to scroll, zoom, or
        // long-press while we're handling these.
        //
        //  · 1-finger pan      — translate panX / panY directly.
        //  · 2-finger pinch    — anchored at the live midpoint in canvas
        //                        coords so the pixel between the fingers
        //                        stays put as they spread / squeeze.
        //  · momentum on lift  — preserved velocity decays via rAF until
        //                        below threshold (skipped under
        //                        prefers-reduced-motion).
        //  · click suppression — 10 px movement budget before a tap is
        //                        treated as a drag.
        (function () {
            const pointers       = new Map();   // pointerId → {x,y, prevX,prevY, startX,startY}
            const CLICK_THRESHOLD = 10;
            const VEL_SAMPLES     = 6;
            const MOMENTUM_DECAY  = 0.94;       // per ~16ms frame
            const MOMENTUM_MIN    = 0.02;       // px/ms cutoff

            let pinchStartDist  = 0;
            let pinchStartZoom  = 1;
            let pinchAnchor     = null;         // { canvasX, canvasY }
            let movedDist       = 0;
            let suppressClick   = false;
            let velSamples      = [];
            let momentumRAF     = 0;

            function stopMomentum() {
                if (momentumRAF) cancelAnimationFrame(momentumRAF);
                momentumRAF = 0;
            }

            function startMomentum() {
                if (reducedMotion) return;
                if (velSamples.length < 2) return;
                const last  = velSamples[velSamples.length - 1];
                const first = velSamples[0];
                const dt = Math.max(1, last.t - first.t);
                let vx = (last.x - first.x) / dt;   // px / ms
                let vy = (last.y - first.y) / dt;
                if (Math.hypot(vx, vy) < 0.05) return;

                stopMomentum();
                let lastT = performance.now();
                const step = (now) => {
                    const dt = Math.min(32, now - lastT);
                    lastT = now;
                    panX += vx * dt;
                    panY += vy * dt;
                    const decay = Math.pow(MOMENTUM_DECAY, dt / 16);
                    vx *= decay;
                    vy *= decay;
                    clampPan();
                    applyTransform();
                    if (Math.hypot(vx, vy) > MOMENTUM_MIN) {
                        momentumRAF = requestAnimationFrame(step);
                    } else {
                        momentumRAF = 0;
                    }
                };
                momentumRAF = requestAnimationFrame(step);
            }

            function pushVelSample() {
                velSamples.push({ x: panX, y: panY, t: performance.now() });
                if (velSamples.length > VEL_SAMPLES) velSamples.shift();
            }

            function startPinch() {
                const arr = Array.from(pointers.values());
                const p1 = arr[0], p2 = arr[1];
                pinchStartDist = Math.hypot(p2.x - p1.x, p2.y - p1.y);
                pinchStartZoom = zoomLevel;
                const r = scroller.getBoundingClientRect();
                const cx = (p1.x + p2.x) / 2 - r.left;
                const cy = (p1.y + p2.y) / 2 - r.top;
                pinchAnchor = {
                    canvasX: (cx - panX) / zoomLevel,
                    canvasY: (cy - panY) / zoomLevel,
                };
            }

            scroller.addEventListener('pointerdown', (e) => {
                // Don't capture from the floating FAB or zoom-bar buttons.
                if (e.target.closest('.canvas-fab, .zoom-bar')) return;
                try { scroller.setPointerCapture(e.pointerId); } catch (_) {}
                pointers.set(e.pointerId, {
                    x: e.clientX, y: e.clientY,
                    prevX: e.clientX, prevY: e.clientY,
                    startX: e.clientX, startY: e.clientY,
                });
                stopMomentum();
                if (pointers.size === 2) {
                    startPinch();
                    suppressClick = true;
                    scroller.classList.add('is-gesturing');
                } else if (pointers.size === 1) {
                    movedDist = 0;
                    suppressClick = false;
                    velSamples = [];
                    pushVelSample();
                }
                e.preventDefault();
            });

            scroller.addEventListener('pointermove', (e) => {
                const p = pointers.get(e.pointerId);
                if (!p) return;
                p.prevX = p.x; p.prevY = p.y;
                p.x = e.clientX; p.y = e.clientY;

                if (pointers.size === 2 && pinchAnchor) {
                    // ----- pinch zoom centered on live midpoint -----
                    const arr = Array.from(pointers.values());
                    const a = arr[0], b = arr[1];
                    const d = Math.hypot(b.x - a.x, b.y - a.y);
                    if (pinchStartDist > 0) {
                        const targetZoom = Math.max(
                            ZOOM_MIN,
                            Math.min(ZOOM_MAX, pinchStartZoom * (d / pinchStartDist))
                        );
                        zoomLevel = targetZoom;
                        const r = scroller.getBoundingClientRect();
                        const cx = (a.x + b.x) / 2 - r.left;
                        const cy = (a.y + b.y) / 2 - r.top;
                        // anchor canvas-coord stays under the new midpoint
                        panX = cx - pinchAnchor.canvasX * zoomLevel;
                        panY = cy - pinchAnchor.canvasY * zoomLevel;
                        clampPan();
                        applyTransform();
                    }
                    e.preventDefault();
                } else if (pointers.size === 1) {
                    // ----- single-finger pan -----
                    const dx = p.x - p.prevX;
                    const dy = p.y - p.prevY;
                    movedDist += Math.hypot(dx, dy);
                    if (movedDist > CLICK_THRESHOLD) {
                        suppressClick = true;
                        scroller.classList.add('is-gesturing');
                    }
                    panX += dx;
                    panY += dy;
                    clampPan();
                    applyTransform();
                    pushVelSample();
                    e.preventDefault();
                }
            });

            function endPointer(e) {
                if (!pointers.has(e.pointerId)) return;
                pointers.delete(e.pointerId);
                try { scroller.releasePointerCapture(e.pointerId); } catch (_) {}

                if (pointers.size === 0) {
                    // last finger lifted — fling momentum if appropriate
                    if (suppressClick) startMomentum();
                    pinchAnchor = null;
                    setTimeout(() => { suppressClick = false; }, 80);
                    scroller.classList.remove('is-gesturing');
                } else if (pointers.size === 1) {
                    // dropped from pinch back to single-finger pan;
                    // reset pan velocity tracking from the remaining finger
                    velSamples = [];
                    pushVelSample();
                    pinchAnchor = null;
                }
            }

            scroller.addEventListener('pointerup',     endPointer);
            scroller.addEventListener('pointercancel', endPointer);
            scroller.addEventListener('pointerleave', (e) => {
                // pointerleave also fires on capture loss — only end if we
                // weren't capturing, otherwise we'd lose ongoing gestures.
                if (!scroller.hasPointerCapture(e.pointerId)) endPointer(e);
            });

            // Suppress the synthetic click that follows a drag/pinch.
            canvas.addEventListener('click', (e) => {
                if (suppressClick) {
                    e.stopPropagation();
                    e.preventDefault();
                }
            }, true);

            // ----- mouse wheel: ctrl/⌘ + wheel = zoom; plain wheel = pan -----
            scroller.addEventListener('wheel', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    setZoomAt(zoomLevel * (1 + (-e.deltaY * 0.0015)),
                              e.clientX, e.clientY);
                } else {
                    e.preventDefault();
                    panX -= e.deltaX;
                    panY -= e.deltaY;
                    clampPan();
                    applyTransform();
                }
            }, { passive: false });

            // ----- double-tap to zoom in / fit -----
            let lastTap = 0;
            let lastTapPos = { x: 0, y: 0 };
            scroller.addEventListener('pointerup', (e) => {
                if (e.pointerType !== 'touch') return;
                if (suppressClick) { lastTap = 0; return; }
                const now = Date.now();
                const dx = e.clientX - lastTapPos.x;
                const dy = e.clientY - lastTapPos.y;
                if (now - lastTap < 280 && Math.hypot(dx, dy) < 30) {
                    const fitZoom = Math.min(scroller.clientWidth / DISPLAY_W,
                                             scroller.clientHeight / DISPLAY_H);
                    const halfway = (fitZoom + ZOOM_MAX) / 2;
                    if (zoomLevel < halfway) {
                        setZoomAt(Math.min(ZOOM_MAX, zoomLevel * 1.8),
                                  e.clientX, e.clientY);
                    } else {
                        fitToViewport();
                    }
                    lastTap = 0;
                } else {
                    lastTap = now;
                    lastTapPos = { x: e.clientX, y: e.clientY };
                }
            });
        })();

        // ===== Init =====
        function boot() {
            fitCanvas();
            computeLayout();
            renderSidePanel();
            // initial transform — JS owns positioning even at rest
            applyTransform();
            requestAnimationFrame(() => {
                fitToViewport();
                draw();
            });
        }

        boot();

        // ===== Pinch & pan onboarding hint =====
        // Mobile-only. Stays visible until the user actually interacts with
        // the seat map (any touch on the scroller — tap, pan, or pinch).
        // The hint itself is `pointer-events: none`, so the underlying
        // gesture passes through to the canvas unaffected.
        (function showGestureHint() {
            const hint = root.querySelector('[data-anba-gesture-hint]');
            if (!hint) return;

            const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
            const isMobileViewport = window.matchMedia('(max-width: 880px)').matches;
            if (!isTouch || !isMobileViewport) {
                hint.parentNode && hint.parentNode.removeChild(hint);
                return;
            }

            let dismissed = false;
            function dismiss() {
                if (dismissed) return;
                dismissed = true;
                hint.classList.remove('is-visible');
                hint.classList.add('is-leaving');
                setTimeout(() => {
                    hint.parentNode && hint.parentNode.removeChild(hint);
                }, 450);
            }

            // Show shortly after init so the canvas has rendered.
            setTimeout(() => {
                if (!dismissed) hint.classList.add('is-visible');
            }, 320);

            // Any real touch on the seat-map area (canvas, FAB, scroller)
            // dismisses the hint. `once: true` auto-removes the listener
            // after the first fire so there's zero ongoing overhead.
            if (scroller) {
                scroller.addEventListener('touchstart', dismiss, { passive: true, once: true });
            }
        })();

        // Redraw on devicePixelRatio change (rare) and re-fit on resize
        // so rotating the device or collapsing the URL bar doesn't leave
        // the canvas off-screen.
        let lastDpr = window.devicePixelRatio || 1;
        window.addEventListener('resize', () => {
            const dpr = window.devicePixelRatio || 1;
            if (dpr !== lastDpr) {
                lastDpr = dpr;
                fitCanvas();
                draw();
            }
            fitToViewport();
        });

        // iOS Safari: visualViewport fires when the URL bar collapses.
        // Re-fit so the canvas reclaims the freed pixels.
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', fitToViewport);
        }
    })();
</script>
