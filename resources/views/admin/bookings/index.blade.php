@extends('layouts.app')

@section('title', 'إدارة الحجوزات')

@section('content')
<section class="space-y-6">

    {{-- ========================== HEADER ========================== --}}
    <div class="prism-glass prism-glow-border p-5 prism-fade-up flex items-center justify-between gap-3 flex-wrap">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                Bookings
            </span>
            <h1 class="prism-headline text-xl sm:text-2xl">
                <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    إدارة الحجوزات
                </span>
            </h1>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
            <span aria-hidden="true">→</span>
            رجوع
        </a>
    </div>

    {{-- ========================== STATUS FLASH ========================== --}}
    @if(session('status'))
        <div class="rounded-2xl px-4 py-3 text-sm prism-fade-up"
             style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
            {{ session('status') }}
        </div>
    @endif

    {{-- ========================== FILTERS ========================== --}}
    <div class="prism-glass p-4 prism-fade-up">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            <input id="searchInput" type="text"
                   placeholder="بحث بالاسم / الموبايل / كود الحجز"
                   class="prism-input text-xs">

            <select id="statusFilter" class="prism-input text-xs">
                <option value="">كل الحالات</option>
                <option value="pending">pending</option>
                <option value="approved">approved</option>
                <option value="rejected">rejected</option>
            </select>

            <select id="dateTimeFilter" class="prism-input text-xs">
                <option value="">كل المواعيد</option>
                @foreach(
                    $bookings->map(fn($b) => $b->showTime
                        ? $b->showTime->date->format('Y-m-d').' '.$b->showTime->time
                        : null)->filter()->unique()->sort()
                    as $dt
                )
                    <option value="{{ $dt }}">
                        {{ \Carbon\Carbon::parse($dt)->format('d/m/Y • g:i A') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ========================== DESKTOP TABLE ========================== --}}
    <div class="hidden md:block prism-fade-up">
        <div class="prism-glass overflow-x-auto">
            <table class="w-full text-sm text-[color:var(--prism-text-2)]">
                <thead style="background: rgba(255,255,255,0.04);">
                    <tr class="text-xs uppercase" style="letter-spacing:.14em; color: var(--prism-text-3);">
                        <th class="px-3 py-3 text-right">الضيف</th>
                        <th class="px-3 py-3 text-right">العرض / الموعد</th>
                        <th class="px-3 py-3 text-right">الحالة</th>
                        <th class="px-3 py-3 text-center">التذكرة</th>
                        <th class="px-3 py-3 text-right">إجراءات</th>
                        <th class="px-3 py-3 text-right">الكود</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($bookings as $booking)
                    @php
                        $dt = $booking->showTime
                            ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                            : '';
                    @endphp
                    <tr class="booking-row"
                        style="border-top: 1px solid rgba(255,255,255,0.06); transition: background .15s ease;"
                        onmouseover="this.style.background='rgba(129,140,248,0.06)'"
                        onmouseout="this.style.background=''"
                        data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                        data-status="{{ $booking->status }}"
                        data-datetime="{{ $dt }}">

                        <td class="px-3 py-3 align-top">
                            <div>
                                <p class="font-bold text-[color:var(--prism-text)]">{{ $booking->full_name }}</p>
                                <p class="text-xs" style="color: var(--prism-gold);">
                                    🎟️ {{ $booking->tickets_count }} تذكرة
                                </p>
                            </div>
                            <span class="text-[color:var(--prism-text-3)] block mb-1">{{ $booking->phone }}</span>

                            @foreach($booking->tickets as $ticket)
                                <div class="text-xs text-[color:var(--prism-text-3)] mr-2">
                                    👤 {{ $ticket->name }} - 📱 {{ $ticket->phone }}
                                </div>
                            @endforeach
                        </td>

                        <td class="px-3 py-3 align-top">
                            <span class="text-[color:var(--prism-text)]">{{ $booking->showTime->show->title ?? '-' }}</span><br>
                            <span class="text-[color:var(--prism-text-3)] text-xs">
                                {{ $booking->showTime?->date->format('d/m/Y') }}
                                • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                            </span>
                        </td>

                        <td class="px-3 py-3 align-top">
                            <span class="prism-pill {{ $booking->status==='approved' ? 'prism-pill-emerald'
                                                       : ($booking->status==='rejected' ? 'prism-pill-rose' : 'prism-pill-sky') }}">
                                {{ $booking->status }}
                            </span>
                        </td>

                        <td class="px-3 py-3 align-top text-center">
                            @php
                                $allSent = $booking->tickets->every(fn($t) => $t->whatsapp_sent);
                                $total   = $booking->tickets->count();
                                $sent    = $booking->tickets->where('whatsapp_sent', true)->count();
                            @endphp
                            <div class="flex flex-col items-center gap-1">
                                <span class="inline-block w-3 h-3 rounded-full"
                                      style="background: {{ $allSent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};
                                             box-shadow: 0 0 10px {{ $allSent ? 'rgba(52,211,153,0.7)' : 'rgba(251,113,133,0.7)' }};"></span>
                                <span class="text-[10px] text-[color:var(--prism-text-3)]">
                                    {{ $sent }}/{{ $total }}
                                </span>
                            </div>
                        </td>

                        <td class="px-3 py-3 align-top">
                            <a href="{{ route('admin.bookings.show',$booking) }}" class="prism-btn-ghost text-xs px-3 py-1.5">
                                تفاصيل
                            </a>
                        </td>

                        <td class="px-3 py-3 align-top font-mono text-xs"
                            style="color: var(--prism-text-2);">
                            {{ $booking->reference_code }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================== MOBILE CARDS ========================== --}}
    <div class="md:hidden space-y-3 prism-stagger">
        @foreach($bookings as $booking)
            @php
                $dt = $booking->showTime
                    ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                    : '';
            @endphp

            <div class="prism-glass p-4 text-xs booking-card prism-fade-up"
                 data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                 data-status="{{ $booking->status }}"
                 data-datetime="{{ $dt }}">

                <div class="flex justify-between mb-2 gap-2">
                    <div>
                        <div class="font-semibold text-sm text-[color:var(--prism-text)]">{{ $booking->full_name }}</div>

                        <div class="text-xs mb-1" style="color: var(--prism-gold);">
                            🎟️ {{ $booking->tickets_count }} تذكرة
                        </div>

                        <div class="text-[color:var(--prism-text-3)]">{{ $booking->phone }}</div>

                        @foreach($booking->tickets as $ticket)
                            <div class="text-[11px] text-[color:var(--prism-text-3)]">
                                👤 {{ $ticket->name }} - 📱 {{ $ticket->phone }}
                            </div>
                        @endforeach
                    </div>
                    <span class="font-mono px-2 py-1 rounded text-[10px] h-fit"
                          style="background: rgba(255,255,255,0.05); border: 1px solid var(--prism-border); color: var(--prism-text-2);">
                        {{ $booking->reference_code }}
                    </span>
                </div>

                <div class="mb-3">
                    <span class="text-[color:var(--prism-text)]">🎭 {{ $booking->showTime->show->title ?? '-' }}</span><br>
                    <span class="text-[color:var(--prism-text-3)]">
                        🕒 {{ $booking->showTime?->date->format('d/m/Y') }}
                        • {{ \Carbon\Carbon::parse($booking->showTime?->time)->format('g:i A') }}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <span class="prism-pill {{ $booking->status==='approved' ? 'prism-pill-emerald'
                                               : ($booking->status==='rejected' ? 'prism-pill-rose' : 'prism-pill-sky') }}">
                        {{ $booking->status }}
                    </span>

                    @php
                        $allSent = $booking->tickets->every(fn($t) => $t->whatsapp_sent);
                        $total   = $booking->tickets->count();
                        $sent    = $booking->tickets->where('whatsapp_sent', true)->count();
                    @endphp

                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full"
                              style="background: {{ $allSent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};
                                     box-shadow: 0 0 8px {{ $allSent ? 'rgba(52,211,153,0.7)' : 'rgba(251,113,133,0.7)' }};"></span>
                        <span class="text-[color:var(--prism-text-3)] text-[11px]">
                            {{ $sent }}/{{ $total }}
                        </span>
                    </div>

                    <a href="{{ route('admin.bookings.show',$booking) }}" class="prism-btn-ghost text-xs px-3 py-1.5">
                        تفاصيل
                    </a>
                </div>
            </div>
        @endforeach
    </div>

</section>

{{-- =============== JS FILTER (logic preserved) =============== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('searchInput');
    const status = document.getElementById('statusFilter');
    const dt     = document.getElementById('dateTimeFilter');
    const items  = document.querySelectorAll('.booking-row, .booking-card');

    function filter(){
        const s = search.value.toLowerCase();
        items.forEach(el=>{
            const ok =
                el.dataset.search.includes(s) &&
                (!status.value || el.dataset.status===status.value) &&
                (!dt.value || el.dataset.datetime===dt.value);
            el.style.display = ok ? '' : 'none';
        });
    }

    [search,status,dt].forEach(i=>{
        i.addEventListener('input',filter);
        i.addEventListener('change',filter);
    });
});
</script>
@endsection
