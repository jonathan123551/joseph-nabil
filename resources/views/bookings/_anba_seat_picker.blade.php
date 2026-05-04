{{-- Public booking form for مسرح الأنبا رويس shows. --}}
{{-- Step 1: pick بلكون / صالة. Step 2: pick seats on the visual map. --}}
{{-- Step 3: per-seat name + phone, then screenshot upload. --}}
<div class="md:col-span-2 bg-black/50 border border-white/10 rounded-3xl p-6 space-y-4"
     data-anba-root
     data-balcony-price="{{ (int) $balconyPrice }}"
     data-hall-price="{{ (int) $hallPrice }}"
     data-unavailable='@json($unavailableSeats)'>

    <h2 class="text-sm font-semibold text-amber-300">
        خطوة 2: اختر القسم والمقاعد
    </h2>

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

        {{-- Section picker --}}
        <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-3">
            <label class="text-xs font-semibold text-white">
                🎭 اختر القسم
            </label>

            <div class="grid grid-cols-2 gap-2">
                <button type="button"
                        data-section-btn="balcony"
                        class="section-btn px-3 py-3 rounded-xl bg-black/40 border border-white/15 text-white text-sm hover:border-amber-400 transition">
                    <div class="font-semibold">بلكون</div>
                    <div class="text-[11px] text-amber-300 mt-1">{{ (int) $balconyPrice }} EGP</div>
                </button>

                <button type="button"
                        data-section-btn="hall"
                        class="section-btn px-3 py-3 rounded-xl bg-black/40 border border-white/15 text-white text-sm hover:border-amber-400 transition">
                    <div class="font-semibold">صالة</div>
                    <div class="text-[11px] text-amber-300 mt-1">{{ (int) $hallPrice }} EGP</div>
                </button>
            </div>

            <input type="hidden" name="section" id="anba_section" value="">
        </div>

        {{-- Seat layout --}}
        <div data-seat-grid
             class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-3 hidden">

            {{-- Stage marker --}}
            <div class="text-center">
                <div class="inline-block px-6 py-1.5 rounded-full bg-amber-400/15 border border-amber-400/40 text-amber-200 text-[11px] tracking-widest">
                    STAGE
                </div>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-3 text-[10px] text-gray-300 justify-center">
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 rounded-sm bg-gray-500/70 border border-gray-400 inline-block"></span>
                    متاح
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 rounded-sm bg-emerald-500 border border-emerald-300 inline-block"></span>
                    محدد
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 rounded-sm bg-red-600 border border-red-500 inline-block"></span>
                    محجوز
                </span>
            </div>

            <div class="overflow-x-auto">
                <div data-seats-container
                     class="min-w-[640px] mx-auto space-y-1 select-none"></div>
            </div>

            <div class="text-xs text-gray-300 text-center">
                <span data-selected-summary>اضغط على المقاعد المتاحة لاختيارها.</span>
            </div>
        </div>

        {{-- Per-seat customer fields --}}
        <div id="anbaNamesContainer" class="space-y-3"></div>

        {{-- Screenshot --}}
        <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-2">
            <label class="text-xs font-semibold text-white">
                📸 Screenshot التحويل
            </label>

            <input type="file"
                   name="payment_screenshot"
                   id="anbaScreenshot"
                   accept="image/*"
                   class="w-full text-xs text-gray-300">
        </div>

        <button type="submit"
                id="anbaSubmitBtn"
                disabled
                class="px-6 py-2.5 rounded-full
                       bg-gray-600 text-black text-sm font-semibold
                       cursor-not-allowed transition">
            إرسال طلب الحجز
        </button>
    </form>

    {{-- Pre-rendered seat data; we filter client-side when section changes --}}
    <script type="application/json" data-seat-data>
        @php
            $payload = [];
            foreach ($seatsByRow as $section => $rows) {
                $payload[$section] = [];
                foreach ($rows as $row => $sides) {
                    $payload[$section][$row] = [
                        'left'   => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['left']),
                        'center' => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['center']),
                        'right'  => array_map(fn($s) => ['id' => $s->id, 'n' => $s->seat_number], $sides['right']),
                    ];
                }
            }
        @endphp
        {!! json_encode($payload, JSON_UNESCAPED_UNICODE) !!}
    </script>
</div>

<script>
    (function () {
        const root = document.querySelector('[data-anba-root]');
        if (!root) return;

        const seatData    = JSON.parse(root.querySelector('[data-seat-data]').textContent);
        const unavailable = new Set((JSON.parse(root.dataset.unavailable || '[]') || []).map(Number));
        const balconyPrice = parseInt(root.dataset.balconyPrice || '0', 10);
        const hallPrice    = parseInt(root.dataset.hallPrice    || '0', 10);

        const sectionInput = root.querySelector('#anba_section');
        const grid         = root.querySelector('[data-seat-grid]');
        const seatsBox     = root.querySelector('[data-seats-container]');
        const summary      = root.querySelector('[data-selected-summary]');
        const namesBox     = root.querySelector('#anbaNamesContainer');
        const screenshot   = root.querySelector('#anbaScreenshot');
        const submitBtn    = root.querySelector('#anbaSubmitBtn');
        const form         = root.querySelector('#anbaBookingForm');

        let activeSection = null;
        const selectedIds = new Set();
        let isSubmitting  = false;

        function renderSection(section) {
            activeSection = section;
            sectionInput.value = section;
            selectedIds.clear();
            seatsBox.innerHTML = '';

            root.querySelectorAll('[data-section-btn]').forEach(btn => {
                const isActive = btn.dataset.sectionBtn === section;
                btn.classList.toggle('border-amber-400', isActive);
                btn.classList.toggle('bg-amber-400/10', isActive);
            });

            const rows = seatData[section] || {};
            const rowLetters = Object.keys(rows).sort();

            rowLetters.forEach(letter => {
                const rowEl = document.createElement('div');
                rowEl.className = 'flex justify-center items-center gap-3 py-0.5';

                const rowLabel = document.createElement('div');
                rowLabel.className = 'w-5 text-[10px] text-gray-400 text-center';
                rowLabel.textContent = letter;

                const left   = makeGroup(rows[letter].left,   letter, 'justify-end');
                const center = makeGroup(rows[letter].center, letter, 'justify-center');
                const right  = makeGroup(rows[letter].right,  letter, 'justify-start');

                rowEl.appendChild(rowLabel.cloneNode(true));
                rowEl.appendChild(left);
                rowEl.appendChild(center);
                rowEl.appendChild(right);
                rowEl.appendChild(rowLabel.cloneNode(true));

                seatsBox.appendChild(rowEl);
            });

            grid.classList.remove('hidden');
            updateSummary();
            renderNames();
            updateSubmitButton();
        }

        function makeGroup(seats, rowLetter, justify) {
            const el = document.createElement('div');
            el.className = 'flex items-center gap-[2px] flex-1 ' + justify;
            seats.forEach(s => el.appendChild(makeSeat(s, rowLetter)));
            return el;
        }

        function makeSeat(seat, rowLetter) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.seatId = seat.id;
            btn.title = rowLetter + seat.n;
            btn.textContent = seat.n;
            btn.className = 'seat-btn w-6 h-6 text-[9px] rounded-sm border transition';

            if (unavailable.has(seat.id)) {
                btn.classList.add('bg-red-600', 'border-red-500', 'text-red-100', 'cursor-not-allowed', 'opacity-80');
                btn.disabled = true;
            } else {
                btn.classList.add('bg-gray-500/70', 'border-gray-400', 'text-white', 'hover:bg-amber-400/30');
            }

            btn.addEventListener('click', () => toggleSeat(btn, seat.id));
            return btn;
        }

        function toggleSeat(btn, seatId) {
            if (selectedIds.has(seatId)) {
                selectedIds.delete(seatId);
                btn.classList.remove('bg-emerald-500', 'border-emerald-300');
                btn.classList.add('bg-gray-500/70', 'border-gray-400');
            } else {
                selectedIds.add(seatId);
                btn.classList.add('bg-emerald-500', 'border-emerald-300');
                btn.classList.remove('bg-gray-500/70', 'border-gray-400');
            }
            updateSummary();
            renderNames();
            updateSubmitButton();
        }

        function unitPrice() {
            return activeSection === 'balcony' ? balconyPrice : hallPrice;
        }

        function updateSummary() {
            const n = selectedIds.size;
            if (n === 0) {
                summary.textContent = 'اضغط على المقاعد المتاحة لاختيارها.';
                return;
            }
            summary.textContent = 'تم اختيار ' + n + ' مقعد · الإجمالي ' + (n * unitPrice()) + ' EGP';
        }

        function renderNames() {
            namesBox.innerHTML = '';
            const ids = Array.from(selectedIds);
            ids.forEach((id, i) => {
                const wrap = document.createElement('div');
                wrap.className = 'space-y-2 bg-black/40 border border-white/10 rounded-xl p-3';
                wrap.innerHTML = `
                    <input type="hidden" name="seat_ids[]" value="${id}">
                    <input type="text" name="names[]"
                           placeholder="اسم الشخص ${i + 1}"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white"
                           required>
                    <input type="text" name="phones[]"
                           placeholder="رقم موبايل واتساب ${i + 1}"
                           class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white"
                           required>
                `;
                namesBox.appendChild(wrap);
            });
        }

        function updateSubmitButton() {
            const ready = selectedIds.size > 0
                       && screenshot.files.length > 0
                       && activeSection !== null
                       && !isSubmitting;
            submitBtn.disabled = !ready;
            if (ready) {
                submitBtn.classList.remove('bg-gray-600', 'cursor-not-allowed');
                submitBtn.classList.add('bg-amber-400');
            } else {
                submitBtn.classList.add('bg-gray-600', 'cursor-not-allowed');
                submitBtn.classList.remove('bg-amber-400');
            }
        }

        root.querySelectorAll('[data-section-btn]').forEach(btn => {
            btn.addEventListener('click', () => renderSection(btn.dataset.sectionBtn));
        });

        screenshot.addEventListener('change', updateSubmitButton);

        form.addEventListener('submit', (e) => {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            if (selectedIds.size === 0) {
                e.preventDefault();
                alert('❌ من فضلك اختر مقعد واحد على الأقل');
                return false;
            }
            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerText = 'جاري الإرسال...';
        });
    })();
</script>
