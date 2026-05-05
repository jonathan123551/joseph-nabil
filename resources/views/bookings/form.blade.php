@extends('layouts.app')

@section('title', 'إكمال الحجز - ' . $showTime->show->title)

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

<section class="max-w-3xl mx-auto px-4 py-6 sm:py-8"
         data-anba-form
         data-section="{{ $sectionParam }}"
         data-show-time-id="{{ (int) $showTime->id }}"
         data-unit-price="{{ $unitPrice }}"
         data-seats-url="{{ route('bookings.seats', $showTime) }}?section={{ $sectionParam }}">

    <style>
        [data-anba-form] .glass {
            background: linear-gradient(180deg, rgba(15,23,42,0.6), rgba(2,6,23,0.7));
            border: 1px solid rgba(251,191,36,0.22);
            border-radius: 24px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.04),
                0 20px 60px -20px rgba(0,0,0,0.7);
        }
        [data-anba-form] .seat-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 3px 6px 3px 10px;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(16,185,129,0.22), rgba(16,185,129,0.10));
            border: 1px solid rgba(52,211,153,0.55);
            color: #d1fae5;
            font-size: 12px; font-weight: 700;
            box-shadow: 0 0 10px rgba(16,185,129,0.25), inset 0 1px 0 rgba(255,255,255,0.06);
        }
        [data-anba-form] .seat-chip [data-remove] {
            display: inline-flex; align-items: center; justify-content: center;
            width: 18px; height: 18px;
            border-radius: 999px;
            background: rgba(2,6,23,0.5);
            color: #fee2e2;
            font-size: 11px; font-weight: 800;
            line-height: 1;
            transition: background .15s ease, transform .15s ease;
            cursor: pointer;
        }
        [data-anba-form] .seat-chip [data-remove]:hover {
            background: rgba(220,38,38,0.6);
            transform: scale(1.06);
        }
        [data-anba-form] .add-seats-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 14px;
            border-radius: 12px;
            background: linear-gradient(180deg, rgba(251,191,36,0.22), rgba(180,83,9,0.10));
            border: 1px solid rgba(251,191,36,0.45);
            color: #fde68a;
            font-size: 12px; font-weight: 700;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        [data-anba-form] .add-seats-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(251,191,36,0.20);
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
            padding-right: 30px;
            font-size: 12px;
            line-height: 1.6;
            color: #e5e7eb;
            margin-bottom: 6px;
        }
        [data-anba-form] .step-list li::before {
            content: counter(step);
            position: absolute;
            right: 0; top: 0;
            width: 22px; height: 22px;
            border-radius: 999px;
            background: rgba(251,191,36,0.18);
            border: 1px solid rgba(251,191,36,0.45);
            color: #fde68a;
            font-size: 11px; font-weight: 800;
            display: inline-flex; align-items: center; justify-content: center;
        }
        [data-anba-form] .attendee-card {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 8px;
            padding: 10px;
            border-radius: 14px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
        }
        [data-anba-form] .attendee-card .seat-pill {
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(180deg, rgba(16,185,129,0.30), rgba(16,185,129,0.15));
            border: 1px solid rgba(52,211,153,0.6);
            color: #ecfdf5;
            font-weight: 800; font-size: 12px;
            border-radius: 12px;
            box-shadow: 0 0 8px rgba(16,185,129,0.30), inset 0 1px 0 rgba(255,255,255,0.06);
        }
        [data-anba-form] .field-input {
            width: 100%;
            background: rgba(2,6,23,0.6);
            border: 1px solid rgba(255,255,255,0.08);
            color: #e5e7eb;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            transition: border-color .15s ease, background .15s ease;
        }
        [data-anba-form] .field-input:focus {
            border-color: rgba(251,191,36,0.55);
            outline: none;
            background: rgba(2,6,23,0.8);
        }
        [data-anba-form] .cta-primary {
            background: linear-gradient(180deg, #fbbf24, #b45309);
            color: #1a0f00;
            font-weight: 800;
            border-radius: 14px;
            padding: 12px 16px;
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
            box-shadow: 0 6px 20px rgba(251,191,36,0.30), inset 0 1px 0 rgba(255,255,255,0.4);
        }
        [data-anba-form] .cta-primary:disabled {
            opacity: .5;
            cursor: not-allowed;
            background: linear-gradient(180deg, rgba(251,191,36,0.30), rgba(180,83,9,0.30));
            box-shadow: none;
        }
        [data-anba-form] .cta-primary:hover:not(:disabled) { transform: translateY(-1px); }

        [data-anba-form] .mobile-cta {
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
        }
        @media (max-width: 1023px) {
            [data-anba-form] .mobile-cta { display: flex; }
            [data-anba-form] .form-spacer-mobile { height: 76px; }
        }
    </style>

    <div class="space-y-5">

        {{-- show details --}}
        <div class="glass p-4 sm:p-5 space-y-2">
            <h1 class="text-amber-300 text-base font-bold">
                🎭 {{ $showTime->show->title }}
            </h1>
            <div class="text-[12px] text-gray-300 space-y-0.5">
                <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                <p class="text-amber-300 font-semibold">
                    🎟️ {{ $unitPrice }} جنيه / مقعد ·
                    {{ $sectionParam === 'balcony' ? 'البلكون' : 'الصالة' }}
                </p>
            </div>
        </div>

        {{-- selected seats summary --}}
        <div class="glass p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-amber-300 text-sm font-semibold">المقاعد المختارة</h2>
                <a href="{{ route('bookings.seats', $showTime) }}?section={{ $sectionParam }}"
                   class="add-seats-btn">
                    <span>＋</span>
                    <span>إضافة / تعديل المقاعد</span>
                </a>
            </div>

            <p class="text-[11px] text-gray-400 leading-relaxed">
                اضغط × على أي مقعد لإلغاء اختياره، أو اضغط "إضافة / تعديل المقاعد" للرجوع لخريطة المقاعد.
            </p>

            <div data-form-chips class="flex flex-wrap gap-1.5 min-h-[40px] p-2 rounded-xl bg-black/40 border border-white/5">
                <span class="text-[11px] text-gray-500" data-empty-msg>جارٍ تحميل المقاعد المختارة...</span>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-amber-400/10 border border-amber-400/30 px-3 py-2 text-amber-100">
                <span class="text-[11px] uppercase tracking-widest">الإجمالي</span>
                <span class="text-base font-bold">
                    <span data-form-total>0</span> <span class="text-[10px]">EGP</span>
                </span>
            </div>
        </div>

        {{-- booking instructions --}}
        <div class="glass p-4 sm:p-5 space-y-3">
            <h3 class="text-amber-300 text-sm font-semibold">📌 خطوات إكمال الحجز</h3>
            <ol class="step-list">
                <li>
                    حوّل قيمة الحجز
                    (<span class="text-amber-300 font-bold"><span data-form-total-inline>0</span> جنيه</span>)
                    على المحفظة أو InstaPay الموضحة بالأسفل.
                </li>
                <li>
                    التقط صورة (Screenshot) لإيصال التحويل وارفعها في الخانة المخصصة.
                </li>
                <li>
                    اكتب اسم ورقم واتساب لكل شخص بترتيب المقاعد المحجوزة.
                </li>
                <li>
                    اضغط <span class="text-amber-300 font-bold">"تأكيد الحجز"</span> —
                    هنراجع الطلب ونرسل التذاكر على رقم الواتساب خلال
                    <span class="text-white font-semibold">24 ساعة</span> كحد أقصى.
                </li>
            </ol>
        </div>

        {{-- transfer info --}}
        @if (!empty($transferWallet) || !empty($transferInsta))
            <div class="glass p-4 sm:p-5 space-y-2">
                <h3 class="text-[12px] text-amber-300 font-semibold">💸 ادفع قيمة الحجز على</h3>
                @if (!empty($transferWallet))
                    <div class="bg-white/5 rounded-xl px-3 py-2">
                        <p class="text-[10px] text-gray-400 mb-0.5">📱 محفظة</p>
                        <p class="text-sm font-bold text-white" dir="ltr">{{ $transferWallet }}</p>
                    </div>
                @endif
                @if (!empty($transferInsta))
                    <div class="bg-white/5 rounded-xl px-3 py-2">
                        <p class="text-[10px] text-gray-400 mb-0.5">⚡ InstaPay</p>
                        <p class="text-sm font-bold text-white" dir="ltr">{{ $transferInsta }}</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- the actual form --}}
        <div class="glass p-4 sm:p-5 space-y-4">

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
                  id="anbaFinalForm"
                  class="space-y-4">
                @csrf
                <input type="hidden" name="section" value="{{ $sectionParam }}">

                {{-- attendee cards rendered into here from localStorage --}}
                <div data-form-attendees class="space-y-2"></div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-white">📸 إيصال التحويل</label>
                    <input type="file"
                           name="payment_screenshot"
                           id="anbaScreenshotFinal"
                           accept="image/*"
                           required
                           class="w-full text-[11px] text-gray-300 file:bg-amber-400/20 file:text-amber-100 file:border-0 file:rounded-md file:px-3 file:py-1.5 file:ml-3 file:cursor-pointer">
                </div>

                <button type="submit"
                        id="anbaFinalSubmit"
                        disabled
                        class="cta-primary w-full">
                    تأكيد الحجز
                </button>
            </form>
        </div>

        <div class="form-spacer-mobile"></div>
    </div>

    {{-- mobile sticky submit so the user always sees the CTA --}}
    <div class="mobile-cta">
        <div class="flex-1 text-amber-100">
            <div class="text-[10px] text-gray-400">الإجمالي</div>
            <div class="text-sm font-bold">
                <span data-form-mobile-count>0</span> مقعد ·
                <span data-form-mobile-total>0</span> EGP
            </div>
        </div>
        <button type="button"
                data-form-mobile-submit
                class="cta-primary px-5 py-2 text-xs">
            تأكيد
        </button>
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
    const submitBtn   = root.querySelector('#anbaFinalSubmit');
    const form        = root.querySelector('#anbaFinalForm');
    const mobileCount = root.querySelector('[data-form-mobile-count]');
    const mobileTotal = root.querySelector('[data-form-mobile-total]');
    const mobileSubmit= root.querySelector('[data-form-mobile-submit]');

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
            span.className = 'text-[11px] text-gray-500';
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
            wrap.innerHTML = `
                <div class="seat-pill">${escapeHtml(s.label)}</div>
                <div class="space-y-2">
                    <input type="hidden" name="seat_ids[]" value="${s.id}">
                    <input type="text" name="names[]"
                           placeholder="اسم الشخص ${i + 1}"
                           class="field-input" required
                           value="${escapeAttr(cached[s.id]?.name || '')}">
                    <input type="text" name="phones[]"
                           placeholder="رقم واتساب ${i + 1}"
                           class="field-input" required
                           value="${escapeAttr(cached[s.id]?.phone || '')}">
                </div>
            `;
            attendees.appendChild(wrap);
        });
    }

    // Single delegated input listener — survives re-renders since it's
    // attached once to the parent.
    attendees.addEventListener('input', updateSubmit);

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
        mobileCount.textContent   = t.count;
        mobileTotal.textContent   = totalStr;
        if (totalInlineEl) totalInlineEl.textContent = totalStr;
    }

    function allFilled() {
        const names  = attendees.querySelectorAll('input[name="names[]"]');
        const phones = attendees.querySelectorAll('input[name="phones[]"]');
        for (let i = 0; i < names.length; i++) {
            if (!names[i].value.trim() || !phones[i].value.trim()) return false;
        }
        return names.length > 0;
    }

    function updateSubmit() {
        const ready = !isSubmitting
                   && seats.length > 0
                   && allFilled()
                   && screenshot.files && screenshot.files.length > 0;
        submitBtn.disabled = !ready;
    }

    screenshot.addEventListener('change', updateSubmit);

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
        updateSubmit();
    });

    if (mobileSubmit) {
        mobileSubmit.addEventListener('click', () => {
            // Trigger the real submit so HTML5 validation runs and the
            // user gets focus/scroll on missing fields.
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }

    form.addEventListener('submit', (e) => {
        if (isSubmitting) { e.preventDefault(); return false; }
        if (seats.length === 0) {
            e.preventDefault();
            alert('❌ من فضلك اختر مقعد واحد على الأقل');
            window.location.replace(seatsUrl);
            return false;
        }
        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerText = 'جارِ الإرسال...';
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
    updateSubmit();
})();
</script>

@endsection
