@extends('layouts.app')

@section('title', 'العروض المتاحة · Premium Tickets')

@section('content')

    {{-- =====================================================================
         Hero — neutral premium identity. The previous Elsar5a/Sarkha team
         identity (logo, slogan, manifesto copy) was removed per the design
         brief and replaced with a generic premium pitch that explains the
         booking experience without referencing any specific brand.
    ===================================================================== --}}
    <section class="mb-10 prism-fade-up">
        <div class="prism-glass prism-glow-border p-5 sm:p-7 md:p-8 grid md:grid-cols-[1fr,auto] gap-6 items-center">

            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="prism-pill prism-pill-neon">
                        <span class="prism-dot prism-dot-emerald"></span>
                        Live Booking
                    </span>
                    <span class="prism-pill">
                        <span class="prism-tagline" style="letter-spacing:.32em;">PREMIUM · STAGE · 2025</span>
                    </span>
                </div>

                <h1 class="prism-headline text-2xl sm:text-3xl md:text-4xl leading-tight">
                    <span class="block" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        احجز تجربتك على المسرح
                    </span>
                    <span class="block text-[color:var(--prism-text-2)] text-base sm:text-lg md:text-xl font-medium mt-1">
                        مقعدك. سعرك. تذكرتك على واتساب.
                    </span>
                </h1>

                <p class="text-sm sm:text-base text-[color:var(--prism-text-2)] leading-relaxed max-w-2xl">
                    منصّة حجز سلسة وأنيقة: تختار العرض، تحجز مقعدك من الخريطة المباشرة،
                    تدفع بأمان، وتستقبل تذكرتك بكود QR على واتساب — كل ده في أقل من دقيقة.
                </p>

                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <span class="prism-pill"><span class="prism-dot prism-dot-sky"></span> اختيار مقعد فوري</span>
                    <span class="prism-pill"><span class="prism-dot prism-dot-amber"></span> دفع آمن</span>
                    <span class="prism-pill"><span class="prism-dot prism-dot-emerald"></span> تذكرة QR</span>
                </div>
            </div>

            {{-- Decorative orb (replaces previous brand logo) --}}
            <div class="hidden md:flex justify-end">
                <div class="relative w-44 h-44 lg:w-52 lg:h-52">
                    <div class="absolute inset-0 rounded-full"
                         style="background: radial-gradient(circle at 30% 30%, rgba(34,211,238,0.55), transparent 60%),
                                            radial-gradient(circle at 70% 70%, rgba(192,132,252,0.55), transparent 60%);
                                filter: blur(20px); opacity: 0.85;"></div>
                    <div class="absolute inset-3 rounded-full prism-glass-strong flex items-center justify-center"
                         style="border-color: rgba(129,140,248,0.45);">
                        <svg width="96" height="96" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="prism-grad-hero" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" stop-color="#22d3ee"/>
                                    <stop offset="0.5" stop-color="#818cf8"/>
                                    <stop offset="1" stop-color="#c084fc"/>
                                </linearGradient>
                            </defs>
                            <path d="M32 6 L56 20 L46 56 L18 56 L8 20 Z"
                                  fill="none" stroke="url(#prism-grad-hero)" stroke-width="2.4" stroke-linejoin="round"/>
                            <path d="M32 6 L32 56 M8 20 L56 20 M18 56 L46 56"
                                  stroke="url(#prism-grad-hero)" stroke-width="1.2" opacity="0.6"/>
                        </svg>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- =====================================================================
         Available Shows — preserved data, redesigned cards.
    ===================================================================== --}}
    <section class="space-y-4 prism-fade-up" style="animation-delay:.08s;">

        <div class="flex items-center justify-between mb-2 gap-2">
            <h2 class="prism-headline text-xl sm:text-2xl">العروض المتاحة</h2>
            @if(!$shows->isEmpty())
                <span class="prism-pill prism-pill-neon">
                    {{ $shows->count() }} عرض متاح للحجز
                </span>
            @endif
        </div>

        @if($shows->isEmpty())
            <div class="prism-glass p-6 text-center">
                <div class="text-[color:var(--prism-text-2)] text-sm">
                    لسه مفيش عروض متاحة حاليًا — انتظرنا قريبًا.
                </div>
            </div>
        @else
            <div class="grid md:grid-cols-2 gap-5 prism-stagger pt-reveal pt-reveal-stagger">
                @foreach($shows as $show)
                    <article class="prism-glass prism-card-hover p-4 flex flex-col justify-between">

                        {{-- Featured badge for the first show --}}
                        @if($loop->first)
                            <div class="mb-2">
                                <span class="prism-pill prism-pill-neon">
                                    <span class="prism-dot prism-dot-amber"></span>
                                    عرض مُميز
                                </span>
                            </div>
                        @endif

                        {{-- Poster --}}
                        @if($show->poster_path)
                            <div class="relative mb-4 rounded-xl overflow-hidden border border-[color:var(--prism-border)]">
                                <img
                                    src="{{ $show->poster_path }}"
                                    alt="{{ $show->title }}"
                                    class="w-full h-auto object-contain transition-transform duration-500 group-hover:scale-[1.02]">
                                <div class="absolute inset-0 pointer-events-none"
                                     style="background: linear-gradient(180deg, transparent 50%, rgba(5,6,13,0.55) 100%);"></div>
                                <div class="absolute bottom-2 left-2">
                                    <span class="prism-pill prism-pill-neon">احجز قبل النفاد</span>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-2">
                            <h3 class="prism-headline text-lg sm:text-xl text-[color:var(--prism-text)]">
                                {{ $show->title }}
                            </h3>
                            <p class="text-sm text-[color:var(--prism-text-2)] leading-relaxed whitespace-pre-line">
                                {{ $show->description }}
                            </p>

                            <div class="mt-4 space-y-2 text-xs text-[color:var(--prism-text-3)]">
                                <p class="font-medium">المواعيد:</p>
                                <ul class="space-y-1.5">
                                    @forelse($show->showTimes->take(2) as $time)
                                        <li class="flex items-center justify-between bg-white/5 border border-[color:var(--prism-border)] rounded-lg px-3 py-2">
                                            <span class="text-[color:var(--prism-text-2)]">
                                                {{ $time->date->format('d/m/Y') }}
                                                ·
                                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                                            </span>
                                            <span class="text-[color:var(--prism-gold)] font-semibold">
                                                {{ $time->ticket_price }} <span class="text-[10px] opacity-70">EGP</span>
                                            </span>
                                        </li>
                                    @empty
                                        <li class="text-[11px] text-[color:var(--prism-text-4)]">
                                            لا توجد مواعيد متاحة حاليًا لهذا العرض.
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-xs text-[color:var(--prism-text-3)] flex items-center gap-2">
                                <span class="prism-dot prism-dot-emerald" style="animation: prismGlowPulse 2s ease-in-out infinite;"></span>
                                {{ $show->showTimes->count() }} موعد متاح
                            </span>
                            <a href="{{ route('shows.show', $show) }}"
                               class="prism-btn prism-ripple">
                                تفاصيل وحجز
                                <span aria-hidden="true">←</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

    </section>
@endsection
