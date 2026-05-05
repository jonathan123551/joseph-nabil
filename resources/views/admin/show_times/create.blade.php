@extends('layouts.app')

@section('title', 'إضافة موعد جديد - ' . $show->title)

@section('content')
    <section class="max-w-xl mx-auto space-y-4 prism-fade-up">
        <div class="prism-glass prism-glow-border p-5">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                New Show Time
            </span>
            <h1 class="prism-headline text-xl mt-2">
                <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    إضافة موعد جديد
                </span>
            </h1>
            <p class="text-xs text-[color:var(--prism-text-3)] mt-1">للعرض: {{ $show->title }}</p>
        </div>

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
              class="prism-glass p-5 space-y-4 prism-fade-up">
            @csrf

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">التاريخ (dd/mm/yyyy)</label>
                    <input type="date" name="date"
                           placeholder="مثال: 25/12/2025"
                           value="{{ old('date') }}"
                           class="prism-input text-sm">
                </div>
                <div>
                    <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">الساعة (HH:mm)</label>
                    <input type="time" name="time"
                           placeholder="مثال: 12:00"
                           value="{{ old('time') }}"
                           class="prism-input text-sm">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">سعر التذكرة (جنيه)</label>
                    <input type="number" step="0.5" min="0" name="ticket_price" value="{{ old('ticket_price') }}"
                           class="prism-input text-sm">
                </div>
                <div>
                    <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">إجمالي التذاكر</label>
                    <input type="number" min="1" name="total_tickets" value="{{ old('total_tickets', 50) }}"
                           class="prism-input text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs mb-1.5 text-[color:var(--prism-text-2)]">التذاكر المتاحة الآن (اختياري)</label>
                <input type="number" min="0" name="available_tickets" value="{{ old('available_tickets') }}"
                       placeholder="لو سيبته فاضي → هيبقى نفس إجمالي التذاكر"
                       class="prism-input text-xs">
            </div>

            <label class="flex items-center gap-2 text-xs cursor-pointer text-[color:var(--prism-text-2)]">
                <input type="checkbox" name="is_sold_out" id="is_sold_out" value="1" class="w-4 h-4">
                تحديد الموعد كـ Sold Out من البداية
            </label>

            <div class="flex items-center justify-between gap-2 flex-wrap pt-2">
                <a href="{{ route('admin.shows.times.index', $show) }}" class="prism-btn-ghost text-xs">
                    <span aria-hidden="true">→</span>
                    إلغاء و رجوع للمواعيد
                </a>

                <button type="submit" class="prism-btn text-sm">
                    حفظ الموعد
                    <span aria-hidden="true">←</span>
                </button>
            </div>
        </form>
    </section>
@endsection
