@extends('layouts.app')

@section('title', 'إدارة العروض')

@section('content')
<section class="space-y-5">

    {{-- Header --}}
    <div class="prism-glass prism-glow-border p-5 prism-fade-up flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="space-y-1">
            <span class="prism-pill prism-pill-neon">
                <span class="prism-dot prism-dot-emerald"></span>
                Shows
            </span>
            <h1 class="prism-headline text-xl sm:text-2xl">
                <span style="background: var(--prism-neon); -webkit-background-clip: text; background-clip: text; color: transparent;">
                    إدارة العروض
                </span>
            </h1>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.shows.create') }}" class="prism-btn text-sm">
                + إضافة عرض
            </a>

            <a href="{{ route('admin.dashboard') }}" class="prism-btn-ghost text-xs">
                <span aria-hidden="true">→</span>
                رجوع
            </a>
        </div>
    </div>

    {{-- Status Message --}}
    @if(session('status'))
        <div class="rounded-xl px-4 py-3 text-sm prism-fade-up"
             style="background: rgba(52,211,153,0.10); border: 1px solid rgba(52,211,153,0.45); color: #6ee7b7;">
            {{ session('status') }}
        </div>
    @endif

    {{-- Empty --}}
    @if($shows->isEmpty())
        <div class="prism-glass p-6 text-center text-sm text-[color:var(--prism-text-3)]">
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
                           class="flex-1 text-center px-3 py-2 rounded-xl transition"
                           style="background: rgba(192,132,252,0.12); border: 1px solid rgba(192,132,252,0.35); color: #ddd6fe;"
                           onmouseover="this.style.background='rgba(192,132,252,0.22)'; this.style.boxShadow='0 0 16px rgba(192,132,252,0.3)';"
                           onmouseout="this.style.background='rgba(192,132,252,0.12)'; this.style.boxShadow='';">
                            المواعيد
                        </a>

                        <a href="{{ route('admin.shows.edit', $show) }}"
                           class="flex-1 text-center px-3 py-2 rounded-xl transition"
                           style="background: rgba(255,255,255,0.06); border: 1px solid var(--prism-border); color: var(--prism-text);"
                           onmouseover="this.style.background='rgba(129,140,248,0.16)'; this.style.borderColor='rgba(129,140,248,0.4)';"
                           onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='var(--prism-border)';">
                            تعديل
                        </a>

                        <form action="{{ route('admin.shows.destroy', $show) }}" method="POST"
                              class="flex-1"
                              onsubmit="return confirm('متأكد إنك عايز تحذف العرض؟');">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="w-full px-3 py-2 rounded-xl transition"
                                    style="background: rgba(244,63,94,0.12); border: 1px solid rgba(251,113,133,0.35); color: #fda4af;"
                                    onmouseover="this.style.background='rgba(244,63,94,0.22)'; this.style.boxShadow='0 0 16px rgba(244,63,94,0.3)';"
                                    onmouseout="this.style.background='rgba(244,63,94,0.12)'; this.style.boxShadow='';">
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
