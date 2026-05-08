@extends('layouts.app')

@section('title', 'تم إرسال طلب الحجز · Premium Tickets')

@php
    // Build calendar metadata once on the server. Show start = date + time;
    // we estimate a 3h end. UTC ISO is required for Google Calendar / .ics.
    $showTime = $booking->showTime ?? null;
    $show     = $showTime ? ($showTime->show ?? null) : null;
    $startAt  = null;
    $endAt    = null;
    $showTitle = $show ? $show->title : '';
    if ($showTime && $showTime->date && $showTime->time) {
        try {
            $start = \Carbon\Carbon::parse(
                $showTime->date->format('Y-m-d') . ' ' . $showTime->time,
                config('app.timezone', 'Africa/Cairo')
            );
            $startAt = $start->copy()->utc();
            $endAt   = $start->copy()->addHours(3)->utc();
        } catch (\Throwable $e) {
            $startAt = null;
            $endAt = null;
        }
    }
    $calLabel = $showTitle !== '' ? $showTitle : 'Premium Tickets';
    $calDetails = 'Reference: ' . $booking->reference_code;
    $googleCalUrl = null;
    if ($startAt && $endAt) {
        $googleCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . rawurlencode($calLabel)
            . '&dates=' . $startAt->format('Ymd\\THis\\Z') . '/' . $endAt->format('Ymd\\THis\\Z')
            . '&details=' . rawurlencode($calDetails);
    }
    $shareText = $showTitle !== ''
        ? ('حجزت تذكرتي لـ "' . $showTitle . '" 🎭')
        : 'حجزت تذكرتي 🎭';
    $shareUrl = url('/');
@endphp

@section('content')
<section class="max-w-lg mx-auto prism-fade-up">

    <div class="prism-glass prism-glow-border p-6 sm:p-8 text-center space-y-5">

        {{-- Animated success orb --}}
        <div class="mx-auto w-20 h-20 relative">
            <div class="absolute inset-0 rounded-full"
                 style="background: radial-gradient(circle, rgba(16,185,129,0.55), transparent 70%);
                        filter: blur(14px);
                        animation: prismGlowPulse 2.4s ease-in-out infinite;"></div>
            <div class="absolute inset-0 flex items-center justify-center rounded-full
                        bg-emerald-500/[0.10] border border-emerald-400/40 text-3xl">
                🎟
            </div>
        </div>

        {{-- Title --}}
        <div>
            <h1 class="prism-headline text-2xl sm:text-3xl"
                data-i18n="thx_title"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                تم إرسال طلب الحجز بنجاح
            </h1>
            <p class="text-xs sm:text-sm text-[color:var(--prism-text-2)] mt-2">
                <span data-i18n="thx_thanks_prefix">شكرًا يا</span>
                <span class="font-semibold text-[color:var(--prism-text)]">{{ $booking->full_name }}</span>
            </p>
        </div>

        {{-- Booking Info --}}
        <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-2xl p-4 text-sm space-y-3 pt-rtl-text">

            <div class="flex justify-between items-center gap-3">
                <span class="text-[color:var(--prism-text-3)] text-xs shrink-0" data-i18n="thx_ref_label">رقم الحجز</span>
                {{-- QW#1: tap-to-copy on the booking ref code --}}
                <button type="button"
                        class="prism-copyable font-mono text-sm tracking-wide gap-2"
                        data-pt-copy="{{ $booking->reference_code }}"
                        data-i18n-attr="aria-label:copy_aria"
                        aria-label="نسخ">
                    <span dir="ltr" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        {{ $booking->reference_code }}
                    </span>
                    <span class="copy-icon" aria-hidden="true">⧉</span>
                </button>
            </div>

            <div class="h-px bg-[color:var(--prism-border)]"></div>

            <div class="flex justify-between items-center">
                <span class="text-[color:var(--prism-text-3)] text-xs" data-i18n="thx_total_label">إجمالي المبلغ</span>
                <span class="font-bold text-[color:var(--prism-emerald)]">
                    {{ $booking->total_price }} <span class="text-[10px] opacity-80" data-i18n="common_egp">جنيه</span>
                </span>
            </div>

            @if($startAt)
                {{-- QW#6: live countdown to show. Pure ms diff against ISO start;
                     updated every minute by JS below. Server-render an initial
                     placeholder so non-JS users still see something useful. --}}
                <div class="h-px bg-[color:var(--prism-border)]"></div>
                <div class="flex justify-between items-center">
                    <span class="text-[color:var(--prism-text-3)] text-xs" data-i18n="thx_countdown_label">الوقت المتبقي</span>
                    <span class="font-semibold text-sm text-[color:var(--prism-text)]"
                          data-pt-countdown
                          data-target-iso="{{ $startAt->toIso8601String() }}"
                          aria-live="polite">—</span>
                </div>
            @endif
        </div>

        {{-- IMPORTANT NOTICE --}}
        <div class="bg-amber-500/[0.06] border border-amber-400/30 rounded-2xl p-4 pt-rtl-text space-y-3">

            <div class="flex items-center gap-2 text-[color:var(--prism-gold)] font-semibold text-sm">
                <span aria-hidden="true">⏳</span>
                <span data-i18n="thx_next_step">الخطوة الجاية</span>
            </div>

            <ul class="space-y-3 text-xs sm:text-sm text-[color:var(--prism-text-2)] leading-relaxed">

                <li class="relative pt-rtl-bullet before:content-[''] before:absolute before:top-[0.55em]
                           before:w-2 before:h-2 before:rounded-full before:bg-amber-300
                           before:shadow-[0_0_10px_rgba(251,191,36,0.7)]"
                    data-i18n-html="thx_step1_html">
                    يتم <span class="text-[color:var(--prism-text)] font-semibold">مراجعة عملية الدفع</span>
                    والتأكد من التحويل.
                </li>

                <li class="relative pt-rtl-bullet before:content-[''] before:absolute before:top-[0.55em]
                           before:w-2 before:h-2 before:rounded-full before:bg-emerald-300
                           before:shadow-[0_0_10px_rgba(110,231,183,0.7)]"
                    data-i18n-html="thx_step2_html">
                    بعد <span class="text-[color:var(--prism-emerald)] font-semibold">تأكيد الحجز</span>،
                    سيتم إرسال <span class="text-[color:var(--prism-text)] font-semibold">التذكرة</span>
                    مباشرة على <span class="text-[color:var(--prism-text)] font-semibold">رقم الواتساب المسجل</span>.
                </li>

                <li class="relative pt-rtl-bullet before:content-[''] before:absolute before:top-[0.55em]
                           before:w-2 before:h-2 before:rounded-full before:bg-sky-300
                           before:shadow-[0_0_10px_rgba(125,211,252,0.7)]"
                    data-i18n-html="thx_step3_html">
                    عملية المراجعة قد تستغرق بحد أقصى
                    <span class="text-[color:var(--prism-text)] font-semibold">24 ساعة</span>.
                </li>

            </ul>

        </div>

        {{-- Footer Note --}}
        <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed"
           data-i18n-html="thx_footer_html">
            لو في أي مشكلة في التحويل أو البيانات، هنتواصل معاك قبل رفض الطلب.
            <br>
            متقلقش، طلبك محفوظ على السيستم ✨
        </p>

        {{-- QW#5 + QW#6: actions row — calendar, WhatsApp share, browse more --}}
        <div class="flex flex-col sm:flex-row flex-wrap gap-2 justify-center items-stretch">
            @if($googleCalUrl)
                <a href="{{ $googleCalUrl }}"
                   target="_blank" rel="noopener"
                   class="prism-btn prism-ripple inline-flex justify-center"
                   data-i18n-attr="aria-label:thx_add_calendar"
                   aria-label="أضف للتقويم">
                    <span aria-hidden="true">📅</span>
                    <span data-i18n="thx_add_calendar">أضف للتقويم</span>
                </a>
            @endif

            <a href="https://wa.me/?text={{ urlencode($shareText . ' ' . $shareUrl) }}"
               target="_blank" rel="noopener"
               class="prism-share-wa inline-flex justify-center"
               data-pt-share-wa
               data-share-title="{{ $showTitle }}"
               data-share-url="{{ $shareUrl }}"
               data-i18n-attr="aria-label:share_wa"
               aria-label="مشاركة عبر واتساب">
                <span class="share-wa-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.05 0C5.5 0 .2 5.3.2 11.86c0 2.09.55 4.13 1.59 5.93L0 24l6.36-1.66a11.83 11.83 0 0 0 5.69 1.45h.01c6.55 0 11.86-5.3 11.86-11.85 0-3.17-1.23-6.15-3.4-8.46zM12.06 21.6h-.01a9.8 9.8 0 0 1-4.99-1.36l-.36-.21-3.78.99 1.01-3.69-.23-.38a9.78 9.78 0 0 1-1.5-5.21c0-5.42 4.41-9.83 9.83-9.83 2.62 0 5.09 1.02 6.95 2.88a9.78 9.78 0 0 1 2.88 6.95c.01 5.43-4.4 9.86-9.8 9.86zm5.39-7.36c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.66.15-.2.3-.76.96-.93 1.16-.17.2-.34.22-.64.07-.3-.15-1.25-.46-2.38-1.46-.88-.78-1.47-1.74-1.64-2.04-.17-.3-.02-.46.13-.61.13-.13.3-.34.45-.51.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.66-1.6-.91-2.19-.24-.58-.49-.5-.66-.51l-.56-.01c-.2 0-.51.07-.78.37-.27.3-1.03 1.01-1.03 2.46 0 1.45 1.06 2.85 1.21 3.05.15.2 2.09 3.2 5.06 4.49.71.31 1.26.49 1.69.62.71.22 1.35.19 1.86.12.57-.08 1.75-.71 2-1.4.25-.69.25-1.28.17-1.4-.07-.13-.27-.2-.57-.35z"/></svg>
                </span>
                <span data-i18n="share_wa">مشاركة عبر واتساب</span>
            </a>

            <a href="{{ route('shows.index') }}"
               class="prism-btn-ghost prism-ripple inline-flex justify-center">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span data-i18n="thx_back_home">رجوع للصفحة الرئيسية</span>
            </a>
        </div>

    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ---------- QW#6: live countdown (updates every 30s) ----------
    var nodes = document.querySelectorAll('[data-pt-countdown]');
    if (!nodes.length) return;

    function fmt(ms) {
        if (!isFinite(ms) || ms <= 0) {
            return (window.PT && window.PT.t) ? window.PT.t('thx_countdown_started') : '—';
        }
        var totalMin = Math.floor(ms / 60000);
        var d = Math.floor(totalMin / (60 * 24));
        var h = Math.floor((totalMin % (60 * 24)) / 60);
        var m = totalMin % 60;
        var t = (window.PT && window.PT.t) ? window.PT.t : function (k) { return k; };
        // Build "X days · Y h · Z m" using i18n suffixes — falls through to
        // single-letter shortforms when keys are missing.
        var parts = [];
        if (d > 0) parts.push(d + ' ' + t('thx_cd_days'));
        if (h > 0 || d > 0) parts.push(h + ' ' + t('thx_cd_hours'));
        parts.push(m + ' ' + t('thx_cd_mins'));
        return parts.join(' · ');
    }

    function tick() {
        nodes.forEach(function (el) {
            var iso = el.getAttribute('data-target-iso');
            if (!iso) return;
            var target = Date.parse(iso);
            if (isNaN(target)) return;
            el.textContent = fmt(target - Date.now());
        });
    }

    tick();
    setInterval(tick, 30000);
    document.addEventListener('pt:langchange', tick);
})();
</script>
@endpush
