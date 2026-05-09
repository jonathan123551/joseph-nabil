{{-- resources/views/shows/show.blade.php --}}
@extends('layouts.app')

@section('title', $show->title . ' · Premium Tickets')
@section('body_class', 'pt-route-show')

@php
    use App\Models\Show as ShowModel;

    $usesSectionPricing = $show->theater_type === ShowModel::THEATER_ANBA_RUWEIS;
    $sectionPrices = [];
    if ($usesSectionPricing) {
        if ((int) ($show->hall_price    ?? 0) > 0) $sectionPrices[] = (int) $show->hall_price;
        if ((int) ($show->balcony_price ?? 0) > 0) $sectionPrices[] = (int) $show->balcony_price;
    }
    $sectionStartsFrom = !empty($sectionPrices) ? min($sectionPrices) : null;

    // Wave 3: pre-compute card data so the template stays clean.
    $cards = $show->showTimes->map(function ($time) {
        $totalTickets = $time->total_tickets;
        $reserved = \App\Models\Booking::where('show_time_id', $time->id)
            ->whereIn('status', ['approved','pending'])
            ->sum('tickets_count');
        $remaining  = $totalTickets - $reserved;
        $isSoldOut  = $time->is_sold_out || $remaining <= 0;
        $fewTickets = $remaining > 0 && $remaining <= 10;
        $startsAt   = \Carbon\Carbon::parse($time->date->format('Y-m-d') . ' ' . $time->time);
        return [
            'time'       => $time,
            'remaining'  => $remaining,
            'isSoldOut'  => $isSoldOut,
            'fewTickets' => $fewTickets,
            'iso'        => $startsAt->toIso8601String(),
        ];
    });

    // "Starts from" price for the sticky CTA bar (lowest available price).
    if ($usesSectionPricing) {
        $stickyFromPrice = $sectionStartsFrom;
    } else {
        $availablePrices = $cards
            ->reject(fn ($c) => $c['isSoldOut'])
            ->pluck('time.ticket_price')
            ->filter(fn ($p) => (int) $p > 0)
            ->map(fn ($p) => (int) $p)
            ->all();
        $stickyFromPrice = !empty($availablePrices) ? min($availablePrices) : null;
    }
    $hasAnyAvailable = $cards->reject(fn ($c) => $c['isSoldOut'])->isNotEmpty();
@endphp

@section('content')

    <section class="space-y-6">

        {{-- ===== W3#1: Cinematic show-detail hero ===== --}}
        <div class="pt-show-hero pt-w3-pageenter">
            @if($show->poster_path)
                <div class="pt-show-hero-poster">
                    <img src="{{ $show->poster_path }}"
                         alt="{{ $show->title }}"
                         fetchpriority="high"
                         loading="eager"
                         decoding="async">
                </div>
            @endif
            <div class="pt-show-hero-veil"  aria-hidden="true"></div>
            <div class="pt-show-hero-grain" aria-hidden="true"></div>

            <div class="pt-show-hero-content">
                <span class="pt-show-hero-eyebrow" data-i18n="show_hero_eyebrow">عرض حصري</span>

                <h1 class="pt-show-hero-title">{{ $show->title }}</h1>

                @if($show->description)
                    <p class="pt-show-hero-desc">{{ $show->description }}</p>
                @endif

                <div class="pt-show-hero-meta">
                    <span class="prism-pill prism-pill-neon" data-i18n="show_pill_kind">عرض مسرحي</span>
                    <span class="prism-pill"><span class="prism-dot prism-dot-sky"></span> <span data-i18n="show_pill_online">حجز إلكتروني</span></span>
                    <span class="prism-pill"><span class="prism-dot prism-dot-emerald"></span> <span data-i18n="show_pill_qr">تذكرة QR</span></span>

                    {{-- QW#5 (preserved): WhatsApp share — pre-fills show title + page link.
                         href is computed at click time via window.PT.shareWA so
                         the user's own URL (with utm/ref) is captured. --}}
                    <a href="https://wa.me/?text={{ urlencode('احجز تذكرتك لـ "' . $show->title . '" 🎭 ' . url()->current()) }}"
                       class="prism-share-wa"
                       target="_blank" rel="noopener"
                       data-pt-share-wa
                       data-share-title="{{ $show->title }}"
                       data-share-url="{{ url()->current() }}"
                       data-i18n-attr="aria-label:share_wa"
                       aria-label="مشاركة عبر واتساب">
                        <span class="share-wa-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.05 0C5.5 0 .2 5.3.2 11.86c0 2.09.55 4.13 1.59 5.93L0 24l6.36-1.66a11.83 11.83 0 0 0 5.69 1.45h.01c6.55 0 11.86-5.3 11.86-11.85 0-3.17-1.23-6.15-3.4-8.46zM12.06 21.6h-.01a9.8 9.8 0 0 1-4.99-1.36l-.36-.21-3.78.99 1.01-3.69-.23-.38a9.78 9.78 0 0 1-1.5-5.21c0-5.42 4.41-9.83 9.83-9.83 2.62 0 5.09 1.02 6.95 2.88a9.78 9.78 0 0 1 2.88 6.95c.01 5.43-4.4 9.86-9.8 9.86zm5.39-7.36c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.66.15-.2.3-.76.96-.93 1.16-.17.2-.34.22-.64.07-.3-.15-1.25-.46-2.38-1.46-.88-.78-1.47-1.74-1.64-2.04-.17-.3-.02-.46.13-.61.13-.13.3-.34.45-.51.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.66-1.6-.91-2.19-.24-.58-.49-.5-.66-.51l-.56-.01c-.2 0-.51.07-.78.37-.27.3-1.03 1.01-1.03 2.46 0 1.45 1.06 2.85 1.21 3.05.15.2 2.09 3.2 5.06 4.49.71.31 1.26.49 1.69.62.71.22 1.35.19 1.86.12.57-.08 1.75-.71 2-1.4.25-.69.25-1.28.17-1.4-.07-.13-.27-.2-.57-.35z"/></svg>
                        </span>
                        <span data-i18n="share_wa">مشاركة عبر واتساب</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ===== W3#2: Premium showtime cards ===== --}}
        <div id="pt-showtimes" class="space-y-3">
            <div class="pt-time-section-head">
                <h2 class="prism-headline text-lg sm:text-xl" data-i18n="show_times_title">المواعيد المتاحة</h2>
                <span class="pt-time-section-eyebrow" data-i18n="show_times_eyebrow">احجز موعدك</span>
            </div>

            @if($cards->isEmpty())
                <p class="text-xs text-[color:var(--prism-text-3)]" data-i18n="show_no_times">
                    لا توجد مواعيد متاحة حاليًا لهذا العرض.
                </p>
            @else
                <div class="pt-time-grid prism-stagger">
                    @foreach($cards as $c)
                        @php
                            $time       = $c['time'];
                            $remaining  = $c['remaining'];
                            $isSoldOut  = $c['isSoldOut'];
                            $fewTickets = $c['fewTickets'];
                            $status     = $isSoldOut ? 'sold' : ($fewTickets ? 'few' : 'open');
                        @endphp

                        <article class="pt-time-card" data-status="{{ $status }}">
                            {{-- Day pillar --}}
                            <div class="pt-time-day">
                                <span class="pt-time-day-num">{{ $time->date->format('d') }}</span>
                                <span class="pt-time-day-mon" data-i18n="mon_{{ strtolower($time->date->format('M')) }}">{{ $time->date->format('M') }}</span>
                                <span class="pt-time-day-dow" data-i18n="dow_{{ strtolower($time->date->format('D')) }}">{{ $time->date->format('D') }}</span>
                            </div>

                            {{-- Mid column: time + ETA + price --}}
                            <div class="pt-time-info">
                                <span class="pt-time-time">
                                    {{ \Carbon\Carbon::parse($time->time)->format('g:i') }}<span class="pt-time-time-meridian">{{ \Carbon\Carbon::parse($time->time)->format('A') }}</span>
                                </span>

                                <span class="pt-time-eta" data-pt-eta="{{ $c['iso'] }}">
                                    <span data-pt-eta-text>—</span>
                                </span>

                                <div class="pt-time-price-row">
                                    @if($usesSectionPricing)
                                        <span class="pt-time-price-from" data-i18n="shows_starts_from">تبدأ من</span>
                                        @if($sectionStartsFrom !== null)
                                            <span class="pt-time-price-amount">{{ $sectionStartsFrom }}</span>
                                            <span class="pt-time-price-currency" data-i18n="shows_egp">جنيه</span>
                                        @endif
                                        <span class="pt-time-price-sections" data-i18n="shows_section_balcony_hall">بلكون / صالة</span>
                                    @else
                                        <span class="pt-time-price-amount">{{ $time->ticket_price }}</span>
                                        <span class="pt-time-price-currency" data-i18n="shows_egp">جنيه</span>
                                    @endif

                                    <span class="pt-time-status" data-status="{{ $status }}">
                                        @if($isSoldOut)
                                            <span data-i18n="shows_status_sold">Sold Out</span>
                                        @elseif($fewTickets)
                                            <span data-i18n="shows_status_few">تبقّى</span> {{ $remaining }} <span data-i18n="shows_status_few_suffix">تذكرة</span>
                                        @else
                                            <span data-i18n="shows_status_available">متاح للحجز</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- CTA cell --}}
                            <div class="pt-time-cta-cell">
                                @if($isSoldOut)
                                    <span class="pt-time-status" data-status="sold" data-i18n="shows_status_sold">Sold Out</span>
                                @else
                                    <a href="{{ route('bookings.create', $time) }}"
                                       class="@if($fewTickets) prism-btn-gold @else prism-btn @endif prism-ripple">
                                        <span data-i18n="btn_book_now">احجز الآن</span>
                                        <span class="pt-arrow-rtl" aria-hidden="true">←</span>
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        <a href="{{ route('shows.index') }}" class="inline-flex items-center gap-2 text-sm text-[color:var(--prism-text-3)] hover:text-[color:var(--prism-text)] transition">
            <span class="pt-arrow-rtl-back" aria-hidden="true">→</span>
            <span data-i18n="btn_back_shows">رجوع لكل العروض</span>
        </a>

    </section>

    @if($hasAnyAvailable && $stickyFromPrice)
        {{-- W3#3: sticky cinematic price/CTA bar (mobile-only via CSS).
             Tap = smooth-scroll back to the showtimes section so the user
             can pick a date — never auto-selects a showtime. --}}
        <a href="#pt-showtimes" class="pt-show-stickybar" id="pt-show-stickybar">
            <span class="pt-show-stickybar-info">
                <span class="pt-show-stickybar-from" data-i18n="shows_starts_from">تبدأ من</span>
                <span class="pt-show-stickybar-amount">{{ $stickyFromPrice }}<span class="pt-show-stickybar-amount-currency" data-i18n="shows_egp">جنيه</span></span>
            </span>
            <span class="prism-btn-gold prism-ripple">
                <span data-i18n="btn_book_now">احجز الآن</span>
                <span class="pt-arrow-rtl" aria-hidden="true">←</span>
            </span>
        </a>
    @endif

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    /* ---- W3#2: ETA chip ("starts in N days · M h") ---- */
    function T(k) {
        return (window.PT && typeof window.PT.t === 'function') ? window.PT.t(k) : k;
    }
    function fmtETA(targetMs) {
        var diff = targetMs - Date.now();
        if (!isFinite(diff) || diff <= 0) return T('show_eta_started');
        var min = Math.floor(diff / 60000);
        var hr  = Math.floor(min / 60);
        var dy  = Math.floor(hr / 24);
        if (dy > 0)  return T('show_eta_in_days').replace('{n}', dy);
        if (hr > 0)  return T('show_eta_in_hours').replace('{n}', hr);
        if (min > 0) return T('show_eta_in_mins').replace('{n}', min);
        return T('show_eta_soon');
    }
    function tickETA() {
        document.querySelectorAll('[data-pt-eta]').forEach(function (chip) {
            var iso = chip.getAttribute('data-pt-eta');
            var txt = chip.querySelector('[data-pt-eta-text]');
            if (!iso || !txt) return;
            var t = Date.parse(iso);
            if (!isFinite(t)) return;
            txt.textContent = fmtETA(t);
        });
    }
    tickETA();
    setInterval(tickETA, 60000);
    document.addEventListener('pt:langchange', tickETA);

    /* ---- W3#3: sticky CTA reveal on scroll past the hero ---- */
    var bar = document.getElementById('pt-show-stickybar');
    if (bar) {
        var lastShown = false;
        function checkShown() {
            var shown = window.scrollY > 220;
            if (shown !== lastShown) {
                bar.classList.toggle('is-shown', shown);
                lastShown = shown;
            }
        }
        window.addEventListener('scroll', checkShown, { passive: true });
        checkShown();
    }
})();
</script>
@endpush
