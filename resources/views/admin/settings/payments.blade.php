@extends('layouts.admin')

@section('title', 'إعدادات التحويلات')

@section('content')
<section class="max-w-lg mx-auto space-y-5 prism-fade-up">
    <div class="prism-glass prism-glow-border p-5 flex items-center justify-between gap-3 flex-wrap">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                <span data-i18n="adm_pay_pill">Payment Settings</span>
            </span>
            <h1 class="prism-headline text-xl">
                <span data-i18n-html="adm_pay_title"
                      style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    إعدادات التحويلات 💳
                </span>
            </h1>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
            <span aria-hidden="true" class="pt-arrow-rtl">→</span>
            <span data-i18n="adm_back_dashboard">رجوع للوحة التحكم</span>
        </a>
    </div>

    @if (session('status'))
        <div class="pt-alert pt-alert-success prism-fade-up">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.payments.update') }}" method="POST"
          class="prism-glass p-5 space-y-4 prism-fade-up">
        @csrf

        <div>
            <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]" data-i18n="adm_pay_wallet_label">رقم المحفظة (اختياري)</label>
            <input type="text" name="transfer_wallet"
                   value="{{ old('transfer_wallet', $transferWallet) }}"
                   class="prism-input text-sm">
            <p class="text-[11px] text-[color:var(--prism-text-3)] mt-1.5" data-i18n="adm_pay_wallet_hint">
                مثلاً: 0100xxxxxxx
            </p>
        </div>

        <button type="submit" class="prism-btn mt-2">
            <span data-i18n="adm_pay_save">حفظ الإعدادات</span>
            <span aria-hidden="true" class="pt-arrow-rtl">←</span>
        </button>
    </form>
</section>
@endsection
