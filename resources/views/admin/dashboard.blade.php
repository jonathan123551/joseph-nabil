@extends('layouts.app')

@section('title', 'لوحة تحكم الأدمن')

@section('content')
    <section class="space-y-7">

        {{-- ============================ HERO ============================ --}}
        <div class="prism-glass prism-glow-border p-5 sm:p-6 prism-fade-up
                    flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="prism-pill prism-pill-neon">
                        <span class="prism-dot prism-dot-emerald"></span>
                        Admin Console
                    </span>
                    <span class="prism-pill">
                        <span style="letter-spacing:.28em; font-size:10px;">PRISM · CONTROL</span>
                    </span>
                </div>
                <h1 class="prism-headline text-xl sm:text-2xl">
                    <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                        لوحة تحكم الأدمن
                    </span>
                </h1>
                <p class="text-sm text-[color:var(--prism-text-2)] max-w-xl">
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

        {{-- ============================ STATS — TOP ROW ============================ --}}
        <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-3 prism-stagger">

            {{-- إجمالي العروض --}}
            <div class="prism-glass p-4 flex flex-col gap-1 prism-fade-up">
                <span class="text-[11px] text-[color:var(--prism-text-3)] uppercase" style="letter-spacing:.18em;">عدد العروض</span>
                <span class="text-3xl font-bold" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">{{ $totalShows }}</span>
                <span class="text-[11px] text-[color:var(--prism-text-3)]">كل العروض المسرحية المسجَّلة على السيستم.</span>
            </div>

            {{-- إجمالي المواعيد --}}
            <div class="prism-glass p-4 flex flex-col gap-1 prism-fade-up">
                <span class="text-[11px] text-[color:var(--prism-text-3)] uppercase" style="letter-spacing:.18em;">مواعيد العروض</span>
                <span class="text-3xl font-bold" style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">{{ $totalShowTimes }}</span>
                <span class="text-[11px] text-[color:var(--prism-text-3)]">عدد المرات اللي العروض هتتقدَّم فيها على المسرح.</span>
            </div>

            {{-- إجمالي التذاكر المتبقية --}}
            <div class="prism-glass p-4 flex flex-col gap-1 prism-fade-up"
                 style="border-color: rgba(52,211,153,0.32); box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 24px 48px -22px rgba(16,185,129,0.25);">
                <span class="text-[11px] text-[color:var(--prism-text-3)] uppercase" style="letter-spacing:.18em;">التذاكر المتبقية</span>
                <span class="text-3xl font-bold" style="color: var(--prism-emerald);">{{ $ticketsRemaining }}</span>
                <span class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed">
                    محسوبة من إجمالي التذاكر الأساسية لكل المواعيد ناقص الحجوزات
                    <span style="color: var(--prism-emerald);">(pending + approved)</span>.
                    الحجوزات المرفوضة مش بتتحسب.
                </span>
            </div>

            {{-- إجمالي التذاكر المعتمدة --}}
            <div class="prism-glass p-4 flex flex-col gap-1 prism-fade-up">
                <span class="text-[11px] text-[color:var(--prism-text-3)] uppercase" style="letter-spacing:.18em;">التذاكر <span style="color: var(--prism-emerald);">approved</span></span>
                <span class="text-3xl font-bold" style="color: var(--prism-emerald);">{{ $totalTicketsApproved }}</span>
                <span class="text-[11px] text-[color:var(--prism-text-3)]">تذاكر لحجوزات اتأكدت واتقبلت، وطلع لها QR.</span>
            </div>
        </div>

        {{-- ============================ STATS — REVENUE / SETTINGS ============================ --}}
        <div class="grid md:grid-cols-3 gap-3 prism-stagger">

            {{-- إجمالي الفلوس --}}
            <div class="prism-glass p-4 flex flex-col gap-1 prism-fade-up"
                 style="border-color: rgba(251,191,36,0.40); box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), 0 24px 48px -22px rgba(251,191,36,0.30), 0 0 24px rgba(251,191,36,0.18);">
                <span class="text-[11px] uppercase" style="letter-spacing:.18em; color: #fde68a;">إجمالي الإيرادات المعتمدة</span>
                <span class="text-3xl sm:text-4xl font-extrabold" style="color: var(--prism-gold);">
                    {{ number_format($totalRevenue, 0) }}
                    <span class="text-sm font-normal opacity-80">جنيه</span>
                </span>
                <span class="text-[11px] text-[color:var(--prism-text-3)]">
                    محسوبة من الحجوزات اللي حالتها
                    <span style="color: var(--prism-emerald);">approved</span>.
                </span>
            </div>

            {{-- حجوزات قيد المراجعة --}}
            <div class="prism-glass p-4 flex flex-col gap-1 prism-fade-up">
                <span class="text-[11px] text-[color:var(--prism-text-3)] uppercase" style="letter-spacing:.18em;">قيد المراجعة</span>
                <span class="text-3xl font-bold" style="color: var(--prism-cyan);">{{ $pendingBookings }}</span>
                <span class="text-[11px] text-[color:var(--prism-text-3)]">لسه محتاجة مراجعة Screenshot والتحويل قبل ما تتقبل.</span>
            </div>

            {{-- كارت إعدادات بيانات التحويل --}}
            <div class="prism-glass p-4 flex flex-col gap-2 prism-fade-up"
                 style="border-color: rgba(52,211,153,0.30);">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] text-[color:var(--prism-text-2)] font-semibold">بيانات التحويل</span>
                    <span class="prism-pill prism-pill-emerald">
                        <span class="prism-dot prism-dot-emerald"></span>
                        يظهر للعميل
                    </span>
                </div>

                <form action="{{ route('admin.settings.payments.update') }}" method="POST" class="space-y-2 text-xs">
                    @csrf

                    <div class="space-y-1">
                        <label class="block text-[11px] text-[color:var(--prism-text-3)]">رقم المحفظة</label>
                        <input type="text"
                               name="transfer_wallet"
                               value="{{ old('transfer_wallet', $transferWallet) }}"
                               class="prism-input text-xs py-2"
                               placeholder="مثال: 010xxxxxxxx">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-[11px] text-[color:var(--prism-text-3)]">InstaPay</label>
                        <input type="text"
                               name="transfer_insta"
                               value="{{ old('transfer_insta', $transferInsta) }}"
                               class="prism-input text-xs py-2"
                               placeholder="مثال: name@instapay">
                    </div>

                    <button type="submit" class="prism-btn prism-btn-emerald text-[11px] px-4 py-2 mt-1">
                        حفظ بيانات التحويل
                    </button>
                </form>
            </div>
        </div>

        {{-- ============================ MAIN CONTROLS ============================ --}}
        <div class="grid md:grid-cols-3 gap-4 prism-stagger">

            <a href="{{ route('admin.shows.index') }}"
               class="prism-glass prism-glow-border p-5 transition group prism-fade-up"
               style="text-decoration: none; transition: all .25s var(--prism-ease);"
               onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(129,140,248,0.6)'; this.style.boxShadow='inset 0 1px 0 rgba(255,255,255,0.06), 0 28px 56px -22px rgba(129,140,248,0.5), 0 0 24px rgba(34,211,238,0.18)';"
               onmouseout="this.style.transform=''; this.style.borderColor=''; this.style.boxShadow='';">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-3xl">🎭</div>
                    <span class="prism-pill text-[10px]">إدارة العروض</span>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[color:var(--prism-text)]">العروض المسرحية</h2>
                <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed">
                    إضافة عروض جديدة، تعديل التفاصيل، رفع البوسترات، وتفعيل/إخفاء العروض من الموقع.
                </p>
            </a>

            <a href="{{ route('admin.bookings.index') }}"
               class="prism-glass prism-glow-border p-5 transition group prism-fade-up"
               style="text-decoration: none; transition: all .25s var(--prism-ease);"
               onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(129,140,248,0.6)'; this.style.boxShadow='inset 0 1px 0 rgba(255,255,255,0.06), 0 28px 56px -22px rgba(129,140,248,0.5), 0 0 24px rgba(34,211,238,0.18)';"
               onmouseout="this.style.transform=''; this.style.borderColor=''; this.style.boxShadow='';">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-3xl">💳</div>
                    <span class="prism-pill text-[10px]">إدارة الحجوزات</span>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[color:var(--prism-text)]">الحجوزات والتحويلات</h2>
                <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed">
                    مراجعة طلبات الحجز، التأكد من التحويلات، واعتماد التذاكر وإرسال الـ QR للحضور.
                </p>
            </a>

            <a href="{{ route('admin.scanner') }}"
               class="prism-glass prism-glow-border p-5 transition group prism-fade-up"
               style="text-decoration: none; transition: all .25s var(--prism-ease);"
               onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(129,140,248,0.6)'; this.style.boxShadow='inset 0 1px 0 rgba(255,255,255,0.06), 0 28px 56px -22px rgba(129,140,248,0.5), 0 0 24px rgba(34,211,238,0.18)';"
               onmouseout="this.style.transform=''; this.style.borderColor=''; this.style.boxShadow='';">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-3xl">📷</div>
                    <span class="prism-pill text-[10px]">على الباب</span>
                </div>
                <h2 class="text-base font-semibold mb-1 text-[color:var(--prism-text)]">وضع Scan تذاكر الـ QR</h2>
                <p class="text-[11px] text-[color:var(--prism-text-3)] leading-relaxed">
                    افتح من موبايل المسؤول على باب المسرح، وامسح كود كل تذكرة عشان تتأكد إن الحجز صالح ومش مستخدم قبل كده.
                </p>
            </a>
        </div>

        {{-- ============================ SHOW TIMES TABLE ============================ --}}
        <section class="space-y-3">

            <h2 class="text-sm font-semibold text-[color:var(--prism-text-2)] mb-2">
                المواعيد والتذاكر لكل عرض
            </h2>

            {{-- DESKTOP TABLE --}}
            <div class="hidden md:block prism-glass overflow-x-auto">
                <table class="min-w-full text-xs text-[color:var(--prism-text-2)]">
                    <thead style="background: rgba(255,255,255,0.04);">
                        <tr class="text-[11px] uppercase" style="letter-spacing:.14em;">
                            <th class="px-3 py-3 text-right">العرض</th>
                            <th class="px-3 py-3 text-right">التاريخ</th>
                            <th class="px-3 py-3 text-right">الساعة</th>
                            <th class="px-3 py-3 text-center">إجمالي</th>
                            <th class="px-3 py-3 text-center" style="color: var(--prism-emerald);">Approved</th>
                            <th class="px-3 py-3 text-center" style="color: var(--prism-gold);">Pending</th>
                            <th class="px-3 py-3 text-center" style="color: var(--prism-cyan);">المتبقي</th>
                            <th class="px-3 py-3 text-center" style="color: var(--prism-gold);">Revenue</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($showTimesStats as $time)
                        <tr style="border-top: 1px solid rgba(255,255,255,0.06); transition: background .15s ease;"
                            onmouseover="this.style.background='rgba(129,140,248,0.06)'"
                            onmouseout="this.style.background=''">
                            <td class="px-3 py-3 text-[color:var(--prism-text)]">{{ $time->show->title }}</td>
                            <td class="px-3 py-3">{{ $time->date?->format('Y-m-d') }}</td>
                            <td class="px-3 py-3">{{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}</td>

                            <td class="px-3 py-3 text-center">
                                <span class="prism-pill">{{ $time->total_tickets }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="prism-pill prism-pill-emerald">{{ $time->approved_tickets }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="prism-pill" style="color: var(--prism-gold); border-color: rgba(251,191,36,0.32); background: rgba(251,191,36,0.08);">{{ $time->pending_tickets }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="prism-pill"
                                      style="
                                          color: {{ $time->remaining_tickets > 0 ? 'var(--prism-cyan)' : 'var(--prism-rose)' }};
                                          border-color: {{ $time->remaining_tickets > 0 ? 'rgba(34,211,238,0.32)' : 'rgba(251,113,133,0.32)' }};
                                          background: {{ $time->remaining_tickets > 0 ? 'rgba(34,211,238,0.08)' : 'rgba(251,113,133,0.08)' }};
                                      ">{{ $time->remaining_tickets }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
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
                            <span class="text-[color:var(--prism-text-3)]">إجمالي</span>
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
                            <span style="color: {{ $time->remaining_tickets > 0 ? 'var(--prism-cyan)' : 'var(--prism-rose)' }};">المتبقي</span>
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
                <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)]">
                    لا توجد بيانات
                </div>
            @endforelse

            </div>
        </section>

        <hr style="border-color: var(--prism-border);">

        <div class="flex items-center gap-4">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="text-xs transition" style="color: var(--prism-rose);"
                        onmouseover="this.style.color='#fb7185'; this.style.opacity='0.8'"
                        onmouseout="this.style.color='var(--prism-rose)'; this.style.opacity='1'">
                    تسجيل خروج
                </button>
            </form>
        </div>

    </section>
@endsection
