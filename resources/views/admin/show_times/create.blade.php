@extends('layouts.admin')

@section('title', 'إضافة موعد جديد - ' . $show->title)

@php
    use App\Models\Show;
    // Section-based pricing means the ticket price is defined on the Show
    // (per section: hall / balcony) rather than per-showtime. When that
    // applies, the standalone showtime ticket_price input is hidden — its
    // value is irrelevant to the booking flow and only causes confusion.
    $usesSectionPricing = $show->theater_type === Show::THEATER_ANBA_RUWEIS;
    // Seatmap-backed shows derive total capacity from the actual seat
    // table — admin no longer types a number. Manual ("Other") shows keep
    // the existing free-form input.
    $usesSeatMap = $show->usesSeatMap();
    $seatCap     = $show->seatMapCapacity();
@endphp

@section('content')
    <section class="max-w-xl mx-auto space-y-4 prism-fade-up">

        {{-- Header card --}}
        <div class="prism-glass prism-glow-border p-5">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="space-y-1">
                    <span class="prism-pill prism-pill-neon">
                        <span class="prism-dot prism-dot-emerald"></span>
                        <span data-i18n="adm_time_new_pill">New Show Time</span>
                    </span>
                    <h1 class="prism-headline text-xl">
                        <span data-i18n="adm_time_new_title"
                              style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                            إضافة موعد جديد
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

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="pt-alert pt-alert-danger text-xs prism-fade-up">
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
                    <span class="pt-form-section-head-title" data-i18n="adm_time_section_when">الموعد</span>
                    <span class="pt-form-section-head-sub" data-i18n="adm_time_section_when_sub">تاريخ وساعة بدء العرض</span>
                </div>

                <div class="pt-form-grid">
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">
                            <span data-i18n="adm_times_col_date">التاريخ</span>
                            <span class="pt-form-req" aria-hidden="true">*</span>
                        </label>
                        <input type="date" name="date"
                               value="{{ old('date') }}"
                               class="prism-input text-sm">
                    </div>
                    <div class="pt-form-field">
                        <label class="pt-form-field-label">
                            <span data-i18n="adm_times_col_time">الساعة</span>
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
                    <span class="pt-form-section-head-title" data-i18n="adm_time_section_pricing">السعر والتذاكر</span>
                </div>

                @if ($usesSectionPricing)
                    {{-- Section-priced shows: ticket_price is irrelevant. We
                         still POST a 0 to satisfy the existing required-numeric
                         validator, and surface a read-only summary of the
                         hall / balcony prices instead. --}}
                    <input type="hidden" name="ticket_price" value="0">

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
                                   value="{{ old('ticket_price') }}"
                                   class="prism-input text-sm" inputmode="decimal">
                        </div>
                    </div>
                @endif

                @if ($usesSeatMap)
                    {{-- Seatmap-backed shows derive their total ticket count
                         from the actual seat layout. The admin can still see
                         the breakdown here, but cannot type a value — the
                         controller re-syncs total_tickets from the seats
                         table on every save (see ShowTimeController). --}}
                    <div class="pt-alert pt-alert-success text-xs">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="prism-dot prism-dot-emerald"></span>
                            <span class="font-semibold text-[color:var(--prism-text)]" data-i18n="adm_time_capacity_card_title">
                                سعة المسرح (تلقائي)
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
                               value="{{ old('total_tickets', 50) }}"
                               class="prism-input text-sm" inputmode="numeric">
                    </div>

                    <div class="pt-form-field">
                        <label class="pt-form-field-label" data-i18n="adm_time_available_now">التذاكر المتاحة الآن (اختياري)</label>
                        <input type="number" min="0" name="available_tickets"
                               value="{{ old('available_tickets') }}"
                               placeholder="فاضي = نفس إجمالي التذاكر"
                               data-i18n-attr="placeholder:adm_time_available_placeholder"
                               class="prism-input text-sm" inputmode="numeric">
                        <p class="pt-form-helper" data-i18n="adm_time_available_helper">
                            لو سيبت الحقل فاضي، النظام هيبدأ بكامل العدد متاح للحجز.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Section: status --}}
            <div class="pt-form-section">
                <div class="pt-form-section-head">
                    <span class="pt-form-section-head-icon" aria-hidden="true">⚙️</span>
                    <span class="pt-form-section-head-title" data-i18n="adm_time_section_status">الحالة</span>
                </div>

                <label class="pt-switch-row cursor-pointer">
                    <span class="text-xs text-[color:var(--prism-text-2)]" data-i18n="adm_time_force_sold_out">تحديد الموعد كـ Sold Out من البداية</span>
                    <input type="checkbox" name="is_sold_out" value="1" class="w-5 h-5"
                           {{ old('is_sold_out') ? 'checked' : '' }}
                           style="accent-color: #fb7185;">
                </label>
            </div>

            {{-- Sticky action bar (sticks to bottom on mobile, inline on desktop) --}}
            <div class="pt-form-actions-sticky">
                <a href="{{ route('admin.shows.times.index', $show) }}" class="prism-btn-ghost text-sm flex items-center justify-center">
                    <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                    <span data-i18n="common_cancel">إلغاء</span>
                </a>
                <button type="submit" class="prism-btn text-sm pt-form-actions-primary flex items-center justify-center">
                    <span data-i18n="adm_time_save_btn">حفظ الموعد</span>
                    <span aria-hidden="true" class="pt-arrow-rtl">←</span>
                </button>
            </div>
        </form>
    </section>
@endsection
