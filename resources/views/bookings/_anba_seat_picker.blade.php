{{--
    Premium cinema-style seat picker for مسرح الأنبا رويس.
    UI/UX redesign — backend contract is unchanged:
        - posts seat_ids[], names[], phones[], section, payment_screenshot
        - section is hardcoded to "hall" (the theater is now a single
          unified section after the JSON reseed)
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
        [data-anba-root] .stage-wrap {
            position: relative;
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        [data-anba-root] .stage-arc {
            position: relative;
            width: min(70%, 480px);
            min-height: 64px;
            padding: 14px 24px 10px;
            text-align: center;
            color: #fde68a;
            border: 1px solid rgba(251,191,36,0.55);
            border-bottom: none;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
            background:
                radial-gradient(ellipse at top, rgba(251,191,36,0.35), rgba(251,191,36,0.05) 70%, transparent 100%),
                linear-gradient(180deg, rgba(251,191,36,0.18), rgba(2,6,23,0));
            box-shadow:
                0 0 60px rgba(251,191,36,0.35),
                inset 0 -10px 20px rgba(2,6,23,0.6);
            letter-spacing: 0.2em;
        }
        [data-anba-root] .stage-arc::after {
            content: "";
            position: absolute;
            left: 8%;
            right: 8%;
            top: 100%;
            height: 80px;
            background: radial-gradient(ellipse at top, rgba(251,191,36,0.30), transparent 70%);
            filter: blur(18px);
            pointer-events: none;
        }
        [data-anba-root] .stage-arc .stage-ar { font-weight: 700; font-size: 13px; }
        [data-anba-root] .stage-arc .stage-en { font-size: 10px; opacity: 0.8; margin-top: 2px; }

        /* the seat plan as a slightly tilted plane */
        [data-anba-root] .seats-plane {
            perspective: 1800px;
            padding: 12px 4px 8px;
            direction: ltr; /* numeric seat layout is LTR even on Arabic page */
            width: max-content;
            margin: 0 auto;
        }
        [data-anba-root] .seats-rows {
            transform: rotateX(10deg);
            transform-origin: top center;
            transform-style: preserve-3d;
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding-bottom: 18px;
        }

        [data-anba-root] .seat-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px; /* aisle gap between left/center/right */
        }
        [data-anba-root] .seat-row .row-label {
            width: 22px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            color: rgba(253, 224, 71, 0.75);
            letter-spacing: 0.05em;
        }
        [data-anba-root] .seat-group {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        /* angle the wings outward to give a fan-shape feel */
        [data-anba-root] .seat-group.left  { transform: rotate(-2.4deg); transform-origin: right center; }
        [data-anba-root] .seat-group.right { transform: rotate(2.4deg);  transform-origin: left  center; }

        /* Row R (curved last row) — wider arc + special amber tint */
        [data-anba-root] .seat-row.row-r .seat-group.left  { transform: rotate(-5deg); }
        [data-anba-root] .seat-row.row-r .seat-group.right { transform: rotate(5deg); }
        [data-anba-root] .seat-row.row-r { gap: 30px; padding-top: 10px; margin-top: 4px; border-top: 1px dashed rgba(251,191,36,0.20); }
        [data-anba-root] .seat-row.row-r .row-label { color: rgba(251,191,36,0.95); }

        /* the seat itself */
        [data-anba-root] .seat-btn {
            width: 24px;
            height: 24px;
            font-size: 8.5px;
            line-height: 1;
            border-radius: 7px 7px 3px 3px;
            border: 1px solid rgba(255,255,255,0.10);
            background: linear-gradient(180deg, #4b5563, #1f2937);
            color: rgba(255,255,255,0.85);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease;
            cursor: pointer;
            user-select: none;
            box-shadow: 0 1px 0 rgba(255,255,255,0.06) inset, 0 2px 4px rgba(0,0,0,0.4);
            padding: 0 2px;
        }
        @media (min-width: 1024px) {
            [data-anba-root] .seat-btn {
                width: 26px;
                height: 26px;
                font-size: 9px;
            }
        }
        [data-anba-root] .seat-btn:hover:not(:disabled) {
            transform: translateY(-1px) scale(1.06);
            border-color: rgba(251,191,36,0.7);
            box-shadow: 0 4px 10px rgba(251,191,36,0.25);
        }

        [data-anba-root] .seat-btn.is-selected {
            background: linear-gradient(180deg, #34d399, #059669);
            border-color: rgba(110,231,183,0.95);
            color: #022c22;
            box-shadow:
                0 0 14px rgba(16,185,129,0.7),
                0 0 4px rgba(110,231,183,0.9) inset;
            transform: scale(1.06);
        }
        [data-anba-root] .seat-btn.is-booked {
            background: linear-gradient(180deg, #b91c1c, #7f1d1d);
            border-color: rgba(239,68,68,0.6);
            color: rgba(254,226,226,0.85);
            cursor: not-allowed;
            opacity: 0.95;
            box-shadow: 0 0 0 1px rgba(239,68,68,0.3) inset;
        }
        [data-anba-root] .seat-btn.is-blocked {
            background: linear-gradient(180deg, #ca8a04, #713f12);
            border-color: rgba(250,204,21,0.55);
            color: #1c1917;
            cursor: not-allowed;
            opacity: 0.92;
        }

        /* zoom controls */
        [data-anba-root] .zoom-bar { display: flex; align-items: center; gap: 6px; }
        [data-anba-root] .zoom-btn {
            width: 28px; height: 28px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.04);
            color: #e5e7eb;
            font-weight: 600;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
        }
        [data-anba-root] .zoom-btn:hover { background: rgba(251,191,36,0.12); border-color: rgba(251,191,36,0.5); }

        /* glassmorphism panel */
        [data-anba-root] .glass {
            background: linear-gradient(180deg, rgba(15,23,42,0.65), rgba(2,6,23,0.85));
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 24px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 30px 80px -30px rgba(0,0,0,0.6);
        }

        /* selected seat chip */
        [data-anba-root] .seat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(16,185,129,0.18);
            border: 1px solid rgba(110,231,183,0.55);
            color: #d1fae5;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
        }
        [data-anba-root] .seat-chip button {
            background: transparent; border: none; color: #fecaca;
            cursor: pointer; font-size: 12px; line-height: 1; padding: 0;
        }

        /* sticky mobile bottom bar */
        [data-anba-root] .mobile-cta {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            padding: 10px 14px calc(10px + env(safe-area-inset-bottom));
            background: linear-gradient(180deg, rgba(2,6,23,0.55), rgba(2,6,23,0.95));
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-top: 1px solid rgba(251,191,36,0.25);
            z-index: 50;
            display: none;
        }
        @media (max-width: 1023px) {
            [data-anba-root].has-selection .mobile-cta { display: flex; align-items: center; gap: 10px; }
            [data-anba-root].has-selection { padding-bottom: 90px; }
        }

        /* faint ambient glow behind everything */
        [data-anba-root] .ambient {
            position: relative;
        }
        [data-anba-root] .ambient::before {
            content: "";
            position: absolute;
            inset: -10% -5% -10% -5%;
            background:
                radial-gradient(ellipse at 50% 0%, rgba(251,191,36,0.18), transparent 55%),
                radial-gradient(ellipse at 50% 90%, rgba(2,6,23,0.4), transparent 65%);
            pointer-events: none;
            z-index: 0;
        }
        [data-anba-root] .ambient > * { position: relative; z-index: 1; }

        /* attendant fields card */
        [data-anba-root] .attendee-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 16px;
            padding: 12px;
            display: grid;
            grid-template-columns: 80px 1fr;
            gap: 8px;
            align-items: center;
        }
        [data-anba-root] .attendee-card .seat-pill {
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            padding: 8px 0;
            border-radius: 12px;
            background: rgba(16,185,129,0.18);
            color: #d1fae5;
            border: 1px solid rgba(110,231,183,0.45);
        }
        [data-anba-root] .field-input {
            width: 100%;
            background: rgba(2,6,23,0.55);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 12px;
            color: #f8fafc;
        }
        [data-anba-root] .field-input::placeholder { color: rgba(148,163,184,0.65); }
        [data-anba-root] .field-input:focus {
            outline: none;
            border-color: rgba(251,191,36,0.55);
            box-shadow: 0 0 0 3px rgba(251,191,36,0.15);
        }

        /* primary CTA */
        [data-anba-root] .cta-primary {
            position: relative;
            padding: 12px 22px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            background: linear-gradient(180deg, #fbbf24, #b45309);
            color: #1c1917;
            border: 1px solid rgba(255,255,255,0.18);
            box-shadow: 0 10px 30px -10px rgba(251,191,36,0.55);
            transition: transform .15s ease, opacity .15s ease;
            cursor: pointer;
        }
        [data-anba-root] .cta-primary:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            background: linear-gradient(180deg, #475569, #1e293b);
            color: #cbd5e1;
            box-shadow: none;
        }
        [data-anba-root] .cta-primary:hover:not(:disabled) { transform: translateY(-1px); }

        /* horizontal seat scroller */
        [data-anba-root] .seats-scroller {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x pan-y;
            scrollbar-width: thin;
            scrollbar-color: rgba(251,191,36,0.5) transparent;
            display: flex;
            justify-content: center;
        }
        [data-anba-root] .seats-scroller::-webkit-scrollbar { height: 6px; }
        [data-anba-root] .seats-scroller::-webkit-scrollbar-thumb { background: rgba(251,191,36,0.5); border-radius: 999px; }
        /* the inner zoom target needs an intrinsic width so flex centering works */
        [data-anba-root] [data-zoom-target] {
            flex: 0 0 auto;
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

            <div class="stage-wrap">
                <div class="stage-arc">
                    <div class="stage-ar">المسرح</div>
                    <div class="stage-en">STAGE</div>
                </div>
            </div>

            <div class="seats-scroller">
                <div data-zoom-target style="transform-origin: top center; transition: transform .25s ease;">
                    <div class="seats-plane">
                        <div class="seats-rows" data-seats-rows></div>
                    </div>
                </div>
            </div>

            <p class="mt-3 text-center text-[11px] text-gray-400">
                مرّر أفقياً أو استعمل أزرار التكبير على الموبايل
            </p>
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
                    اضغط على أي مقعد رمادي لاختياره. تقدر تختار أكتر من مقعد، وتلغيهم بالضغط مرة تانية.
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

        const seatData     = JSON.parse(root.querySelector('[data-seat-data]').textContent);
        const unavailable  = new Set((JSON.parse(root.dataset.unavailable || '[]') || []).map(Number));
        const blocked      = new Set((JSON.parse(root.dataset.blocked     || '[]') || []).map(Number));
        const hallPrice    = parseInt(root.dataset.hallPrice || '0', 10);

        const rowsBox      = root.querySelector('[data-seats-rows]');
        const chipsBox     = root.querySelector('[data-selected-chips]');
        const emptyMsg     = root.querySelector('[data-empty-msg]');
        const countEl      = root.querySelector('[data-selected-count]');
        const totalEl      = root.querySelector('[data-total-price]');
        const attendees    = root.querySelector('[data-attendees]');
        const screenshot   = root.querySelector('#anbaScreenshot');
        const submitBtn    = root.querySelector('#anbaSubmitBtn');
        const form         = root.querySelector('#anbaBookingForm');
        const mobileCount  = root.querySelector('[data-mobile-count]');
        const mobileTotal  = root.querySelector('[data-mobile-total]');
        const jumpBtn      = root.querySelector('[data-jump-to-form]');
        const zoomTarget   = root.querySelector('[data-zoom-target]');

        // map seatId -> { row, n }
        const seatIndex = new Map();
        const selected  = new Map(); // seatId -> { row, n }
        let isSubmitting = false;
        let zoomLevel    = 1;

        function buildLayout() {
            const rows = seatData.hall || {};
            const letters = Object.keys(rows).sort();
            letters.forEach(letter => {
                const rowEl = document.createElement('div');
                rowEl.className = 'seat-row' + (letter === 'R' ? ' row-r' : '');

                rowEl.appendChild(makeRowLabel(letter));
                rowEl.appendChild(makeGroup(rows[letter].left,   letter, 'left'));
                rowEl.appendChild(makeGroup(rows[letter].center, letter, 'center'));
                rowEl.appendChild(makeGroup(rows[letter].right,  letter, 'right'));
                rowEl.appendChild(makeRowLabel(letter));

                rowsBox.appendChild(rowEl);
            });
        }

        function makeRowLabel(letter) {
            const el = document.createElement('div');
            el.className = 'row-label';
            el.textContent = letter;
            return el;
        }

        function makeGroup(seats, rowLetter, side) {
            const el = document.createElement('div');
            el.className = 'seat-group ' + side;
            (seats || []).forEach(s => el.appendChild(makeSeat(s, rowLetter)));
            return el;
        }

        function makeSeat(seat, rowLetter) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.seatId = seat.id;
            btn.dataset.row = rowLetter;
            btn.dataset.n   = seat.n;
            btn.className   = 'seat-btn';
            btn.textContent = rowLetter + seat.n;
            btn.title = 'مقعد ' + rowLetter + seat.n;
            btn.setAttribute('aria-label', 'مقعد ' + rowLetter + seat.n);

            seatIndex.set(seat.id, { row: rowLetter, n: seat.n });

            if (unavailable.has(seat.id)) {
                if (blocked.has(seat.id)) {
                    btn.classList.add('is-blocked');
                    btn.title = 'محجوز إداري · ' + rowLetter + seat.n;
                } else {
                    btn.classList.add('is-booked');
                    btn.title = 'محجوز · ' + rowLetter + seat.n;
                }
                btn.disabled = true;
            } else {
                btn.addEventListener('click', () => toggleSeat(btn, seat.id));
            }
            return btn;
        }

        function toggleSeat(btn, seatId) {
            if (selected.has(seatId)) {
                selected.delete(seatId);
                btn.classList.remove('is-selected');
            } else {
                const meta = seatIndex.get(seatId);
                selected.set(seatId, meta);
                btn.classList.add('is-selected');
            }
            renderState();
        }

        function renderState() {
            const ids = Array.from(selected.keys());
            const n   = ids.length;

            countEl.textContent  = n;
            totalEl.textContent  = (n * hallPrice).toLocaleString('en-US');
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
                // sort by row letter then seat_number for a tidy display
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
            // Preserve currently typed values across re-renders
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

        // chip remove handler (event delegation)
        chipsBox.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove]');
            if (!btn) return;
            const id  = parseInt(btn.dataset.remove, 10);
            const seatBtn = root.querySelector('.seat-btn[data-seat-id="' + id + '"]');
            if (seatBtn) toggleSeat(seatBtn, id);
        });

        screenshot.addEventListener('change', updateSubmitButton);

        // zoom controls (mobile + desktop)
        function applyZoom() {
            zoomTarget.style.transform = 'scale(' + zoomLevel + ')';
        }
        root.querySelectorAll('[data-zoom]').forEach(btn => {
            btn.addEventListener('click', () => {
                const dir = parseInt(btn.dataset.zoom, 10);
                if (dir === 0) { zoomLevel = 1; }
                else { zoomLevel = Math.max(0.6, Math.min(1.6, zoomLevel + (dir * 0.1))); }
                applyZoom();
            });
        });

        // jump-to-form on mobile sticky CTA
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

        buildLayout();
        renderState();
    })();
</script>
