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
            grid-template-columns: 60px 1fr;
            gap: 10px;
            padding: 12px;
            border-radius: 14px;
            background: rgba(255,255,255,0.035);
            border: 1px solid var(--prism-border);
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease);
        }
        [data-anba-form] .attendee-card:focus-within {
            border-color: rgba(129,140,248,0.45);
            background: rgba(255,255,255,0.05);
        }
        [data-anba-form] .attendee-card .seat-pill {
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(180deg, rgba(16,185,129,0.30), rgba(16,185,129,0.15));
            border: 1px solid rgba(52,211,153,0.6);
            color: #ecfdf5;
            font-weight: 800; font-size: 13px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(16,185,129,0.30), inset 0 1px 0 rgba(255,255,255,0.06);
        }
        [data-anba-form] .field-input {
            width: 100%;
            background: rgba(8, 10, 20, 0.7);
            border: 1px solid var(--prism-border);
            color: var(--prism-text);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease), box-shadow .2s var(--prism-ease);
            min-height: 44px;
        }
        [data-anba-form] .field-input:focus {
            border-color: rgba(129,140,248,0.6);
            outline: none;
            background: rgba(8,10,20,0.9);
            box-shadow: 0 0 0 3px rgba(129,140,248,0.12);
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

        [data-anba-form] .mobile-cta {
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
            padding-bottom: max(10px, env(safe-area-inset-bottom));
        }
        @media (max-width: 1023px) {
            [data-anba-form] .mobile-cta { display: flex; }
            [data-anba-form] .form-spacer-mobile { height: 84px; }
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
        <div class="prism-glass p-5 sm:p-6 space-y-4">

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
                  class="space-y-4">
                @csrf
                <input type="hidden" name="section" value="{{ $sectionParam }}">

                {{-- attendee cards rendered into here from localStorage --}}
                <div data-form-attendees class="space-y-2"></div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-[color:var(--prism-text)] flex items-center gap-2">
                        📸 إيصال التحويل
                    </label>
                    <input type="file"
                           name="payment_screenshot"
                           id="anbaScreenshotFinal"
                           accept="image/*"
                           required
                           class="w-full text-[11px] text-[color:var(--prism-text-2)]
                                  file:bg-white/[0.06] file:text-[color:var(--prism-text)]
                                  file:border file:border-[color:var(--prism-border)]
                                  file:rounded-full file:px-4 file:py-2 file:ml-3 file:cursor-pointer
                                  file:hover:bg-white/[0.10] file:transition">
                </div>

                <button type="submit"
                        id="anbaFinalSubmit"
                        disabled
                        class="prism-btn prism-ripple w-full">
                    تأكيد الحجز
                    <span aria-hidden="true">✓</span>
                </button>
            </form>
        </div>

        <div class="form-spacer-mobile"></div>
    </div>

    {{-- mobile sticky submit so the user always sees the CTA --}}
    <div class="mobile-cta">
        <div class="flex-1">
            <div class="text-[10px] text-[color:var(--prism-text-3)]">الإجمالي</div>
            <div class="text-sm font-bold text-[color:var(--prism-text)]">
                <span data-form-mobile-count>0</span> مقعد ·
                <span class="text-[color:var(--prism-gold)]"><span data-form-mobile-total>0</span> EGP</span>
            </div>
        </div>
        <button type="button"
                data-form-mobile-submit
                class="prism-btn prism-ripple px-5 py-2 text-xs">
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
