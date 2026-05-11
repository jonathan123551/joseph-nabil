@extends('layouts.app')

@section('title', 'تعديل موعد - ' . $show->title)

@php
    use App\Models\Show;
    $usesSectionPricing = $show->theater_type === Show::THEATER_ANBA_RUWEIS;
    // Seatmap-backed shows derive total capacity from the actual seat
    // table — admin no longer types a number. Manual ("Other") shows keep
    // the existing free-form input.
    $usesSeatMap = $show->usesSeatMap();
    $seatCap     = $show->seatMapCapacity();
@endphp

@section('content')
<section class="max-w-2xl mx-auto space-y-4 prism-fade-up">

    {{-- Header --}}
    <div class="prism-glass prism-glow-border p-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="space-y-1">
                <span class="prism-pill prism-pill-neon">
                    <span class="prism-dot prism-dot-emerald"></span>
                    <span data-i18n="adm_time_edit_pill">Edit Show Time</span>
                </span>
                <h1 class="prism-headline text-xl md:text-2xl">
                    <span data-i18n="adm_time_edit_title"
                          style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        تعديل موعد
                    </span>
                </h1>
                <p class="text-xs text-[color:var(--prism-text-3)]">🎭 {{ $show->title }}</p>
            </div>

            <a href="{{ route('admin.shows.times.index', $show) }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span data-i18n="adm_back_times">رجوع للمواعيد</span>
            </a>
        </div>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="pt-alert pt-alert-danger text-xs prism-fade-up">
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
                <span class="pt-form-section-head-title" data-i18n="adm_time_section_when">الموعد</span>
            </div>

            <div class="pt-form-grid">
                <div class="pt-form-field">
                    <label class="pt-form-field-label">
                        <span data-i18n="adm_times_col_date">التاريخ</span>
                        <span class="pt-form-req" aria-hidden="true">*</span>
                    </label>
                    <input type="date" name="date"
                           value="{{ old('date', $showTime->date->format('Y-m-d')) }}"
                           class="prism-input text-sm">
                </div>
                <div class="pt-form-field">
                    <label class="pt-form-field-label">
                        <span data-i18n="adm_times_col_time">الساعة</span>
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
                <span class="pt-form-section-head-title" data-i18n="adm_time_section_pricing">السعر والتذاكر</span>
            </div>

            @if ($usesSectionPricing)
                {{-- Hidden — keep the column populated to satisfy validation,
                     but the value is not used by the booking flow for shows
                     that price by section. --}}
                <input type="hidden" name="ticket_price"
                       value="{{ old('ticket_price', $showTime->ticket_price ?? 0) }}">

                <div class="pt-alert pt-alert-info text-xs">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="prism-dot prism-dot-emerald"></span>
                        <span class="font-semibold text-[color:var(--prism-text)]" data-i18n="adm_time_section_pricing_split">الأسعار من العرض (لكل فئة)</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="pt-mini-card pt-mini-card-gold">
                            <div class="pt-mini-card-label" data-i18n="adm_section_hall">صالة</div>
                            <div class="pt-mini-card-value">
                                {{ (int) ($show->hall_price ?? 0) }} <span data-i18n="common_currency_short">ج</span>
                            </div>
                        </div>
                        <div class="pt-mini-card pt-mini-card-violet">
                            <div class="pt-mini-card-label" data-i18n="adm_section_balcony">بلكون</div>
                            <div class="pt-mini-card-value">
                                {{ (int) ($show->balcony_price ?? 0) }} <span data-i18n="common_currency_short">ج</span>
                            </div>
                        </div>
                    </div>
                    <p class="pt-form-helper mt-2" data-i18n="adm_time_section_pricing_helper">
                        هذا العرض يستخدم تسعير حسب القسم. عدّل الأسعار من صفحة تعديل العرض.
                    </p>
                </div>
            @else
                <div class="pt-form-grid">
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">
                            <span data-i18n="adm_time_ticket_price">سعر التذكرة (جنيه)</span>
                            <span class="pt-form-req" aria-hidden="true">*</span>
                        </label>
                        <input type="number" step="0.5" min="0" name="ticket_price"
                               value="{{ old('ticket_price', $showTime->ticket_price) }}"
                               class="prism-input text-sm" inputmode="decimal"
                               style="color: var(--prism-gold); font-weight: 700;">
                    </div>
                </div>
            @endif

            @if ($usesSeatMap)
                {{-- Seatmap-backed shows derive their total ticket count from
                     the live seat layout — admin gets a read-only breakdown
                     here. The controller re-syncs total_tickets on save so
                     the saved value always matches the seat table. --}}
                <div class="pt-alert pt-alert-success text-xs">
                    <div class="flex items-center justify-between mb-2 gap-2 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="prism-dot prism-dot-emerald"></span>
                            <span class="font-semibold text-[color:var(--prism-text)]" data-i18n="adm_time_capacity_card_title">
                                سعة المسرح (تلقائي)
                            </span>
                        </div>
                        <span class="text-[10px] text-[color:var(--prism-text-3)]" data-i18n="adm_time_capacity_saved_label">
                            المحفوظ حاليًا: {{ (int) $showTime->total_tickets }}
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="pt-mini-card pt-mini-card-gold">
                            <div class="pt-mini-card-label" data-i18n="adm_section_hall">صالة</div>
                            <div class="pt-mini-card-value">
                                {{ (int) ($seatCap['hall'] ?? 0) }}
                            </div>
                        </div>
                        <div class="pt-mini-card pt-mini-card-violet">
                            <div class="pt-mini-card-label" data-i18n="adm_section_balcony">بلكون</div>
                            <div class="pt-mini-card-value">
                                {{ (int) ($seatCap['balcony'] ?? 0) }}
                            </div>
                        </div>
                        <div class="pt-mini-card pt-mini-card-emerald">
                            <div class="pt-mini-card-label" data-i18n="adm_time_capacity_total">الإجمالي</div>
                            <div class="pt-mini-card-value">
                                {{ (int) ($seatCap['total'] ?? 0) }}
                            </div>
                        </div>
                    </div>
                    <p class="pt-form-helper mt-2" data-i18n="adm_time_capacity_helper">
                        يتم حساب إجمالي التذاكر تلقائيًا من خريطة المقاعد للمسرح.
                    </p>
                </div>
            @else
                <div class="pt-form-field">
                    <label class="pt-form-field-label">
                        <span data-i18n="adm_time_total">إجمالي التذاكر</span>
                        <span class="pt-form-req" aria-hidden="true">*</span>
                    </label>
                    <input type="number" min="1" name="total_tickets"
                           value="{{ old('total_tickets', $showTime->total_tickets) }}"
                           class="prism-input text-sm" inputmode="numeric">
                </div>
            @endif
        </div>

        {{-- Section: status --}}
        <div class="pt-form-section">
            <div class="pt-form-section-head">
                <span class="pt-form-section-head-icon" aria-hidden="true">⚙️</span>
                <span class="pt-form-section-head-title" data-i18n="adm_time_section_status">الحالة</span>
            </div>

            <div class="pt-switch-row">
                <span class="text-xs text-[color:var(--prism-text-2)]" data-i18n="adm_time_status_label">حالة الموعد</span>

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
            <p class="pt-form-helper" data-i18n="adm_time_status_helper">
                لما تفعّل Sold Out، الموعد بيختفي من صفحات الحجز ومش هيقدر يحجزه أي حد.
            </p>
        </div>

        {{-- Sticky action bar --}}
        <div class="pt-form-actions-sticky">
            <a href="{{ route('admin.shows.times.index', $show) }}"
               class="prism-btn-ghost text-sm flex items-center justify-center">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span data-i18n="adm_back">رجوع</span>
            </a>
            <button type="submit" class="prism-btn text-sm pt-form-actions-primary flex items-center justify-center">
                <span data-i18n="adm_show_save_btn">حفظ التعديلات</span>
                <span aria-hidden="true" class="pt-arrow-rtl">←</span>
            </button>
        </div>
    </form>
</section>
@endsection
