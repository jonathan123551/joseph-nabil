@extends('layouts.app')

@section('title', 'حجز تذاكر · ' . $showTime->show->title)

@section('content')

@if ($isAnbaRuweis ?? false)
{{-- =====================================================================
     STEP 1 — Section selection (Sala / Balcony)
     The user picks a section here, then is sent to the canvas seat picker
     page. Balcony is currently disabled (no balcony seats seeded yet).
===================================================================== --}}
<section class="max-w-3xl mx-auto prism-fade-up">

    {{-- Local tweaks (kept scoped to step 1 — picks up the global PRISM tokens). --}}
    <style>
        .anba-step1 .section-btn {
            display: block;
            width: 100%;
            padding: 18px 20px;
            border-radius: 18px;
            text-align: right;
            background:
                linear-gradient(135deg, rgba(34,211,238,0.10), rgba(192,132,252,0.10)),
                linear-gradient(180deg, rgba(20,24,38,0.55), rgba(8,10,20,0.65));
            border: 1px solid rgba(129,140,248,0.35);
            color: #f1f5fb;
            font-weight: 600;
            box-shadow: 0 10px 32px -14px rgba(129,140,248,0.45), inset 0 1px 0 rgba(255,255,255,0.06);
            transition: transform .2s var(--prism-ease), box-shadow .2s var(--prism-ease), border-color .2s var(--prism-ease);
            position: relative;
            overflow: hidden;
            min-height: 64px;
        }
        .anba-step1 .section-btn::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(34,211,238,0.0), rgba(192,132,252,0.18) 50%, rgba(34,211,238,0.0));
            background-size: 200% 100%;
            background-position: 0% 0%;
            transition: background-position .6s var(--prism-ease);
            pointer-events: none;
        }
        .anba-step1 .section-btn:hover {
            transform: translateY(-2px);
            border-color: rgba(129,140,248,0.6);
            box-shadow: 0 18px 40px -14px rgba(129,140,248,0.6), 0 0 22px rgba(34,211,238,0.18), inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .anba-step1 .section-btn:hover::before { background-position: 100% 0%; }
        .anba-step1 .section-btn .section-label {
            display: block;
            font-size: 18px;
            background: var(--prism-neon);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
            font-weight: 700;
        }
        .anba-step1 .section-btn .section-price {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            color: var(--prism-gold);
            font-weight: 600;
        }
        .anba-step1 .section-btn .section-meta {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            color: rgba(229, 231, 235, 0.6);
            font-weight: 400;
        }
        .anba-step1 .section-btn[disabled],
        .anba-step1 .section-btn.disabled {
            opacity: .55;
            cursor: not-allowed;
            background: linear-gradient(180deg, rgba(75,85,99,0.18), rgba(31,41,55,0.10));
            border-color: rgba(156,163,175,0.25);
            box-shadow: none;
        }
        .anba-step1 .section-btn[disabled]:hover,
        .anba-step1 .section-btn.disabled:hover { transform: none; }
        .anba-step1 .section-btn .badge-soon {
            display: inline-block;
            margin-top: 6px;
            padding: 2px 10px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 999px;
            background: rgba(99,102,241,0.18);
            border: 1px solid rgba(99,102,241,0.45);
            color: #c7d2fe;
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
    </style>

    <div class="anba-step1 space-y-5">

        {{-- step indicator --}}
        <div class="step-indicator prism-fade-up">
            <span class="dot">1</span>
            <span>القسم</span>
            <span class="line"></span>
            <span class="dot dim">2</span>
            <span>المقعد</span>
            <span class="line"></span>
            <span class="dot dim">3</span>
            <span>التأكيد</span>
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
                <h2 class="prism-headline text-sm sm:text-base">اختار القسم</h2>
                <p class="text-[11px] text-[color:var(--prism-text-3)] mt-1">
                    حدد القسم اللي عايز تحجز فيه
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <a href="{{ route('bookings.seats', $showTime) }}?section=hall"
                   class="section-btn prism-ripple"
                   data-section="hall">
                    <span class="section-label">الصالة (Hall)</span>
                    <span class="section-price">
                        {{ $hallPrice }} جنيه / مقعد
                    </span>
                    <span class="section-meta">اختار مقعدك من خريطة الصالة</span>
                </a>

                <button type="button"
                        class="section-btn disabled"
                        disabled
                        aria-disabled="true"
                        data-section="balcony">
                    <span class="section-label">البلكون (Balcony)</span>
                    <span class="section-price">
                        {{ $balconyPrice > 0 ? $balconyPrice . ' جنيه / تذكرة' : '—' }}
                    </span>
                    <span class="badge-soon">قريبًا</span>
                </button>
            </div>
        </div>

        {{-- transfer info (kept here so the user sees the price + payment
             instructions before clicking through to seat selection) --}}
        @if (!empty($transferWallet) || !empty($transferInsta))
            <div class="prism-glass p-5 sm:p-6 space-y-2 prism-fade-up" style="animation-delay:.15s;">
                <h3 class="text-[12px] font-semibold flex items-center gap-2"
                    style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    💸 ادفع قيمة التذكرة على
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
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                🎭 تفاصيل العرض
            </h2>

            <p class="text-sm text-[color:var(--prism-text)] font-medium">
                {{ $showTime->show->title }}
            </p>

            <div class="space-y-1.5 text-xs text-[color:var(--prism-text-2)]">
                <p class="flex items-center gap-2"><span>📅</span>{{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                <p class="flex items-center gap-2"><span>⏰</span>{{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
                <p class="text-[color:var(--prism-gold)] font-semibold flex items-center gap-2">
                    <span>🎟️</span>{{ $showTime->ticket_price }} جنيه
                </p>
            </div>

            {{-- خطوة 1 --}}
            <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-2xl p-4 space-y-2">

                <h3 class="text-xs font-semibold"
                    style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    خطوة 1: حوّل قيمة التذكرة
                </h3>

                <p class="text-[11px] text-[color:var(--prism-text-3)]">
                    حوّل {{ $showTime->ticket_price }} جنيه على أحد الأرقام التالية:
                </p>

                <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-xl p-2.5">
                    <p class="text-[10px] text-[color:var(--prism-text-3)]">📱 محفظة</p>
                    <p class="text-sm font-bold text-[color:var(--prism-text)]" dir="ltr">{{ $transferWallet }}</p>
                </div>

                <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-xl p-2.5">
                    <p class="text-[10px] text-[color:var(--prism-text-3)]">⚡ InstaPay</p>
                    <p class="text-sm font-bold text-[color:var(--prism-text)]" dir="ltr">{{ $transferInsta }}</p>
                </div>

            </div>

        </div>

        {{-- ======================
        | 📝 FORM
        ======================= --}}
        <div class="md:col-span-2 prism-glass p-6 space-y-4">

            <h2 class="text-sm font-semibold"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
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
                    <label class="text-xs font-semibold text-[color:var(--prism-text)]">
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
                    <label class="text-xs font-semibold text-[color:var(--prism-text)]">
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
                    إرسال طلب الحجز
                    <span aria-hidden="true">←</span>
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
const maxTickets = {{ max(0, $showTime->total_tickets - $showTime->bookings()
    ->whereIn('status',['approved','pending'])
    ->sum('tickets_count')) }};

const namesContainer = document.getElementById('namesContainer');
const ticketsInput = document.getElementById('tickets_count');
const countDisplay = document.getElementById('ticketsCount');

function renderNames() {
    namesContainer.innerHTML = '';

    for (let i = 1; i <= count; i++) {
        const wrap = document.createElement('div');
        wrap.className = 'space-y-2 bg-white/[0.04] border border-[color:var(--prism-border)] rounded-xl p-3';
        wrap.innerHTML = `
            <input type="text"
                name="names[]"
                placeholder="اسم الشخص ${i}"
                class="prism-input"
                required>

            <input type="text"
                name="phones[]"
                placeholder="رقم موبايل واتساب ${i}"
                class="prism-input"
                required>
        `;
        namesContainer.appendChild(wrap);
    }
}

function changeCount(val) {
    count += val;

    if (count < 1) count = 1;

    if (count > maxTickets) {
        count = maxTickets;

        alert("❌ لا يوجد تذاكر متاحة، المتاح: " + maxTickets);
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
    submitBtn.innerText = 'جارِ الإرسال...';
});

</script>
@endif

@endsection
