@extends('layouts.app')

@section('title', 'العروض المتاحة · Premium Tickets')

@php
    // Bilingual price chip helpers — declared above the markup so partials
    // and JS can both reach them. Section-priced shows surface a generic
    // "Balcony / Hall" chip with a "from {min}" hint; everything else uses
    // the per-time ticket price.
@endphp

@section('content')

@php
    $totalSeats = $shows->sum(function ($s) { return (int) $s->showTimes->sum('total_tickets'); });
    $featured   = $shows->first();
    $rest       = $shows->slice(1)->values();
@endphp

{{-- =====================================================================
     1) Cinematic Hero
     - Dual spotlight beams + animated marquee + ticket-stub motif
     - Strong gradient headline, supporting copy, CTA pair, stat trio
===================================================================== --}}
<section class="pt-hero prism-fade-up" aria-labelledby="pt-hero-heading" style="position: relative;">
    {{-- Cinematic ambient layer (orbs + particles). Sits behind the
         existing beams/marquee/curtain so they remain the dominant motif. --}}
    <div class="pt-cinema-atmos" aria-hidden="true">
        <span class="pt-cinema-orb pt-cinema-orb-a"></span>
        <span class="pt-cinema-orb pt-cinema-orb-b"></span>
        <span class="pt-cinema-orb pt-cinema-orb-c"></span>
    </div>
    <div class="pt-cinema-particles" aria-hidden="true">
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
    </div>
    {{-- Spotlight cursor glow (homepage only, gated to fine pointer + reduced-motion in JS) --}}
    <div class="pt-cinema-spot" aria-hidden="true"></div>

    <div class="pt-hero-marquee" aria-hidden="true">
        <i></i><i></i><i></i><i></i><i></i><i></i><i></i>
    </div>
    <div class="pt-hero-beam pt-hero-beam-left" aria-hidden="true"></div>
    <div class="pt-hero-beam pt-hero-beam-right" aria-hidden="true"></div>
    <div class="pt-hero-curtain" aria-hidden="true"></div>

    <div class="pt-hero-inner" style="position: relative; z-index: 2;">
        <div class="pt-cinema-float-slow pt-cinema-stagger">
            <span class="pt-hero-eyebrow">
                <span class="pt-live-dot"></span>
                <span data-i18n="hero_eyebrow">حجز مباشر · المسرح المصري</span>
            </span>

            <h1 id="pt-hero-heading" class="pt-hero-title">
                <span class="block pt-grad" data-i18n="hero_title_a">احجز تجربتك</span>
                <span class="block pt-strike" data-i18n="hero_title_b">على المسرح</span>
            </h1>

            <p class="pt-hero-sub" data-i18n="hero_sub">
                منصة حجز سلسة وأنيقة: تختار العرض، تحجز مقعدك من الخريطة المباشرة،
                تدفع بأمان، وتستقبل تذكرتك بكود QR على واتساب.
            </p>

            <div class="pt-hero-ctas">
                <a href="#shows-grid" class="prism-btn prism-ripple pt-cinema-magnet" data-i18n="hero_cta_primary">تصفح العروض</a>
                <a href="#how-it-works" class="prism-btn-ghost pt-cinema-magnet" data-i18n="hero_cta_secondary">كيف يعمل؟</a>
            </div>

            <div class="pt-hero-stats" role="list">
                <div class="pt-hero-stat" role="listitem">
                    <div class="pt-hero-stat-num">{{ $shows->count() }}+</div>
                    <div class="pt-hero-stat-label" data-i18n="hero_stat_shows_label">عرض متاح</div>
                </div>
                <div class="pt-hero-stat" role="listitem">
                    <div class="pt-hero-stat-num">{{ max($totalSeats, 200) }}+</div>
                    <div class="pt-hero-stat-label" data-i18n="hero_stat_seats_label">مقعد جاهز</div>
                </div>
                <div class="pt-hero-stat" role="listitem">
                    <div class="pt-hero-stat-num">QR</div>
                    <div class="pt-hero-stat-label" data-i18n="hero_stat_qr_label">تذكرة QR فورية</div>
                </div>
            </div>
        </div>

        {{-- Hero ticket-stub motif (decorative) --}}
        <div class="pt-hero-art pt-cinema-float" aria-hidden="true">
            <div class="pt-ticket-stub">
                <div class="pt-ticket-stub-side">
                    <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                        <defs>
                            <linearGradient id="pt-grad-stub" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#22d3ee"/>
                                <stop offset="0.5" stop-color="#818cf8"/>
                                <stop offset="1" stop-color="#c084fc"/>
                            </linearGradient>
                        </defs>
                        <path d="M32 6 L56 20 L46 56 L18 56 L8 20 Z" fill="none" stroke="url(#pt-grad-stub)" stroke-width="2.5" stroke-linejoin="round"/>
                        <path d="M32 6 L32 56 M8 20 L56 20 M18 56 L46 56" stroke="url(#pt-grad-stub)" stroke-width="1.4" opacity="0.6"/>
                    </svg>
                </div>
                <div class="pt-ticket-stub-body">
                    <div class="pt-ticket-stub-meta">
                        <div class="pt-ticket-stub-row">
                            <span class="pt-ticket-stub-label">SHOW</span>
                            <span class="pt-ticket-stub-value">PREMIUM</span>
                        </div>
                        <div class="pt-ticket-stub-row">
                            <span class="pt-ticket-stub-label">SEAT</span>
                            <span class="pt-ticket-stub-value">A · 12</span>
                        </div>
                    </div>
                    <div class="pt-ticket-stub-headline">STAGE</div>
                    <div class="pt-ticket-stub-footer">
                        <div class="pt-ticket-stub-qr"></div>
                        <div class="flex flex-col leading-tight">
                            <span style="font-family:'Space Grotesk',sans-serif;font-weight:700;color:var(--prism-text);letter-spacing:.18em;font-size:11px;">REF · {{ strtoupper(substr(md5(now()), 0, 6)) }}</span>
                            <span style="margin-top:2px;font-size:9px;letter-spacing:.18em;">SCAN AT ENTRY</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =====================================================================
     2) Trust marquee
===================================================================== --}}
<section class="pt-trust prism-fade-up" aria-label="Trust" style="animation-delay:.06s;">
    <div class="pt-trust-track">
        @php
            $items = [
                ['key' => 'trust_instant', 'label' => 'حجز فوري'],
                ['key' => 'trust_seat',    'label' => 'اختيار مقعد مباشر'],
                ['key' => 'trust_secure',  'label' => 'دفع آمن'],
                ['key' => 'trust_qr',      'label' => 'QR على واتساب'],
                ['key' => 'trust_mobile',  'label' => 'يعمل على الموبايل'],
                ['key' => 'trust_247',     'label' => 'متاح 24/7'],
            ];
        @endphp
        @foreach(array_merge($items, $items) as $i => $item)
            <span class="pt-trust-item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 L9 17 L4 12"/></svg>
                <span data-i18n="{{ $item['key'] }}">{{ $item['label'] }}</span>
            </span>
        @endforeach
    </div>
</section>

{{-- =====================================================================
     3) Featured show (spotlight) — preserves all booking links & data
===================================================================== --}}
@php
    use App\Models\Show as ShowModel;
    // Returns a string suitable for the small price chip on a showtime row.
    // For section-priced shows we surface "بلكون / صالة" (with optional
    // "from X" hint), for everything else we keep the legacy single price.
    $priceChipFor = function ($show, $time) {
        if (!$show || $show->theater_type !== ShowModel::THEATER_ANBA_RUWEIS) {
            return [
                'label'       => $time->ticket_price,
                'label_key'   => null,
                'unit'        => 'EGP',
                'starts_from' => null,
            ];
        }
        $prices = array_values(array_filter([
            (int) ($show->hall_price    ?? 0),
            (int) ($show->balcony_price ?? 0),
        ]));
        $startsFrom = !empty($prices) ? min($prices) : null;
        return [
            // Bilingual: rendered with `shows_section_balcony_hall` key,
            // sub-line composed at render-time from `shows_from` + price + `shows_egp`.
            'label'       => 'بلكون / صالة',
            'label_key'   => 'shows_section_balcony_hall',
            'unit'        => null,
            'starts_from' => $startsFrom,
        ];
    };
@endphp
@if($featured)
<section class="mt-10 prism-fade-up" aria-labelledby="pt-featured-title" style="animation-delay:.12s;">
    <div class="pt-section-head">
        <div>
            <div class="prism-pill prism-pill-amber inline-flex items-center gap-2">
                <span class="prism-dot prism-dot-amber"></span>
                <span data-i18n="shows_eyebrow_featured">عرض مميز</span>
            </div>
            <h2 id="pt-featured-title" class="pt-section-title mt-2">{{ $featured->title }}</h2>
        </div>
        <div class="hidden sm:block">
            <span class="prism-pill">
                <span class="prism-dot prism-dot-emerald"></span>
                {{ $featured->showTimes->count() }} <span data-i18n="shows_pill_times">موعد متاح</span>
            </span>
        </div>
    </div>

    <article class="pt-featured prism-glow-border">
        <div class="pt-featured-poster">
            @if($featured->poster_path)
                <img src="{{ $featured->poster_path }}" alt="{{ $featured->title }}" loading="eager" decoding="async">
            @else
                <div class="w-full h-full flex items-center justify-center text-[color:var(--prism-text-4)]" data-i18n="shows_no_poster">بدون بوستر</div>
            @endif
        </div>
        <div class="pt-featured-content">
            <h3 class="pt-featured-title">{{ $featured->title }}</h3>
            <p class="text-sm sm:text-base leading-relaxed" style="color: var(--prism-text-2); white-space: pre-line;">{{ $featured->description }}</p>

            <div class="pt-show-times">
                @forelse($featured->showTimes->take(3) as $time)
                    <div class="pt-show-time">
                        <span class="flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span>
                                {{ $time->date->format('d/m/Y') }}
                                ·
                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </span>
                        </span>
                        @php $chip = $priceChipFor($featured, $time); @endphp
                        <span class="pt-show-time-price">
                            @if (!empty($chip['label_key']))
                                <span data-i18n="{{ $chip['label_key'] }}">{{ $chip['label'] }}</span>
                            @else
                                {{ $chip['label'] }}
                            @endif
                            @if (!empty($chip['unit']))
                                <span class="text-[10px] opacity-70">{{ $chip['unit'] }}</span>
                            @elseif (!empty($chip['starts_from']))
                                <span class="text-[10px] opacity-70">· <span data-i18n="shows_from">من</span> {{ $chip['starts_from'] }} <span data-i18n="shows_egp">جنيه</span></span>
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="text-xs" style="color: var(--prism-text-4);" data-i18n="shows_no_times_card">لا توجد مواعيد متاحة حاليا.</div>
                @endforelse
            </div>

            @php
                // Footer "من …" line: section-priced shows use min(hall, balcony)
                // from the show itself; everything else uses the cheapest
                // showtime ticket_price.
                if ($featured->theater_type === ShowModel::THEATER_ANBA_RUWEIS) {
                    $featuredFrom = array_values(array_filter([
                        (int) ($featured->hall_price    ?? 0),
                        (int) ($featured->balcony_price ?? 0),
                    ]));
                    $featuredFromLabel = !empty($featuredFrom) ? min($featuredFrom) : '—';
                } else {
                    $featuredFromLabel = $featured->showTimes->min('ticket_price') ?? '—';
                }
            @endphp
            <div class="pt-show-card-foot mt-2">
                <span class="text-xs" style="color: var(--prism-text-3);"><span data-i18n="shows_from">من</span> <span class="prism-wordmark" style="font-size:11px;">EGP</span> {{ $featuredFromLabel }}</span>
                <a href="{{ route('shows.show', $featured) }}" class="prism-btn prism-ripple">
                    <span data-i18n="btn_details_book">تفاصيل وحجز</span>
                    <span class="pt-arrow-rtl" aria-hidden="true">←</span>
                </a>
            </div>
        </div>
    </article>
</section>
@endif

{{-- =====================================================================
     4) Cinematic scroll storytelling (4 steps)
     - Alternating glass cards with neon-edge, drift orbs, and a
       central spine line on desktop. Reveals as user scrolls.
===================================================================== --}}
<section id="how-it-works" class="mt-14 sm:mt-20 prism-fade-up" aria-labelledby="pt-how-title" style="animation-delay:.18s;">
    <div class="pt-section-head pt-cinema-reveal pt-reveal">
        <div>
            <h2 id="pt-how-title" class="pt-section-title" data-i18n="how_title">كيف تحجز تذكرتك</h2>
            <div class="pt-section-sub" data-i18n="how_sub_4">أربع خطوات سينمائية من الاختيار حتى الواتساب.</div>
        </div>
    </div>

    <div class="pt-cinema-story">
        <article class="pt-cinema-step pt-cinema-reveal pt-cinema-reveal-l pt-reveal">
            <div class="pt-cinema-step-head">
                <span class="pt-cinema-step-emoji" aria-hidden="true">🎭</span>
                <span class="pt-cinema-step-num">01</span>
            </div>
            <h3 class="pt-cinema-step-title" data-i18n="cine_1_t">اختر عرضك</h3>
            <p class="pt-cinema-step-body" data-i18n="cine_1_b">تصفح العروض المباشرة واختر الموعد اللي يناسبك بلمسة واحدة.</p>
            <div class="pt-cinema-step-visual" aria-hidden="true">
                {{-- Card 1 mock: three floating poster panels --}}
                <div class="pt-cinema-mock-posters">
                    <span class="pt-cinema-mock-poster is-p1"></span>
                    <span class="pt-cinema-mock-poster is-p2"></span>
                    <span class="pt-cinema-mock-poster is-p3"></span>
                </div>
            </div>
            <span class="pt-cinema-step-sheen" aria-hidden="true"></span>
        </article>

        <article class="pt-cinema-step pt-cinema-reveal pt-cinema-reveal-r pt-reveal">
            <div class="pt-cinema-step-head">
                <span class="pt-cinema-step-emoji" aria-hidden="true">🪑</span>
                <span class="pt-cinema-step-num">02</span>
            </div>
            <h3 class="pt-cinema-step-title" data-i18n="cine_2_t">اختر مقعدك</h3>
            <p class="pt-cinema-step-body" data-i18n="cine_2_b">خريطة مباشرة للصالة توريلك المتاح لحظة بلحظة عشان تحجز مقعدك بثقة.</p>
            <div class="pt-cinema-step-visual" aria-hidden="true">
                {{-- Card 2 mock: 10x4 seat grid with one row pulse-glowing --}}
                <div class="pt-cinema-mock-seats">
                    @for ($i = 0; $i < 40; $i++)
                        @php
                            // Highlight a center row of 5 seats (row index 2, cols 3..7)
                            $row = intdiv($i, 10);
                            $col = $i % 10;
                            $isPick = ($row === 2 && $col >= 3 && $col <= 7);
                        @endphp
                        <span @if($isPick) class="is-pick" @endif></span>
                    @endfor
                </div>
            </div>
            <span class="pt-cinema-step-sheen" aria-hidden="true"></span>
        </article>

        <article class="pt-cinema-step pt-cinema-reveal pt-cinema-reveal-l pt-reveal">
            <div class="pt-cinema-step-head">
                <span class="pt-cinema-step-emoji" aria-hidden="true">📲</span>
                <span class="pt-cinema-step-num">03</span>
            </div>
            <h3 class="pt-cinema-step-title" data-i18n="cine_3_t">ارفع التحويل</h3>
            <p class="pt-cinema-step-body" data-i18n="cine_3_b">حوّل على المحفظة أو InstaPay وارفع صورة التحويل بثواني داخل تدفق آمن وأنيق.</p>
            <div class="pt-cinema-step-visual" aria-hidden="true">
                {{-- Card 3 mock: animated upload bar + pulsing checkmark --}}
                <div class="pt-cinema-mock-upload">
                    <div class="pt-cinema-mock-upload-bar"></div>
                    <div class="pt-cinema-mock-upload-check">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12 L10 17 L19 7"/></svg>
                    </div>
                </div>
            </div>
            <span class="pt-cinema-step-sheen" aria-hidden="true"></span>
        </article>

        <article class="pt-cinema-step pt-cinema-reveal pt-cinema-reveal-r pt-reveal">
            <div class="pt-cinema-step-head">
                <span class="pt-cinema-step-emoji" aria-hidden="true">🎟️</span>
                <span class="pt-cinema-step-num">04</span>
            </div>
            <h3 class="pt-cinema-step-title" data-i18n="cine_4_t">استلم تذكرتك</h3>
            <p class="pt-cinema-step-body" data-i18n="cine_4_b">تذكرة QR توصلك على واتساب فور الاعتماد · جاهزة للمسح عند البوابة.</p>
            <div class="pt-cinema-step-visual" aria-hidden="true">
                {{-- Card 4 mock: 8x4 QR module grid + horizontal light sweep --}}
                <div class="pt-cinema-mock-qr">
                    <div class="pt-cinema-mock-qr-grid">
                        @for ($i = 0; $i < 32; $i++)
                            <span style="animation-delay: {{ ($i % 8) * 0.06 + (intdiv($i, 8)) * 0.12 }}s;"></span>
                        @endfor
                    </div>
                    <span class="pt-cinema-mock-qr-sweep" aria-hidden="true"></span>
                </div>
            </div>
            <span class="pt-cinema-step-sheen" aria-hidden="true"></span>
        </article>
    </div>
</section>

{{-- =====================================================================
     5) Available shows (full grid)
     - Anchor #shows-grid is referenced from the navbar + drawer + hero CTA
===================================================================== --}}
<section id="shows-grid" class="mt-12 prism-fade-up" aria-labelledby="pt-shows-title" style="animation-delay:.24s;">
    <div class="pt-section-head">
        <div>
            <h2 id="pt-shows-title" class="pt-section-title" data-i18n="shows_title">العروض المتاحة</h2>
            <div class="pt-section-sub" data-i18n="shows_sub">اختر عرضك وابدأ الحجز.</div>
        </div>
        <span class="prism-pill prism-pill-neon">
            <span class="prism-dot prism-dot-emerald"></span>
            {{ $shows->count() }} <span data-i18n="hero_stat_shows_label">عرض متاح</span>
        </span>
    </div>

    @if($shows->isEmpty())
        <div class="prism-glass p-8 sm:p-10 text-center">
            <p class="text-[color:var(--prism-text-2)]">
                <span data-i18n="shows_empty_title">لا توجد عروض متاحة حاليا</span>.
                <span data-i18n="shows_empty_body">تابعنا — هنفعّل عروض جديدة قريبا.</span>
            </p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 prism-stagger pt-reveal pt-reveal-stagger">
            @foreach($rest->count() ? $rest : $shows as $show)
                <article class="pt-show-card pt-cinema-tilt pt-cinema-magnet group">
                    <span class="pt-show-card-glow" aria-hidden="true"></span>
                    @if($show->poster_path)
                        <a href="{{ route('shows.show', $show) }}" class="pt-show-poster" aria-label="{{ $show->title }}">
                            <img src="{{ $show->poster_path }}" alt="{{ $show->title }}" loading="lazy" decoding="async">
                            <span class="pt-show-poster-veil" aria-hidden="true"></span>
                        </a>
                    @endif

                    <div class="pt-show-card-body">
                        <h3 class="pt-show-card-title">{{ $show->title }}</h3>
                        <p class="pt-show-card-desc">{{ $show->description }}</p>

                        <div class="pt-show-times">
                            @forelse($show->showTimes->take(2) as $time)
                                <div class="pt-show-time">
                                    <span>
                                        {{ $time->date->format('d/m/Y') }}
                                        ·
                                        {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                                    </span>
                                    @php $chip = $priceChipFor($show, $time); @endphp
                                    <span class="pt-show-time-price">
                                        @if (!empty($chip['label_key']))
                                            <span data-i18n="{{ $chip['label_key'] }}">{{ $chip['label'] }}</span>
                                        @else
                                            {{ $chip['label'] }}
                                        @endif
                                        @if (!empty($chip['unit']))
                                            <span class="text-[10px] opacity-70">{{ $chip['unit'] }}</span>
                                        @elseif (!empty($chip['starts_from']))
                                            <span class="text-[10px] opacity-70">· <span data-i18n="shows_from">من</span> {{ $chip['starts_from'] }} <span data-i18n="shows_egp">جنيه</span></span>
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <div class="text-xs" style="color: var(--prism-text-4);" data-i18n="shows_no_times_card">لا توجد مواعيد متاحة حاليا.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="pt-show-card-foot">
                        <span class="text-xs flex items-center gap-2" style="color: var(--prism-text-3);">
                            <span class="prism-dot prism-dot-emerald" style="animation: prismGlowPulse 2s ease-in-out infinite;"></span>
                            {{ $show->showTimes->count() }} <span data-i18n="shows_pill_times">موعد متاح</span>
                        </span>
                        <a href="{{ route('shows.show', $show) }}" class="prism-btn prism-ripple text-xs">
                            <span data-i18n="btn_details_book">تفاصيل وحجز</span>
                            <span class="pt-arrow-rtl" aria-hidden="true">←</span>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@endsection
