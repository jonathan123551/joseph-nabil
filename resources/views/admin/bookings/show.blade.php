@extends('layouts.app')

@section('title', 'تفاصيل الحجز #' . $booking->id)

@section('content')
<section class="space-y-5 max-w-4xl mx-auto px-3 sm:px-0">

    {{-- Status flash --}}
    @if(session('status'))
        <div class="rounded-2xl px-4 py-3 text-sm text-center prism-fade-up"
             style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
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
                   aria-label="رجوع">
                    <span aria-hidden="true">→</span>
                </a>
                <span class="prism-pill prism-pill-neon">
                    <span class="prism-dot prism-dot-emerald"></span>
                    حجز #{{ $booking->id }}
                </span>
            </div>

            <h2 class="text-sm font-semibold flex items-center gap-2 mt-1"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                🎟️ التذاكر
            </h2>

            <div class="space-y-3 max-h-[500px] overflow-auto pr-1">

                @foreach($booking->tickets as $ticket)
                    <div class="rounded-xl p-3 transition"
                         style="background: rgba(255,255,255,0.04); border: 1px solid var(--prism-border);"
                         onmouseover="this.style.background='rgba(129,140,248,0.06)'; this.style.borderColor='rgba(129,140,248,0.3)';"
                         onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.borderColor='';">

                        <div class="flex justify-between items-center gap-2">
                            <div>
                                <p class="font-semibold text-[color:var(--prism-text)]">{{ $ticket->name }}</p>
                                <p class="text-xs text-[color:var(--prism-text-3)]">{{ $ticket->phone }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full"
                                      style="background: {{ $ticket->whatsapp_sent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};
                                             box-shadow: 0 0 8px {{ $ticket->whatsapp_sent ? 'rgba(52,211,153,0.7)' : 'rgba(251,113,133,0.7)' }};"></span>

                                <span class="text-[10px]"
                                      style="color: {{ $ticket->whatsapp_sent ? 'var(--prism-emerald)' : 'var(--prism-rose)' }};">
                                    {{ $ticket->whatsapp_sent ? 'تم الاستلام' : 'لم يستلم' }}
                                </span>
                            </div>
                        </div>

                        @if($booking->status === 'approved')
                            <div class="flex gap-2 mt-2 flex-wrap">

                                @if($ticket->qr_image_path)
                                    <a href="{{ $ticket->qr_image_path }}" target="_blank"
                                       class="prism-btn-ghost text-[10px] px-3 py-1">
                                        عرض 🎫
                                    </a>
                                @endif

                                <form action="{{ route('admin.resend.ticket', $ticket->id) }}" method="POST">
                                    @csrf
                                    <button class="prism-btn-cyan text-[10px] px-3 py-1">
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

            <h2 class="text-sm font-semibold flex items-center gap-2 mb-3"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                📊 الحجز
            </h2>

            <div class="space-y-3 text-sm">

                <div class="flex justify-between items-center py-2"
                     style="border-bottom: 1px solid var(--prism-border);">
                    <span class="text-[color:var(--prism-text-3)] text-xs">عدد التذاكر</span>
                    <span class="text-[color:var(--prism-text)] font-bold">{{ $booking->tickets_count }}</span>
                </div>

                <div class="flex justify-between items-center py-2"
                     style="border-bottom: 1px solid var(--prism-border);">
                    <span class="text-[color:var(--prism-text-3)] text-xs">السعر</span>
                    <span class="font-bold text-base" style="color: var(--prism-gold);">
                        {{ $booking->total_price }} <span class="text-xs opacity-80">جنيه</span>
                    </span>
                </div>

                <div class="flex justify-between items-center py-2">
                    <span class="text-[color:var(--prism-text-3)] text-xs">الحالة</span>

                    @if($booking->status === 'approved')
                        <span class="prism-pill prism-pill-emerald">
                            <span class="prism-dot prism-dot-emerald"></span>
                            ✔ مقبول
                        </span>
                    @elseif($booking->status === 'rejected')
                        <span class="prism-pill prism-pill-rose">
                            <span class="prism-dot prism-dot-rose"></span>
                            ✖ مرفوض
                        </span>
                    @else
                        <span class="prism-pill prism-pill-sky">
                            <span class="prism-dot prism-dot-sky"></span>
                            ⏳ pending
                        </span>
                    @endif
                </div>

            </div>

        </div>

    </div>

    {{-- Screenshot --}}
    @if($booking->transfer_screenshot_path)
        <div class="prism-glass p-3 prism-fade-up">
            <h3 class="text-xs uppercase mb-2 px-2 pt-1"
                style="color: var(--prism-text-3); letter-spacing:.18em;">إيصال التحويل</h3>
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
                  data-pt-confirm='{"tone":"error","title":"حذف الحجز بالكامل؟","body":"هيمسح الحجز وكل التذاكر اللي طلعت منه. الإجراء ده مش بيتراجع فيه.","okLabel":"حذف نهائي","cancelLabel":"إلغاء","okVariant":"rose"}'>
                @csrf
                @method('DELETE')

                <button class="prism-btn-rose text-sm px-5 py-2">
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

    <div class="pt-action-bar is-on pt-bar-admin" id="ptAdminBar" role="region" aria-label="إجراءات الحجز">
        <div class="pt-action-bar-inner">
            <div class="pt-bar-summary">
                <span class="pt-bar-label">حجز قيد المراجعة · {{ $bkRef }}</span>
                <span class="pt-bar-meta">
                    {{ $bkName }}
                    @if($bkPhone)<span class="pt-bar-sep" aria-hidden="true">·</span> <span dir="ltr">{{ $bkPhone }}</span>@endif
                </span>
                <span class="pt-bar-meta-row">
                    <span class="pt-bar-chip"><span aria-hidden="true">🎟</span> {{ $tCount }}</span>
                    <span class="pt-bar-chip pt-bar-chip-gold">{{ $tTotal }} <span class="opacity-70">جنيه</span></span>
                    @if($whenLabel)
                        <span class="pt-bar-chip pt-bar-chip-muted"><span aria-hidden="true">⏰</span> {{ $whenLabel }}</span>
                    @endif
                </span>
            </div>
            <div class="pt-bar-actions">
                <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                      data-pt-confirm='{"tone":"error","title":"رفض الحجز؟","body":"الحجز هيترفض، ومش هيوصل أي QR للعميل.","okLabel":"رفض","cancelLabel":"إلغاء","okVariant":"rose"}'>
                    @csrf
                    <button class="prism-btn-rose pt-bar-btn">
                        <span aria-hidden="true">✖</span> رفض
                    </button>
                </form>
                <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST"
                      data-pt-confirm='{"tone":"warn","title":"اعتماد الحجز؟","body":"هتأكد الحجز ويتبعت QR للعميل على واتساب.","okLabel":"اعتماد","cancelLabel":"إلغاء","okVariant":"emerald"}'>
                    @csrf
                    <button class="prism-btn-emerald pt-bar-btn">
                        <span aria-hidden="true">✔</span> اعتماد
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
