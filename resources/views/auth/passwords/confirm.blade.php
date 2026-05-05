@extends('layouts.app')

@section('title', __('Confirm Password'))

@section('content')
    <section class="max-w-sm mx-auto mt-12 prism-fade-up">
        <div class="prism-glass prism-glow-border p-6 sm:p-7 space-y-5">

            <div class="text-center space-y-2">
                <h2 class="prism-headline text-xl">
                    <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        {{ __('Confirm Password') }}
                    </span>
                </h2>
                <p class="text-xs text-[color:var(--prism-text-3)]">
                    {{ __('Please confirm your password before continuing.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-3">
                @csrf

                <div>
                    <label for="password" class="text-xs mb-1 block text-[color:var(--prism-text-2)]">{{ __('Password') }}</label>
                    <input id="password" type="password" name="password"
                           class="prism-input text-sm @error('password') ring-1 ring-rose-400 @enderror"
                           required autocomplete="current-password">
                    @error('password')
                        <span class="text-[11px] mt-1 block" style="color: #fda4af;">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="prism-btn w-full mt-2">
                    {{ __('Confirm Password') }}
                </button>

                @if (Route::has('password.request'))
                    <a class="block text-center text-[11px] text-[color:var(--prism-text-3)] hover:text-[color:var(--prism-text)] transition"
                       href="{{ route('password.request') }}">
                        {{ __('Forgot Your Password?') }}
                    </a>
                @endif
            </form>
        </div>
    </section>
@endsection
