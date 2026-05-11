@extends('layouts.app')

@section('title', 'حصلت مشكلة · Premium Tickets')

@section('content')
<section class="max-w-lg mx-auto prism-fade-up">
    <div class="prism-glass prism-glow-border p-8 sm:p-10 text-center space-y-5"
         style="position: relative; overflow: hidden;">

        <div class="mx-auto w-20 h-20 relative">
            <div class="absolute inset-0 rounded-full"
                 style="background: radial-gradient(circle, rgba(244,63,94,0.35), transparent 70%);
                        filter: blur(14px);"></div>
            <div class="absolute inset-0 flex items-center justify-center rounded-full
                        bg-rose-500/[0.08] border border-rose-400/30 text-3xl">
                ⚠️
            </div>
        </div>

        <div>
            <span class="pt-thx-eyebrow" data-i18n="error_500_eyebrow">حصل خطأ</span>
            <h1 class="prism-headline text-2xl sm:text-3xl mt-2"
                data-i18n="error_500_title">
                حصلت مشكلة من جهتنا
            </h1>
            <p class="text-xs sm:text-sm text-[color:var(--prism-text-2)] mt-2"
               data-i18n="error_500_sub">
                حاول تاني بعد شوية. لو المشكلة فضلت موجودة كلّمنا على واتساب.
            </p>
        </div>

        <a href="{{ url('/') }}" class="prism-btn prism-ripple inline-flex justify-center"
           data-i18n="error_back_home">
            الرجوع للرئيسية
        </a>
    </div>
</section>
@endsection
