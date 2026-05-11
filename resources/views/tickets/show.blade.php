@extends('layouts.app')

@section('title', 'تذكرتك · Premium Tickets')
@section('headMeta')
    <meta name="pt-title-i18n" content="page_title_ticket_lookup">
@endsection

@php
    $showTime  = $booking->showTime ?? null;
    $show      = $showTime ? ($showTime->show ?? null) : null;
    $showTitle = $show ? $show->title : '';

    $whenText = '';
    if ($showTime && $showTime->date && $showTime->time) {
        try {
            $when = \Carbon\Carbon::parse(
                $showTime->date->format('Y-m-d') . ' ' . $showTime->time,
                config('app.timezone', 'Africa/Cairo')
            );
            // Compact, locale-agnostic format. Day name + day-of-month + month
            // short + HH:MM. The JS-side language already controls direction;
            // this string is intentionally neutral so it reads OK in both AR
            // and EN without per-locale formatting.
            $whenText = $when->format('D · d M · H:i');
        } catch (\Throwable $e) {
            $whenText = '';
        }
    }

    // Build a short seat label list like "A12, A13, A15" — capped so the
    // line never wraps awkwardly on small screens. Guarded so a missing
    // relation never escalates into a 500.
    $seatLabels = [];
    try {
        $seats = $booking->relationLoaded('seats') ? $booking->seats : collect();
        foreach ($seats as $s) {
            $row = $s->row_letter ?? '';
            $num = $s->seat_number ?? '';
            if ($row !== '' && $num !== '') {
                $seatLabels[] = $row . $num;
            }
        }
    } catch (\Throwable $e) {
        $seatLabels = [];
    }
    $seatCount = count($seatLabels);
    $seatLine  = '';
    if ($seatCount > 0) {
        $seatLine = $seatCount > 4
            ? implode(', ', array_slice($seatLabels, 0, 4)) . ' +' . ($seatCount - 4)
            : implode(', ', $seatLabels);
    }

    $status = $booking->status ?? 'pending';
    $statusFlash    = session('ticket_lookup_status');
    $cooldownLeft   = (int) session('ticket_lookup_cooldown', 0);
@endphp

@section('content')
<section class="max-w-lg mx-auto prism-fade-up">

    <div class="prism-glass prism-glow-border p-6 sm:p-8 text-center space-y-5"
         style="position: relative; overflow: hidden;">

        {{-- Status-specific orb. Color shifts between pending (amber),
             approved (emerald), rejected (rose). All three reuse the
             same blur-glow pattern from the thank-you page. --}}
        <div class="mx-auto w-20 h-20 relative" style="z-index: 1;">
            @if ($status === 'approved')
                <div class="absolute inset-0 rounded-full"
                     style="background: radial-gradient(circle, rgba(16,185,129,0.55), transparent 70%);
                            filter: blur(14px);
                            animation: prismGlowPulse 2.4s ease-in-out infinite;"></div>
                <div class="absolute inset-0 flex items-center justify-center rounded-full
                            bg-emerald-500/[0.10] border border-emerald-400/40 text-3xl">
                    🎟
                </div>
            @elseif ($status === 'rejected')
                <div class="absolute inset-0 rounded-full"
                     style="background: radial-gradient(circle, rgba(244,63,94,0.45), transparent 70%);
                            filter: blur(14px);"></div>
                <div class="absolute inset-0 flex items-center justify-center rounded-full
                            bg-rose-500/[0.08] border border-rose-400/30 text-3xl">
                    ✕
                </div>
            @else
                <div class="absolute inset-0 rounded-full"
                     style="background: radial-gradient(circle, rgba(251,191,36,0.45), transparent 70%);
                            filter: blur(14px);
                            animation: prismGlowPulse 2.4s ease-in-out infinite;"></div>
                <div class="absolute inset-0 flex items-center justify-center rounded-full
                            bg-amber-500/[0.08] border border-amber-400/30 text-3xl">
                    ⏳
                </div>
            @endif
        </div>

        {{-- Title — varies by status --}}
        <div style="position: relative; z-index: 1;">
            <span class="pt-thx-eyebrow" data-i18n="ticket_lookup_eyebrow">تذكرتك</span>
            @if ($status === 'approved')
                <h1 class="prism-headline text-2xl sm:text-3xl mt-2"
                    data-i18n="ticket_lookup_approved_title"
                    style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    حجزك معتمد ✅
                </h1>
                <p class="text-xs sm:text-sm text-[color:var(--prism-text-2)] mt-2"
                   data-i18n="ticket_lookup_approved_sub">
                    تم إرسال التذاكر على واتساب. لو ما وصلتش، تقدر تعيد الإرسال للرقم المسجل.
                </p>
            @elseif ($status === 'rejected')
                <h1 class="prism-headline text-2xl sm:text-3xl mt-2"
                    data-i18n="ticket_lookup_rejected_title">
                    الحجز غير معتمد
                </h1>
                <p class="text-xs sm:text-sm text-[color:var(--prism-text-2)] mt-2"
                   data-i18n="ticket_lookup_rejected_sub">
                    للأسف الحجز ده ما اتعتمدش. لو فيه استفسار اتواصل مع الدعم على واتساب.
                </p>
            @else
                <h1 class="prism-headline text-2xl sm:text-3xl mt-2"
                    data-i18n="ticket_lookup_pending_title"
                    style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    حجزك قيد المراجعة
                </h1>
                <p class="text-xs sm:text-sm text-[color:var(--prism-text-2)] mt-2"
                   data-i18n="ticket_lookup_pending_sub">
                    هنراجع التحويل ونبعتلك التذاكر على واتساب فور الاعتماد.
                </p>
            @endif
        </div>

        {{-- Flash toast from resend action. Color shifts by status code. --}}
        @if ($statusFlash)
            @php
                $flashI18n = 'ticket_lookup_status_' . $statusFlash;
                $flashAr = match ($statusFlash) {
                    'success'      => '✅ تم إعادة إرسال التذاكر على واتساب',
                    'cooldown'     => '⏱️ استنى شوية وحاول تاني',
                    'no_qr'        => 'التذاكر لسه ما اتجهزتش. حاول تاني بعد شوية.',
                    'not_approved' => 'الحجز ده لسه ما اتعتمدش.',
                    default        => '',
                };
                $flashTone = $statusFlash === 'success'
                    ? 'bg-emerald-500/[0.10] border-emerald-400/40 text-emerald-200'
                    : 'bg-amber-500/[0.08] border-amber-400/30 text-amber-200';
            @endphp
            <div class="rounded-2xl border px-4 py-3 text-xs sm:text-sm {{ $flashTone }}"
                 role="status"
                 data-i18n="{{ $flashI18n }}"
                 @if ($statusFlash === 'cooldown' && $cooldownLeft > 0) data-cooldown-left="{{ $cooldownLeft }}" @endif>
                {{ $flashAr }}
            </div>
        @endif

        {{-- Booking summary --}}
        <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-2xl p-4 text-sm space-y-3 pt-rtl-text">

            <div class="flex justify-between items-center gap-3">
                <span class="text-[color:var(--prism-text-3)] text-xs shrink-0"
                      data-i18n="ticket_lookup_ref_label">رقم الحجز</span>
                <span dir="ltr" class="font-mono text-sm tracking-wide"
                      style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    {{ $booking->reference_code }}
                </span>
            </div>

            @if ($showTitle !== '')
                <div class="h-px bg-[color:var(--prism-border)]"></div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-[color:var(--prism-text-3)] text-xs shrink-0"
                          data-i18n="ticket_lookup_show_label">العرض</span>
                    <span class="font-semibold text-[color:var(--prism-text)] text-end">{{ $showTitle }}</span>
                </div>
            @endif

            @if ($whenText !== '')
                <div class="h-px bg-[color:var(--prism-border)]"></div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-[color:var(--prism-text-3)] text-xs shrink-0"
                          data-i18n="ticket_lookup_when_label">موعد العرض</span>
                    <span class="font-semibold text-[color:var(--prism-text)] tabular-nums" dir="ltr">{{ $whenText }}</span>
                </div>
            @endif

            @if ($seatLine !== '')
                <div class="h-px bg-[color:var(--prism-border)]"></div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-[color:var(--prism-text-3)] text-xs shrink-0"
                          data-i18n="ticket_lookup_seats_label">المقاعد</span>
                    <span class="font-mono text-[color:var(--prism-text)]" dir="ltr">{{ $seatLine }}</span>
                </div>
            @endif

            <div class="h-px bg-[color:var(--prism-border)]"></div>
            <div class="flex justify-between items-center gap-3">
                <span class="text-[color:var(--prism-text-3)] text-xs shrink-0"
                      data-i18n="ticket_lookup_total_label">إجمالي المبلغ</span>
                <span class="font-bold text-[color:var(--prism-emerald)]">
                    {{ $booking->total_price }} <span class="text-[10px] opacity-80" data-i18n="common_egp">جنيه</span>
                </span>
            </div>

            <div class="h-px bg-[color:var(--prism-border)]"></div>
            <div class="flex justify-between items-center gap-3">
                <span class="text-[color:var(--prism-text-3)] text-xs shrink-0"
                      data-i18n="ticket_lookup_phone_label">رقم واتساب</span>
                <span class="font-mono text-[color:var(--prism-text)]" dir="ltr">{{ $maskedPhone }}</span>
            </div>
        </div>

        {{-- Resend action — approved bookings only. The form posts to a
             throttled endpoint that only sends to the phone on file. --}}
        @if ($status === 'approved')
            <form method="POST"
                  action="{{ route('tickets.resend', ['reference' => $booking->reference_code]) }}"
                  class="space-y-2"
                  data-ticket-resend>
                @csrf
                <button type="submit"
                        class="prism-btn prism-ripple w-full inline-flex justify-center items-center gap-2"
                        data-ticket-resend-btn>
                    <span aria-hidden="true">📩</span>
                    <span data-i18n="ticket_lookup_resend_btn">إعادة إرسال التذاكر على واتساب</span>
                </button>
                <p class="text-[11px] text-[color:var(--prism-text-3)]"
                   data-i18n="ticket_lookup_resend_hint">
                    الإرسال للرقم المسجل بس · مرة كل دقيقة
                </p>
            </form>
        @endif

        {{-- Back to home --}}
        <div>
            <a href="{{ url('/') }}"
               class="text-xs sm:text-sm text-[color:var(--prism-text-3)] hover:text-[color:var(--prism-text)] transition-colors inline-flex items-center gap-1"
               data-i18n="ticket_lookup_back_home">
                الرجوع للرئيسية
            </a>
        </div>
    </div>
</section>

<script>
    // Disable the resend button on submit and surface an inline spinner
    // (via the layout's `.is-loading` class) so a slow WhatsApp call
    // doesn't tempt the user to double-tap. bfcache restore resets the
    // state — without this, iOS back-button would leave the user with
    // a permanently-disabled greyed-out button.
    (function () {
        const form = document.querySelector('[data-ticket-resend]');
        if (!form) return;
        const btn = form.querySelector('[data-ticket-resend-btn]');
        form.addEventListener('submit', function () {
            if (!btn) return;
            btn.disabled = true;
            btn.classList.add('is-loading');
            btn.setAttribute('aria-busy', 'true');
        });
        window.addEventListener('pageshow', function (e) {
            if (!e.persisted || !btn) return;
            btn.disabled = false;
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
        });
    })();
</script>
@endsection
