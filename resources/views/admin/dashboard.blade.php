@extends('layouts.app')

@section('title', 'لوحة تحكم الأدمن')

@section('content')
    <section class="space-y-7">

        {{-- ============================ HERO ============================ --}}
        <div class="prism-glass prism-glow-border p-5 sm:p-6 prism-fade-up
                    flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="prism-pill prism-pill-neon">
                        <span class="prism-dot prism-dot-emerald"></span>
                        <span data-i18n="adm_console_pill">Admin Console</span>
                    </span>
                    <span class="prism-eyebrow" data-i18n="adm_console_eyebrow">PREMIUM · CONTROL</span>
                </div>
                <h1 class="prism-headline text-xl sm:text-2xl">
                    <span data-i18n="adm_dashboard_title"
                          style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        لوحة تحكم الأدمن
                    </span>
                </h1>
                <p class="text-sm text-[color:var(--prism-text-2)] max-w-xl"
                   data-i18n="adm_dashboard_lede">
                    من هنا تقدر تتابع نبض العروض، الحجوزات، والتذاكر اللي طلعت للجمهور.
                </p>
            </div>

            @if(session('status'))
                <div class="prism-pill prism-pill-emerald self-start sm:self-auto">
                    <span class="prism-dot prism-dot-emerald"></span>
                    {{ session('status') }}
                </div>
            @endif
        </div>

        {{-- ============================ PRIMARY KPI + ATTENTION ============================ --}}
        {{-- Two cards on desktop: revenue (primary, gold-accented, takes 2 columns)
             and pending review (cyan attention card, deep-link to bookings list with
             status filter). Strong hierarchy: revenue dominates, pending sits beside it. --}}
        <div class="grid md:grid-cols-3 gap-3 prism-stagger pt-reveal pt-reveal-stagger">

            <div class="prism-stat is-primary md:col-span-2 prism-fade-up">
                <span class="prism-stat-label" data-i18n="adm_kpi_revenue_label">إجمالي الإيرادات المعتمدة</span>
                <span class="prism-stat-value">
                    {{ number_format($totalRevenue, 0) }}
                    <span class="text-base font-semibold opacity-80 tracking-normal" data-i18n="common_egp">جنيه</span>
                </span>
                <span class="prism-stat-caption" data-i18n-html="adm_kpi_revenue_caption">
                    محسوبة من الحجوزات اللي حالتها
                    <span style="color: var(--prism-emerald);">approved</span>
                    فقط — قيد المراجعة والمرفوضة لا تُحتسب.
                </span>
            </div>

            <a href="{{ route('admin.bookings.index') }}"
               class="prism-stat is-attention prism-fade-up"
               style="text-decoration: none;">
                <div class="flex items-center justify-between">
                    <span class="prism-stat-label" style="color: var(--prism-cyan);">
                        <span class="prism-dot prism-dot-sky" style="width:6px;height:6px;"></span>
                        <span data-i18n="adm_kpi_pending">قيد المراجعة</span>
                    </span>
                    @if($pendingBookings > 0)
                        <span class="prism-pill prism-pill-sky" style="font-size:10px;" data-i18n="adm_kpi_pending_pill">يحتاج مراجعة</span>
                    @endif
                </div>
                <span class="prism-stat-value">{{ $pendingBookings }}</span>
                <span class="prism-stat-caption flex items-center justify-between gap-2">
                    <span data-i18n="adm_kpi_pending_caption">طلبات حجز محتاجة Screenshot والاعتماد.</span>
                    <span aria-hidden="true" class="prism-quick-action-arrow pt-arrow-rtl"
                          style="width:24px;height:24px;font-size:12px;">←</span>
                </span>
            </a>
        </div>

        {{-- ============================ SECONDARY STATS ============================ --}}
        <div>
            <div class="prism-section-head">
                <span class="prism-section-title" data-i18n="adm_overview_title">المؤشرات العامة</span>
                <span class="prism-eyebrow">OVERVIEW</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 prism-stagger pt-reveal pt-reveal-stagger">

                <div class="prism-stat prism-fade-up">
                    <span class="prism-stat-label" data-i18n="adm_kpi_shows">عدد العروض</span>
                    <span class="prism-stat-value" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">{{ $totalShows }}</span>
                    <span class="prism-stat-caption" data-i18n="adm_kpi_shows_caption">العروض المسرحية المسجَّلة على السيستم.</span>
                </div>

                <div class="prism-stat prism-fade-up">
                    <span class="prism-stat-label" data-i18n="adm_kpi_showtimes">مواعيد العروض</span>
                    <span class="prism-stat-value" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">{{ $totalShowTimes }}</span>
                    <span class="prism-stat-caption" data-i18n="adm_kpi_showtimes_caption">عدد المرات اللي العروض هتتقدَّم فيها على المسرح.</span>
                </div>

                <div class="prism-stat is-positive prism-fade-up">
                    <span class="prism-stat-label" data-i18n-html="adm_kpi_approved">التذاكر <span style="color: var(--prism-emerald);">approved</span></span>
                    <span class="prism-stat-value">{{ $totalTicketsApproved }}</span>
                    <span class="prism-stat-caption" data-i18n="adm_kpi_approved_caption">تذاكر لحجوزات اتأكدت واتقبلت، وطلع لها QR.</span>
                </div>

                <div class="prism-stat is-attention prism-fade-up">
                    <span class="prism-stat-label" data-i18n="adm_kpi_remaining">التذاكر المتبقية</span>
                    <span class="prism-stat-value">{{ $ticketsRemaining }}</span>
                    <span class="prism-stat-caption" data-i18n-html="adm_kpi_remaining_caption">
                        إجمالي التذاكر ناقص الحجوزات
                        <span style="color: var(--prism-emerald);">(pending + approved)</span>.
                    </span>
                </div>
            </div>
        </div>

        {{-- ============================ MAIN CONTROLS ============================ --}}
        <div>
            <div class="prism-section-head">
                <span class="prism-section-title" data-i18n="adm_quick_title">الإجراءات السريعة</span>
                <span class="prism-eyebrow">QUICK ACTIONS</span>
            </div>

            <div class="grid md:grid-cols-3 gap-4 prism-stagger pt-reveal pt-reveal-stagger">

                <a href="{{ route('admin.shows.index') }}" class="prism-quick-action prism-fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl">🎭</div>
                        <span class="prism-quick-action-arrow pt-arrow-rtl" aria-hidden="true">←</span>
                    </div>
                    <span class="prism-eyebrow mb-1" data-i18n="adm_quick_shows_eyebrow">إدارة العروض</span>
                    <h2 class="text-base font-semibold mt-1 mb-1 text-[color:var(--prism-text)]" data-i18n="adm_quick_shows_title">العروض المسرحية</h2>
                    <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed" data-i18n="adm_quick_shows_body">
                        إضافة عروض جديدة، تعديل التفاصيل، رفع البوسترات، وتفعيل/إخفاء العروض من الموقع.
                    </p>
                </a>

                <a href="{{ route('admin.bookings.index') }}" class="prism-quick-action prism-fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl">💳</div>
                        <span class="prism-quick-action-arrow pt-arrow-rtl" aria-hidden="true">←</span>
                    </div>
                    <span class="prism-eyebrow mb-1" data-i18n="adm_quick_bookings_eyebrow">إدارة الحجوزات</span>
                    <h2 class="text-base font-semibold mt-1 mb-1 text-[color:var(--prism-text)]" data-i18n="adm_quick_bookings_title">الحجوزات والتحويلات</h2>
                    <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed" data-i18n="adm_quick_bookings_body">
                        مراجعة طلبات الحجز، التأكد من التحويلات، واعتماد التذاكر وإرسال الـ QR للحضور.
                    </p>
                </a>

                <a href="{{ route('admin.scanner') }}" class="prism-quick-action prism-fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl">📷</div>
                        <span class="prism-quick-action-arrow pt-arrow-rtl" aria-hidden="true">←</span>
                    </div>
                    <span class="prism-eyebrow mb-1" data-i18n="adm_quick_scanner_eyebrow">على الباب</span>
                    <h2 class="text-base font-semibold mt-1 mb-1 text-[color:var(--prism-text)]" data-i18n="adm_quick_scanner_title">وضع Scan تذاكر QR</h2>
                    <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed" data-i18n="adm_quick_scanner_body">
                        افتح من موبايل المسؤول على باب المسرح، وامسح كود كل تذكرة عشان تتأكد إن الحجز صالح.
                    </p>
                </a>
            </div>
        </div>

        {{-- ============================ SHOW TIMES TABLE ============================ --}}
        <section class="space-y-3 pt-reveal">

            <div class="prism-section-head">
                <span class="prism-section-title" data-i18n="adm_showtimes_title">المواعيد والتذاكر لكل عرض</span>
                <span class="prism-eyebrow">SHOW TIMES</span>
            </div>

            {{-- DESKTOP TABLE --}}
            <div class="hidden md:block prism-glass overflow-x-auto">
                <table class="prism-table-clean">
                    <thead>
                        <tr>
                            <th class="text-right pt-rtl-text" data-i18n="adm_th_show">العرض</th>
                            <th class="text-right pt-rtl-text" data-i18n="adm_th_date">التاريخ</th>
                            <th class="text-right pt-rtl-text" data-i18n="adm_th_time">الساعة</th>
                            <th class="text-center" data-i18n="adm_th_total">إجمالي</th>
                            <th class="text-center" style="color: var(--prism-emerald);">Approved</th>
                            <th class="text-center" style="color: var(--prism-gold);">Pending</th>
                            <th class="text-center" style="color: var(--prism-cyan);" data-i18n="adm_th_remaining">المتبقي</th>
                            <th class="text-center" style="color: var(--prism-gold);">Revenue</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($showTimesStats as $time)
                        <tr>
                            <td class="text-[color:var(--prism-text)] font-medium">{{ $time->show->title }}</td>
                            <td>{{ $time->date?->format('Y-m-d') }}</td>
                            <td>{{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}</td>

                            <td class="text-center">
                                <span class="prism-pill">{{ $time->total_tickets }}</span>
                            </td>
                            <td class="text-center">
                                <span class="prism-pill prism-pill-emerald">{{ $time->approved_tickets }}</span>
                            </td>
                            <td class="text-center">
                                <span class="prism-pill" style="color: var(--prism-gold); border-color: rgba(251,191,36,0.32); background: rgba(251,191,36,0.08);">{{ $time->pending_tickets }}</span>
                            </td>
                            <td class="text-center">
                                <span class="prism-pill"
                                      style="
                                          color: {{ $time->remaining_tickets > 0 ? 'var(--prism-cyan)' : 'var(--prism-rose)' }};
                                          border-color: {{ $time->remaining_tickets > 0 ? 'rgba(34,211,238,0.32)' : 'rgba(251,113,133,0.32)' }};
                                          background: {{ $time->remaining_tickets > 0 ? 'rgba(34,211,238,0.08)' : 'rgba(251,113,133,0.08)' }};
                                      ">{{ $time->remaining_tickets }}</span>
                            </td>
                            <td class="text-center">
                                <span class="prism-pill" style="color: var(--prism-gold); border-color: rgba(251,191,36,0.32); background: rgba(251,191,36,0.08);">
                                    {{ number_format($time->revenue, 0) }} EGP
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE CARDS --}}
            <div class="md:hidden space-y-3 prism-stagger">

            @forelse($showTimesStats as $time)

                <div class="prism-glass p-4 space-y-3 prism-fade-up">

                    {{-- Header --}}
                    <div class="flex justify-between text-xs items-center">
                        <span class="text-[color:var(--prism-text-2)] flex items-center gap-1">
                            <span>🎭</span>
                            <span class="font-semibold">{{ $time->show->title }}</span>
                        </span>
                        <span class="font-medium" style="color: var(--prism-gold);">
                            {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                        </span>
                    </div>

                    {{-- التاريخ --}}
                    <div class="text-xs text-[color:var(--prism-text-3)] flex items-center gap-1">
                        <span>📅</span>
                        <span>{{ $time->date?->format('Y-m-d') }}</span>
                    </div>

                    {{-- Stats grid --}}
                    <div class="grid grid-cols-2 gap-2 text-xs">

                        <div class="flex justify-between rounded-lg px-3 py-1.5"
                             style="background: rgba(255,255,255,0.04); border: 1px solid var(--prism-border);">
                            <span class="text-[color:var(--prism-text-3)]" data-i18n="adm_th_total">إجمالي</span>
                            <span class="text-[color:var(--prism-text)] font-semibold">{{ $time->total_tickets }}</span>
                        </div>

                        <div class="flex justify-between rounded-lg px-3 py-1.5"
                             style="background: rgba(52,211,153,0.08); border: 1px solid rgba(52,211,153,0.32);">
                            <span style="color: var(--prism-emerald);">Approved</span>
                            <span style="color: var(--prism-emerald);" class="font-semibold">
                                {{ $time->approved_tickets }}
                            </span>
                        </div>

                        <div class="flex justify-between rounded-lg px-3 py-1.5"
                             style="background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.32);">
                            <span style="color: var(--prism-gold);">Pending</span>
                            <span style="color: var(--prism-gold);" class="font-semibold">
                                {{ $time->pending_tickets }}
                            </span>
                        </div>

                        <div class="flex justify-between rounded-lg px-3 py-1.5"
                             style="background: {{ $time->remaining_tickets > 0 ? 'rgba(34,211,238,0.08)' : 'rgba(251,113,133,0.08)' }};
                                    border: 1px solid {{ $time->remaining_tickets > 0 ? 'rgba(34,211,238,0.32)' : 'rgba(251,113,133,0.32)' }};">
                            <span style="color: {{ $time->remaining_tickets > 0 ? 'var(--prism-cyan)' : 'var(--prism-rose)' }};" data-i18n="adm_th_remaining">المتبقي</span>
                            <span class="font-semibold"
                                  style="color: {{ $time->remaining_tickets > 0 ? 'var(--prism-cyan)' : 'var(--prism-rose)' }};">
                                {{ $time->remaining_tickets }}
                            </span>
                        </div>

                        <div class="flex justify-between rounded-lg px-3 py-1.5 col-span-2"
                             style="background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.32);">
                            <span style="color: var(--prism-gold);">Revenue</span>
                            <span style="color: var(--prism-gold);" class="font-semibold">
                                {{ number_format($time->revenue, 0) }} EGP
                            </span>
                        </div>

                    </div>
                </div>

            @empty
                <div class="prism-glass p-4 text-center text-xs text-[color:var(--prism-text-3)]"
                     data-i18n="adm_showtimes_empty">
                    لسه مفيش مواعيد متسجلة على السيستم.
                </div>
            @endforelse
            </div>
        </section>

        {{-- ============================ SETTINGS ============================ --}}
        {{-- Moved out of the stats grid into its own settings section so it
             reads as configuration rather than a KPI tile. --}}
        <section class="space-y-3 pt-reveal">

            <div class="prism-section-head">
                <span class="prism-section-title" data-i18n="adm_payments_title">إعدادات الدفع</span>
                <span class="prism-eyebrow" data-i18n="adm_payments_eyebrow">SETTINGS · يظهر للعميل</span>
            </div>

            <div class="prism-glass p-5 prism-fade-up max-w-2xl"
                 style="border-color: rgba(52,211,153,0.30);">

                <form action="{{ route('admin.settings.payments.update') }}" method="POST" class="space-y-3 text-sm">
                    @csrf

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="prism-eyebrow" data-i18n="adm_payments_wallet">رقم المحفظة</label>
                            <input type="text"
                                   name="transfer_wallet"
                                   value="{{ old('transfer_wallet', $transferWallet) }}"
                                   class="prism-input text-sm"
                                   placeholder="010xxxxxxxx">
                        </div>

                        <div class="space-y-1">
                            <label class="prism-eyebrow">InstaPay</label>
                            <input type="text"
                                   name="transfer_insta"
                                   value="{{ old('transfer_insta', $transferInsta) }}"
                                   class="prism-input text-sm"
                                   placeholder="name@instapay">
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-2">
                        <span class="text-[11px] text-[color:var(--prism-text-3)]"
                              data-i18n="adm_payments_hint">
                            هتظهر في صفحة الدفع للعميل عشان يحوّل عليها.
                        </span>
                        <button type="submit" class="prism-btn-emerald text-xs px-4 py-2"
                                data-i18n="adm_payments_save">
                            حفظ بيانات التحويل
                        </button>
                    </div>
                </form>
            </div>
        </section>

    </section>
@endsection
