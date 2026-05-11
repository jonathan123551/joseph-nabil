@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
<section class="space-y-5 max-w-4xl mx-auto px-3 sm:px-0">

    {{-- Status flash (kept as a non-JS fallback; on JS-enabled clients the
         resend action is intercepted below and shows the premium toast
         instead of going through a redirect/flash). --}}
    @if(session('status'))
        <div class="pt-alert pt-alert-success text-center prism-fade-up">
            {{ session('status') }}
        </div>
    @endif

    {{-- GRID --}}
    <div class="grid sm:grid-cols-2 gap-4 prism-stagger">

        {{-- 🎟️ التذاكر --}}
        <div class="prism-glass p-5 space-y-3 flex flex-col prism-fade-up">

            {{-- Compact back chevron + booking ref. Replaces the heavy
                 page-header card; reclaims ~120 px of vertical space on
                 mobile, leaves room for the floating action bar. --}}
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.bookings.index') }}"
                   class="pt-back-chevron"
                   data-i18n-attr="aria-label:adm_back"
                   aria-label="رجوع">
                    <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                </a>
                <div class="flex items-center gap-2 flex-wrap justify-end">
                    {{-- Cross-link to the showtime-level manifest so admins
                         working a single booking can jump to the full
                         attendee list for that event without backtracking. --}}
                    @if ($booking->showTime)
                        <a href="{{ route('admin.show-times.manifest', $booking->showTime) }}"
                           class="prism-btn-ghost text-xs"
                           title="Seat occupancy / attendee manifest for this showtime">
                            📋 <span>مانيفست العرض</span>
                        </a>
                    @endif
                    <span class="prism-pill prism-pill-neon">
                        <span class="prism-dot prism-dot-emerald"></span>
                        <span data-i18n="adm_bk_pill_prefix">حجز</span> #{{ $booking->id }}
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 mt-1">
                <span class="prism-section-title" data-i18n-html="adm_bk_tickets_title">🎟️ التذاكر</span>
                <span class="prism-eyebrow">{{ $booking->tickets->count() }} <span data-i18n="adm_bk_tickets_word">TICKETS</span></span>
            </div>

            <div class="space-y-3 max-h-[500px] overflow-auto pr-1 mt-2">

                @foreach($booking->tickets as $ticket)
                    @php
                        // Resolve this ticket's seat (PR #70 per-ticket identity).
                        // Tickets without a seat (manual / "Other" venue) stay valid.
                        $bs = $ticket->bookingSeat;
                        $seatSectionLabel = '';
                        $seatLabel = '';
                        if ($bs) {
                            $seatSectionLabel = $bs->section === 'balcony' ? 'بلكون' : 'صالة';
                            $seatLabel = $bs->row_letter . $bs->seat_number;
                        }
                    @endphp
                    <div class="pt-ticket-row rounded-xl p-3"
                         data-attendee-name="{{ $ticket->name }}"
                         data-attendee-seat="{{ $bs ? ($seatSectionLabel . ' ' . $seatLabel) : '' }}">

                        <div class="flex justify-between items-center gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-[color:var(--prism-text)] flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span>{{ $ticket->name }}</span>
                                    @if ($bs)
                                        <span class="pt-seat-chip pt-seat-chip-{{ $bs->section === 'balcony' ? 'balcony' : 'hall' }}"
                                              aria-label="{{ $seatSectionLabel }} {{ $seatLabel }}">
                                            <span aria-hidden="true">🎟</span>
                                            <span class="pt-seat-chip-section" data-i18n="{{ $bs->section === 'balcony' ? 'section_balcony' : 'section_hall' }}">{{ $seatSectionLabel }}</span>
                                            <span class="pt-seat-chip-seat" dir="ltr">{{ $seatLabel }}</span>
                                        </span>
                                    @endif
                                </p>
                                <p class="text-xs text-[color:var(--prism-text-3)]" dir="ltr">{{ $ticket->phone }}</p>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="w-2 h-2 rounded-full"
                                      style="background: {{ $ticket->whatsapp_sent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};
                                             box-shadow: 0 0 8px {{ $ticket->whatsapp_sent ? 'rgba(52,211,153,0.7)' : 'rgba(251,113,133,0.7)' }};"></span>

                                <span class="text-[10px]"
                                      data-i18n="{{ $ticket->whatsapp_sent ? 'adm_bk_received' : 'adm_bk_not_received' }}"
                                      style="color: {{ $ticket->whatsapp_sent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};">
                                    {{ $ticket->whatsapp_sent ? 'تم الاستلام' : 'لم يستلم' }}
                                </span>
                            </div>
                        </div>

                        @if($booking->status === 'approved')
                            <div class="flex gap-2 mt-2 flex-wrap">

                                @if($ticket->qr_image_path)
                                    <a href="{{ $ticket->qr_image_path }}" target="_blank"
                                       class="prism-btn-ghost text-[10px] px-3 py-1"
                                       data-i18n-html="adm_bk_view_ticket">
                                        عرض 🎫
                                    </a>
                                @endif

                                <form action="{{ route('admin.resend.ticket', $ticket->id) }}" method="POST">
                                    @csrf
                                    <button class="prism-btn-cyan text-[10px] px-3 py-1"
                                            data-i18n="adm_bk_resend">
                                        إعادة إرسال
                                    </button>
                                </form>

                            </div>
                        @endif

                    </div>
                @endforeach

            </div>
        </div>

        {{-- 📊 الحجز --}}
        <div class="prism-glass p-5 prism-fade-up">

            <div class="flex items-center justify-between gap-2 mb-4">
                <span class="prism-section-title" data-i18n-html="adm_bk_summary_title">📊 الحجز</span>
                <span class="prism-eyebrow" data-i18n="adm_bk_summary_eyebrow">SUMMARY</span>
            </div>

            <div class="text-sm">

                <div class="prism-data-row">
                    <span class="prism-data-key" data-i18n="adm_bk_count">عدد التذاكر</span>
                    <span class="prism-data-val">{{ $booking->tickets_count }}</span>
                </div>

                <div class="prism-data-row">
                    <span class="prism-data-key" data-i18n="adm_bk_price">السعر</span>
                    <span class="prism-data-val prism-data-val-gold text-base">
                        {{ $booking->total_price }} <span class="text-xs opacity-80" data-i18n="common_currency">جنيه</span>
                    </span>
                </div>

                <div class="prism-data-row">
                    <span class="prism-data-key" data-i18n="adm_bk_status">الحالة</span>

                    @if($booking->status === 'approved')
                        <span class="prism-pill prism-pill-emerald">
                            <span class="prism-dot prism-dot-emerald"></span>
                            <span data-i18n-html="adm_bk_status_approved">✔ مقبول</span>
                        </span>
                    @elseif($booking->status === 'rejected')
                        <span class="prism-pill prism-pill-rose">
                            <span class="prism-dot prism-dot-rose"></span>
                            <span data-i18n-html="adm_bk_status_rejected">✖ مرفوض</span>
                        </span>
                    @else
                        <span class="prism-pill prism-pill-sky">
                            <span class="prism-dot prism-dot-sky"></span>
                            <span data-i18n-html="adm_bk_status_pending">⏳ pending</span>
                        </span>
                    @endif
                </div>

                @if($booking->reference_code)
                    <div class="prism-data-row">
                        <span class="prism-data-key" data-i18n="adm_bk_ref">كود الحجز</span>
                        <span class="prism-data-val font-mono text-xs" style="color: var(--prism-text-2);">{{ $booking->reference_code }}</span>
                    </div>
                @endif

                <div class="prism-data-row">
                    <span class="prism-data-key" data-i18n="adm_bk_name">الاسم</span>
                    <span class="prism-data-val">{{ $booking->full_name }}</span>
                </div>

                @if($booking->phone)
                    <div class="prism-data-row">
                        <span class="prism-data-key" data-i18n="adm_bk_phone">الموبايل</span>
                        <span class="prism-data-val font-mono text-xs" dir="ltr">{{ $booking->phone }}</span>
                    </div>
                @endif

            </div>

        </div>

    </div>

    {{-- Screenshot --}}
    @if($booking->transfer_screenshot_path)
        <div class="prism-glass p-3 prism-fade-up">
            <div class="flex items-center justify-between gap-2 mb-3 px-2 pt-1">
                <span class="prism-section-title" data-i18n="adm_bk_transfer_title">إيصال التحويل</span>
                <span class="prism-eyebrow" data-i18n="adm_bk_transfer_eyebrow">TRANSFER · PROOF</span>
            </div>
            <img src="{{ $booking->transfer_screenshot_path }}"
                 class="w-full rounded-xl"
                 style="border: 1px solid var(--prism-border);">
        </div>
    @endif

    {{-- Pending bookings: approve/reject lives ONLY in the floating
         action bar at the bottom of the viewport (see below). The inline
         buttons that used to render here have been removed to eliminate
         the duplicate-CTA experience. --}}

         
    {{-- DELETE BUTTON (يظهر بس لو approved) --}}
    @if($booking->status === 'approved')
        <div class="text-center mt-6">

            <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                  data-pt-confirm='{"tone":"error","title":"حذف الحجز بالكامل؟","body":"هيمسح الحجز وكل التذاكر اللي طلعت منه. الإجراء ده مش بيتراجع فيه.","okLabel":"حذف نهائي","cancelLabel":"إلغاء","okVariant":"rose","i18nKeys":{"title":"adm_bk_delete_title","body":"adm_bk_delete_body","okLabel":"adm_bk_delete_ok","cancelLabel":"common_cancel"}}'>
                @csrf
                @method('DELETE')

                <button class="prism-btn-rose text-sm px-5 py-2"
                        data-i18n-html="adm_bk_delete_btn">
                    🗑️ حذف الحجز بالكامل
                </button>
            </form>

        </div>
    @endif

    {{-- spacer so the floating action bar doesn't cover the last content
         (kept slightly taller now that the bar carries a richer summary). --}}
    @if($booking->status === 'pending')
        <div style="height: 124px;" aria-hidden="true"></div>
    @endif

</section>

{{-- Sticky floating action bar (admin pending review).
     Single source of truth for approve/reject. Glass + neon styling,
     min-height 48 px tap targets, springy entrance via .pt-action-bar
     in layouts/app.blade.php. --}}
@if($booking->status === 'pending')
    @php
        $bkName  = $booking->full_name ?? ($booking->name ?? '');
        $bkRef   = $booking->reference_code ?? ('#' . $booking->id);
        $bkPhone = $booking->phone ?? '';
        $tCount  = $booking->tickets_count ?? ($booking->tickets->count() ?? 0);
        $tTotal  = (int) ($booking->total_price ?? 0);

        // human-readable "time to show" — falls back to the formatted
        // showtime if the diff isn't sensible (e.g. show already past).
        // ShowTime->date is cast as Carbon `date`, so stringifying it yields
        // "Y-m-d 00:00:00"; we extract the date portion explicitly before
        // concatenating the time so Carbon::parse doesn't choke on duplicated
        // hh:mm:ss segments.
        $whenLabel = '';
        try {
            if ($booking->showTime && $booking->showTime->date) {
                $rawDate = $booking->showTime->date instanceof \Carbon\CarbonInterface
                    ? $booking->showTime->date->toDateString()
                    : (string) $booking->showTime->date;
                $rawTime = (string) ($booking->showTime->time ?? '00:00:00');
                $when = \Carbon\Carbon::parse($rawDate . ' ' . $rawTime);
                $whenLabel = $when->isFuture()
                    ? $when->locale('ar')->diffForHumans(['parts' => 1])
                    : $when->locale('ar')->isoFormat('D MMM');
            }
        } catch (\Throwable $e) { /* tolerate bad data */ }
    @endphp

    <div class="pt-action-bar is-on pt-bar-admin" id="ptAdminBar" role="region"
         data-i18n-attr="aria-label:adm_bk_actions_aria"
         aria-label="إجراءات الحجز">
        <div class="pt-action-bar-inner">
            <div class="pt-bar-summary">
                <span class="pt-bar-label"><span data-i18n="adm_bk_pending_label">حجز قيد المراجعة</span> · {{ $bkRef }}</span>
                <span class="pt-bar-meta">
                    {{ $bkName }}
                    @if($bkPhone)<span class="pt-bar-sep" aria-hidden="true">·</span> <span dir="ltr">{{ $bkPhone }}</span>@endif
                </span>
                <span class="pt-bar-meta-row">
                    <span class="pt-bar-chip"><span aria-hidden="true">🎟</span> {{ $tCount }}</span>
                    <span class="pt-bar-chip pt-bar-chip-gold">{{ $tTotal }} <span class="opacity-70" data-i18n="common_currency">جنيه</span></span>
                    @if($whenLabel)
                        <span class="pt-bar-chip pt-bar-chip-muted"><span aria-hidden="true">⏰</span> {{ $whenLabel }}</span>
                    @endif
                </span>
            </div>
            <div class="pt-bar-actions">
                <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                      data-pt-confirm='{"tone":"error","title":"رفض الحجز؟","body":"الحجز هيترفض، ومش هيوصل أي QR للعميل.","okLabel":"رفض","cancelLabel":"إلغاء","okVariant":"rose","i18nKeys":{"title":"adm_bk_reject_title","body":"adm_bk_reject_body","okLabel":"adm_bk_reject_ok","cancelLabel":"common_cancel"}}'>
                    @csrf
                    <button class="prism-btn-rose pt-bar-btn">
                        <span aria-hidden="true">✖</span> <span data-i18n="adm_bk_reject_btn">رفض</span>
                    </button>
                </form>
                <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST"
                      data-pt-confirm='{"tone":"warn","title":"اعتماد الحجز؟","body":"هتأكد الحجز ويتبعت QR للعميل على واتساب.","okLabel":"اعتماد","cancelLabel":"إلغاء","okVariant":"emerald","i18nKeys":{"title":"adm_bk_approve_title","body":"adm_bk_approve_body","okLabel":"adm_bk_approve_ok","cancelLabel":"common_cancel"}}'>
                    @csrf
                    <button class="prism-btn-emerald pt-bar-btn">
                        <span aria-hidden="true">✔</span> <span data-i18n="adm_bk_approve_btn">اعتماد</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
{{-- Premium center-screen toast for resend-ticket feedback.

     Rendered once per page, hidden by default. The script below intercepts
     the resend-ticket form submission, posts via fetch (no page reload),
     and shows this toast on response — success or failure.

     This avoids depending on session-flash string matching (which proved
     brittle in production) and gives the user immediate, reliable visual
     feedback. The form's normal submit still works as a non-JS fallback. --}}
<div class="pt-toast-overlay" data-pt-toast role="status" aria-live="polite" hidden>
    <div class="pt-toast-card" data-pt-toast-card>
        <div class="pt-toast-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path data-pt-toast-path d="M5 12.5 L10 17.5 L19 7"/>
            </svg>
        </div>
        <div class="pt-toast-title" data-pt-toast-title data-i18n="adm_bk_resend_ok">
            تمت إعادة إرسال التذكرة بنجاح
        </div>
        <div class="pt-toast-msg" data-pt-toast-msg></div>
    </div>
</div>

<script>
    (function () {
        const toast = document.querySelector('[data-pt-toast]');
        if (!toast) return;
        // Portal the toast to <body> so its position:fixed is anchored to the
        // viewport instead of the transformed `.pt-page` ancestor (which has
        // an entrance-animation CSS transform that creates a containing block
        // and pushes the toast far down the page, out of view).
        if (toast.parentNode !== document.body) {
            document.body.appendChild(toast);
        }
        const card  = toast.querySelector('[data-pt-toast-card]');
        const path  = toast.querySelector('[data-pt-toast-path]');
        const title = toast.querySelector('[data-pt-toast-title]');
        const msg   = toast.querySelector('[data-pt-toast-msg]');

        const PATH_OK   = 'M5 12.5 L10 17.5 L19 7';
        const PATH_FAIL = 'M6 6 L18 18 M18 6 L6 18';
        function _t(key, fallback) {
            try {
                if (window.PT_T) return window.PT_T(key, fallback);
            } catch (_) {}
            return fallback;
        }
        function getOkTitle()   { return _t('adm_bk_resend_ok',   'تمت إعادة إرسال التذكرة بنجاح'); }
        function getFailTitle() { return _t('adm_bk_resend_fail', 'تعذّر إعادة الإرسال'); }

        let timer    = null;
        let removeT  = null;

        function show(opts) {
            const isError = !!(opts && opts.error);
            card.classList.toggle('is-error', isError);
            path.setAttribute('d', isError ? PATH_FAIL : PATH_OK);
            title.textContent = (opts && opts.title) || (isError ? getFailTitle() : getOkTitle());
            msg.textContent = (opts && opts.body) || '';
            msg.style.display = msg.textContent ? '' : 'none';

            if (removeT) { clearTimeout(removeT); removeT = null; }
            toast.hidden = false;
            // Restart the icon stroke-draw animation each time.
            const svgPath = card.querySelector('svg path');
            if (svgPath) {
                svgPath.style.animation = 'none';
                // force reflow then re-trigger
                void svgPath.offsetWidth;
                svgPath.style.animation = '';
            }
            // Double-rAF so the entrance transition animates from the
            // initial state instead of jumping to the open state.
            requestAnimationFrame(() => {
                requestAnimationFrame(() => toast.classList.add('is-on'));
            });
            if (timer) clearTimeout(timer);
            timer = setTimeout(hide, 2800);
        }

        function hide() {
            if (timer) { clearTimeout(timer); timer = null; }
            toast.classList.remove('is-on');
            removeT = setTimeout(() => {
                toast.hidden = true;
                removeT = null;
            }, 350);
        }

        toast.addEventListener('click', hide);
        toast.addEventListener('touchend', hide, { passive: true });

        // Intercept any resend-ticket form on this page. The controller
        // returns JSON ({ ok: true } / { ok: false }) for AJAX requests so
        // the toast variant is decided from a structured response rather
        // than scraping the redirected page (which used to embed this very
        // script's strings and always self-matched as an error).
        const forms = document.querySelectorAll('form[action*="/admin/resend-ticket/"]');
        forms.forEach((form) => {
            form.addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const btn = form.querySelector('button');
                if (btn) {
                    btn.disabled = true;
                    btn.classList.add('is-loading');
                    btn.setAttribute('aria-busy', 'true');
                }
                // Pull this row's attendee + seat so the success toast can say
                // exactly which ticket was resent (e.g. "Kareem · بلكون B7").
                const row = form.closest('.pt-ticket-row');
                const aName = row && row.dataset ? (row.dataset.attendeeName || '') : '';
                const aSeat = row && row.dataset ? (row.dataset.attendeeSeat || '') : '';
                const context = [aName, aSeat].filter(Boolean).join(' · ');
                try {
                    const fd = new FormData(form);
                    const res = await fetch(form.action, {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        show({ error: true, body: context });
                        return;
                    }
                    let payload = null;
                    try { payload = await res.json(); } catch (_) { /* tolerate */ }
                    if (payload && payload.ok === true) {
                        show({ body: context });
                    } else {
                        show({ error: true, body: context });
                    }
                } catch (err) {
                    show({ error: true, body: context });
                } finally {
                    if (btn) setTimeout(() => {
                        btn.disabled = false;
                        btn.classList.remove('is-loading');
                        btn.removeAttribute('aria-busy');
                    }, 600);
                }
            });
        });
    })();
</script>
@endsection
