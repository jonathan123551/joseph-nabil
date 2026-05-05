{{--
    Premium cinema-style seat picker for مسرح الأنبا رويس.
    UI/UX redesign — backend contract is unchanged:
        - posts seat_ids[], names[], phones[], section, payment_screenshot
        - section is hardcoded to "hall" (the theater is now a single
          unified section after the JSON reseed)

    v2 — pure HTML Canvas rendering. No flex/grid for the seat plan.
    Each row is rendered as a true arc (row A small radius, row R large)
    with seats absolutely positioned via polar geometry. Hit-testing,
    hover, and selection are all handled in JS against a list of
    rotated-rectangle seat hitboxes.
--}}

@php
    $hallPriceInt = (int) ($hallPrice ?? 0);
    $hallSeats    = $seatsByRow['hall'] ?? [];
    // Produce a stable A→R order so the script doesn't have to sort.
    ksort($hallSeats);
@endphp

<div data-anba-root
     data-hall-price="{{ $hallPriceInt }}"
     data-unavailable='@json($unavailableSeats)'
     data-blocked='@json($blockedSeats ?? [])'>

    <style>
        /* ===== premium cinema theme — scoped to [data-anba-root] ===== */

        [data-anba-root] .glass {
            background: linear-gradient(180deg, rgba(15,23,42,0.6), rgba(2,6,23,0.7));
            border: 1px solid rgba(251,191,36,0.22);
            border-radius: 24px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.04),
                0 20px 60px -20px rgba(0,0,0,0.7);
        }
        [data-anba-root] .ambient {
            background:
                radial-gradient(ellipse 120% 60% at 50% -10%, rgba(251,191,36,0.10), transparent 60%),
                radial-gradient(ellipse 80% 50% at 50% 110%, rgba(99,102,241,0.06), transparent 60%),
                linear-gradient(180deg, rgba(15,23,42,0.6), rgba(2,6,23,0.85));
        }

        [data-anba-root] .zoom-bar {
            display: inline-flex;
            border: 1px solid rgba(251,191,36,0.30);
            border-radius: 999px;
            overflow: hidden;
            background: rgba(2,6,23,0.55);
            backdrop-filter: blur(8px);
        }
        [data-anba-root] .zoom-btn {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #fde68a; font-weight: 700; font-size: 14px;
            transition: background 0.15s ease;
        }
        [data-anba-root] .zoom-btn:hover { background: rgba(251,191,36,0.15); }
        [data-anba-root] .zoom-btn + .zoom-btn { border-right: 1px solid rgba(251,191,36,0.18); }

        /* ===== Canvas wrapper ===== */
        [data-anba-root] .canvas-scroller {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y;
            scrollbar-width: thin;
            scrollbar-color: rgba(251,191,36,0.5) transparent;
            border-radius: 20px;
            background:
                radial-gradient(ellipse 90% 50% at 50% 0%, rgba(251,191,36,0.08), transparent 60%),
                linear-gradient(180deg, #020617, #0b1224);
            border: 1px solid rgba(251,191,36,0.10);
            position: relative;
        }
        [data-anba-root] .canvas-scroller::-webkit-scrollbar { height: 6px; }
        [data-anba-root] .canvas-scroller::-webkit-scrollbar-thumb { background: rgba(251,191,36,0.45); border-radius: 999px; }

        [data-anba-root] canvas.seat-canvas {
            display: block;
            cursor: pointer;
            margin: 0 auto;
            user-select: none;
        }

        /* ===== Side panel ===== */
        [data-anba-root] .seat-chip {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 6px 3px 10px;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(16,185,129,0.22), rgba(16,185,129,0.10));
            border: 1px solid rgba(52,211,153,0.55);
            color: #d1fae5;
            font-size: 11px; font-weight: 700;
            box-shadow: 0 0 10px rgba(16,185,129,0.25), inset 0 1px 0 rgba(255,255,255,0.06);
        }
        [data-anba-root] .seat-chip [data-remove] {
            display: inline-flex; align-items: center; justify-content: center;
            width: 16px; height: 16px;
            border-radius: 999px;
            background: rgba(2,6,23,0.5);
            color: #fee2e2;
            font-size: 10px; font-weight: 700;
            transition: background .15s ease;
        }
        [data-anba-root] .seat-chip [data-remove]:hover { background: rgba(220,38,38,0.6); }

        [data-anba-root] .attendee-card {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 8px;
            padding: 8px;
            border-radius: 14px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
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
            background: rgba(2,6,23,0.6);
            border: 1px solid rgba(255,255,255,0.08);
            color: #e5e7eb;
            border-radius: 10px;
            padding: 6px 10px;
            font-size: 12px;
            transition: border-color .15s ease, background .15s ease;
        }
        [data-anba-root] .field-input:focus {
            border-color: rgba(251,191,36,0.55);
            outline: none;
            background: rgba(2,6,23,0.8);
        }

        [data-anba-root] .cta-primary {
            background: linear-gradient(180deg, #fbbf24, #b45309);
            color: #1a0f00;
            font-weight: 800;
            border-radius: 14px;
            padding: 10px 16px;
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
            box-shadow: 0 6px 20px rgba(251,191,36,0.30), inset 0 1px 0 rgba(255,255,255,0.4);
        }
        [data-anba-root] .cta-primary:disabled {
            opacity: .5;
            cursor: not-allowed;
            background: linear-gradient(180deg, rgba(251,191,36,0.30), rgba(180,83,9,0.30));
            box-shadow: none;
        }
        [data-anba-root] .cta-primary:hover:not(:disabled) { transform: translateY(-1px); }

        /* ===== Sticky mobile CTA ===== */
        [data-anba-root] .mobile-cta {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 60;
            display: none;
            padding: 10px 14px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            background: linear-gradient(180deg, rgba(2,6,23,0.85), rgba(2,6,23,0.95));
            border-top: 1px solid rgba(251,191,36,0.30);
            align-items: center;
            gap: 10px;
            transform: translateY(0);
            transition: transform .25s ease;
        }
        @media (max-width: 1023px) {
            [data-anba-root].has-selection .mobile-cta { display: flex; }
        }
    </style>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr),360px] gap-5">

        {{-- ===================== SEAT MAP ===================== --}}
        <section class="glass ambient p-4 sm:p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-amber-300">
                    🎭 خريطة المقاعد · {{ $showTime->show->title ?? 'مسرح الأنبا رويس' }}
                </h2>
                <div class="zoom-bar">
                    <button type="button" class="zoom-btn" data-zoom="-1" aria-label="تصغير">−</button>
                    <button type="button" class="zoom-btn" data-zoom="0"  aria-label="إعادة">⟳</button>
                    <button type="button" class="zoom-btn" data-zoom="1"  aria-label="تكبير">+</button>
                </div>
            </div>

            <div class="canvas-scroller" data-canvas-scroller>
                <canvas class="seat-canvas" data-seat-canvas
                        width="1400" height="700"
                        role="img"
                        aria-label="خريطة مقاعد الصالة"></canvas>
            </div>

            <p class="mt-3 text-center text-[11px] text-gray-400">
                مرّر أفقياً أو استعمل أزرار التكبير على الموبايل · المقاعد ذات الـ✕ مخصصة للإدارة
            </p>

            {{-- live status (used by canvas tooltip on hover) --}}
            <p class="text-center mt-1 text-[12px] text-amber-200 min-h-[18px]" data-hover-status></p>
        </section>

        {{-- ===================== SIDE PANEL ===================== --}}
        <aside class="glass p-5 lg:sticky lg:top-4 self-start space-y-5">

            {{-- show details --}}
            <div class="space-y-1.5">
                <h3 class="text-amber-300 text-sm font-bold">🎭 {{ $showTime->show->title }}</h3>
                <div class="text-[11px] text-gray-300 space-y-0.5">
                    <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                    <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                    <p class="text-amber-300 font-semibold">🎟️ {{ $hallPriceInt }} جنيه / مقعد</p>
                </div>
            </div>

            {{-- transfer instructions --}}
            @if (!empty($transferWallet) || !empty($transferInsta))
                <div class="bg-black/40 border border-amber-400/20 rounded-2xl p-3 space-y-2">
                    <h4 class="text-[11px] text-amber-300 font-semibold">خطوة 1 · حوّل قيمة الحجز</h4>
                    @if (!empty($transferWallet))
                        <div class="bg-white/5 rounded-xl px-3 py-2">
                            <p class="text-[9px] text-gray-400 mb-0.5">📱 محفظة</p>
                            <p class="text-xs font-bold text-white" dir="ltr">{{ $transferWallet }}</p>
                        </div>
                    @endif
                    @if (!empty($transferInsta))
                        <div class="bg-white/5 rounded-xl px-3 py-2">
                            <p class="text-[9px] text-gray-400 mb-0.5">⚡ InstaPay</p>
                            <p class="text-xs font-bold text-white" dir="ltr">{{ $transferInsta }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <div>
                <h3 class="text-amber-300 text-sm font-bold mb-2">اختار مقاعدك</h3>
                <p class="text-[11px] text-gray-400 leading-relaxed">
                    اضغط على أي مقعد رمادي لاختياره. المقاعد ذات العلامة ✕ مخصصة للإدارة ولا يمكن حجزها.
                </p>
            </div>

            {{-- legend --}}
            <div class="grid grid-cols-2 gap-2 text-[11px] text-gray-200">
                <div class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded bg-gradient-to-b from-gray-500 to-gray-700 border border-white/15 inline-block"></span> متاح</div>
                <div class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded bg-gradient-to-b from-emerald-400 to-emerald-700 border border-emerald-200/80 inline-block shadow-[0_0_8px_rgba(16,185,129,0.6)]"></span> مختار</div>
                <div class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded bg-gradient-to-b from-red-600 to-red-900 border border-red-400/60 inline-block"></span> محجوز</div>
                <div class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded bg-gradient-to-b from-yellow-500 to-yellow-900 border border-yellow-300/60 inline-block"></span> محجوز إداري</div>
            </div>

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/40 text-red-200 text-xs rounded-xl p-3">
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
                  id="anbaBookingForm"
                  class="space-y-4">
                @csrf
                {{-- Section is now always 'hall' — single unified seating area --}}
                <input type="hidden" name="section" value="hall">

                <div>
                    <div class="flex items-center justify-between text-[11px] text-gray-400 mb-1">
                        <span>المقاعد المختارة</span>
                        <span data-selected-count>0</span>
                    </div>
                    <div data-selected-chips class="flex flex-wrap gap-1.5 min-h-[36px] p-2 rounded-xl bg-black/40 border border-white/5">
                        <span class="text-[11px] text-gray-500" data-empty-msg>لم تختر أي مقعد بعد</span>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-xl bg-amber-400/10 border border-amber-400/30 px-3 py-2 text-amber-100">
                    <span class="text-[11px] uppercase tracking-widest">الإجمالي</span>
                    <span class="text-base font-bold"><span data-total-price>0</span> <span class="text-[10px]">EGP</span></span>
                </div>

                <div data-attendees class="space-y-2"></div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-white">📸 إيصال التحويل</label>
                    <input type="file"
                           name="payment_screenshot"
                           id="anbaScreenshot"
                           accept="image/*"
                           class="w-full text-[11px] text-gray-300 file:bg-amber-400/20 file:text-amber-100 file:border-0 file:rounded-md file:px-3 file:py-1.5 file:ml-3 file:cursor-pointer">
                </div>

                <button type="submit"
                        id="anbaSubmitBtn"
                        disabled
                        class="cta-primary w-full">
                    تأكيد الحجز
                </button>
            </form>
        </aside>
    </div>

    {{-- mobile sticky CTA --}}
    <div class="mobile-cta">
        <div class="flex-1 text-amber-100">
            <div class="text-[10px] text-gray-400">المختار</div>
            <div class="text-sm font-bold">
                <span data-mobile-count>0</span> مقعد ·
                <span data-mobile-total>0</span> EGP
            </div>
        </div>
        <button type="button" data-jump-to-form
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

        // ----- data wiring (unchanged contract with the form) -----
        const seatData     = JSON.parse(root.querySelector('[data-seat-data]').textContent);
        const unavailable  = new Set((JSON.parse(root.dataset.unavailable || '[]') || []).map(Number));
        const blocked      = new Set((JSON.parse(root.dataset.blocked     || '[]') || []).map(Number));
        const hallPrice    = parseInt(root.dataset.hallPrice || '0', 10);

        const canvas       = root.querySelector('[data-seat-canvas]');
        const scroller     = root.querySelector('[data-canvas-scroller]');
        const ctx          = canvas.getContext('2d');
        const hoverStatus  = root.querySelector('[data-hover-status]');

        const chipsBox     = root.querySelector('[data-selected-chips]');
        const countEl      = root.querySelector('[data-selected-count]');
        const totalEl      = root.querySelector('[data-total-price]');
        const attendees    = root.querySelector('[data-attendees]');
        const screenshot   = root.querySelector('#anbaScreenshot');
        const submitBtn    = root.querySelector('#anbaSubmitBtn');
        const form         = root.querySelector('#anbaBookingForm');
        const mobileCount  = root.querySelector('[data-mobile-count]');
        const mobileTotal  = root.querySelector('[data-mobile-total]');
        const jumpBtn      = root.querySelector('[data-jump-to-form]');

        // map seatId -> { row, n, isAdminOnly? }
        const seatMeta  = new Map();
        const selected  = new Map();
        let isSubmitting = false;
        let zoomLevel    = 1;

        // ===== Geometry constants =====
        //
        // Real-theater linear layout. The center column is rendered as a
        // straight vertical column, perfectly centered. Left and right
        // wings get three independent transforms, exposed below as the
        // three knobs you can edit:
        //
        //   1. CURVE_FACTOR   → fans wings outward as you move toward the back
        //   2. INWARD_STRENGTH → pulls outer wing seats back toward the center
        //   3. STAGGER         → half-seat horizontal shift on alternate rows
        //
        // Search the file for "===== CURVE CONTROL =====",
        // "===== INWARD OFFSET =====", and "===== STAGGER =====" to find
        // the exact spots in computeLayout() where each knob is applied.
        const ROWS_ORDER       = ['A','B','C','D','E','F','G','H','GAP','I','J','K','L','M','N','O','P','Q','R'];
        const SEAT_W           = 22;     // seat box width  (px)
        const SEAT_H           = 20;     // seat box height (px)
        const SEAT_GAP         = 5;      // horizontal gap between adjacent seats
        const ROW_GAP          = 10;     // vertical gap between rows
        const ROW_PITCH        = SEAT_H + ROW_GAP;  // distance between row centers
        const AISLE_GAP        = 32;     // horizontal gap between center and each wing
        const ROW_A_GAP        = 78;     // mid-gap when row has no center (e.g. row A)
        const ROW_R_GAP        = 140;    // big mid-gap for row R (split halves)

        // ===== CURVE CONTROL =====   (knob #1 — bottom rows fan out wider)
        // Increase  → more pronounced arc, wings sweep further out
        // Decrease  → flatter, more grid-like
        const CURVE_FACTOR     = 0;     // px each wing shifts outward per row index

        // ===== INWARD OFFSET =====   (knob #2 — pull outer wing seats toward center)
        // Increase  → outer seats lean further in (more pronounced "(" / ")" shape)
        // Decrease  → wings stay rectangular
        const INWARD_STRENGTH  = 0;

        // ===== STAGGER =====         (knob #3 — half-seat shift on odd rows)
        // Set to 0 to disable the alternating-row offset entirely.
        const STAGGER          = (SEAT_W + SEAT_GAP) / 2;

        const STAGE_H          = 70;
        const TOP_PAD          = 28;
        const ROW_AREA_TOP     = TOP_PAD + STAGE_H + 28;  // first row baseline (y of row A center)
        const SIDE_PAD         = 36;

        // Display size (CSS pixels). Will be scaled by devicePixelRatio internally.
        // Width is wide enough to fit row Q's left wing + full curve outward.
        let DISPLAY_W = 1400;
        let DISPLAY_H = 700;
        let CX        = DISPLAY_W / 2;

        // ===== State =====
        // Each seat: { id, label, row, n, x, y, angle, w, h, state, isAdminOnly }
        const SEATS = [];
        let hoverIdx = -1;

        // ===== Row metadata cache =====
        // Per-row geometry: where the center column starts/ends, where each
        // wing's "anchor" column sits before curve/inward/stagger are applied.
        // We keep these so drawRowLabel() can place labels next to the
        // outermost seat without recomputing the layout.
        const ROW_META = new Map();

        // ===== Layout computation =====
        function computeLayout() {
            SEATS.length = 0;
            seatMeta.clear();
            ROW_META.clear();

            const rows = seatData.hall || {};
            const SEAT_PITCH = SEAT_W + SEAT_GAP;

            let visualRow = 0;

            ROWS_ORDER.forEach((letter, idx) => {
                if (letter === 'GAP') {
                    visualRow += 1.5; // مسافة زيادة
                    return;
                }   
                const data = rows[letter];
                if (!data) return;

                const cL = (data.left   || []).length;
                const cC = (data.center || []).length;
                const cR = (data.right  || []).length;

                // ===== CURVE CONTROL =====
                // Wings shift outward as we move toward the back of the
                // hall. Top rows tighter, bottom rows wider.
                const curve = idx * CURVE_FACTOR;

                // ===== STAGGER =====
                // Odd-indexed rows expand by half a seat on each wing,
                // creating the alternating cinema-row look.
                const st = (idx % 2 === 0) ? 0 : STAGGER;

                const rowY = ROW_AREA_TOP + visualRow * ROW_PITCH;
                visualRow++;

                // Center anchor: perfectly centered on CX.
                const centerWidth   = cC > 0 ? cC * SEAT_PITCH - SEAT_GAP : 0;
                const centerStartX  = CX - centerWidth / 2;

                // Pick the gap between the two wings:
                //   - row R   → ROW_R_GAP (split halves with a big aisle)
                //   - row A   → ROW_A_GAP (no center column at all)
                //   - rows w/ center → 2 × AISLE_GAP (center + 2 aisles)
                let leftEndX;
                let rightStartX;
                if (cC === 0 && letter === 'R') {
                    leftEndX    = CX - ROW_R_GAP / 2;
                    rightStartX = CX + ROW_R_GAP / 2;
                } else if (letter === 'A') {
    const next = rows['B'];
    const nextCenterCount = (next?.center || []).length;

    const nextCenterWidth = nextCenterCount > 0
        ? nextCenterCount * SEAT_PITCH - SEAT_GAP
        : 0;

    const nextCenterStartX = CX - nextCenterWidth / 2;

    leftEndX    = nextCenterStartX - AISLE_GAP;
    rightStartX = nextCenterStartX + nextCenterWidth + AISLE_GAP;
} else {
                    leftEndX    = centerStartX - AISLE_GAP;
                    rightStartX = centerStartX + centerWidth + AISLE_GAP;
                }

                // Left wing — data.left is ordered OUTER → INNER (e.g. [23,21,…,11]).
                // Inner anchor sits flush to leftEndX, outer extends leftward.
                // For each seat at index i (0 = outermost):
                //   inward shifts seat RIGHT (toward center) — outer most, inner least.
                //   curve   shifts seat LEFT (away from center).
                //   stagger shifts seat LEFT on odd rows (matches the user's design
                //   where stagger expands the row symmetrically on both sides).
                if (cL > 0) {
                    const leftWingWidth = cL * SEAT_PITCH - SEAT_GAP;
                    const leftBaseX     = leftEndX - leftWingWidth;
                    for (let i = 0; i < cL; i++) {
                         // ===== LEFT (FIXED ALIGNMENT) =====

                        const isStart10 = (idx % 2 === 0); // A, C, E...

                        const shift = isStart10 ? -(SEAT_PITCH / 2) : 0;

                        const x = leftBaseX
                                + i * SEAT_PITCH
                                + SEAT_W / 2
                                - shift;
                    }
                }

                // Center column — straight, no curve / no inward / no stagger.
                // Row I center is the "خاص بالإدارة" block (admin-only with X).
                for (let i = 0; i < cC; i++) {
                    const x = centerStartX + i * SEAT_PITCH + SEAT_W / 2;
                    pushSeat(data.center[i], letter, x, rowY, false);
                }

                // Right wing — data.right is ordered INNER → OUTER (e.g. [12,14,…,24]).
                if (cR > 0) {
                    for (let i = 0; i < cR; i++) {
                        // ===== RIGHT (FIXED ALIGNMENT) =====

                        const isStart10 = (idx % 2 === 0); // A, C, E...

                        const shift = isStart10 ? -(SEAT_PITCH / 2) : 0;

                        const x = rightStartX
                                + i * SEAT_PITCH
                                + SEAT_W / 2
                                + shift;
                     }
                }

                ROW_META.set(letter, {
                    idx,
                    rowY,
                    leftEndX,
                    rightStartX,
                    cL, cC, cR,
                    curve, st
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

        const STATE_STYLES = {
            available: {
                fill: ['#4b5563', '#1f2937'],
                stroke: 'rgba(255,255,255,0.10)',
                text: 'rgba(255,255,255,0.85)',
                shadow: null
            },
            selected: {
                fill: ['#34d399', '#047857'],
                stroke: 'rgba(167,243,208,0.85)',
                text: '#ecfdf5',
                shadow: { color: 'rgba(16,185,129,0.85)', blur: 14 }
            },
            booked: {
                fill: ['#dc2626', '#7f1d1d'],
                stroke: 'rgba(248,113,113,0.65)',
                text: '#fee2e2',
                shadow: null
            },
            admin: {
                fill: ['#eab308', '#713f12'],
                stroke: 'rgba(253,224,71,0.65)',
                text: '#fef3c7',
                shadow: null
            }
        };

        function drawStage() {
            const w = Math.min(DISPLAY_W * 0.55, 460);
            const x = (DISPLAY_W - w) / 2;
            const y = TOP_PAD;
            const h = STAGE_H - 8;

            // glow halo below the arc
            const halo = ctx.createRadialGradient(DISPLAY_W/2, y + h, 10, DISPLAY_W/2, y + h, w * 0.7);
            halo.addColorStop(0, 'rgba(251,191,36,0.35)');
            halo.addColorStop(1, 'rgba(251,191,36,0)');
            ctx.fillStyle = halo;
            ctx.fillRect(0, y + h - 10, DISPLAY_W, 80);

            // arc
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(x, y + h);
            ctx.bezierCurveTo(x + w * 0.10, y - 6, x + w * 0.90, y - 6, x + w, y + h);
            ctx.closePath();
            const grad = ctx.createLinearGradient(0, y, 0, y + h);
            grad.addColorStop(0,   'rgba(251,191,36,0.25)');
            grad.addColorStop(0.6, 'rgba(251,191,36,0.10)');
            grad.addColorStop(1,   'rgba(2,6,23,0.5)');
            ctx.fillStyle = grad;
            ctx.fill();
            ctx.lineWidth = 1.2;
            ctx.strokeStyle = 'rgba(251,191,36,0.65)';
            ctx.stroke();
            ctx.restore();

            // text
            ctx.fillStyle = '#fde68a';
            ctx.font = '700 14px system-ui, -apple-system, "Segoe UI", sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('المسرح', DISPLAY_W / 2, y + h / 2 - 4);
            ctx.font = '600 9px system-ui, -apple-system, sans-serif';
            ctx.fillStyle = 'rgba(253,224,71,0.7)';
            ctx.fillText('S T A G E', DISPLAY_W / 2, y + h / 2 + 12);
        }

        function drawRowLabel(letter) {
            const meta = ROW_META.get(letter);
            if (!meta) return;

            const SEAT_PITCH = SEAT_W + SEAT_GAP;
            // Leftmost seat x — leftEndX shifted by full wing width + curve + stagger.
            const leftWingWidth = meta.cL > 0 ? meta.cL * SEAT_PITCH - SEAT_GAP : 0;
            const leftMostX  = meta.leftEndX  - leftWingWidth - meta.curve - meta.st - 18;
            const rightMostX = meta.rightStartX + (meta.cR > 0 ? meta.cR * SEAT_PITCH - SEAT_GAP : 0)
                               + meta.curve + meta.st + 18;

            const isR = letter === 'R';
            ctx.fillStyle = isR ? 'rgba(251,191,36,0.95)' : 'rgba(253,224,71,0.7)';
            ctx.font = '700 11px system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(letter, leftMostX,  meta.rowY);
            ctx.fillText(letter, rightMostX, meta.rowY);
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
                ctx.shadowColor = 'rgba(251,191,36,0.55)';
                ctx.shadowBlur  = 10;
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

        // ===== Hit testing =====
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

            renderAttendees(ids);
            updateSubmitButton();
        }

        function renderAttendees(ids) {
            const cached = {};
            attendees.querySelectorAll('.attendee-card').forEach(card => {
                const sid = card.dataset.seatId;
                cached[sid] = {
                    name:  card.querySelector('input[name="names[]"]').value,
                    phone: card.querySelector('input[name="phones[]"]').value,
                };
            });

            attendees.innerHTML = '';
            ids.forEach((id, i) => {
                const meta = selected.get(id);
                const wrap = document.createElement('div');
                wrap.className = 'attendee-card';
                wrap.dataset.seatId = id;
                wrap.innerHTML = `
                    <div class="seat-pill">${meta.row}${meta.n}</div>
                    <div class="space-y-2">
                        <input type="hidden" name="seat_ids[]" value="${id}">
                        <input type="text" name="names[]" placeholder="اسم الشخص ${i + 1}"
                               class="field-input" required value="${escapeAttr(cached[id]?.name || '')}">
                        <input type="text" name="phones[]" placeholder="رقم واتساب ${i + 1}"
                               class="field-input" required value="${escapeAttr(cached[id]?.phone || '')}">
                    </div>
                `;
                attendees.appendChild(wrap);
            });
        }

        function escapeAttr(v) {
            return String(v).replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        function updateSubmitButton() {
            const ready = selected.size > 0
                       && (screenshot.files?.length > 0)
                       && !isSubmitting;
            submitBtn.disabled = !ready;
        }

        chipsBox.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove]');
            if (!btn) return;
            const id = parseInt(btn.dataset.remove, 10);
            const meta = seatMeta.get(id);
            if (meta) toggleSeat(meta);
        });

        screenshot.addEventListener('change', updateSubmitButton);

        // ===== Zoom =====
        // Pure CSS scale — keeps the underlying canvas geometry unchanged.
        function applyZoomCss() {
            canvas.style.width  = (DISPLAY_W * zoomLevel) + 'px';
            canvas.style.height = (DISPLAY_H * zoomLevel) + 'px';
        }

        root.querySelectorAll('[data-zoom]').forEach(btn => {
            btn.addEventListener('click', () => {
                const dir = parseInt(btn.dataset.zoom, 10);
                if (dir === 0) zoomLevel = 1;
                else zoomLevel = Math.max(0.7, Math.min(1.8, zoomLevel + dir * 0.1));
                applyZoomCss();
            });
        });

        if (jumpBtn) {
            jumpBtn.addEventListener('click', () => {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        form.addEventListener('submit', (e) => {
            if (isSubmitting) { e.preventDefault(); return false; }
            if (selected.size === 0) {
                e.preventDefault();
                alert('❌ من فضلك اختر مقعد واحد على الأقل');
                return false;
            }
            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerText = 'جارِ الإرسال...';
        });

        // ===== Init =====
        function boot() {
            fitCanvas();
            computeLayout();
            renderSidePanel();
            draw();
            // After load, scroll to center the seat plan horizontally on
            // mobile (the canvas is wider than the viewport).
            requestAnimationFrame(() => {
                if (scroller.scrollWidth > scroller.clientWidth) {
                    scroller.scrollLeft = (scroller.scrollWidth - scroller.clientWidth) / 2;
                }
            });
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
        });
    })();
</script>
