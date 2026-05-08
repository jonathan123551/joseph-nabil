@extends('layouts.app')

@section('title', 'إضافة موعد جديد - ' . $show->title)

@php
    use App\Models\Show;
    // Section-based pricing means the ticket price is defined on the Show
    // (per section: hall / balcony) rather than per-showtime. When that
    // applies, the standalone showtime ticket_price input is hidden — its
    // value is irrelevant to the booking flow and only causes confusion.
    $usesSectionPricing = $show->theater_type === Show::THEATER_ANBA_RUWEIS;
@endphp

@section('content')
    <section class="max-w-xl mx-auto space-y-4 prism-fade-up">

        {{-- Header card --}}
        <div class="prism-glass prism-glow-border p-5">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="space-y-1">
                    <span class="prism-pill prism-pill-neon">
                        <span class="prism-dot prism-dot-emerald"></span>
                        New Show Time
                    </span>
                    <h1 class="prism-headline text-xl">
                        <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                            إضافة موعد جديد
                        </span>
                    </h1>
                    <p class="text-xs text-[color:var(--prism-text-3)]">🎭 {{ $show->title }}</p>
                </div>

                <a href="{{ route('admin.shows.times.index', $show) }}" class="prism-btn-ghost text-xs">
                    <span aria-hidden="true">→</span>
                    رجوع للمواعيد
                </a>
            </div>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="rounded-xl px-4 py-3 text-xs prism-fade-up"
                 style="background: rgba(244,63,94,0.10); border: 1px solid rgba(251,113,133,0.45); color: #fda4af;">
                <ul class="list-disc pr-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.shows.times.store', $show) }}" method="POST"
              class="space-y-4 prism-fade-up" autocomplete="off">
            @csrf

            {{-- Section: scheduling --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">📅</span>
                    <span class="pt-form-section-head-title">الموعد</span>
                    <span class="pt-form-section-head-sub">تاريخ وساعة بدء العرض</span>
                </div>

                <div class="pt-form-grid">
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">
                            التاريخ
                            <span class="pt-form-req" aria-hidden="true">*</span>
                        </label>
                        <input type="date" name="date"
                               value="{{ old('date') }}"
                               class="prism-input text-sm">
                    </div>
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">
                            الساعة
                            <span class="pt-form-req" aria-hidden="true">*</span>
                        </label>
                        <input type="time" name="time"
                               value="{{ old('time') }}"
                               class="prism-input text-sm">
                    </div>
                </div>
            </div>

            {{-- Section: pricing & inventory --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">💰</span>
                    <span class="pt-form-section-head-title">السعر والتذاكر</span>
                </div>

                @if ($usesSectionPricing)
                    {{-- Section-priced shows: ticket_price is irrelevant. We
                         still POST a 0 to satisfy the existing required-numeric
                         validator, and surface a read-only summary of the
                         hall / balcony prices instead. --}}
                    <input type="hidden" name="ticket_price" value="0">

                    <div class="rounded-xl px-3 py-3 text-xs"
                         style="background: rgba(34,211,238,0.06); border: 1px solid rgba(129,140,248,0.32); color: var(--prism-text-2);">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="prism-dot prism-dot-emerald"></span>
                            <span class="font-semibold" style="color: var(--prism-text);">الأسعار من العرض (لكل فئة)</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-lg px-3 py-2"
                                 style="background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.32);">
                                <div class="text-[10px] text-[color:var(--prism-text-3)]">صالة</div>
                                <div class="font-semibold" style="color: var(--prism-gold);">
                                    {{ (int) ($show->hall_price ?? 0) }} ج
                                </div>
                            </div>
                            <div class="rounded-lg px-3 py-2"
                                 style="background: rgba(192,132,252,0.08); border: 1px solid rgba(192,132,252,0.32);">
                                <div class="text-[10px] text-[color:var(--prism-text-3)]">بلكون</div>
                                <div class="font-semibold" style="color: var(--prism-violet, #c084fc);">
                                    {{ (int) ($show->balcony_price ?? 0) }} ج
                                </div>
                            </div>
                        </div>
                        <p class="pt-form-helper mt-2">
                            هذا العرض يستخدم تسعير حسب القسم. عدّل الأسعار من صفحة تعديل العرض.
                        </p>
                    </div>

                    <div class="pt-form-field">
                        <label class="pt-form-field-label">
                            إجمالي التذاكر
                            <span class="pt-form-req" aria-hidden="true">*</span>
                        </label>
                        <input type="number" min="1" name="total_tickets"
                               value="{{ old('total_tickets', 50) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                @else
                    <div class="pt-form-grid">
                        <div class="pt-form-field">
                            <label class="pt-form-field-label">
                                سعر التذكرة (جنيه)
                                <span class="pt-form-req" aria-hidden="true">*</span>
                            </label>
                            <input type="number" step="0.5" min="0" name="ticket_price"
                                   value="{{ old('ticket_price') }}"
                                   class="prism-input text-sm" inputmode="decimal">
                        </div>
                        <div class="pt-form-field">
                            <label class="pt-form-field-label">
                                إجمالي التذاكر
                                <span class="pt-form-req" aria-hidden="true">*</span>
                            </label>
                            <input type="number" min="1" name="total_tickets"
                                   value="{{ old('total_tickets', 50) }}"
                                   class="prism-input text-sm" inputmode="numeric">
                        </div>
                    </div>
                @endif

                <div class="pt-form-field">
                    <label class="pt-form-field-label">التذاكر المتاحة الآن (اختياري)</label>
                    <input type="number" min="0" name="available_tickets"
                           value="{{ old('available_tickets') }}"
                           placeholder="فاضي = نفس إجمالي التذاكر"
                           class="prism-input text-sm" inputmode="numeric">
                    <p class="pt-form-helper">
                        لو سيبت الحقل فاضي، النظام هيبدأ بكامل العدد متاح للحجز.
                    </p>
                </div>
            </div>

            {{-- Section: status --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">⚙️</span>
                    <span class="pt-form-section-head-title">الحالة</span>
                </div>

                <label class="pt-switch-row cursor-pointer">
                    <span class="text-xs text-[color:var(--prism-text-2)]">تحديد الموعد كـ Sold Out من البداية</span>
                    <input type="checkbox" name="is_sold_out" value="1" class="w-5 h-5"
                           {{ old('is_sold_out') ? 'checked' : '' }}
                           style="accent-color: #fb7185;">
                </label>
            </div>

            {{-- Sticky action bar (sticks to bottom on mobile, inline on desktop) --}}
            <div class="pt-form-actions-sticky">
                <a href="{{ route('admin.shows.times.index', $show) }}" class="prism-btn-ghost text-sm flex items-center justify-center">
                    <span aria-hidden="true">→</span>
                    إلغاء
                </a>
                <button type="submit" class="prism-btn text-sm pt-form-actions-primary flex items-center justify-center">
                    حفظ الموعد
                    <span aria-hidden="true">←</span>
                </button>
            </div>
        </form>
    </section>
@endsection
