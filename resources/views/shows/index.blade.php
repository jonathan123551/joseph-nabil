@extends('layouts.app')

@section('title', 'العروض المتاحة · Premium Tickets')

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
<section class="pt-hero prism-fade-up" aria-labelledby="pt-hero-heading">
    <div class="pt-hero-marquee" aria-hidden="true">
        <i></i><i></i><i></i><i></i><i></i><i></i><i></i>
    </div>
    <div class="pt-hero-beam pt-hero-beam-left" aria-hidden="true"></div>
    <div class="pt-hero-beam pt-hero-beam-right" aria-hidden="true"></div>
    <div class="pt-hero-curtain" aria-hidden="true"></div>

    <div class="pt-hero-inner">
        <div>
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
                <a href="#shows-grid" class="prism-btn prism-ripple" data-i18n="hero_cta_primary">تصفح العروض</a>
                <a href="#how-it-works" class="prism-btn-ghost" data-i18n="hero_cta_secondary">كيف يعمل؟</a>
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
        <div class="pt-hero-art" aria-hidden="true">
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
<section class="pt-trust prism-fade-up" aria-label="Why us" style="animation-delay:.06s;">
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
@if($featured)
<section class="mt-10 prism-fade-up" aria-labelledby="pt-featured-title" style="animation-delay:.12s;">
    <div class="pt-section-head">
        <div>
            <div class="prism-pill prism-pill-amber inline-flex items-center gap-2">
                <span class="prism-dot prism-dot-amber"></span>
                <span>عرض مميز</span>
            </div>
            <h2 id="pt-featured-title" class="pt-section-title mt-2">{{ $featured->title }}</h2>
        </div>
        <div class="hidden sm:block">
            <span class="prism-pill">
                <span class="prism-dot prism-dot-emerald"></span>
                {{ $featured->showTimes->count() }} موعد متاح
            </span>
        </div>
    </div>

    <article class="pt-featured prism-glow-border">
        <div class="pt-featured-poster">
            @if($featured->poster_path)
                <img src="{{ $featured->poster_path }}" alt="{{ $featured->title }}" loading="eager" decoding="async">
            @else
                <div class="w-full h-full flex items-center justify-center text-[color:var(--prism-text-4)]">No poster</div>
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
                        <span class="pt-show-time-price">{{ $time->ticket_price }} <span class="text-[10px] opacity-70">EGP</span></span>
                    </div>
                @empty
                    <div class="text-xs" style="color: var(--prism-text-4);">لا توجد مواعيد متاحة حاليا.</div>
                @endforelse
            </div>

            <div class="pt-show-card-foot mt-2">
                <span class="text-xs" style="color: var(--prism-text-3);">من <span class="prism-wordmark" style="font-size:11px;">EGP</span> {{ $featured->showTimes->min('ticket_price') ?? '—' }}</span>
                <a href="{{ route('shows.show', $featured) }}" class="prism-btn prism-ripple">
                    تفاصيل وحجز <span aria-hidden="true">←</span>
                </a>
            </div>
        </div>
    </article>
</section>
@endif

{{-- =====================================================================
     4) How it works (3 steps)
===================================================================== --}}
<section id="how-it-works" class="mt-12 prism-fade-up" aria-labelledby="pt-how-title" style="animation-delay:.18s;">
    <div class="pt-section-head">
        <div>
            <h2 id="pt-how-title" class="pt-section-title" data-i18n="how_title">كيف تحجز تذكرتك</h2>
            <div class="pt-section-sub" data-i18n="how_sub">ثلاث خطوات بسيطة من الاختيار حتى الواتساب.</div>
        </div>
    </div>

    <div class="pt-how">
        <div class="pt-how-step">
            <div class="pt-how-step-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 12h18"/></svg>
            </div>
            <div class="pt-how-step-num">01</div>
            <div class="pt-how-step-title" data-i18n="how_1_t">اختر العرض</div>
            <div class="pt-how-step-body" data-i18n="how_1_b">استعرض العروض المتاحة واختر الموعد المناسب.</div>
        </div>
        <div class="pt-how-step">
            <div class="pt-how-step-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18M5 14h14M7 18h10"/><circle cx="12" cy="6" r="2"/></svg>
            </div>
            <div class="pt-how-step-num">02</div>
            <div class="pt-how-step-title" data-i18n="how_2_t">احجز مقعدك</div>
            <div class="pt-how-step-body" data-i18n="how_2_b">اختر مقعدك من خريطة القاعة المباشرة وادفع بأمان.</div>
        </div>
        <div class="pt-how-step">
            <div class="pt-how-step-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><path d="M14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z"/></svg>
            </div>
            <div class="pt-how-step-num">03</div>
            <div class="pt-how-step-title" data-i18n="how_3_t">استقبل التذكرة</div>
            <div class="pt-how-step-body" data-i18n="how_3_b">تذكرة QR تصلك على واتساب في أقل من دقيقة.</div>
        </div>
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
            {{ $shows->count() }} عرض متاح للحجز
        </span>
    </div>

    @if($shows->isEmpty())
        <div class="prism-glass p-8 sm:p-10 text-center">
            <p class="text-[color:var(--prism-text-2)]">لا توجد عروض متاحة حالياً. تابعونا قريباً.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 prism-stagger pt-reveal pt-reveal-stagger">
            @foreach($rest->count() ? $rest : $shows as $show)
                <article class="pt-show-card group">
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
                                    <span class="pt-show-time-price">{{ $time->ticket_price }} <span class="text-[10px] opacity-70">EGP</span></span>
                                </div>
                            @empty
                                <div class="text-xs" style="color: var(--prism-text-4);">لا توجد مواعيد متاحة حالياً.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="pt-show-card-foot">
                        <span class="text-xs flex items-center gap-2" style="color: var(--prism-text-3);">
                            <span class="prism-dot prism-dot-emerald" style="animation: prismGlowPulse 2s ease-in-out infinite;"></span>
                            {{ $show->showTimes->count() }} موعد متاح
                        </span>
                        <a href="{{ route('shows.show', $show) }}" class="prism-btn prism-ripple text-xs">
                            تفاصيل وحجز <span aria-hidden="true">←</span>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@endsection
