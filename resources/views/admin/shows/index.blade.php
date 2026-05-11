@extends('layouts.app')

@section('title', 'إدارة العروض')

@section('content')
<section class="space-y-5">

    {{-- Header --}}
    <div class="prism-glass prism-glow-border p-5 prism-fade-up flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                <span data-i18n="adm_shows_pill">Shows</span>
            </span>
            <h1 class="prism-headline text-xl sm:text-2xl">
                <span data-i18n="adm_shows_title"
                      style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    إدارة العروض
                </span>
            </h1>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.shows.create') }}" class="prism-btn text-sm">
                <span data-i18n="adm_shows_add">+ إضافة عرض</span>
            </a>

            <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true" class="pt-arrow-rtl">→</span>
                <span data-i18n="adm_back">رجوع</span>
            </a>
        </div>
    </div>

    {{-- Status Message --}}
    @if(session('status'))
        <div class="pt-alert pt-alert-success prism-fade-up">
            {{ session('status') }}
        </div>
    @endif

    {{-- Empty --}}
    @if($shows->isEmpty())
        <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)]"
             data-i18n="adm_shows_empty">
            لا يوجد عروض حالياً.
        </div>
    @else

        <div class="grid gap-4 prism-stagger pt-reveal pt-reveal-stagger">

            @foreach($shows as $show)
                <div class="prism-glass p-4 sm:p-5 space-y-3 prism-fade-up">

                    {{-- Top --}}
                    <div class="flex items-center gap-3">

                        @if($show->poster_path)
                            <img src="{{ $show->poster_path }}"
                                 class="w-16 h-16 rounded-xl object-cover"
                                 style="border: 1px solid var(--prism-border); box-shadow: 0 8px 18px -10px rgba(129,140,248,0.4);">
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm sm:text-base text-[color:var(--prism-text)]">
                                {{ $show->title }}
                            </div>

                            <div class="text-xs text-[color:var(--prism-text-3)] line-clamp-2">
                                {{ $show->description }}
                            </div>
                        </div>

                    </div>

                    {{-- Bottom Row --}}
                    <div class="flex items-center justify-between">

                        {{-- Switch --}}
                        <form action="{{ route('admin.shows.toggle', $show) }}" method="POST">
                            @csrf

                            <button type="submit" class="flex items-center gap-2">

                                <span class="text-[11px]"
                                      data-i18n="{{ $show->is_active ? 'adm_show_active' : 'adm_show_hidden' }}"
                                      style="color: {{ $show->is_active ? 'var(--prism-emerald)' : 'var(--prism-text-3)' }};">
                                    {{ $show->is_active ? 'فعال' : 'مخفي' }}
                                </span>

                                <div class="relative w-11 h-6 rounded-full transition-all duration-300"
                                     style="background: {{ $show->is_active ? 'var(--prism-emerald)' : 'rgba(107,115,133,0.5)' }};
                                            box-shadow: {{ $show->is_active ? '0 0 14px rgba(52,211,153,0.55)' : 'none' }};">
                                    <div class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow-md transition-all duration-300
                                                {{ $show->is_active ? 'right-0.5' : 'right-5' }}">
                                    </div>
                                </div>

                            </button>
                        </form>

                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap gap-2 text-xs">

                        <a href="{{ route('admin.shows.times.index', $show) }}"
                           class="pt-action-pill pt-action-pill-violet flex-1"
                           data-i18n="adm_shows_times">
                            المواعيد
                        </a>

                        <a href="{{ route('admin.shows.edit', $show) }}"
                           class="pt-action-pill flex-1"
                           data-i18n="adm_edit">
                            تعديل
                        </a>

                        <form action="{{ route('admin.shows.destroy', $show) }}" method="POST"
                              class="flex-1"
                              onsubmit="return confirm((window.PT && window.PT.lang() === 'en') ? 'Are you sure you want to delete this show?' : 'متأكد إنك عايز تحذف العرض؟');">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="pt-action-pill pt-action-pill-rose w-full"
                                    data-i18n="adm_delete">
                                حذف
                            </button>
                        </form>

                    </div>

                </div>
            @endforeach

        </div>

    @endif

</section>
@endsection
