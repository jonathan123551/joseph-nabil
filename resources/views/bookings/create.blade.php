@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')

@if ($isAnbaRuweis ?? false)
{{-- =====================================================================
     STEP 1 — Section selection (Sala / Balcony)
     The user picks a section here, then is sent to the canvas seat picker
     page. Balcony is currently disabled (no balcony seats seeded yet).
===================================================================== --}}
<section class="max-w-3xl mx-auto px-4 py-6 sm:py-10">

    {{-- premium card aesthetic, matches the seat picker / form pages --}}
    <style>
        .anba-step1 .glass {
            background: linear-gradient(180deg, rgba(15,23,42,0.6), rgba(2,6,23,0.7));
            border: 1px solid rgba(251,191,36,0.22);
            border-radius: 24px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.04),
                0 20px 60px -20px rgba(0,0,0,0.7);
        }
        .anba-step1 .ambient {
            background:
                radial-gradient(ellipse 120% 60% at 50% -10%, rgba(251,191,36,0.10), transparent 60%),
                radial-gradient(ellipse 80% 50% at 50% 110%, rgba(99,102,241,0.06), transparent 60%),
                linear-gradient(180deg, rgba(15,23,42,0.6), rgba(2,6,23,0.85));
        }
        .anba-step1 .section-btn {
            display: block;
            width: 100%;
            padding: 18px 20px;
            border-radius: 18px;
            text-align: right;
            background: linear-gradient(180deg, rgba(251,191,36,0.18), rgba(180,83,9,0.10));
            border: 1px solid rgba(251,191,36,0.45);
            color: #fef3c7;
            font-weight: 700;
            box-shadow: 0 6px 20px rgba(251,191,36,0.18), inset 0 1px 0 rgba(255,255,255,0.06);
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        }
        .anba-step1 .section-btn:hover {
            transform: translateY(-2px);
            border-color: rgba(251,191,36,0.75);
            box-shadow: 0 10px 28px rgba(251,191,36,0.32), inset 0 1px 0 rgba(255,255,255,0.10);
        }
        .anba-step1 .section-btn .section-label {
            display: block;
            font-size: 18px;
            color: #fde68a;
        }
        .anba-step1 .section-btn .section-price {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            color: #fbbf24;
            font-weight: 600;
        }
        .anba-step1 .section-btn .section-meta {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            color: rgba(229, 231, 235, 0.65);
            font-weight: 400;
        }
        .anba-step1 .section-btn[disabled],
        .anba-step1 .section-btn.disabled {
            opacity: .55;
            cursor: not-allowed;
            background: linear-gradient(180deg, rgba(75,85,99,0.20), rgba(31,41,55,0.10));
            border-color: rgba(156,163,175,0.30);
            box-shadow: none;
        }
        .anba-step1 .section-btn[disabled]:hover,
        .anba-step1 .section-btn.disabled:hover {
            transform: none;
        }
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
    </style>

    <div class="anba-step1 space-y-6">

        {{-- show header --}}
        <div class="glass ambient p-5 sm:p-6 space-y-2 text-center">
            <h1 class="text-amber-300 text-base sm:text-lg font-bold">
                🎭 {{ $showTime->show->title }}
            </h1>
            <div class="text-[12px] text-gray-300 space-y-0.5">
                <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
                <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
            </div>
        </div>

        {{-- section choice --}}
        <div class="glass p-5 sm:p-6 space-y-4">
            <div class="text-center">
                <h2 class="text-amber-300 text-sm font-semibold">اختار القسم</h2>
                <p class="text-[11px] text-gray-400 mt-1">
                    حدد القسم اللي عايز تحجز فيه
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <a href="{{ route('bookings.seats', $showTime) }}?section=hall"
                   class="section-btn"
                   data-section="hall">
                    <span class="section-label">الصالة (Sala)</span>
                    <span class="section-price">{{ $hallPrice }} جنيه / تذكرة</span>
                    <span class="section-meta">إجلس على المقاعد الرئيسية ذات الإطلالة المباشرة</span>
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
            <div class="glass p-4 sm:p-5 space-y-2">
                <h3 class="text-[12px] text-amber-300 font-semibold">
                    💸 ادفع قيمة التذكرة على
                </h3>
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
<section class="max-w-5xl mx-auto px-4">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- ======================
        | 🎭 DETAILS + PAYMENT
        ======================= --}}
<div class="md:col-span-1 relative
    bg-black/50
    border border-amber-400/40
    rounded-3xl p-5 space-y-4
    shadow-[0_0_100px_rgba(250,204,21,0.3)]">

    <h2 class="text-sm font-semibold text-amber-300">🎭 تفاصيل العرض</h2>

    <p class="text-sm text-white font-medium">
        {{ $showTime->show->title }}
    </p>

    <div class="space-y-1 text-xs text-gray-300">
        <p>📅 {{ \Carbon\Carbon::parse($showTime->date)->format('d-m-Y') }}</p>
        <p>⏰ {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}</p>
        <p class="text-amber-300 font-semibold">
            🎟️ {{ $showTime->ticket_price }} جنيه
        </p>
    </div>

    {{-- خطوة 1 --}}
    <div class="bg-black/40 border border-amber-400/20 rounded-2xl p-4 space-y-2">

        <h3 class="text-xs text-amber-300 font-semibold">
            خطوة 1: حوّل قيمة التذكرة
        </h3>

        <p class="text-[11px] text-gray-400">
            حوّل {{ $showTime->ticket_price }} جنيه على أحد الأرقام التالية:
        </p>

        <div class="bg-white/5 rounded-xl p-2">
            <p class="text-[10px] text-gray-400">📱 محفظة</p>
            <p class="text-sm font-bold text-white">{{ $transferWallet }}</p>
        </div>

        <div class="bg-white/5 rounded-xl p-2">
            <p class="text-[10px] text-gray-400">⚡ InstaPay</p>
            <p class="text-sm font-bold text-white">{{ $transferInsta }}</p>
        </div>

    </div>

</div>
        {{-- ======================
        | 📝 FORM
        ======================= --}}
        <div class="md:col-span-2 bg-black/50 border border-white/10 rounded-3xl p-6 space-y-4">

            <h2 class="text-sm font-semibold text-amber-300">
                خطوة 2: ارفع Screenshot وكمّل البيانات
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
                  id="bookingForm"
                  class="space-y-4">
                @csrf

                {{-- 👥 عدد التذاكر --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-3">
                    <label class="text-xs font-semibold text-white">
                        👥 عدد التذاكر
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="changeCount(-1)" class="px-3 py-1 rounded bg-white/10">-</button>

                        <span id="ticketsCount" class="text-white font-bold">1</span>

                        <button type="button" onclick="changeCount(1)" class="px-3 py-1 rounded bg-white/10">+</button>
                    </div>

                    <input type="hidden" name="tickets_count" id="tickets_count" value="1">
                </div>

                {{-- 👤 الأشخاص --}}
                <div id="namesContainer" class="space-y-3"></div>

                {{-- Screenshot --}}
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 space-y-2">
                    <label class="text-xs font-semibold text-white">
                        📸 Screenshot التحويل
                    </label>

                    <input type="file"
                           name="payment_screenshot"
                           id="screenshot"
                           accept="image/*"
                           class="w-full text-xs text-gray-300">
                </div>

                <button type="submit"
                        id="submitBtn"
                        disabled
                        class="px-6 py-2.5 rounded-full
                               bg-gray-600 text-black text-sm font-semibold
                               cursor-not-allowed transition">
                    إرسال طلب الحجز
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
        namesContainer.innerHTML += `
            <div class="space-y-2 bg-black/40 border border-white/10 rounded-xl p-3">

                <input type="text"
                    name="names[]"
                    placeholder="اسم الشخص ${i}"
                    class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white"
                    required>

                <input type="text"
                    name="phones[]"
                    placeholder="رقم موبايل واتساب ${i}"
                    class="w-full rounded-xl bg-black/60 border border-white/15 px-3 py-2 text-sm text-white"
                    required>

            </div>
        `;
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
    if (screenshotReady && !isSubmitting) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('bg-gray-600', 'cursor-not-allowed');
        submitBtn.classList.add('bg-amber-400');
    } else {
        submitBtn.disabled = true;
    }
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
    submitBtn.innerText = 'جاري الإرسال...';
});

</script>
@endif

@endsection
