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
    // Produce a stable A→R order so the script doesn't have to sort.
    ksort($hallSeats);
@endphp

<div data-anba-root
     @if ($isFullscreen) data-fullscreen="1" @endif
     data-hall-price="{{ $unitPrice }}"
     data-section="{{ $sectionParam }}"
     data-show-time-id="{{ (int) $showTime->id }}"
     data-form-url="{{ route('bookings.form', $showTime) }}?section={{ $sectionParam }}"
     data-back-url="{{ route('bookings.create', $showTime) }}"
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

        /* ===== Canvas wrapper ===== */
        [data-anba-root] .canvas-scroller {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y;
            scrollbar-width: thin;
            scrollbar-color: rgba(129,140,248,0.55) transparent;
            border-radius: 18px;
            background:
                radial-gradient(ellipse 90% 60% at 50% 0%, rgba(34,211,238,0.10), transparent 60%),
                radial-gradient(ellipse 60% 40% at 50% 110%, rgba(192,132,252,0.06), transparent 60%),
                linear-gradient(180deg, #06081a, #03050d);
            border: 1px solid var(--p-border);
            position: relative;
        }
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
        }
        [data-anba-root] .canvas-scroller::-webkit-scrollbar { height: 6px; }
        [data-anba-root] .canvas-scroller::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, rgba(34,211,238,0.6), rgba(192,132,252,0.6));
            border-radius: 999px;
        }

        [data-anba-root] canvas.seat-canvas {
            display: block;
            cursor: pointer;
            margin: 0 auto;
            user-select: none;
            position: relative;
            z-index: 1;
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

        /* ===== Sticky mobile CTA ===== */
        [data-anba-root] .mobile-cta {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 60;
            display: none;
            padding: 10px 14px;
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            background: linear-gradient(180deg, rgba(5,6,13,0.78), rgba(5,6,13,0.95));
            border-top: 1px solid rgba(129,140,248,0.32);
            align-items: center;
            gap: 10px;
            transform: translateY(0);
            transition: transform .25s var(--p-ease);
        }
        @media (max-width: 1023px) {
            [data-anba-root].has-selection .mobile-cta { display: flex; }
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
            overflow: hidden;          /* no scrollbars in fullscreen mode */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            border: 1px solid var(--p-border);
        }
        [data-anba-root][data-fullscreen="1"] canvas.seat-canvas {
            margin: auto;
        }
        /* hide the small "scroll hint" text + hover status under the map in
           fullscreen — it's noise on a small screen. */
        [data-anba-root][data-fullscreen="1"] .fs-mapwrap > p { display: none; }

        /* sticky bottom bar (always visible in fullscreen, both desktop +
           mobile, since the side panel is gone). */
        [data-anba-root][data-fullscreen="1"] .mobile-cta {
            position: relative;
            display: flex;
            inset: auto;
            z-index: 1;
            padding: 10px 12px;
            margin: 0;
            background: linear-gradient(180deg, rgba(5,6,13,0.78), rgba(5,6,13,0.95));
            border-top: 1px solid rgba(129,140,248,0.32);
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            padding-bottom: max(10px, env(safe-area-inset-bottom));
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
                    <a href="{{ route('bookings.create', $showTime) }}" class="fs-back">
                        <span aria-hidden="true">→</span>
                        رجوع
                    </a>
                    <span class="fs-title">
                        ◆ اختار مقعدك
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
            </div>

            <p class="mt-3 text-center text-[11px] text-[color:var(--p-text-3)]">
                مرّر أفقياً أو استعمل أزرار التكبير على الموبايل · المقاعد ذات الـ✕ مخصصة للإدارة
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
                    <p class="font-semibold" style="color: var(--p-gold);">🎟️ {{ $hallPriceInt }} جنيه / مقعد</p>
                </div>
            </div>

            {{-- transfer instructions --}}
            @if (!empty($transferWallet) || !empty($transferInsta))
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
                    اختار مقاعدك
                </h3>
                <p class="text-[11px] text-[color:var(--p-text-3)] leading-relaxed">
                    اضغط على أي مقعد رمادي لاختياره. المقاعد ذات العلامة ✕ مخصصة للإدارة ولا يمكن حجزها.
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

                <div class="flex items-center justify-between rounded-xl px-3 py-2"
                     style="background: linear-gradient(135deg, rgba(251,191,36,0.10), rgba(251,191,36,0.04));
                            border: 1px solid rgba(251,191,36,0.32); color: #fef3c7;">
                    <span class="text-[11px] uppercase" style="letter-spacing: .18em;">الإجمالي</span>
                    <span class="text-base font-bold" style="color: var(--p-gold);">
                        <span data-total-price>0</span> <span class="text-[10px] opacity-80">EGP</span>
                    </span>
                </div>

                <button type="button"
                        id="anbaContinueBtn"
                        data-continue
                        disabled
                        class="cta-primary w-full">
                    إكمال الحجز
                    <span aria-hidden="true">←</span>
                </button>

                <a href="{{ route('bookings.create', $showTime) }}"
                   class="block text-center text-[11px] transition"
                   style="color: var(--p-text-3);"
                   onmouseover="this.style.color='var(--p-text)'"
                   onmouseout="this.style.color='var(--p-text-3)'">
                    → الرجوع لاختيار القسم
                </a>
            </div>
        </aside>
    </div>

    {{-- mobile sticky CTA --}}
    <div class="mobile-cta">
        <div class="flex-1">
            <div class="text-[10px] text-[color:var(--p-text-3)]">المختار</div>
            <div class="text-sm font-bold text-[color:var(--p-text)]">
                <span data-mobile-count>0</span> مقعد ·
                <span style="color: var(--p-gold);"><span data-mobile-total>0</span> EGP</span>
            </div>
        </div>
        <button type="button" data-continue
                disabled
                class="cta-primary px-5 py-2 text-xs">إكمال الحجز</button>
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
        let zoomLevel    = 1;

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
                fill: ['#34d399', '#047857'],
                stroke: 'rgba(167,243,208,0.95)',
                text: '#ecfdf5',
                shadow: { color: 'rgba(16,185,129,0.95)', blur: 18 }
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

            // hover lift
            if (isHovered && state === 'available') {
                ctx.translate(0, -2);
            }

            // glow shadow for selected (or hovered available)
            if (styles.shadow) {
                ctx.shadowColor = styles.shadow.color;
                ctx.shadowBlur  = styles.shadow.blur;
            } else if (isHovered && state === 'available') {
                // PRISM hover glow — indigo / cyan.
                ctx.shadowColor = 'rgba(129,140,248,0.65)';
                ctx.shadowBlur  = 12;
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

            // border
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur  = 0;
            ctx.lineWidth   = 1;
            ctx.strokeStyle = styles.stroke;
            roundedRect(ctx, -w/2, -h/2, w, h, rx, ry);
            ctx.stroke();

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
                    canvas.style.cursor = (st === 'available') ? 'pointer' : 'not-allowed';
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
            if (st !== 'available' && st !== 'selected') return;
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
            renderSidePanel();
            requestRedraw();
        }

        // ===== Side panel rendering (chips, attendees, total, mobile bar) =====
        function renderSidePanel() {
            const ids = Array.from(selected.keys());
            const n = ids.length;

            countEl.textContent     = n;
            totalEl.textContent     = (n * hallPrice).toLocaleString('en-US');
            mobileCount.textContent = n;
            mobileTotal.textContent = (n * hallPrice).toLocaleString('en-US');
            root.classList.toggle('has-selection', n > 0);

            // chips
            chipsBox.innerHTML = '';
            if (n === 0) {
                const m = document.createElement('span');
                m.className = 'text-[11px] text-gray-500';
                m.textContent = 'لم تختر أي مقعد بعد';
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
                    chip.innerHTML = `<span>${meta.row}${meta.n}</span><button type="button" aria-label="إلغاء" data-remove="${id}">✕</button>`;
                    chipsBox.appendChild(chip);
                });
            }

            updateContinueButton();
        }

        function updateContinueButton() {
            const ready = selected.size > 0;
            continueBtns.forEach(btn => { btn.disabled = !ready; });
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

        continueBtns.forEach(btn => btn.addEventListener('click', saveAndContinue));

        // Restore prior selection (e.g. user came back from the form page).
        try {
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

        function applyZoomCss() {
            canvas.style.width  = (DISPLAY_W * zoomLevel) + 'px';
            canvas.style.height = (DISPLAY_H * zoomLevel) + 'px';
        }

        function fitToViewport() {
            const w = scroller.clientWidth;
            const h = scroller.clientHeight;
            if (w <= 0 || h <= 0) return;
            const z = Math.min(w / DISPLAY_W, h / DISPLAY_H);
            zoomLevel = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, z));
            applyZoomCss();
        }

        root.querySelectorAll('[data-zoom]').forEach(btn => {
            btn.addEventListener('click', () => {
                const dir = parseInt(btn.dataset.zoom, 10);
                if (dir === 0) {
                    if (isFullscreen) fitToViewport();
                    else { zoomLevel = 1; applyZoomCss(); }
                } else {
                    zoomLevel = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, zoomLevel + dir * 0.1));
                    applyZoomCss();
                }
            });
        });

        // ===== Native pinch-to-zoom + drag-to-pan (canvas viewport only;
        //       seat geometry / computeLayout / RIGHT_SHIFT_STEPS untouched) =====
        (function () {
            let pinchActive   = false;
            let panActive     = false;
            let startDist     = 0;
            let startZoom     = 1;
            let pinchCenter   = { x: 0, y: 0 };
            let startScroll   = { left: 0, top: 0 };
            let panLast       = { x: 0, y: 0 };
            let panStartTs    = 0;
            let suppressClick = false;

            function dist(t1, t2) {
                const dx = t1.clientX - t2.clientX;
                const dy = t1.clientY - t2.clientY;
                return Math.hypot(dx, dy);
            }
            function center(t1, t2) {
                return { x: (t1.clientX + t2.clientX) / 2, y: (t1.clientY + t2.clientY) / 2 };
            }

            scroller.addEventListener('touchstart', function (e) {
                if (e.touches.length === 2) {
                    pinchActive = true;
                    panActive   = false;
                    startDist   = dist(e.touches[0], e.touches[1]);
                    startZoom   = zoomLevel;
                    const r     = scroller.getBoundingClientRect();
                    const c     = center(e.touches[0], e.touches[1]);
                    pinchCenter = { x: c.x - r.left + scroller.scrollLeft,
                                    y: c.y - r.top  + scroller.scrollTop };
                    e.preventDefault();
                } else if (e.touches.length === 1) {
                    panActive   = true;
                    pinchActive = false;
                    panLast     = { x: e.touches[0].clientX, y: e.touches[0].clientY };
                    startScroll = { left: scroller.scrollLeft, top: scroller.scrollTop };
                    panStartTs  = Date.now();
                }
            }, { passive: false });

            scroller.addEventListener('touchmove', function (e) {
                if (pinchActive && e.touches.length === 2) {
                    const d = dist(e.touches[0], e.touches[1]);
                    if (startDist > 0) {
                        const factor = d / startDist;
                        const newZoom = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, startZoom * factor));
                        const ratio = newZoom / zoomLevel;
                        zoomLevel = newZoom;
                        applyZoomCss();
                        // keep pinch center stationary
                        scroller.scrollLeft = pinchCenter.x * ratio - (pinchCenter.x - scroller.scrollLeft);
                        scroller.scrollTop  = pinchCenter.y * ratio - (pinchCenter.y - scroller.scrollTop);
                        pinchCenter.x *= ratio;
                        pinchCenter.y *= ratio;
                    }
                    e.preventDefault();
                } else if (panActive && e.touches.length === 1) {
                    const dx = e.touches[0].clientX - panLast.x;
                    const dy = e.touches[0].clientY - panLast.y;
                    if (Math.abs(dx) + Math.abs(dy) > 4) suppressClick = true;
                    // native scroll handles this; we just update last for click suppression
                    panLast = { x: e.touches[0].clientX, y: e.touches[0].clientY };
                }
            }, { passive: false });

            function endPinch() {
                pinchActive = false;
                panActive   = false;
                setTimeout(() => suppressClick = false, 80);
            }
            scroller.addEventListener('touchend',    endPinch);
            scroller.addEventListener('touchcancel', endPinch);

            // suppress synthetic click after a pan
            canvas.addEventListener('click', function (e) {
                if (suppressClick) {
                    e.stopPropagation();
                    e.preventDefault();
                    suppressClick = false;
                }
            }, true);

            // mouse wheel zoom (desktop convenience; ctrl/cmd + wheel)
            scroller.addEventListener('wheel', function (e) {
                if (!(e.ctrlKey || e.metaKey)) return;
                e.preventDefault();
                const r = scroller.getBoundingClientRect();
                const cx = e.clientX - r.left + scroller.scrollLeft;
                const cy = e.clientY - r.top  + scroller.scrollTop;
                const oldZoom = zoomLevel;
                const delta = -e.deltaY * 0.0015;
                zoomLevel = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, zoomLevel * (1 + delta)));
                if (zoomLevel === oldZoom) return;
                applyZoomCss();
                const ratio = zoomLevel / oldZoom;
                scroller.scrollLeft = cx * ratio - (cx - scroller.scrollLeft);
                scroller.scrollTop  = cy * ratio - (cy - scroller.scrollTop);
            }, { passive: false });

            // double-tap to zoom (mobile)
            let lastTap = 0;
            scroller.addEventListener('touchend', function (e) {
                const now = Date.now();
                if (now - lastTap < 280 && e.changedTouches.length === 1) {
                    const r = scroller.getBoundingClientRect();
                    const t = e.changedTouches[0];
                    const cx = t.clientX - r.left + scroller.scrollLeft;
                    const cy = t.clientY - r.top  + scroller.scrollTop;
                    const targetZoom = (zoomLevel < (ZOOM_MIN + ZOOM_MAX) / 2)
                        ? Math.min(ZOOM_MAX, zoomLevel * 1.6)
                        : (isFullscreen ? Math.min(scroller.clientWidth / DISPLAY_W,
                                                   scroller.clientHeight / DISPLAY_H) : 1);
                    const ratio = targetZoom / zoomLevel;
                    zoomLevel = targetZoom;
                    applyZoomCss();
                    scroller.scrollLeft = cx * ratio - (cx - scroller.scrollLeft);
                    scroller.scrollTop  = cy * ratio - (cy - scroller.scrollTop);
                }
                lastTap = now;
            });
        })();

        // ===== Init =====
        function boot() {
            fitCanvas();
            computeLayout();
            renderSidePanel();
            if (isFullscreen) {
                // wait one frame so the scroller has a measurable size
                requestAnimationFrame(() => {
                    fitToViewport();
                    draw();
                });
            } else {
                draw();
                // After load, scroll to center the seat plan horizontally on
                // mobile (the canvas is wider than the viewport).
                requestAnimationFrame(() => {
                    if (scroller.scrollWidth > scroller.clientWidth) {
                        scroller.scrollLeft = (scroller.scrollWidth - scroller.clientWidth) / 2;
                    }
                });
            }
        }

        boot();

        // Redraw on devicePixelRatio change (rare) — also handles a zoom change.
        let lastDpr = window.devicePixelRatio || 1;
        window.addEventListener('resize', () => {
            const dpr = window.devicePixelRatio || 1;
            if (dpr !== lastDpr) {
                lastDpr = dpr;
                fitCanvas();
                draw();
            }
            // In fullscreen mode also re-fit the canvas to the new viewport.
            if (isFullscreen) fitToViewport();
        });

        // Re-fit when the visual viewport changes on iOS Safari (URL bar
        // collapse) so the canvas always uses the maximum available space.
        if (isFullscreen && window.visualViewport) {
            window.visualViewport.addEventListener('resize', fitToViewport);
        }
    })();
</script>
