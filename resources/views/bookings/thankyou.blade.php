@extends('layouts.app')

@section('title', 'تم إرسال طلب الحجز · Premium Tickets')

@php
    // Build calendar metadata once on the server. Show start = date + time;
    // we estimate a 3h end. UTC ISO is required for the .ics VEVENT block.
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
    $calLabel   = $showTitle !== '' ? $showTitle : 'Premium Tickets';
    $calDetails = 'Reference: ' . $booking->reference_code;
    // Build a real RFC-5545 .ics VEVENT body. We embed the bytes in a
    // data URL on the link so iOS Safari opens it in Apple Calendar,
    // Android opens it in the user's default calendar app, and desktop
    // browsers download it. No backend route required — the link is
    // self-contained and respects RFC line-folding limits.
    $icsDataUrl = null;
    if ($startAt && $endAt) {
        $fmt = static fn (\Carbon\Carbon $d) => $d->format('Ymd\\THis\\Z');
        $esc = static function (string $s): string {
            // Escape per RFC 5545 §3.3.11.
            $s = str_replace(['\\', "\n", "\r", ',', ';'], ['\\\\', '\\n', '', '\\,', '\\;'], $s);
            return $s;
        };
        $uid = $booking->reference_code . '@el3abed-tickets';
        $dtstamp = now()->utc()->format('Ymd\\THis\\Z');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Premium Tickets//AR//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtstamp,
            'DTSTART:' . $fmt($startAt),
            'DTEND:'   . $fmt($endAt),
            'SUMMARY:' . $esc($calLabel),
            'DESCRIPTION:' . $esc($calDetails),
            'END:VEVENT',
            'END:VCALENDAR',
        ];
        // CRLF is required by spec; many parsers tolerate \n but iOS
        // Calendar is strict, so we use \r\n.
        $ics = implode("\r\n", $lines) . "\r\n";
        $icsDataUrl = 'data:text/calendar;charset=utf-8;base64,' . base64_encode($ics);
    }
    $shareText = $showTitle !== ''
        ? ('حجزت تذكرتي لـ "' . $showTitle . '" 🎭')
        : 'حجزت تذكرتي 🎭';
    $shareUrl = url('/');
    // Filename used by the download attribute on the link.
    $icsFileName = 'premium-tickets-' . $booking->reference_code . '.ics';
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
                     updated every 30s by JS below. Compact "5d 3h 22m" / "5ي 3س 22د"
                     format keeps the value short enough to never wrap on a phone. --}}
                <div class="h-px bg-[color:var(--prism-border)]"></div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-[color:var(--prism-text-3)] text-xs shrink-0" data-i18n="thx_countdown_label">يبدأ العرض خلال</span>
                    <span class="font-semibold text-sm text-[color:var(--prism-text)] whitespace-nowrap tabular-nums"
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
            @if($icsDataUrl)
                {{-- Native calendar handoff: serves a real .ics so iOS
                     Safari opens Apple Calendar, Android opens the user's
                     default calendar app, and desktop downloads. No
                     Google login wall. --}}
                <a href="{{ $icsDataUrl }}"
                   download="{{ $icsFileName }}"
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

    // Hardcoded short suffixes — the previous "thx_cd_days/hours/mins"
    // i18n keys were never added to the dictionary, so PT.t() fell
    // through to returning the key name and the countdown rendered as
    // e.g. "5 thx_cd_days · 3 thx_cd_hours · 22 thx_cd_mins". Using
    // single-letter glyphs per language gives a compact, predictable
    // "5d 3h 22m" / "5ي 3س 22د" that never wraps on a phone.
    function fmt(ms) {
        if (!isFinite(ms) || ms <= 0) {
            return (window.PT && window.PT.t) ? window.PT.t('thx_countdown_started') : '—';
        }
        var totalMin = Math.floor(ms / 60000);
        var d = Math.floor(totalMin / (60 * 24));
        var h = Math.floor((totalMin % (60 * 24)) / 60);
        var m = totalMin % 60;
        var isAr = (document.documentElement.lang || 'ar').toLowerCase().indexOf('ar') === 0
                || document.documentElement.dir === 'rtl';
        var lD = isAr ? 'ي' : 'd';
        var lH = isAr ? 'س' : 'h';
        var lM = isAr ? 'د' : 'm';
        var parts = [];
        if (d > 0)            parts.push(d + lD);
        if (h > 0 || d > 0)   parts.push(h + lH);
        parts.push(m + lM);
        return parts.join(' ');
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
