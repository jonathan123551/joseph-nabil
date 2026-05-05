@extends('layouts.app')

@section('title', 'إدارة المقاعد - ' . $showTime->show->title)

@section('content')

<section class="space-y-4 max-w-5xl mx-auto">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold mb-1">إدارة المقاعد</h1>
            <p class="text-xs text-gray-400">
                {{ $showTime->show->title }} ·
                {{ $showTime->date->format('d/m/Y') }} ·
                {{ \Carbon\Carbon::parse($showTime->time)->format('g:i A') }}
            </p>
        </div>
        <a href="{{ route('admin.shows.times.index', $showTime->show) }}"
           class="text-xs px-3 py-1.5 rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition">
            ← رجوع
        </a>
    </div>

    @if(session('status'))
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-200 text-xs rounded-xl p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="text-xs text-gray-300 bg-black/40 border border-white/10 rounded-xl p-3 leading-relaxed">
        اضغط على أي مقعد لحجبه/إعادة تفعيله. المقاعد المحجوزة من العملاء لا يمكن تعديلها من هنا (ارفض الحجز أولًا).
    </div>

    {{-- Stage marker --}}
    <div class="text-center">
        <div class="inline-block px-6 py-1.5 rounded-full bg-amber-400/15 border border-amber-400/40 text-amber-200 text-[11px] tracking-widest">
            STAGE
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-3 text-[10px] text-gray-300 justify-center">
        <span class="flex items-center gap-1">
            <span class="w-3 h-3 rounded-sm bg-gray-500/70 border border-gray-400 inline-block"></span>
            متاح
        </span>
        <span class="flex items-center gap-1">
            <span class="w-3 h-3 rounded-sm bg-yellow-500 border border-yellow-300 inline-block"></span>
            محجوب (إدارة)
        </span>
        <span class="flex items-center gap-1">
            <span class="w-3 h-3 rounded-sm bg-red-600 border border-red-500 inline-block"></span>
            محجوز (عميل)
        </span>
    </div>

    @php
        $bookedSet  = collect($bookedSeatIds)->flip();
        $blockedSet = collect($blockedSeatIds)->flip();
    @endphp

    @foreach(['balcony' => 'بلكون', 'hall' => 'صالة'] as $section => $label)
        <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-2">
            <h3 class="text-sm font-semibold text-amber-300">{{ $label }}</h3>

            <div class="overflow-x-auto">
                <div class="min-w-[640px] mx-auto space-y-1 select-none">
                    @foreach($seatsByRow[$section] ?? [] as $rowLetter => $sides)
                        <div class="flex justify-center items-center gap-3 py-0.5">
                            <div class="w-5 text-[10px] text-gray-400 text-center">{{ $rowLetter }}</div>

                            @foreach(['left' => 'justify-end', 'center' => 'justify-center', 'right' => 'justify-start'] as $side => $align)
                                <div class="flex items-center gap-[2px] flex-1 {{ $align }}">
                                    @foreach($sides[$side] as $seat)
                                        @php
                                            $isBooked  = $bookedSet->has($seat->id);
                                            $isBlocked = $blockedSet->has($seat->id);
                                            $cls = $isBooked
                                                ? 'bg-red-600 border-red-500 text-red-100 cursor-not-allowed opacity-90'
                                                : ($isBlocked
                                                    ? 'bg-yellow-500 border-yellow-300 text-black hover:bg-yellow-400'
                                                    : 'bg-gray-500/70 border-gray-400 text-white hover:bg-amber-400/30');
                                        @endphp
                                        <form action="{{ route('admin.show-times.seats.toggle', [$showTime, $seat]) }}"
                                              method="POST"
                                              class="m-0 p-0">
                                            @csrf
                                            <button type="submit"
                                                    title="{{ $rowLetter }}{{ $seat->seat_number }}"
                                                    @if($isBooked) disabled @endif
                                                    class="w-6 h-6 text-[9px] rounded-sm border transition {{ $cls }}">
                                                {{ $seat->seat_number }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endforeach

                            <div class="w-5 text-[10px] text-gray-400 text-center">{{ $rowLetter }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

</section>

@endsection
