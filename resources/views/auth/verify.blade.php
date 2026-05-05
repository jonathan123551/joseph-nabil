@extends('layouts.app')

@section('title', __('Verify Your Email Address'))

@section('content')
    <section class="max-w-md mx-auto mt-12 prism-fade-up">
        <div class="prism-glass prism-glow-border p-6 sm:p-7 space-y-5">

            <div class="text-center space-y-2">
                <h2 class="prism-headline text-xl">
                    <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        {{ __('Verify Your Email Address') }}
                    </span>
                </h2>
            </div>

            @if (session('resent'))
                <div class="rounded-xl px-3 py-2 text-xs"
                     style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
                    {{ __('A fresh verification link has been sent to your email address.') }}
                </div>
            @endif

            <p class="text-sm text-[color:var(--prism-text-2)] leading-relaxed">
                {{ __('Before proceeding, please check your email for a verification link.') }}
            </p>

            <p class="text-sm text-[color:var(--prism-text-2)] leading-relaxed">
                {{ __('If you did not receive the email') }}،
                <form class="inline" method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit"
                            class="underline transition"
                            style="color: var(--prism-cyan);">
                        {{ __('click here to request another') }}
                    </button>.
                </form>
            </p>
        </div>
    </section>
@endsection
