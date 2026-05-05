@extends('layouts.app')

@section('title', 'إعدادات التحويلات')

@section('content')
<section class="max-w-lg mx-auto space-y-5 prism-fade-up">
    <div class="prism-glass prism-glow-border p-5 flex items-center justify-between gap-3 flex-wrap">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                Payment Settings
            </span>
            <h1 class="prism-headline text-xl">
                <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    إعدادات التحويلات 💳
                </span>
            </h1>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
            <span aria-hidden="true">→</span>
            رجوع للوحة التحكم
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-xl px-4 py-3 text-sm prism-fade-up"
             style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.payments.update') }}" method="POST"
          class="prism-glass p-5 space-y-4 prism-fade-up">
        @csrf

        <div>
            <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">رقم المحفظة (اختياري)</label>
            <input type="text" name="transfer_wallet"
                   value="{{ old('transfer_wallet', $transferWallet) }}"
                   class="prism-input text-sm">
            <p class="text-[11px] text-[color:var(--prism-text-3)] mt-1.5">
                مثلاً: 0100xxxxxxx
            </p>
        </div>

        <div>
            <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">حساب InstaPay (اختياري)</label>
            <input type="text" name="transfer_insta"
                   value="{{ old('transfer_insta', $transferInsta) }}"
                   class="prism-input text-sm">
            <p class="text-[11px] text-[color:var(--prism-text-3)] mt-1.5">
                مثلاً: EGxxxxxxxxxx أو email@domain.com
            </p>
        </div>

        <button type="submit" class="prism-btn mt-2">
            حفظ الإعدادات
            <span aria-hidden="true">←</span>
        </button>
    </form>
</section>
@endsection
