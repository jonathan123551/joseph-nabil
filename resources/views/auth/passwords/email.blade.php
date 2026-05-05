@extends('layouts.app')

@section('title', __('Reset Password'))

@section('content')
    <section class="max-w-sm mx-auto mt-12 prism-fade-up">
        <div class="prism-glass prism-glow-border p-6 sm:p-7 space-y-5">

            <div class="text-center space-y-2">
                <span class="prism-pill prism-pill-neon mx-auto">
                    <span class="prism-dot prism-dot-sky"></span>
                    {{ __('Reset Password') }}
                </span>
                <h2 class="prism-headline text-xl">
                    <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        {{ __('Reset Password') }}
                    </span>
                </h2>
            </div>

            @if (session('status'))
                <div class="rounded-xl px-3 py-2 text-xs"
                     style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-3">
                @csrf

                <div>
                    <label for="email" class="text-xs mb-1 block text-[color:var(--prism-text-2)]">{{ __('Email Address') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="prism-input text-sm @error('email') ring-1 ring-rose-400 @enderror"
                           required autocomplete="email" autofocus>
                    @error('email')
                        <span class="text-[11px] mt-1 block" style="color: #fda4af;">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="prism-btn w-full mt-2">
                    {{ __('Send Password Reset Link') }}
                </button>
            </form>
        </div>
    </section>
@endsection
