@extends('layouts.app')

@section('title', 'حساب جديد')

@section('content')
    <section class="max-w-md mx-auto mt-12 prism-fade-up">
        <div class="prism-glass prism-glow-border p-6 sm:p-7 space-y-5">

            <div class="text-center space-y-2">
                <span class="prism-pill prism-pill-neon mx-auto">
                    <span class="prism-dot prism-dot-emerald"></span>
                    {{ __('Register') }}
                </span>
                <h2 class="prism-headline text-xl">
                    <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        {{ __('Register') }}
                    </span>
                </h2>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-3">
                @csrf

                <div>
                    <label for="name" class="text-xs mb-1 block text-[color:var(--prism-text-2)]">{{ __('Name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                           class="prism-input text-sm @error('name') ring-1 ring-rose-400 @enderror"
                           required autocomplete="name" autofocus>
                    @error('name')
                        <span class="text-[11px] mt-1 block" style="color: #fda4af;">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="email" class="text-xs mb-1 block text-[color:var(--prism-text-2)]">{{ __('Email Address') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="prism-input text-sm @error('email') ring-1 ring-rose-400 @enderror"
                           required autocomplete="email">
                    @error('email')
                        <span class="text-[11px] mt-1 block" style="color: #fda4af;">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password" class="text-xs mb-1 block text-[color:var(--prism-text-2)]">{{ __('Password') }}</label>
                    <input id="password" type="password" name="password"
                           class="prism-input text-sm @error('password') ring-1 ring-rose-400 @enderror"
                           required autocomplete="new-password">
                    @error('password')
                        <span class="text-[11px] mt-1 block" style="color: #fda4af;">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password-confirm" class="text-xs mb-1 block text-[color:var(--prism-text-2)]">{{ __('Confirm Password') }}</label>
                    <input id="password-confirm" type="password" name="password_confirmation"
                           class="prism-input text-sm" required autocomplete="new-password">
                </div>

                <button type="submit" class="prism-btn w-full mt-2">
                    {{ __('Register') }}
                    <span aria-hidden="true">←</span>
                </button>
            </form>
        </div>
    </section>
@endsection
