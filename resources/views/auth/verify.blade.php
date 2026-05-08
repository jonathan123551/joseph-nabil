@extends('layouts.app')

@section('title', __('Verify Your Email Address'))

@section('content')
    <section class="max-w-md mx-auto mt-12 prism-fade-up">
        <div class="prism-glass prism-glow-border p-6 sm:p-7 space-y-5">

            <div class="text-center space-y-2">
                <h2 class="prism-headline text-xl">
                    <span data-i18n="auth_verify_title"
                          style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        تأكيد البريد الإلكتروني
                    </span>
                </h2>
            </div>

            @if (session('resent'))
                <div class="rounded-xl px-3 py-2 text-xs"
                     style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;"
                     data-i18n="auth_verify_resent">
                    تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني.
                </div>
            @endif

            <p class="text-sm text-[color:var(--prism-text-2)] leading-relaxed"
               data-i18n="auth_verify_check_email">
                قبل المتابعة، تحقق من بريدك الإلكتروني للحصول على رابط التأكيد.
            </p>

            <p class="text-sm text-[color:var(--prism-text-2)] leading-relaxed">
                <span data-i18n="auth_verify_didnt_receive">إذا لم يصلك البريد</span>،
                <form class="inline" method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit"
                            class="underline transition"
                            style="color: var(--prism-cyan);"
                            data-i18n="auth_verify_resend_link">
                        اضغط هنا لإرسال رابط جديد
                    </button>.
                </form>
            </p>
        </div>
    </section>
@endsection
