@extends('layouts.app')

@section('title', 'تسجيل دخول الأدمن')

@section('content')
    <section class="max-w-sm mx-auto mt-12 prism-fade-up">
        <div class="prism-glass prism-glow-border p-6 sm:p-7 space-y-5">

            <div class="text-center space-y-2">
                <span class="prism-pill prism-pill-neon mx-auto">
                    <span class="prism-dot prism-dot-emerald"></span>
                    <span data-i18n="auth_admin_pill">Admin Access</span>
                </span>
                <h2 class="prism-headline text-xl">
                    <span data-i18n="auth_admin_title"
                          style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        دخول الأدمن
                    </span>
                </h2>
                <p class="text-xs text-[color:var(--prism-text-3)]"
                   data-i18n="auth_admin_subtitle">سجّل دخولك للوحة التحكم</p>
            </div>

            @if ($errors->any())
                <div class="rounded-xl px-3 py-2 text-xs text-center"
                     style="background: rgba(244,63,94,0.10); border: 1px solid rgba(251,113,133,0.45); color: #fda4af;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" class="space-y-3">
                @csrf

                <div>
                    <label class="text-xs mb-1 block text-[color:var(--prism-text-2)]"
                           data-i18n="auth_email">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="prism-input text-sm" autocomplete="email">
                </div>

                <div>
                    <label class="text-xs mb-1 block text-[color:var(--prism-text-2)]"
                           data-i18n="auth_password">كلمة المرور</label>
                    <input type="password" name="password"
                           class="prism-input text-sm" autocomplete="current-password">
                </div>

                <button class="prism-btn w-full mt-2">
                    <span data-i18n="auth_login_btn">دخول</span>
                    <span aria-hidden="true" class="pt-arrow-rtl">←</span>
                </button>
            </form>
        </div>
    </section>
@endsection
