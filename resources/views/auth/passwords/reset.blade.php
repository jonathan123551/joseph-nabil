@extends('layouts.app')

@section('title', __('Reset Password'))

@section('content')
    <section class="max-w-sm mx-auto mt-12 prism-fade-up">
        <div class="prism-glass prism-glow-border p-6 sm:p-7 space-y-5">

            <div class="text-center space-y-2">
                <span class="prism-pill prism-pill-neon mx-auto">
                    <span class="prism-dot prism-dot-sky"></span>
                    <span data-i18n="auth_reset_pill">إعادة تعيين كلمة المرور</span>
                </span>
                <h2 class="prism-headline text-xl">
                    <span data-i18n="auth_reset_title"
                          style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        إعادة تعيين كلمة المرور
                    </span>
                </h2>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="text-xs mb-1 block text-[color:var(--prism-text-2)]"
                           data-i18n="auth_email">البريد الإلكتروني</label>
                    <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}"
                           class="prism-input text-sm @error('email') ring-1 ring-rose-400 @enderror"
                           required autocomplete="email" autofocus>
                    @error('email')
                        <span class="text-[11px] mt-1 block" style="color: #fda4af;">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password" class="text-xs mb-1 block text-[color:var(--prism-text-2)]"
                           data-i18n="auth_password">كلمة المرور</label>
                    <input id="password" type="password" name="password"
                           class="prism-input text-sm @error('password') ring-1 ring-rose-400 @enderror"
                           required autocomplete="new-password">
                    @error('password')
                        <span class="text-[11px] mt-1 block" style="color: #fda4af;">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password-confirm" class="text-xs mb-1 block text-[color:var(--prism-text-2)]"
                           data-i18n="auth_password_confirm">تأكيد كلمة المرور</label>
                    <input id="password-confirm" type="password" name="password_confirmation"
                           class="prism-input text-sm" required autocomplete="new-password">
                </div>

                <button type="submit" class="prism-btn w-full mt-2"
                        data-i18n="auth_reset_btn">
                    إعادة تعيين كلمة المرور
                </button>
            </form>
        </div>
    </section>
@endsection
