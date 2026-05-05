@extends('layouts.app')

@section('title', 'تم إرسال طلب الحجز · PRISM')

@section('content')
<section class="max-w-lg mx-auto prism-fade-up">

    <div class="prism-glass prism-glow-border p-6 sm:p-8 text-center space-y-5">

        {{-- Animated success orb --}}
        <div class="mx-auto w-20 h-20 relative">
            <div class="absolute inset-0 rounded-full"
                 style="background: radial-gradient(circle, rgba(16,185,129,0.55), transparent 70%);
                        filter: blur(14px);
                        animation: prismGlowPulse 2.4s ease-in-out infinite;"></div>
            <div class="absolute inset-0 flex items-center justify-center rounded-full
                        bg-emerald-500/[0.10] border border-emerald-400/40 text-3xl">
                🎟
            </div>
        </div>

        {{-- Title --}}
        <div>
            <h1 class="prism-headline text-2xl sm:text-3xl"
                style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                تم إرسال طلب الحجز بنجاح
            </h1>
            <p class="text-xs sm:text-sm text-[color:var(--prism-text-2)] mt-2">
                شكرًا يا <span class="font-semibold text-[color:var(--prism-text)]">{{ $booking->full_name }}</span>
            </p>
        </div>

        {{-- Booking Info --}}
        <div class="bg-white/[0.04] border border-[color:var(--prism-border)] rounded-2xl p-4 text-sm space-y-3 text-right">

            <div class="flex justify-between items-center">
                <span class="text-[color:var(--prism-text-3)] text-xs">رقم الحجز</span>
                <span class="font-mono text-sm tracking-wide" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    {{ $booking->reference_code }}
                </span>
            </div>

            <div class="h-px bg-[color:var(--prism-border)]"></div>

            <div class="flex justify-between items-center">
                <span class="text-[color:var(--prism-text-3)] text-xs">إجمالي المبلغ</span>
                <span class="font-bold text-[color:var(--prism-emerald)]">
                    {{ $booking->total_price }} <span class="text-[10px] opacity-80">جنيه</span>
                </span>
            </div>
        </div>

        {{-- IMPORTANT NOTICE — content unchanged, only restyled --}}
        <div class="bg-amber-500/[0.06] border border-amber-400/30 rounded-2xl p-4 text-right space-y-3">

            <div class="flex items-center gap-2 text-[color:var(--prism-gold)] font-semibold text-sm">
                <span>⏳</span>
                الخطوة الجاية
            </div>

            <ul class="space-y-3 text-xs sm:text-sm text-[color:var(--prism-text-2)] leading-relaxed">

                <li class="relative pr-5 before:content-[''] before:absolute before:right-0 before:top-[0.55em]
                           before:w-2 before:h-2 before:rounded-full before:bg-amber-300
                           before:shadow-[0_0_10px_rgba(251,191,36,0.7)]">
                    يتم <span class="text-[color:var(--prism-text)] font-semibold">مراجعة عملية الدفع</span>
                    والتأكد من التحويل.
                </li>

                <li class="relative pr-5 before:content-[''] before:absolute before:right-0 before:top-[0.55em]
                           before:w-2 before:h-2 before:rounded-full before:bg-emerald-300
                           before:shadow-[0_0_10px_rgba(110,231,183,0.7)]">
                    بعد <span class="text-[color:var(--prism-emerald)] font-semibold">تأكيد الحجز</span>،
                    سيتم إرسال <span class="text-[color:var(--prism-text)] font-semibold">التذكرة</span>
                    مباشرة على <span class="text-[color:var(--prism-text)] font-semibold">رقم الواتساب المسجل</span>.
                </li>

                <li class="relative pr-5 before:content-[''] before:absolute before:right-0 before:top-[0.55em]
                           before:w-2 before:h-2 before:rounded-full before:bg-sky-300
                           before:shadow-[0_0_10px_rgba(125,211,252,0.7)]">
                    عملية المراجعة قد تستغرق بحد أقصى
                    <span class="text-[color:var(--prism-text)] font-semibold">24 ساعة</span>.
                </li>

            </ul>

        </div>

        {{-- Footer Note --}}
        <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed">
            لو في أي مشكلة في التحويل أو البيانات، هنتواصل معاك قبل رفض الطلب.
            <br>
            متقلقش، طلبك محفوظ على السيستم ✨
        </p>

        {{-- Action --}}
        <a href="{{ route('shows.index') }}"
           class="prism-btn-ghost prism-ripple inline-flex w-full sm:w-auto">
            <span aria-hidden="true">→</span>
            رجوع للصفحة الرئيسية
        </a>

    </div>
</section>
@endsection
