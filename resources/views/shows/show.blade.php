{{-- resources/views/shows/show.blade.php --}}
@extends('layouts.app')

@section('title', $show->title . ' · Premium Tickets')

@php
    use App\Models\Show as ShowModel;
    $usesSectionPricing = $show->theater_type === ShowModel::THEATER_ANBA_RUWEIS;
    $sectionPrices = [];
    if ($usesSectionPricing) {
        if ((int) ($show->hall_price    ?? 0) > 0) $sectionPrices[] = (int) $show->hall_price;
        if ((int) ($show->balcony_price ?? 0) > 0) $sectionPrices[] = (int) $show->balcony_price;
    }
    $sectionStartsFrom = !empty($sectionPrices) ? min($sectionPrices) : null;
@endphp
@section('content')

    <section class="space-y-6 prism-fade-up">

        {{-- ===== Show details (poster + description) ===== --}}
        <div class="prism-glass p-5 sm:p-6">
            <div class="flex flex-col md:flex-row gap-6">

                @if($show->poster_path)
                    <div class="relative w-full md:w-72 overflow-hidden rounded-2xl border border-[color:var(--prism-border)]
                                shadow-[0_0_40px_rgba(129,140,248,0.18)]">
                        <img src="{{ $show->poster_path }}"
                            alt="{{ $show->title }}"
                            class="w-full h-96 object-cover transform hover:scale-[1.03] transition-transform duration-500">

                        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/25 to-transparent pointer-events-none"></div>

                        <div class="absolute top-3 end-3">
                            <span class="prism-pill prism-pill-neon" data-i18n="show_pill_kind">عرض مسرحي</span>
                        </div>
                    </div>
                @endif

                <div class="flex-1 space-y-3">
                    <h1 class="prism-headline text-2xl md:text-3xl">{{ $show->title }}</h1>

                    <p class="text-sm text-[color:var(--prism-text-2)] leading-relaxed whitespace-pre-line">
                        {{ $show->description }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2 items-center">
                        <span class="prism-pill"><span class="prism-dot prism-dot-sky"></span> <span data-i18n="show_pill_online">حجز إلكتروني</span></span>
                        <span class="prism-pill"><span class="prism-dot prism-dot-emerald"></span> <span data-i18n="show_pill_qr">تذكرة QR</span></span>

                        {{-- QW#5: WhatsApp share — pre-fills show title + page link.
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
        </div>

        {{-- ===== Available show times — preserved data, restyled rows ===== --}}
        <div class="space-y-3">
            <h2 class="prism-headline text-lg sm:text-xl" data-i18n="show_times_title">المواعيد المتاحة</h2>

            <div class="space-y-3 prism-stagger">

                @forelse($show->showTimes as $time)

                    @php
                        $totalTickets = $time->total_tickets;

                        $reserved = \App\Models\Booking::where('show_time_id', $time->id)
                            ->whereIn('status', ['approved','pending'])
                            ->sum('tickets_count');

                        $remaining = $totalTickets - $reserved;

                        $isSoldOut  = $time->is_sold_out || $remaining <= 0;
                        $fewTickets = $remaining > 0 && $remaining <= 10;
                    @endphp

                    <div class="relative prism-glass prism-card-hover px-4 sm:px-5 py-3 sm:py-4
                                flex flex-col sm:flex-row sm:items-center justify-between gap-3
                                @if($isSoldOut) opacity-60 @endif">

                        {{-- Status accent rail — uses logical inset-inline-start so
                             it stays on the leading edge in both RTL and LTR. --}}
                        <div class="absolute start-0 top-0 bottom-0 w-1 rounded-e-2xl
                            @if($isSoldOut)
                                bg-rose-500
                            @elseif($fewTickets)
                                bg-amber-400
                            @else
                                bg-emerald-400
                            @endif"
                            style="box-shadow: 0 0 14px currentColor;">
                        </div>

                        <div class="ps-3 space-y-1">
                            <div class="text-sm font-medium text-[color:var(--prism-text)]">
                                {{ $time->date->format('d/m/Y') }}
                                <span class="text-[color:var(--prism-text-4)] mx-1">·</span>
                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </div>

                            <div class="text-xs text-[color:var(--prism-text-3)] flex flex-wrap gap-2 items-center">

                                @if ($usesSectionPricing)
                                    <span>
                                        <span data-i18n="show_prices_label">الأسعار:</span>
                                        <span class="text-[color:var(--prism-gold)] font-semibold" data-i18n="shows_section_balcony_hall">
                                            بلكون / صالة
                                        </span>
                                        @if ($sectionStartsFrom !== null)
                                            <span class="text-[color:var(--prism-text-3)] text-[10px]">
                                                — <span data-i18n="shows_starts_from">تبدأ من</span> {{ $sectionStartsFrom }} <span data-i18n="shows_egp">جنيه</span>
                                            </span>
                                        @endif
                                    </span>
                                @else
                                    <span>
                                        <span data-i18n="show_price_label">سعر التذكرة:</span>
                                        <span class="text-[color:var(--prism-gold)] font-semibold">
                                            {{ $time->ticket_price }} <span data-i18n="shows_egp">جنيه</span>
                                        </span>
                                    </span>
                                @endif

                                <span class="prism-pill
                                    @if($isSoldOut)        prism-badge-rose
                                    @elseif($fewTickets)   prism-badge-amber
                                    @else                  prism-badge-emerald
                                    @endif border">
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

                        <div class="pe-1">
                            @if($isSoldOut)
                                <span class="prism-pill prism-badge-rose border" data-i18n="shows_status_sold">Sold Out</span>
                            @else
                                <a href="{{ route('bookings.create', $time) }}"
                                   class="@if($fewTickets) prism-btn-gold @else prism-btn @endif prism-ripple">
                                    <span data-i18n="btn_book_now">احجز الآن</span>
                                    <span class="pt-arrow-rtl" aria-hidden="true">←</span>
                                </a>
                            @endif
                        </div>

                    </div>

                @empty
                    <p class="text-xs text-[color:var(--prism-text-3)]" data-i18n="show_no_times">
                        لا توجد مواعيد متاحة حاليًا لهذا العرض.
                    </p>
                @endforelse

            </div>
        </div>

        <a href="{{ route('shows.index') }}" class="inline-flex items-center gap-2 text-sm text-[color:var(--prism-text-3)] hover:text-[color:var(--prism-text)] transition">
            <span class="pt-arrow-rtl-back" aria-hidden="true">→</span>
            <span data-i18n="btn_back_shows">رجوع لكل العروض</span>
        </a>

    </section>

@endsection
