{{-- resources/views/shows/show.blade.php --}}
@extends('layouts.app')

@section('title', $show->title . ' · PRISM')

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

                        <div class="absolute top-3 right-3">
                            <span class="prism-pill prism-pill-neon">عرض مسرحي</span>
                        </div>
                    </div>
                @endif

                <div class="flex-1 space-y-3">
                    <h1 class="prism-headline text-2xl md:text-3xl">{{ $show->title }}</h1>

                    <p class="text-sm text-[color:var(--prism-text-2)] leading-relaxed whitespace-pre-line">
                        {{ $show->description }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="prism-pill"><span class="prism-dot prism-dot-sky"></span> حجز إلكتروني</span>
                        <span class="prism-pill"><span class="prism-dot prism-dot-emerald"></span> تذكرة QR</span>
                    </div>
                </div>

            </div>
        </div>

        {{-- ===== Available show times — preserved data, restyled rows ===== --}}
        <div class="space-y-3">
            <h2 class="prism-headline text-lg sm:text-xl">المواعيد المتاحة</h2>

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

                        {{-- Left status accent rail --}}
                        <div class="absolute right-0 top-0 bottom-0 w-1 rounded-r-2xl
                            @if($isSoldOut)
                                bg-rose-500
                            @elseif($fewTickets)
                                bg-amber-400
                            @else
                                bg-emerald-400
                            @endif"
                            style="box-shadow: 0 0 14px currentColor;">
                        </div>

                        <div class="pr-3 space-y-1">
                            <div class="text-sm font-medium text-[color:var(--prism-text)]">
                                {{ $time->date->format('d/m/Y') }}
                                <span class="text-[color:var(--prism-text-4)] mx-1">·</span>
                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </div>

                            <div class="text-xs text-[color:var(--prism-text-3)] flex flex-wrap gap-2 items-center">

                                <span>
                                    سعر التذكرة:
                                    <span class="text-[color:var(--prism-gold)] font-semibold">
                                        {{ $time->ticket_price }} جنيه
                                    </span>
                                </span>

                                <span class="prism-pill
                                    @if($isSoldOut)        prism-badge-rose
                                    @elseif($fewTickets)   prism-badge-amber
                                    @else                  prism-badge-emerald
                                    @endif border">
                                    @if($isSoldOut)
                                        Sold Out
                                    @elseif($fewTickets)
                                        تبقّى {{ $remaining }} تذكرة
                                    @else
                                        متاح للحجز
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="pl-1">
                            @if($isSoldOut)
                                <span class="prism-pill prism-badge-rose border">Sold Out</span>
                            @else
                                <a href="{{ route('bookings.create', $time) }}"
                                   class="@if($fewTickets) prism-btn-gold @else prism-btn @endif prism-ripple">
                                    احجز الآن
                                    <span aria-hidden="true">←</span>
                                </a>
                            @endif
                        </div>

                    </div>

                @empty
                    <p class="text-xs text-[color:var(--prism-text-3)]">
                        لا توجد مواعيد متاحة حاليًا لهذا العرض.
                    </p>
                @endforelse

            </div>
        </div>

        <a href="{{ route('shows.index') }}" class="inline-flex items-center gap-2 text-sm text-[color:var(--prism-text-3)] hover:text-[color:var(--prism-text)] transition">
            <span aria-hidden="true">→</span>
            رجوع لكل العروض
        </a>

    </section>

@endsection
