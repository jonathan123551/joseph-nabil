@extends('layouts.app')

@section('title', 'إدارة المقاعد - ' . $showTime->show->title)

@section('content')
<section class="space-y-5 max-w-5xl mx-auto prism-fade-up">

    {{-- Header --}}
    <div class="prism-glass prism-glow-border p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                Seats Management
            </span>
            <h1 class="prism-headline text-xl sm:text-2xl">
                <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    إدارة المقاعد
                </span>
            </h1>
            <p class="text-xs text-[color:var(--prism-text-3)]">
                {{ $showTime->show->title }} ·
                {{ $showTime->date->format('d/m/Y') }} ·
                {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}
            </p>
        </div>

        <a href="{{ route('admin.shows.times.index', $showTime->show) }}" class="prism-btn-ghost text-xs">
            <span aria-hidden="true">→</span>
            رجوع
        </a>
    </div>

    @if(session('status'))
        <div class="rounded-xl px-4 py-3 text-sm prism-fade-up"
             style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
            {{ session('status') }}
        </div>
    @endif

    <div class="prism-glass p-3.5 text-xs text-[color:var(--prism-text-2)] leading-relaxed prism-fade-up">
        اضغط على أي مقعد لحجبه/إعادة تفعيله. المقاعد المحجوزة من العملاء لا يمكن تعديلها من هنا (ارفض الحجز أولًا).
    </div>

    {{-- Stage marker --}}
    <div class="text-center prism-fade-up">
        <div class="inline-block px-6 py-1.5 rounded-full text-[11px] tracking-widest"
             style="background: linear-gradient(135deg, rgba(34,211,238,0.14), rgba(192,132,252,0.14));
                    border: 1px solid var(--prism-border-strong);
                    color: #e0e7ff;
                    box-shadow: 0 0 18px rgba(129,140,248,0.25), 0 0 36px rgba(34,211,238,0.10);">
            STAGE
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-3 text-[10px] text-[color:var(--prism-text-2)] justify-center prism-fade-up">
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm inline-block"
                  style="background: rgba(58,66,86,0.85); border: 1px solid rgba(180,200,230,0.18);"></span>
            متاح
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm inline-block"
                  style="background: #fbbf24; border: 1px solid rgba(253,224,71,0.75); box-shadow: 0 0 8px rgba(251,191,36,0.5);"></span>
            محجوب (إدارة)
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm inline-block"
                  style="background: #fb7185; border: 1px solid rgba(252,165,165,0.65); box-shadow: 0 0 8px rgba(251,113,133,0.5);"></span>
            محجوز (عميل)
        </span>
    </div>

    @php
        $bookedSet  = collect($bookedSeatIds)->flip();
        $blockedSet = collect($blockedSeatIds)->flip();
    @endphp

    @foreach(['balcony' => 'بلكون', 'hall' => 'صالة'] as $section => $label)
        <div class="prism-glass p-4 space-y-3 prism-fade-up">
            <h3 class="text-sm font-semibold"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $label }}
            </h3>

            <div class="overflow-x-auto">
                <div class="min-w-[640px] mx-auto space-y-1 select-none">
                    @foreach($seatsByRow[$section] ?? [] as $rowLetter => $sides)
                        <div class="flex justify-center items-center gap-3 py-0.5">
                            <div class="w-5 text-[10px] text-[color:var(--prism-text-3)] text-center">{{ $rowLetter }}</div>

                            @foreach(['left' => 'justify-end', 'center' => 'justify-center', 'right' => 'justify-start'] as $side => $align)
                                <div class="flex items-center gap-[2px] flex-1 {{ $align }}">
                                    @foreach($sides[$side] as $seat)
                                        @php
                                            $isBooked  = $bookedSet->has($seat->id);
                                            $isBlocked = $blockedSet->has($seat->id);
                                        @endphp
                                        <form action="{{ route('admin.show-times.seats.toggle', [$showTime, $seat]) }}"
                                              method="POST"
                                              class="m-0 p-0">
                                            @csrf
                                            <button type="submit"
                                                    title="{{ $rowLetter }}{{ $seat->seat_number }}"
                                                    @if($isBooked) disabled @endif
                                                    class="w-6 h-6 text-[9px] rounded-md border transition"
                                                    style="
                                                        @if($isBooked)
                                                            background: linear-gradient(180deg, #fb7185, #7f1d1d);
                                                            border-color: rgba(252,165,165,0.65);
                                                            color: #fee2e2;
                                                            cursor: not-allowed;
                                                            opacity: 0.92;
                                                        @elseif($isBlocked)
                                                            background: linear-gradient(180deg, #fbbf24, #713f12);
                                                            border-color: rgba(253,224,71,0.75);
                                                            color: #fef3c7;
                                                        @else
                                                            background: linear-gradient(180deg, #3a4256, #1a1f2e);
                                                            border-color: rgba(180,200,230,0.18);
                                                            color: rgba(255,255,255,0.85);
                                                        @endif
                                                    "
                                                    @if(!$isBooked)
                                                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 0 12px rgba(129,140,248,0.55)';"
                                                        onmouseout="this.style.transform=''; this.style.boxShadow='';"
                                                    @endif>
                                                {{ $seat->seat_number }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endforeach

                            <div class="w-5 text-[10px] text-[color:var(--prism-text-3)] text-center">{{ $rowLetter }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

</section>
@endsection
