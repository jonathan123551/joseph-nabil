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
    $isFullscreen    = (bool) ($fullscreen ?? false);
    // Admin variant: same picker, same gestures, but the click semantics
    // toggle SeatBlock rows via a bulk endpoint instead of redirecting to
    // the customer form.
    $adminMode       = (bool) ($adminMode ?? false);
    $bulkToggleUrl   = $bulkToggleUrl ?? '';
    $adminBackUrl    = $adminBackUrl ?? '';

    // ===== THEATER LAYOUT PRESETS =====
    //
    // Reusable layout registry. Each preset describes the ROW geometry of
    // one section; the seat-picker engine (rendering, gestures, animations,
    // admin flow) is shared across every preset. Adding a new theater
    // layout = adding a new preset entry — no JS engine changes needed.
    //
    //   rowsOrder       : visual top→bottom row sequence. The literal
    //                     'GAP' inserts a 1.5×ROW_PITCH walkway/gap in
    //                     the geometry (NOT a CSS margin) — this is how
    //                     the balcony walkway between C and D is drawn.
    //                     'GAP_HALF' inserts a 0.75×ROW_PITCH separator
    //                     (half the size of 'GAP') — a subtle visual
    //                     break, NOT a full aisle. Used between Q and R
    //                     in the hall preset.
    //   rightShiftSteps : per-row outermost wing offset, in HALF-SEAT
    //                     units. 0 = wings flush with center column;
    //                     positive = wings pushed outward (cinematic fan);
    //                     negative = wings pulled inward (back-row stagger).
    //   noCenterAnchors : for rows that have no center column, the row
    //                     letter to ANCHOR each wing's inner edge to.
    //                     Object cast forces JSON object (not array).
    //   adminOnlyCenter : rows whose center seats are admin-only and
    //                     therefore skipped during render. Currently empty
    //                     for the hall preset — row Q's historical 9-seat
    //                     center block was retired (see the
    //                     2026-05-09 drop_anba_ruweis_q_center migration).
    $presets = [
        'hall' => [
            // 'GAP_HALF' between Q and R adds a subtle vertical separator
            // (0.75×ROW_PITCH ≈ 22.5 px extra), about half the size of the
            // H↔I walkway. Not a full aisle — a gentle visual hint that R
            // is the offset back-row.
            'rowsOrder'        => ['A','B','C','D','E','F','G','H','GAP','I','J','K','L','M','N','O','P','Q','GAP_HALF','R'],
            'rightShiftSteps'  => (object) [
                'Q' => 0,    'P' => 1,    'O' => 2,    'N' => 3,
                'M' => 3.0,  'L' => 3.2,  'K' => 3.4,  'J' => 3.6, 'I' => 4,
                'H' => 5,    'G' => 6,    'F' => 7,    'E' => 8,   'D' => 9, 'C' => 10,
                'B' => 10.2, 'A' => 11.2,
                // Row R: NEGATIVE step = wings pulled INWARD relative to Q
                // (the anchor). -2 = one full seat width inward — R's
                // innermost seats sit visibly inside Q's innermost seats,
                // a pronounced back-row sight-line stagger.
                'R' => -2,
            ],
            // Q is the anchor for R as well so R's inner edge lines up with
            // Q's inner edge (both anchor to P's center column). Without
            // this, R would anchor to Q (which has no center column) and
            // its inner edge would collapse onto CX ± AISLE_GAP — far
            // inside Q's inner edge.
            'noCenterAnchors'  => (object) ['A' => 'B', 'Q' => 'P', 'R' => 'P'],
            'adminOnlyCenter'  => [],
        ],
        // anba_ruweis_ballacon — 8-row premium balcony tier.
        // Rows A/B/C carry the central premium block (left + center + right);
        // rows D–H span the rest of the balcony as left + right only.
        // The 'GAP' between C and D produces a real walkway (geometry, not CSS).
        'balcony' => [
            'rowsOrder'        => ['A','B','C','GAP','D','E','F','G','H'],
            'rightShiftSteps'  => (object) [
                // Front group with center column — flush, no outward fan.
                'A' => 0,    'B' => 0,    'C' => 0,
                // Mid (no center) — gentle progressive widening front→back.
                'D' => 0.5,  'E' => 1.0,  'F' => 1.5,
                // Back (no center, 16-seat wings) — widest fan.
                'G' => 2.5,  'H' => 3.0,
            ],
            // D–H have no center column; anchor each wing's inner edge to
            // row C's center column so the layout stays vertically aligned.
            'noCenterAnchors'  => (object) ['D' => 'C', 'E' => 'C', 'F' => 'C', 'G' => 'C', 'H' => 'C'],
            'adminOnlyCenter'  => [],
        ],
    ];
    $preset       = $presets[$sectionParam] ?? $presets['hall'];

    // Seats for THIS preset only. Hall seats remain reachable under the
    // 'hall' key; balcony seats live under 'balcony' (added by the
    // 2026_05_08 seeder migration). The view renders only the section
    // requested via $sectionParam.
    $sectionSeats = $seatsByRow[$sectionParam] ?? [];
    ksort($sectionSeats);
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

        /* ===== QW#7 fullscreen auto-pick chip =====
           Floats over the canvas at top-inline-start (mirrors .canvas-fab
           which is bottom-inline-end). Glass + amber so it reads as a
           "shortcut" affordance distinct from the zoom rail. */
        [data-anba-root] .auto-pick-fab {
            position: absolute;
            top: 12px;
            inset-inline-start: 12px;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            color: #fef3c7;
            background: linear-gradient(135deg, rgba(245,158,11,0.34), rgba(251,191,36,0.18));
            border: 1px solid rgba(251,191,36,0.55);
            box-shadow:
                0 6px 18px -6px rgba(245,158,11,0.55),
                inset 0 1px 0 rgba(255,255,255,0.10);
            backdrop-filter: blur(10px) saturate(160%);
            -webkit-backdrop-filter: blur(10px) saturate(160%);
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: transform .15s var(--p-ease), box-shadow .2s var(--p-ease), background .15s var(--p-ease);
        }
        [data-anba-root] .auto-pick-fab:hover {
            background: linear-gradient(135deg, rgba(245,158,11,0.50), rgba(251,191,36,0.30));
            box-shadow:
                0 10px 24px -6px rgba(245,158,11,0.7),
                inset 0 1px 0 rgba(255,255,255,0.14);
        }
        [data-anba-root] .auto-pick-fab:active { transform: scale(0.96); }
        @media (max-width: 480px) {
            [data-anba-root] .auto-pick-fab {
                font-size: 11px;
                padding: 7px 10px;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            [data-anba-root] .auto-pick-fab { transition: none; }
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

        /* ===== "More seats →" edge arrows =====
           Pulsing chevrons pinned to the leading / trailing edges of the
           scroller, visible only when the canvas extends past the
           viewport on that axis. Mobile-first; hidden on desktop and on
           reduced-motion (replaced with static visibility). Pointer-
           events-none so the underlying gesture pipeline is unaffected. */
        [data-anba-root] .edge-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(90deg, rgba(8,10,20,0.78), rgba(8,10,20,0.42));
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            color: rgba(34,211,238,0.95);
            font-size: 22px;
            font-weight: 800;
            line-height: 1;
            opacity: 0;
            transition: opacity .22s var(--p-ease);
        }
        [data-anba-root] .edge-arrow.is-visible { opacity: 1; }
        [data-anba-root] .edge-arrow.start { inset-inline-start: 6px; }
        [data-anba-root] .edge-arrow.end   { inset-inline-end:   6px; }
        [data-anba-root] .edge-arrow.start { background: linear-gradient(90deg, rgba(8,10,20,0.78), rgba(8,10,20,0.10)); }
        [data-anba-root] .edge-arrow.end   { background: linear-gradient(-90deg, rgba(8,10,20,0.78), rgba(8,10,20,0.10)); }
        /* Subtle nudge animation — translateX 3px every 1.6s. Direction
           flips per side (leading edge pulses leftward, trailing edge
           pulses rightward) to suggest "more on this side". */
        @keyframes edgeArrowPulseStart {
            0%, 100% { transform: translateY(-50%) translateX(0); }
            50%      { transform: translateY(-50%) translateX(-3px); }
        }
        @keyframes edgeArrowPulseEnd {
            0%, 100% { transform: translateY(-50%) translateX(0); }
            50%      { transform: translateY(-50%) translateX(3px); }
        }
        [data-anba-root] .edge-arrow.start.is-visible { animation: edgeArrowPulseStart 1.6s ease-in-out infinite; }
        [data-anba-root] .edge-arrow.end.is-visible   { animation: edgeArrowPulseEnd   1.6s ease-in-out infinite; }
        /* Soft fade-out shell so the arrows don't visually clash with
           seats at the edge — same color as the surrounding canvas. */
        [data-anba-root] .edge-arrow svg {
            width: 14px;
            height: 14px;
            display: block;
            filter: drop-shadow(0 0 6px rgba(34,211,238,0.35));
        }
        :root[data-pt-theme="light"] [data-anba-root] .edge-arrow {
            color: #4338ca;
            background: linear-gradient(90deg, rgba(255,255,255,0.92), rgba(255,255,255,0.42));
        }
        :root[data-pt-theme="light"] [data-anba-root] .edge-arrow.start { background: linear-gradient(90deg, rgba(255,255,255,0.92), rgba(255,255,255,0.10)); }
        :root[data-pt-theme="light"] [data-anba-root] .edge-arrow.end   { background: linear-gradient(-90deg, rgba(255,255,255,0.92), rgba(255,255,255,0.10)); }
        :root[data-pt-theme="light"] [data-anba-root] .edge-arrow svg   { filter: drop-shadow(0 0 6px rgba(99,102,241,0.35)); }
        @media (min-width: 880px) {
            [data-anba-root] .edge-arrow { display: none !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            [data-anba-root] .edge-arrow.start.is-visible,
            [data-anba-root] .edge-arrow.end.is-visible { animation: none; }
            [data-anba-root] .edge-arrow { transition: none; }
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
        /* Inline seat labels in the mobile CTA so the user can verify
           the actual seats picked (not just the count). Truncates to
           one line; the full list is still in the chips region of the
           side panel above. */
        [data-anba-root] [data-mobile-labels] {
            color: var(--p-gold);
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            max-width: 60vw;
            vertical-align: bottom;
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

        /* ===== Auto-pick chip modal =====
           Touch-first replacement for `window.prompt()`. A small glass
           card with N tappable chips (1..max). Closes on overlay tap or
           Escape. Layered on top of the seat-picker viewport so it works
           in both inline and fullscreen modes. */
        .anba-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(3, 5, 12, 0.72);
            backdrop-filter: blur(8px) saturate(140%);
            -webkit-backdrop-filter: blur(8px) saturate(140%);
            opacity: 0;
            transition: opacity .18s ease-out;
        }
        .anba-modal-backdrop.is-open {
            display: flex;
            opacity: 1;
        }
        .anba-modal-card {
            width: min(360px, 92vw);
            border-radius: 18px;
            padding: 18px 18px 14px;
            background: linear-gradient(180deg, rgba(15,18,32,0.96), rgba(8,10,20,0.96));
            border: 1px solid rgba(251,191,36,0.40);
            box-shadow:
                0 24px 60px -12px rgba(0,0,0,0.65),
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 1px 0 rgba(255,255,255,0.10) inset;
            color: var(--p-text);
            transform: translateY(6px) scale(.98);
            transition: transform .22s var(--p-ease);
        }
        .anba-modal-backdrop.is-open .anba-modal-card {
            transform: translateY(0) scale(1);
        }
        .anba-modal-eyebrow {
            font-size: 10.5px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #fcd34d;
            font-weight: 700;
            margin-bottom: 6px;
            text-align: center;
        }
        .anba-modal-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--p-text);
            text-align: center;
            margin-bottom: 12px;
        }
        .anba-modal-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }
        .anba-modal-chip {
            appearance: none;
            -webkit-appearance: none;
            border: 1px solid rgba(251,191,36,0.30);
            background: rgba(251,191,36,0.06);
            color: #fde68a;
            border-radius: 12px;
            padding: 12px 0;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            min-height: 44px;
            transition: transform .12s var(--p-ease), background .12s var(--p-ease), border-color .12s var(--p-ease);
        }
        .anba-modal-chip:hover {
            background: rgba(251,191,36,0.16);
            border-color: rgba(251,191,36,0.55);
        }
        .anba-modal-chip:active { transform: scale(0.95); }
        .anba-modal-cancel {
            display: block;
            width: 100%;
            background: transparent;
            border: 1px solid var(--p-border);
            color: var(--p-text-3);
            border-radius: 12px;
            padding: 10px 0;
            font-size: 13px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            min-height: 40px;
        }
        .anba-modal-cancel:hover {
            background: rgba(255,255,255,0.04);
            color: var(--p-text);
        }
        @media (max-width: 360px) {
            .anba-modal-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        @media (prefers-reduced-motion: reduce) {
            .anba-modal-backdrop,
            .anba-modal-card { transition: none; }
        }
        /* ---- Light-mode overrides: auto-pick chip-count modal ----
           Fired from the auto-pick FAB. The dark amber-on-navy card looks
           pasted-in on cream; swap to a white-cream card with amber
           accent, neutral cancel button, and a softer scrim.
           NB: the modal is rendered OUTSIDE [data-anba-root] (a sibling
           of the picker), so the `--p-*` tokens don't cascade to it.
           We use the global `--prism-*` tokens instead. */
        :root[data-pt-theme="light"] .anba-modal-backdrop {
            background: rgba(15,23,42,0.32);
        }
        :root[data-pt-theme="light"] .anba-modal-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.98));
            border-color: rgba(245,158,11,0.45);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.7),
                0 24px 60px -12px rgba(15,23,42,0.28),
                0 0 24px rgba(245,158,11,0.16);
            color: var(--prism-text);
        }
        :root[data-pt-theme="light"] .anba-modal-eyebrow {
            color: var(--prism-gold);
        }
        :root[data-pt-theme="light"] .anba-modal-title {
            color: var(--prism-text);
        }
        :root[data-pt-theme="light"] .anba-modal-chip {
            background: rgba(245,158,11,0.10);
            border-color: rgba(245,158,11,0.45);
            color: var(--prism-gold);
        }
        :root[data-pt-theme="light"] .anba-modal-chip:hover {
            background: rgba(245,158,11,0.20);
            border-color: rgba(245,158,11,0.65);
        }
        :root[data-pt-theme="light"] .anba-modal-cancel {
            border-color: rgba(15,23,42,0.12);
            color: var(--prism-text-3);
        }
        :root[data-pt-theme="light"] .anba-modal-cancel:hover {
            background: rgba(15,23,42,0.04);
            color: var(--prism-text);
        }

        /* =====================================================================
           LIGHT THEME — seat picker chrome.
           The canvas itself stays dark (it represents the theater stage) but
           the wrapping glass panels, side panel, zoom bar, legend pills,
           attendee cards, field inputs and transfer instructions all need
           light overrides — the dark slate surfaces look pasted-in on cream
           and the bg-white/[0.04] tinted info pills become invisible.
           Dark mode is untouched.
        ===================================================================== */
        :root[data-pt-theme="light"] [data-anba-root] .glass {
            background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(252,250,245,0.86));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 24px 52px -22px rgba(15,23,42,0.26),
                0 4px 10px -4px rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] [data-anba-root] .ambient {
            /* Keep the soft cyan / violet halo, but drop the dark slate base
               so the wrapping glass stays light around the dark canvas. */
            background:
                radial-gradient(ellipse 120% 60% at 50% -10%, rgba(34,211,238,0.10), transparent 60%),
                radial-gradient(ellipse 80% 50% at 50% 110%, rgba(192,132,252,0.10), transparent 60%);
        }
        :root[data-pt-theme="light"] [data-anba-root] .zoom-bar {
            background: rgba(255,255,255,0.85);
            border-color: rgba(79,70,229,0.30);
            box-shadow:
                0 4px 14px -6px rgba(79,70,229,0.25),
                inset 0 1px 0 rgba(255,255,255,0.95);
        }
        :root[data-pt-theme="light"] [data-anba-root] .zoom-btn {
            color: #3730a3;
        }
        :root[data-pt-theme="light"] [data-anba-root] .zoom-btn:hover {
            background: rgba(79,70,229,0.10);
            color: #312e81;
        }
        :root[data-pt-theme="light"] [data-anba-root] .zoom-btn + .zoom-btn {
            border-right-color: rgba(79,70,229,0.20);
        }
        :root[data-pt-theme="light"] [data-anba-root] .legend-pill {
            background: rgba(15,23,42,0.04);
            border-color: rgba(15,23,42,0.14);
            color: var(--prism-text-2);
        }
        /* Available swatch needs a different gradient on cream so it doesn't
           collide with the slate-on-slate dark look. */
        :root[data-pt-theme="light"] [data-anba-root] .legend-swatch {
            border-color: rgba(15,23,42,0.20);
        }
        :root[data-pt-theme="light"] [data-anba-root] .legend-swatch.avail {
            background: linear-gradient(180deg, #cbd5e1, #94a3b8);
        }
        :root[data-pt-theme="light"] [data-anba-root] .attendee-card {
            background: rgba(255,255,255,0.92);
            border-color: rgba(15,23,42,0.12);
            box-shadow:
                0 8px 18px -10px rgba(15,23,42,0.16),
                inset 0 1px 0 rgba(255,255,255,0.85);
        }
        :root[data-pt-theme="light"] [data-anba-root] .field-input {
            background: #ffffff;
            border-color: rgba(15,23,42,0.16);
            color: var(--prism-text);
        }
        :root[data-pt-theme="light"] [data-anba-root] .field-input:focus {
            border-color: rgba(79,70,229,0.55);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.14);
        }
        :root[data-pt-theme="light"] [data-anba-root] .seat-chip {
            background: linear-gradient(135deg, rgba(4,120,87,0.14), rgba(8,145,178,0.10));
            border-color: rgba(4,120,87,0.45);
            color: #064e3b;
            box-shadow: 0 0 12px rgba(4,120,87,0.14), inset 0 1px 0 rgba(255,255,255,0.6);
        }
        :root[data-pt-theme="light"] [data-anba-root] .seat-chip [data-remove] {
            background: rgba(15,23,42,0.10);
            color: #7f1d1d;
        }
        :root[data-pt-theme="light"] [data-anba-root] .seat-chip [data-remove]:hover {
            background: rgba(244,63,94,0.20);
            color: #7f1d1d;
        }
        :root[data-pt-theme="light"] [data-anba-root] .cta-primary {
            background: linear-gradient(135deg, #6366f1 0%, #7c3aed 50%, #8b5cf6 100%);
            color: #ffffff;
            border-color: rgba(255,255,255,0.85);
            box-shadow:
                0 10px 24px -8px rgba(79,70,229,0.45),
                0 2px 4px -2px rgba(15,23,42,0.12),
                inset 0 1px 0 rgba(255,255,255,0.30);
        }
        :root[data-pt-theme="light"] [data-anba-root] .cta-primary:hover:not(:disabled) {
            box-shadow:
                0 14px 32px -8px rgba(79,70,229,0.65),
                0 0 24px rgba(124,58,237,0.30),
                inset 0 1px 0 rgba(255,255,255,0.30);
        }
        :root[data-pt-theme="light"] [data-anba-root] .cta-primary:disabled {
            background: linear-gradient(180deg, rgba(148,163,184,0.45), rgba(100,116,139,0.40));
            color: rgba(15,23,42,0.55);
        }
        /* Transfer instructions inline-style override (the inline
           rgba(8,10,20,0.55) is too dark on cream). */
        :root[data-pt-theme="light"] [data-anba-root] [data-anba-transfer] {
            background: rgba(255,255,255,0.78) !important;
            border-color: rgba(79,70,229,0.22) !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.95);
        }
        /* The wallet / insta info chips use bg-white/[0.04] which is invisible
           on cream; mark them so we can lift them in light mode. */
        :root[data-pt-theme="light"] [data-anba-root] [data-anba-payinfo] {
            background: rgba(15,23,42,0.04) !important;
            border-color: rgba(15,23,42,0.12) !important;
        }
        /* The "view more / less attendees" stack toggle. */
        :root[data-pt-theme="light"] [data-anba-root] .attendee-stack {
            background: rgba(15,23,42,0.04);
            border-color: rgba(15,23,42,0.14);
        }
    </style>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr),360px] gap-5 fs-grid">

        {{-- ===================== SEAT MAP ===================== --}}
        <section class="glass ambient p-4 sm:p-6 fs-mapwrap">
            @if ($isFullscreen)
                {{-- compact top bar shown only in fullscreen mode --}}
                <div class="fs-topbar">
                    <a href="{{ $adminMode ? $adminBackUrl : route('bookings.create', $showTime) }}" class="fs-back">
                        <span class="pt-arrow-rtl-back" aria-hidden="true">→</span>
                        <span data-i18n="seat_back">رجوع</span>
                    </a>
                    <span class="fs-title">
                        @if ($adminMode)
                            ◆ <span data-i18n="seat_admin_title">إدارة المقاعد</span> · {{ $showTime->show->title ?? '' }}
                        @else
                            ◆ <span data-i18n="seat_pick_title">اختار مقعدك</span>
                        @endif
                    </span>
                    <div class="zoom-bar">
                        <button type="button" class="zoom-btn" data-zoom="-1" data-i18n-attr="aria-label:seat_zoom_out" aria-label="تصغير">−</button>
                        <button type="button" class="zoom-btn" data-zoom="0"  data-i18n-attr="aria-label:seat_zoom_reset" aria-label="إعادة">⟳</button>
                        <button type="button" class="zoom-btn" data-zoom="1"  data-i18n-attr="aria-label:seat_zoom_in" aria-label="تكبير">+</button>
                    </div>
                </div>
            @else
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold"
                        style="background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        ◆ <span data-i18n="seat_map">خريطة المقاعد</span> · {{ $showTime->show->title ?? '' }}
                    </h2>
                    <div class="zoom-bar">
                        <button type="button" class="zoom-btn" data-zoom="-1" data-i18n-attr="aria-label:seat_zoom_out" aria-label="تصغير">−</button>
                        <button type="button" class="zoom-btn" data-zoom="0"  data-i18n-attr="aria-label:seat_zoom_reset" aria-label="إعادة">⟳</button>
                        <button type="button" class="zoom-btn" data-zoom="1"  data-i18n-attr="aria-label:seat_zoom_in" aria-label="تكبير">+</button>
                    </div>
                </div>
            @endif

            <div class="canvas-scroller" data-canvas-scroller>
                <canvas class="seat-canvas" data-seat-canvas
                        width="1400" height="700"
                        role="img"
                        data-i18n-attr="aria-label:seat_canvas_aria"
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
                        <div class="hint-text" data-i18n="seat_gesture_hint">استخدم إصبعين للتكبير والتحريك</div>
                        <div class="hint-sub">Pinch &amp; pan</div>
                    </div>
                </div>

                {{-- Edge "more seats" indicators. Pulsing chevrons on
                     the leading / trailing edges of the canvas viewport,
                     visible only when content extends off-screen on
                     that side. Mobile-only via CSS; toggled by JS as
                     the user pans / zooms. --}}
                <div class="edge-arrow start" data-anba-edge-arrow="start" aria-hidden="true">
                    <svg viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 1 L3 6 L8 11" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="edge-arrow end" data-anba-edge-arrow="end" aria-hidden="true">
                    <svg viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 1 L9 6 L4 11" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                @if ($isFullscreen)
                    {{-- Floating zoom controls (fullscreen mobile primary path).
                         Glass + neon, sits above the sticky CTA. --}}
                    <div class="canvas-fab" aria-hidden="false">
                        <button type="button" class="fab-btn" data-zoom="1"  data-i18n-attr="aria-label:seat_zoom_in"    aria-label="تكبير">+</button>
                        <button type="button" class="fab-btn" data-zoom="0"  data-i18n-attr="aria-label:seat_zoom_reset" aria-label="إعادة">⤢</button>
                        <button type="button" class="fab-btn" data-zoom="-1" data-i18n-attr="aria-label:seat_zoom_out"   aria-label="تصغير">−</button>
                    </div>

                    {{-- QW#7 (fullscreen): floating auto-pick chip. The
                         side-panel auto-pick button is hidden in fullscreen
                         mode (.fs-aside { display: none }), so customers
                         couldn't reach it. This chip is rendered on top of
                         the canvas at the leading-top edge so it's always
                         discoverable on the seat-picker page. --}}
                    @unless ($adminMode)
                        <button type="button"
                                data-anba-auto-pick
                                class="auto-pick-fab">
                            <span aria-hidden="true">✨</span>
                            <span data-i18n="seat_auto_pick">اختر أفضل المقاعد</span>
                        </button>
                    @endunless
                @endif
            </div>

            <p class="mt-3 text-center text-[11px] text-[color:var(--p-text-3)]"
               @if ($adminMode) data-i18n="seat_legend_hint_admin" @else data-i18n="seat_legend_hint_user" @endif>
                @if ($adminMode)
                    اسحب للتنقل · قرّب بإصبعين أو بضغطة مزدوجة · اضغط على أي مقعد لحظره أو فك حظره
                @else
                    اسحب للتنقل · قرّب بإصبعين أو بضغطة مزدوجة · المقاعد ذات الـ✕ مخصصة للإدارة
                @endif
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
                        <p class="font-semibold" style="color: var(--p-gold);">🛠️ <span data-i18n="seat_admin_mode">وضع الإدارة</span></p>
                    @else
                        <p class="font-semibold" style="color: var(--p-gold);">🎟️ {{ $hallPriceInt }} <span data-i18n="seat_per_seat">جنيه / مقعد</span></p>
                    @endif
                </div>
            </div>

            {{-- transfer instructions — customer flow only --}}
            @if (!$adminMode && (!empty($transferWallet) || !empty($transferInsta)))
                <div class="rounded-2xl p-3 space-y-2"
                     data-anba-transfer
                     style="background: rgba(8,10,20,0.55); border: 1px solid rgba(129,140,248,0.18);">
                    <h4 class="text-[11px] font-semibold"
                        style="background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc); -webkit-background-clip: text; background-clip: text; color: transparent;"
                        data-i18n="seat_step1_pay">
                        خطوة 1 · حوّل قيمة الحجز
                    </h4>
                    @if (!empty($transferWallet))
                        <div class="bg-white/[0.04] border border-[color:var(--p-border)] rounded-xl px-3 py-2" data-anba-payinfo>
                            <p class="text-[9px] text-[color:var(--p-text-3)] mb-0.5" data-i18n="pay_wallet">📱 محفظة</p>
                            <p class="text-xs font-bold text-[color:var(--p-text)]" dir="ltr">{{ $transferWallet }}</p>
                        </div>
                    @endif
                    @if (!empty($transferInsta))
                        <div class="bg-white/[0.04] border border-[color:var(--p-border)] rounded-xl px-3 py-2" data-anba-payinfo>
                            <p class="text-[9px] text-[color:var(--p-text-3)] mb-0.5" data-i18n="pay_insta">⚡ InstaPay</p>
                            <p class="text-xs font-bold text-[color:var(--p-text)]" dir="ltr">{{ $transferInsta }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <div>
                <h3 class="text-sm font-bold mb-2"
                    style="background: linear-gradient(135deg,#22d3ee,#818cf8,#c084fc); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    @if ($adminMode)
                        <span data-i18n="seat_admin_panel_title">إدارة المقاعد</span>
                    @else
                        <span data-i18n="seat_user_panel_title">اختار مقاعدك</span>
                    @endif
                </h3>
                <p class="text-[11px] text-[color:var(--p-text-3)] leading-relaxed"
                   @if ($adminMode) data-i18n="seat_admin_panel_desc" @else data-i18n="seat_user_panel_desc" @endif>
                    @if ($adminMode)
                        اضغط على المقاعد لتحديدها — رمادي يصبح مذهَّباً (محجوب للإدارة) والعكس صحيح. المقاعد المحجوزة من العملاء (وردية) لا يمكن تعديلها.
                    @else
                        اضغط على أي مقعد رمادي لاختياره. المقاعد ذات العلامة ✕ مخصصة للإدارة ولا يمكن حجزها.
                    @endif
                </p>
            </div>

            {{-- QW#7: auto-pick best seats — customer flow only.
                 Asks for a count, then picks N contiguous available seats
                 closest to the canvas centerline. --}}
            @unless ($adminMode)
                <button type="button"
                        data-anba-auto-pick
                        class="prism-auto-pick w-full">
                    <span aria-hidden="true">✨</span>
                    <span data-i18n="seat_auto_pick">اختر أفضل المقاعد</span>
                </button>
            @endunless

            {{-- legend --}}
            <div class="grid grid-cols-2 gap-2 text-[11px] text-[color:var(--p-text-2)]">
                <div class="legend-pill"><span class="legend-swatch avail"></span> <span data-i18n="seat_legend_available">متاح</span></div>
                <div class="legend-pill"><span class="legend-swatch sel"></span> <span data-i18n="seat_legend_selected">مختار</span></div>
                <div class="legend-pill"><span class="legend-swatch booked"></span> <span data-i18n="seat_legend_reserved">محجوز</span></div>
                <div class="legend-pill"><span class="legend-swatch admin"></span> <span data-i18n="seat_legend_admin">إدارة</span></div>
            </div>

            {{-- selection summary (chips + total) — read-only here; the
                 form on the next page is where attendees + payment go. --}}
            <div class="space-y-3">
                <div>
                    <div class="flex items-center justify-between text-[11px] text-[color:var(--p-text-3)] mb-1">
                        <span data-i18n="seat_selected_label">المقاعد المختارة</span>
                        <span data-selected-count>0</span>
                    </div>
                    <div data-selected-chips class="flex flex-wrap gap-1.5 min-h-[36px] p-2 rounded-xl"
                         style="background: rgba(8,10,20,0.55); border: 1px solid var(--p-border);">
                        <span class="text-[11px] text-[color:var(--p-text-4)]" data-empty-msg data-i18n="seat_none_selected">لم تختر أي مقعد بعد</span>
                    </div>
                </div>

                @unless ($adminMode)
                    <div class="flex items-center justify-between rounded-xl px-3 py-2"
                         style="background: linear-gradient(135deg, rgba(251,191,36,0.10), rgba(251,191,36,0.04));
                                border: 1px solid rgba(251,191,36,0.32); color: #fef3c7;">
                        <span class="text-[11px] uppercase" style="letter-spacing: .18em;" data-i18n="seat_total">الإجمالي</span>
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
                        <span data-cta-label data-i18n="seat_save_changes">حفظ التغييرات</span>
                        <span aria-hidden="true">✔</span>
                    @else
                        <span data-i18n="seat_complete_booking">إكمال الحجز</span>
                        <span class="pt-arrow-rtl" aria-hidden="true">←</span>
                    @endif
                </button>

                <a href="{{ $adminMode ? $adminBackUrl : route('bookings.create', $showTime) }}"
                   class="block text-center text-[11px] transition"
                   style="color: var(--p-text-3);"
                   onmouseover="this.style.color='var(--p-text)'"
                   onmouseout="this.style.color='var(--p-text-3)'">
                    @if ($adminMode)
                        <span class="pt-arrow-rtl-back" aria-hidden="true">→</span>
                        <span data-i18n="seat_back_shows_admin">رجوع لإدارة العروض</span>
                    @else
                        <span class="pt-arrow-rtl-back" aria-hidden="true">→</span>
                        <span data-i18n="seat_back_section">الرجوع لاختيار القسم</span>
                    @endif
                </a>
            </div>
        </aside>
    </div>

    {{-- mobile sticky CTA --}}
    <div class="mobile-cta">
        <div class="flex-1 min-w-0">
            <div class="text-[10px] text-[color:var(--p-text-3)]">
                @if ($adminMode)
                    <span data-i18n="seat_pending_changes">تغييرات معلَّقة</span>
                @else
                    <span data-i18n="seat_chip_selected">المختار</span>
                @endif
            </div>
            {{-- Count + (labels) on first line. The labels list is
                 written by JS in renderSidePanel(); it gracefully
                 truncates to one ellipsised line and stays empty
                 when nothing is selected. --}}
            <div class="text-sm font-bold text-[color:var(--p-text)] truncate">
                <span data-mobile-count>0</span> <span data-i18n="seat_chip_seat">مقعد</span>
                <span data-mobile-labels></span>
            </div>
            @unless ($adminMode)
                <div class="text-[11px] mt-0.5" style="color: var(--p-gold);">
                    <span data-mobile-total>0</span> EGP
                </div>
            @endunless
        </div>
        <button type="button" data-continue
                disabled
                class="cta-primary px-5 py-2 text-xs">
            @if ($adminMode)
                <span data-cta-label data-i18n="seat_save_changes">حفظ التغييرات</span>
            @else
                <span data-i18n="seat_complete_booking">إكمال الحجز</span>
            @endif
        </button>
    </div>

    <script type="application/json" data-seat-data>
        @php
            $payload = [$sectionParam => []];
            foreach ($sectionSeats as $row => $sides) {
                $payload[$sectionParam][$row] = [
                    'left'   => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['left']   ?? []),
                    'center' => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['center'] ?? []),
                    'right'  => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['right']  ?? []),
                ];
            }
        @endphp
        {!! json_encode($payload, JSON_UNESCAPED_UNICODE) !!}
    </script>

    {{-- Per-preset geometry config (ROWS_ORDER, RIGHT_SHIFT_STEPS,
         NO_CENTER_ANCHORS, admin-only rows). Read once by the JS engine
         to drive computeLayout() so the same engine renders every preset. --}}
    <script type="application/json" data-seat-preset>
        {!! json_encode($preset, JSON_UNESCAPED_UNICODE) !!}
    </script>

    {{-- Auto-pick chip modal (mobile-first, replaces native prompt()).
         Hidden by default; toggled .is-open from JS. The grid is filled
         dynamically with N chips (1..max). All copy localized via
         data-i18n so AR/EN switches live without re-render. --}}
    @unless ($adminMode)
        <div class="anba-modal-backdrop"
             data-anba-modal
             role="dialog"
             aria-modal="true"
             aria-labelledby="anba-modal-title"
             hidden>
            <div class="anba-modal-card" role="document">
                <div class="anba-modal-eyebrow" data-i18n="seat_auto_pick_eyebrow">اختيار سريع</div>
                <h2 id="anba-modal-title" class="anba-modal-title" data-i18n="seat_auto_pick_prompt">كم مقعد تريد؟</h2>
                <div class="anba-modal-grid" data-anba-modal-grid></div>
                <button type="button" class="anba-modal-cancel" data-anba-modal-cancel data-i18n="seat_auto_pick_cancel">إلغاء</button>
            </div>
        </div>
    @endunless
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
        // Per-preset geometry (rowsOrder, rightShiftSteps, noCenterAnchors,
        // adminOnlyCenter). The JS engine is identical across presets — only
        // these values change per layout. Falls back to a hall-shaped default
        // if the preset script is missing for any reason.
        const presetEl     = root.querySelector('[data-seat-preset]');
        const PRESET       = presetEl
            ? JSON.parse(presetEl.textContent)
            : {
                rowsOrder: ['A','B','C','D','E','F','G','H','GAP','I','J','K','L','M','N','O','P','Q','GAP_HALF','R'],
                rightShiftSteps: {},
                noCenterAnchors: {},
                adminOnlyCenter: [],
            };
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
        const mobileLabels = root.querySelector('[data-mobile-labels]');

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
        // multiplied by STEP). Row Q is the anchor (offset = 0); rows in
        // FRONT of Q step further OUT (positive steps), and row R (behind
        // Q) is staggered IN (negative step) so its innermost seats sit
        // half a seat inside Q's inner edge — a natural back-row stagger.
        // No interpolation, no stagger-within-wing, no bias — every seat
        // in a wing moves by exactly the same amount, so the wing keeps
        // its natural SEAT_PITCH spacing.
        //
        // To find where the math runs, search the file for:
        //   ===== WING OFFSET (LEFT)  =====
        //   ===== WING OFFSET (RIGHT) =====
        // ROWS_ORDER + RIGHT_SHIFT_STEPS come from the active preset
        // (PHP-side $presets registry → data-seat-preset script). This is
        // the ONLY thing that changes per theater layout — the rest of
        // the engine (computeLayout, drawing, gestures, hit-testing,
        // auto-pick, admin flow) is identical across every preset.
        const ROWS_ORDER       = Array.isArray(PRESET.rowsOrder) ? PRESET.rowsOrder : [];
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
        // widths), supplied by the active preset. The same value is used
        // for both left and right wings so the layout stays symmetric.
        // Inner-edge seats keep their natural SEAT_PITCH spacing; only
        // the spread of the wing grows.
        const RIGHT_SHIFT_STEPS = (PRESET.rightShiftSteps && typeof PRESET.rightShiftSteps === 'object')
            ? PRESET.rightShiftSteps
            : {};

        // ===== NO-CENTER ANCHORS =====
        // Per-preset map: row letter (without center column) → row letter
        // to anchor each wing's inner edge to. The anchor row's center
        // column position is reused so the layout stays vertically
        // aligned across the gap. Empty for presets where every row has
        // a center column.
        const NO_CENTER_ANCHORS = (PRESET.noCenterAnchors && typeof PRESET.noCenterAnchors === 'object')
            ? PRESET.noCenterAnchors
            : {};

        // ===== ADMIN-ONLY CENTER =====
        // Rows whose center seats are reserved for admin use only and
        // therefore skipped during render (e.g. row Q in hall).
        const ADMIN_ONLY_CENTER = new Set(Array.isArray(PRESET.adminOnlyCenter) ? PRESET.adminOnlyCenter : []);

        const STAGE_H          = 70;
        const TOP_PAD          = 28;
        const ROW_AREA_TOP     = TOP_PAD + STAGE_H + 28;  // first row baseline (y of row A center)
        const SIDE_PAD         = 36;

        // Display size (CSS pixels). Will be scaled by devicePixelRatio internally.
        // Width is wide enough to fit the front rows' full progressive offset.
        // Height accounts for the back row (R) plus the GAP_HALF separator
        // between Q and R (~22.5 px) plus a comfortable bottom buffer so the
        // last row never clips on mobile fit-to-screen.
        let DISPLAY_W = 1400;
        let DISPLAY_H = 740;
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

            const rows = seatData[sectionParam] || {};

            let visualRow = 0;

            ROWS_ORDER.forEach((letter, idx) => {
                if (letter === 'GAP') {
                    visualRow += 1.5; // walkway / section break (geometry, not CSS)
                    return;
                }
                if (letter === 'GAP_HALF') {
                    // Half-size separator (0.75×ROW_PITCH ≈ 22.5 px extra).
                    // Subtle visual break, NOT a walkway — used between Q
                    // and R in the hall preset to telegraph R's stagger.
                    visualRow += 0.75;
                    return;
                }
                const data = rows[letter];
                if (!data) return;

                const cL = (data.left   || []).length;
                // Some rows have admin-only management blocks in the center
                // (e.g. row Q in hall). Skip rendering those so the row is
                // two wings only.
                const cC = ADMIN_ONLY_CENTER.has(letter) ? 0 : (data.center || []).length;
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
                //   - rows w/ no center → anchor to the preset's
                //     NO_CENTER_ANCHORS[letter] row (e.g. hall row A
                //     anchors to row B's center column; balcony rows
                //     D–H anchor to row C's center column)
                //   - rows w/ center → 2 × AISLE_GAP around centerStartX
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

        // Resolve a tap/click on the canvas to a seat-toggle. Extracted
        // so we can call it from both the native `click` event AND the
        // pointerup-based tap detector below — `e.preventDefault()` on
        // pointerdown (needed to suppress browser pan/scroll) blocks the
        // synthetic click on desktop in some browsers, which previously
        // made desktop seat selection silently unresponsive.
        function handleCanvasTap(evt) {
            const { x, y } = pointToCanvas(evt);
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
        }

        canvas.addEventListener('click', (e) => {
            // Skipped if a tap was already handled via pointerup (mouse).
            if (canvas.__tapHandled) { canvas.__tapHandled = false; return; }
            if (suppressClick) return;
            handleCanvasTap(e);
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
                // QW#12: light haptic on seat selection (Android only).
                if (navigator.vibrate) { try { navigator.vibrate(8); } catch (_) {} }
            }
            triggerPop(s.id);
            renderSidePanel();
            requestRedraw();
        }

        // ===== QW#7: auto-pick best N seats =====
        // Strategy:
        //   1. Group seats by row (in visual order, front rows first).
        //   2. For each row, sort seats left→right by x.
        //   3. Mark seats available iff getState(seat) === 'available'.
        //   4. Slide a window of size N over each row's available seats and
        //      keep only contiguous windows (no booked/admin gaps inside).
        //   5. Score each candidate window by:
        //         rowBonus  — earlier rows score higher (closer to stage)
        //         centerPen — distance of window's mean-x from CX
        //      Final score = rowBonus * 1000 - centerPen.
        //   6. Pick the highest-scoring window.
        function autoPickBestSeats(N) {
            if (!isFinite(N) || N <= 0) return null;

            // Group seats by row in insertion order (front→back since
            // SEATS was built by iterating ROWS_ORDER in computeLayout).
            const groups = new Map();
            for (const s of SEATS) {
                if (!groups.has(s.row)) groups.set(s.row, []);
                groups.get(s.row).push(s);
            }
            const rowList = Array.from(groups.keys());

            let best = null; // { score, ids:[] }
            rowList.forEach((rowLetter, rowIdx) => {
                const seatsInRow = groups.get(rowLetter).slice().sort((a, b) => a.x - b.x);
                if (seatsInRow.length < N) return;

                for (let i = 0; i + N <= seatsInRow.length; i++) {
                    const window = seatsInRow.slice(i, i + N);

                    // All must be available + adjacent on the canvas.
                    let ok = true;
                    let sumX = 0;
                    for (let j = 0; j < window.length; j++) {
                        if (getState(window[j]) !== 'available') { ok = false; break; }
                        if (j > 0) {
                            const dx = Math.abs(window[j].x - window[j - 1].x);
                            // Allow up to 1.6× SEAT_PITCH for tolerated gap;
                            // anything bigger is an aisle/section break.
                            if (dx > SEAT_PITCH * 1.6) { ok = false; break; }
                        }
                        sumX += window[j].x;
                    }
                    if (!ok) continue;

                    const meanX = sumX / window.length;
                    const centerPen = Math.abs(meanX - CX);
                    const rowBonus = rowList.length - rowIdx;
                    const score = rowBonus * 1000 - centerPen;

                    if (!best || score > best.score) {
                        best = { score, seats: window };
                    }
                }
            });
            return best;
        }

        function applyAutoPickN(N) {
            const t = window.PT_T || ((k) => k);
            const result = autoPickBestSeats(N);
            if (!result) {
                if (window.PT && window.PT.toast) {
                    window.PT.toast(t('seat_auto_pick_none'), 2200);
                }
                return false;
            }
            // Clear any prior selection then pick the chosen window.
            selected.clear();
            result.seats.forEach((s) => {
                selected.set(s.id, { row: s.row, n: s.n });
                triggerPop(s.id);
            });
            if (navigator.vibrate) { try { navigator.vibrate(12); } catch (_) {} }
            renderSidePanel();
            requestRedraw();
            if (window.PT && window.PT.toast) {
                window.PT.toast(t('seat_auto_pick_done'), 1800);
            }
            // Smoothly pan the canvas so the picked seats end up centered
            // in the viewport. This is the visual confirmation that the
            // user *can* see what got picked, even when the seats were
            // off-screen at the moment of auto-pick. Looked up by id in
            // seatMeta (the layout-computed cache) which already has
            // canvas-local x/y coords for every seat.
            const seatList = result.seats
                .map((s) => seatMeta.get(s.id))
                .filter(Boolean);
            if (seatList.length && typeof panToSeats === 'function') {
                // Defer one frame so the bottom-bar render + redraw
                // run first; the camera move then feels like a
                // confirmation rather than racing the UI.
                requestAnimationFrame(() => panToSeats(seatList));
            }
            return true;
        }

        // Auto-pick chip modal — replaces window.prompt() with a
        // touch-first chip grid. Keyboard: Escape closes; Enter on a
        // chip activates it. The grid is populated lazily on first open
        // and re-uses the same DOM thereafter.
        const AUTO_PICK_MAX = 12;
        const modal     = root.querySelector('[data-anba-modal]');
        const modalGrid = root.querySelector('[data-anba-modal-grid]');
        const modalCancel = root.querySelector('[data-anba-modal-cancel]');
        let modalOpen = false;
        let lastFocus = null;

        function ensureModalGrid() {
            if (!modalGrid || modalGrid.children.length) return;
            for (let i = 1; i <= AUTO_PICK_MAX; i++) {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'anba-modal-chip';
                chip.textContent = String(i);
                chip.dataset.n = String(i);
                modalGrid.appendChild(chip);
            }
        }

        function openModal(triggerBtn) {
            if (!modal) return;
            ensureModalGrid();
            lastFocus = triggerBtn || document.activeElement;
            modal.hidden = false;
            // double-rAF so the .is-open transition kicks in cleanly
            requestAnimationFrame(() => requestAnimationFrame(() => {
                modal.classList.add('is-open');
            }));
            modalOpen = true;
            const firstChip = modalGrid && modalGrid.firstElementChild;
            if (firstChip) firstChip.focus({ preventScroll: true });
        }

        function closeModal() {
            if (!modal || !modalOpen) return;
            modal.classList.remove('is-open');
            modalOpen = false;
            // Wait for the fade-out, then fully hide so it doesn't
            // intercept taps.
            setTimeout(() => {
                if (!modalOpen && modal) modal.hidden = true;
            }, 220);
            if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus({ preventScroll: true });
            }
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            if (modalCancel) {
                modalCancel.addEventListener('click', closeModal);
            }
            if (modalGrid) {
                modalGrid.addEventListener('click', (e) => {
                    const chip = e.target.closest('[data-n]');
                    if (!chip) return;
                    const n = parseInt(chip.dataset.n, 10);
                    if (!isFinite(n) || n <= 0 || n > AUTO_PICK_MAX) return;
                    closeModal();
                    applyAutoPickN(n);
                });
            }
            document.addEventListener('keydown', (e) => {
                if (!modalOpen) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeModal();
                }
            });
        }

        // Wire up auto-pick buttons. There may be more than one button —
        // the side-panel one is hidden in fullscreen, so a second copy is
        // rendered as a floating chip on the canvas. Same modal + same
        // N for both. If the modal element is missing (admin mode), fall
        // back to the legacy native prompt so the feature still works.
        root.querySelectorAll('[data-anba-auto-pick]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (modal) {
                    openModal(btn);
                    return;
                }
                const t = window.PT_T || ((k) => k);
                const raw = window.prompt(t('seat_auto_pick_prompt'), '2');
                if (raw === null) return;
                const n = parseInt(String(raw).trim(), 10);
                if (!isFinite(n) || n <= 0 || n > AUTO_PICK_MAX) return;
                applyAutoPickN(n);
            });
        });

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

            // Inline seat-label preview for the mobile CTA so the user
            // can verify the actual seats picked (not just the count).
            // Sorts by row + number, prefixes with " · ", truncates to 6
            // labels with "+N" suffix when more are selected. Cleared
            // when the selection is empty.
            if (mobileLabels) {
                if (n === 0) {
                    mobileLabels.textContent = '';
                } else {
                    const sorted = ids.slice().sort((a, b) => {
                        const ma = selected.get(a), mb = selected.get(b);
                        if (ma.row !== mb.row) return ma.row < mb.row ? -1 : 1;
                        return ma.n - mb.n;
                    });
                    const PREVIEW = 6;
                    const head = sorted.slice(0, PREVIEW).map(id => {
                        const meta = selected.get(id);
                        return meta.row + meta.n;
                    });
                    const rest = n - head.length;
                    const str = head.join(', ') + (rest > 0 ? ' +' + rest : '');
                    mobileLabels.textContent = ' · ' + str;
                }
            }

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
                // Don't capture from the floating FAB, zoom-bar buttons, or
                // the auto-pick chip — those have their own click handlers
                // and capturing the pointer here would also trigger a stray
                // seat-tap synthesis on pointerup.
                if (e.target.closest('.canvas-fab, .zoom-bar, .auto-pick-fab')) return;
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

            scroller.addEventListener('pointerup', (e) => {
                // Capture state BEFORE endPointer mutates it.
                const wasOurPointer = pointers.has(e.pointerId);
                const wasNotDrag    = !suppressClick;
                const wasOnlyOne    = pointers.size === 1;
                endPointer(e);
                // Synthesize a tap from pointerup for ALL pointer types
                // (mouse, pen, AND touch). Calling preventDefault() on
                // pointerdown to suppress native scroll/pan also blocks
                // the subsequent synthetic `click` event in some browsers
                // — we observed this on desktop after PR #57 and on iOS
                // Safari after PR #58. Going through pointerup is the
                // single deterministic path. The `__tapHandled` flag +
                // 350 ms reset window keeps a stray browser-fired click
                // from double-toggling the seat without permanently
                // suppressing future clicks if no click ever arrives.
                if (wasOurPointer && wasOnlyOne && wasNotDrag) {
                    canvas.__tapHandled = true;
                    setTimeout(() => { canvas.__tapHandled = false; }, 350);
                    handleCanvasTap(e);
                }
            });
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
        // Mobile-only. Subtle glass card centered over the seat map that
        // reminds users the canvas extends beyond the viewport and they
        // can pinch / drag to explore.
        // - mobile viewport only (<880px)
        // - touch-capable devices only
        // - shown EVERY time the seat picker opens (no persistent
        //   dismissal). Many users only enter the picker once every few
        //   weeks/months and forget the gestures, so the hint is treated
        //   as recurring onboarding, not a one-shot tutorial.
        // - auto-dismisses after 7s
        // - dismisses on first real touch anywhere in the seat picker
        // - the hint itself is `pointer-events: none`, so the underlying
        //   gesture passes straight through to the canvas
        // - prefers-reduced-motion handled in CSS (no idle animation)
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

            // Show shortly after init so the canvas has rendered and any
            // entrance animation has settled. 380ms feels intentional, not
            // jumpy — pairs with the inline `<style>` hintCardIn anim.
            setTimeout(() => {
                if (!dismissed) hint.classList.add('is-visible');
            }, 380);

            // Any real touch anywhere in the seat picker dismisses the
            // hint (canvas, scroller, FABs, side panel). `once: true`
            // auto-removes the listener after the first fire so there's
            // zero ongoing overhead. Listening on `root` is broader than
            // just `scroller` so taps on the floating zoom buttons or the
            // auto-pick FAB also count as "the user knows what to do".
            const dismissOpts = { passive: true, once: true };
            root.addEventListener('touchstart', dismiss, dismissOpts);
            root.addEventListener('pointerdown', dismiss, dismissOpts);
            // Auto-dismiss after 7s so the hint never lingers if the user
            // is reading the page without panning yet.
            setTimeout(dismiss, 7000);
        })();

        // ===== "More seats" edge arrows =====
        // Show pulsing chevrons on the leading / trailing edges of the
        // scroller whenever the canvas extends past that edge, so the
        // user knows there's content to pan toward. Hidden on desktop
        // via CSS (>=880px). Updated on every gesture / zoom / resize.
        (function setupEdgeArrows() {
            const startArrow = root.querySelector('[data-anba-edge-arrow="start"]');
            const endArrow   = root.querySelector('[data-anba-edge-arrow="end"]');
            if (!startArrow || !endArrow) return;

            // Tolerance — within this many CSS px of the edge we
            // consider the content "fully visible" and hide the arrow.
            const EDGE_EPS = 6;

            function update() {
                const sw = scroller.clientWidth;
                const cw = DISPLAY_W * zoomLevel;
                // panX is the canvas-left offset relative to scroller-left.
                // If panX < -EDGE_EPS the canvas extends off the leading
                // edge → show the start arrow. If (panX + cw) > sw + EDGE_EPS
                // the canvas extends off the trailing edge → show the
                // end arrow.
                const overflowStart = panX < -EDGE_EPS;
                const overflowEnd   = (panX + cw) > sw + EDGE_EPS;
                startArrow.classList.toggle('is-visible', overflowStart);
                endArrow.classList.toggle('is-visible',   overflowEnd);
            }

            // The gesture pipeline writes panX/panY/zoomLevel and then
            // calls applyTransform() at the end of every frame. We can't
            // monkey-patch applyTransform from outside its closure, so
            // we instead schedule an arrow-visibility recompute on every
            // pointer / wheel / resize event via rAF (which keeps cost
            // capped at one update per frame regardless of event rate).
            let rafScheduled = false;
            function scheduleUpdate() {
                if (rafScheduled) return;
                rafScheduled = true;
                requestAnimationFrame(() => {
                    rafScheduled = false;
                    update();
                });
            }
            scroller.addEventListener('pointermove', scheduleUpdate, { passive: true });
            scroller.addEventListener('pointerup',   scheduleUpdate, { passive: true });
            scroller.addEventListener('wheel',       scheduleUpdate, { passive: true });
            window.addEventListener('resize', scheduleUpdate);
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', scheduleUpdate);
            }
            // Publish so non-pointer flows (auto-pick pan animation,
            // double-tap fit) can also keep arrows in sync.
            window.__ANBA_EDGE_ARROWS_UPDATE = scheduleUpdate;
            // Initial paint
            requestAnimationFrame(update);
            // Also re-check after the boot fit (which runs in a deferred
            // rAF) — at this point the layout has stabilized.
            setTimeout(scheduleUpdate, 80);
            setTimeout(scheduleUpdate, 320);
        })();

        // ===== Pan to selection =====
        // Smoothly tween panX/panY so a group of seats ends up centered
        // in the viewport. Used after auto-pick so the user can see the
        // seats that got picked even if they were off-screen. Skipped
        // under prefers-reduced-motion — we just snap.
        function panToSeats(seatList) {
            if (!Array.isArray(seatList) || seatList.length === 0) return;
            // centroid in canvas-local coords
            let cx = 0, cy = 0, count = 0;
            for (const s of seatList) {
                if (!s || typeof s.x !== 'number') continue;
                cx += s.x; cy += s.y; count++;
            }
            if (count === 0) return;
            cx /= count; cy /= count;
            // Target pan such that the centroid lands at scroller center.
            const sw = scroller.clientWidth;
            const sh = scroller.clientHeight;
            const targetX = sw / 2 - cx * zoomLevel;
            const targetY = sh / 2 - cy * zoomLevel;
            const startX = panX, startY = panY;
            const dx = targetX - startX, dy = targetY - startY;
            // Bail out for tiny motions — keeps animation cost zero
            // when the seats were already near center.
            if (Math.hypot(dx, dy) < 4) {
                panX = targetX; panY = targetY;
                clampPan(); applyTransform();
                if (typeof window.__ANBA_EDGE_ARROWS_UPDATE === 'function') {
                    window.__ANBA_EDGE_ARROWS_UPDATE();
                }
                return;
            }
            if (reducedMotion) {
                panX = targetX; panY = targetY;
                clampPan(); applyTransform();
                if (typeof window.__ANBA_EDGE_ARROWS_UPDATE === 'function') {
                    window.__ANBA_EDGE_ARROWS_UPDATE();
                }
                return;
            }
            const DUR = 420; // ms
            const t0 = performance.now();
            function step(now) {
                const u = Math.min(1, (now - t0) / DUR);
                // easeOutCubic — premium settle
                const k = 1 - Math.pow(1 - u, 3);
                panX = startX + dx * k;
                panY = startY + dy * k;
                clampPan();
                applyTransform();
                // Keep the edge-arrow visibility in sync with the camera
                // move. Throttled internally to one update per frame.
                if (typeof window.__ANBA_EDGE_ARROWS_UPDATE === 'function') {
                    window.__ANBA_EDGE_ARROWS_UPDATE();
                }
                if (u < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }
        // Publish on a stable symbol so the auto-pick handler can call
        // it across closure boundaries.
        window.__ANBA_PAN_TO_SEATS = panToSeats;

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
