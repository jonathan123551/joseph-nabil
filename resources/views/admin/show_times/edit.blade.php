@extends('layouts.app')

@section('title', 'تعديل موعد - ' . $show->title)

@php
    use App\Models\Show;
    $usesSectionPricing = $show->theater_type === Show::THEATER_ANBA_RUWEIS;
@endphp

@section('content')
<section class="max-w-2xl mx-auto space-y-4 prism-fade-up">

    {{-- Header --}}
    <div class="prism-glass prism-glow-border p-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="space-y-1">
                <span class="prism-pill prism-pill-neon">
                    <span class="prism-dot prism-dot-emerald"></span>
                    Edit Show Time
                </span>
                <h1 class="prism-headline text-xl md:text-2xl">
                    <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        تعديل موعد
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

    {{-- Errors --}}
    @if ($errors->any())
        <div class="rounded-xl px-4 py-3 text-xs prism-fade-up"
             style="background: rgba(244,63,94,0.10); border: 1px solid rgba(251,113,133,0.45); color: #fda4af;">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.shows.times.update', [$show, $showTime]) }}" method="POST"
          class="space-y-4" autocomplete="off">
        @csrf
        @method('PUT')

        {{-- Section: scheduling --}}
        <div class="pt-form-section">
            <div class="pt-form-section-head">
                <span class="pt-form-section-head-icon" aria-hidden="true">📅</span>
                <span class="pt-form-section-head-title">الموعد</span>
            </div>

            <div class="pt-form-grid">
                <div class="pt-form-field">
                    <label class="pt-form-field-label">
                        التاريخ
                        <span class="pt-form-req" aria-hidden="true">*</span>
                    </label>
                    <input type="date" name="date"
                           value="{{ old('date', $showTime->date->format('Y-m-d')) }}"
                           class="prism-input text-sm">
                </div>
                <div class="pt-form-field">
                    <label class="pt-form-field-label">
                        الساعة
                        <span class="pt-form-req" aria-hidden="true">*</span>
                    </label>
                    <input type="time" name="time"
                           value="{{ old('time', $showTime->time) }}"
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
                {{-- Hidden — keep the column populated to satisfy validation,
                     but the value is not used by the booking flow for shows
                     that price by section. --}}
                <input type="hidden" name="ticket_price"
                       value="{{ old('ticket_price', $showTime->ticket_price ?? 0) }}">

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
                           value="{{ old('total_tickets', $showTime->total_tickets) }}"
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
                               value="{{ old('ticket_price', $showTime->ticket_price) }}"
                               class="prism-input text-sm" inputmode="decimal"
                               style="color: var(--prism-gold); font-weight: 700;">
                    </div>

                    <div class="pt-form-field">
                        <label class="pt-form-field-label">
                            إجمالي التذاكر
                            <span class="pt-form-req" aria-hidden="true">*</span>
                        </label>
                        <input type="number" min="1" name="total_tickets"
                               value="{{ old('total_tickets', $showTime->total_tickets) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>
                </div>
            @endif
        </div>

        {{-- Section: status --}}
        <div class="pt-form-section">
            <div class="pt-form-section-head">
                <span class="pt-form-section-head-icon" aria-hidden="true">⚙️</span>
                <span class="pt-form-section-head-title">الحالة</span>
            </div>

            <div class="pt-switch-row">
                <span class="text-xs text-[color:var(--prism-text-2)]">حالة الموعد</span>

                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_sold_out" value="1"
                           class="sr-only peer"
                           {{ $showTime->is_sold_out ? 'checked' : '' }}>

                    {{-- background --}}
                    <div class="w-14 h-8 rounded-full transition-all duration-300"
                         style="background: rgba(16,185,129,0.18); border: 1px solid rgba(52,211,153,0.45);"
                         data-on-bg></div>

                    {{-- circle --}}
                    <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-6 shadow-md"></div>

                    <style>
                        .peer:checked ~ [data-on-bg] {
                            background: rgba(244,63,94,0.22) !important;
                            border-color: rgba(251,113,133,0.45) !important;
                        }
                    </style>
                </label>
            </div>
            <p class="pt-form-helper">
                لما تفعّل Sold Out، الموعد بيختفي من صفحات الحجز ومش هيقدر يحجزه أي حد.
            </p>
        </div>

        {{-- Sticky action bar --}}
        <div class="pt-form-actions-sticky">
            <a href="{{ route('admin.shows.times.index', $show) }}"
               class="prism-btn-ghost text-sm flex items-center justify-center">
                <span aria-hidden="true">→</span>
                رجوع
            </a>
            <button type="submit" class="prism-btn text-sm pt-form-actions-primary flex items-center justify-center">
                حفظ التعديلات
                <span aria-hidden="true">←</span>
            </button>
        </div>
    </form>
</section>
@endsection
