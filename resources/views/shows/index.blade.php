@extends('layouts.app')

@section('title', 'العروض المتاحة · Premium Tickets')

@section('body_class', 'is-pt-cine')

@section('content')

@php
    use App\Models\Show as ShowModel;

    $totalSeats = $shows->sum(function ($s) {
        return (int) $s->showTimes->sum('total_tickets');
    });
    $featured = $shows->first();
    $rest     = $shows->slice(1)->values();

    // Compute aggregate "selling fast" / "last N seats" / "trending" hint
    // per show using already-loaded showTimes. We sum total_tickets and
    // ShowTime::effectiveRemainingTickets() (which deducts both customer
    // bookings and, for seatmap-backed shows, admin-blocked seats) — that
    // way ribbons reflect the same supply the seat picker would actually
    // sell. Each call hits 1-2 small relations on an already-loaded
    // showTimes collection; no N+1 across shows.
    $showHint = function ($show) {
        $total = (int) $show->showTimes->sum('total_tickets');
        if ($total <= 0) return null;
        $available = (int) $show->showTimes->sum(function ($t) {
            return $t->effectiveRemainingTickets();
        });
        if ($available <= 0) return null;
        $ratio = $available / max($total, 1);
        if ($available <= 5) {
            return ['kind' => 'last', 'n' => $available];
        }
        if ($ratio < 0.30) {
            return ['kind' => 'fast'];
        }
        if ($total >= 200 && $ratio < 0.55) {
            return ['kind' => 'trending'];
        }
        return null;
    };

    // Bilingual price chip helpers — declared above the markup so partials
    // and JS can both reach them. Section-priced shows surface a generic
    // "Balcony / Hall" chip with a "from {min}" hint; everything else uses
    // the per-time ticket price.
    $priceChipFor = function ($show, $time) {
        if (!$show || $show->theater_type !== ShowModel::THEATER_ANBA_RUWEIS) {
            return [
                'label'       => $time->ticket_price,
                'label_key'   => null,
                'unit'        => 'EGP',
                'starts_from' => null,
            ];
        }
        $prices = array_values(array_filter([
            (int) ($show->hall_price    ?? 0),
            (int) ($show->balcony_price ?? 0),
        ]));
        $startsFrom = !empty($prices) ? min($prices) : null;
        return [
            'label'       => 'بلكون / صالة',
            'label_key'   => 'shows_section_balcony_hall',
            'unit'        => null,
            'starts_from' => $startsFrom,
        ];
    };
@endphp

<div class="pt-cine" data-pt-cine>

{{-- =====================================================================
     Scene 1 — Cinematic intro
     Full-screen opener. No header on this scene; the floating nav fades
     in once the user scrolls past it.
===================================================================== --}}
<section class="pt-cine-scene is-scene-intro"
         data-cine-scene="intro"
         aria-labelledby="pt-cine-intro-title">
    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-a"></span>
        <span class="pt-cine-orb pt-cine-orb-b"></span>
        <span class="pt-cine-orb pt-cine-orb-c"></span>
        <span class="pt-cine-grain"></span>
    </div>

    <div class="pt-cine-particles" aria-hidden="true">
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="pt-cine-intro-content pt-cine-stagger">
        <span class="pt-cine-brand-mark" aria-hidden="true">
            <svg width="44" height="44" viewBox="0 0 64 64" fill="none">
                <defs>
                    <linearGradient id="pt-cine-mark" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#22d3ee"/>
                        <stop offset="0.5" stop-color="#818cf8"/>
                        <stop offset="1" stop-color="#c084fc"/>
                    </linearGradient>
                </defs>
                <path d="M32 6 L56 20 L46 56 L18 56 L8 20 Z" fill="none" stroke="url(#pt-cine-mark)" stroke-width="2.4" stroke-linejoin="round"/>
                <circle cx="32" cy="34" r="6" fill="url(#pt-cine-mark)" opacity="0.7"/>
            </svg>
        </span>

        <span class="pt-cine-eyebrow">
            <span class="pt-live-dot"></span>
            <span data-i18n="cine_intro_eyebrow">PREMIUM TICKETS</span>
        </span>

        <h1 id="pt-cine-intro-title" class="pt-cine-intro-title">
            <span class="pt-cine-line" data-i18n="cine_intro_line_a">اكتشف</span>
            <span class="pt-cine-line pt-cine-grad" data-i18n="cine_intro_line_b">حجز التذاكر</span>
            <span class="pt-cine-line" data-i18n="cine_intro_line_c">بشكل مختلف.</span>
        </h1>

        <p class="pt-cine-intro-sub" data-i18n="cine_intro_sub">
            تجربة سينمائية للحجز على الموبايل · من اختيار العرض حتى تذكرة الـQR على واتساب.
        </p>

        <span class="pt-cine-scroll-cue" aria-hidden="true">
            <span data-i18n="cine_scroll_cue">اسحب للأسفل</span>
            <span class="pt-cine-scroll-cue-line"></span>
        </span>
    </div>

    {{-- Skip-to-shows pill — hidden by default, revealed when JS detects
         this is a return visit (visit_count >= 2 in localStorage). --}}
    <a href="#shows-grid"
       class="prism-skip-pill"
       data-pt-skip-shows
       data-i18n="shows_skip_pill">تخطّي إلى العروض ↓</a>
</section>

{{-- =====================================================================
     Scene 2 — Prologue / side-entering glass message
===================================================================== --}}
<section class="pt-cine-scene is-scene-prologue"
         data-cine-scene="prologue"
         aria-labelledby="pt-cine-prologue-title">
    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-d"></span>
        <span class="pt-cine-orb pt-cine-orb-e"></span>
    </div>

    <div class="pt-cine-prologue-card pt-cine-stagger">
        <span class="pt-cine-eyebrow">
            <span class="pt-live-dot"></span>
            <span data-i18n="cine_prologue_eyebrow">أهلا بك</span>
        </span>

        <h2 id="pt-cine-prologue-title" class="pt-cine-prologue-title">
            <span class="pt-cine-line pt-cine-grad" data-i18n="cine_prologue_title_a">حجز</span>
            <span class="pt-cine-line" data-i18n="cine_prologue_title_b">من نوع تاني.</span>
        </h2>

        <p class="pt-cine-prologue-body" data-i18n="cine_prologue_body">
            اختر العرض، احجز مقعدك من الخريطة المباشرة، ادفع بأمان،
            وتسلّم تذكرتك بكود QR على واتساب — كل ده من الموبايل.
        </p>

        <div class="pt-cine-prologue-tags" role="list">
            <span class="pt-cine-prologue-tag" role="listitem" data-i18n="cine_prologue_tag_1">سينمائي</span>
            <span class="pt-cine-prologue-tag" role="listitem" data-i18n="cine_prologue_tag_2">مباشر</span>
            <span class="pt-cine-prologue-tag" role="listitem" data-i18n="cine_prologue_tag_3">آمن</span>
        </div>
    </div>
</section>

{{-- =====================================================================
     Scenes 3–6 — Four step cards (full-screen each)
     Visual mocks (posters / seats / upload / QR) reuse the v2 motion
     classes so we don't re-define keyframes.
===================================================================== --}}

{{-- Scene 3 — Step 01 · Choose your event --}}
<section class="pt-cine-scene is-scene-step is-step-1"
         data-cine-scene="step1"
         aria-labelledby="pt-cine-step-1-title">
    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-step-a"></span>
    </div>

    <div class="pt-cine-step-num pt-cine-step-num-bg" aria-hidden="true">01</div>

    <div class="pt-cine-step-stage pt-cine-mock-host" aria-hidden="true">
        <div class="pt-cinema-mock-posters">
            <span class="pt-cinema-mock-poster is-p1"></span>
            <span class="pt-cinema-mock-poster is-p2"></span>
            <span class="pt-cinema-mock-poster is-p3"></span>
        </div>
    </div>

    <div class="pt-cine-step-content pt-cine-stagger">
        <span class="pt-cine-step-eyebrow">
            <span class="pt-cine-step-emoji" aria-hidden="true">🎭</span>
            <span data-i18n="cine_step_eyebrow_1">الخطوة الأولى</span>
        </span>
        <h2 id="pt-cine-step-1-title" class="pt-cine-step-title" data-i18n="cine_1_t">اختر عرضك</h2>
        <p class="pt-cine-step-body" data-i18n="cine_1_b">تصفح العروض المباشرة واختر الموعد اللي يناسبك بلمسة واحدة.</p>
    </div>
</section>

{{-- Scene 4 — Step 02 · Pick your seats --}}
<section class="pt-cine-scene is-scene-step is-step-2"
         data-cine-scene="step2"
         aria-labelledby="pt-cine-step-2-title">
    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-step-b"></span>
    </div>

    <div class="pt-cine-step-num pt-cine-step-num-bg" aria-hidden="true">02</div>

    <div class="pt-cine-step-stage pt-cine-mock-host" aria-hidden="true">
        <div class="pt-cinema-mock-seats">
            @for ($i = 0; $i < 40; $i++)
                @php
                    $row = intdiv($i, 10);
                    $col = $i % 10;
                    $isPick = ($row === 2 && $col >= 3 && $col <= 7);
                @endphp
                <span @if($isPick) class="is-pick" @endif></span>
            @endfor
        </div>
    </div>

    <div class="pt-cine-step-content pt-cine-stagger">
        <span class="pt-cine-step-eyebrow">
            <span class="pt-cine-step-emoji" aria-hidden="true">🪑</span>
            <span data-i18n="cine_step_eyebrow_2">الخطوة الثانية</span>
        </span>
        <h2 id="pt-cine-step-2-title" class="pt-cine-step-title" data-i18n="cine_2_t">اختر مقعدك</h2>
        <p class="pt-cine-step-body" data-i18n="cine_2_b">خريطة مباشرة للصالة توريلك المتاح لحظة بلحظة عشان تحجز مقعدك بثقة.</p>
    </div>
</section>

{{-- Scene 5 — Step 03 · Upload transfer --}}
<section class="pt-cine-scene is-scene-step is-step-3"
         data-cine-scene="step3"
         aria-labelledby="pt-cine-step-3-title">
    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-step-c"></span>
    </div>

    <div class="pt-cine-step-num pt-cine-step-num-bg" aria-hidden="true">03</div>

    <div class="pt-cine-step-stage pt-cine-mock-host" aria-hidden="true">
        <div class="pt-cinema-mock-upload">
            <div class="pt-cinema-mock-upload-bar"></div>
            <div class="pt-cinema-mock-upload-check">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12 L10 17 L19 7"/></svg>
            </div>
        </div>
    </div>

    <div class="pt-cine-step-content pt-cine-stagger">
        <span class="pt-cine-step-eyebrow">
            <span class="pt-cine-step-emoji" aria-hidden="true">📲</span>
            <span data-i18n="cine_step_eyebrow_3">الخطوة الثالثة</span>
        </span>
        <h2 id="pt-cine-step-3-title" class="pt-cine-step-title" data-i18n="cine_3_t">ارفع التحويل</h2>
        <p class="pt-cine-step-body" data-i18n="cine_3_b">حوّل على المحفظة أو InstaPay وارفع صورة التحويل بثواني داخل تدفق آمن وأنيق.</p>
    </div>
</section>

{{-- Scene 6 — Step 04 · Receive QR ticket --}}
<section class="pt-cine-scene is-scene-step is-step-4"
         data-cine-scene="step4"
         aria-labelledby="pt-cine-step-4-title">
    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-step-d"></span>
    </div>

    <div class="pt-cine-step-num pt-cine-step-num-bg" aria-hidden="true">04</div>

    <div class="pt-cine-step-stage pt-cine-mock-host" aria-hidden="true">
        <div class="pt-cinema-mock-qr">
            <div class="pt-cinema-mock-qr-grid">
                @for ($i = 0; $i < 32; $i++)
                    <span style="animation-delay: {{ ($i % 8) * 0.06 + (intdiv($i, 8)) * 0.12 }}s;"></span>
                @endfor
            </div>
            <span class="pt-cinema-mock-qr-sweep" aria-hidden="true"></span>
        </div>
    </div>

    <div class="pt-cine-step-content pt-cine-stagger">
        <span class="pt-cine-step-eyebrow">
            <span class="pt-cine-step-emoji" aria-hidden="true">🎟️</span>
            <span data-i18n="cine_step_eyebrow_4">الخطوة الرابعة</span>
        </span>
        <h2 id="pt-cine-step-4-title" class="pt-cine-step-title" data-i18n="cine_4_t">استلم تذكرتك</h2>
        <p class="pt-cine-step-body" data-i18n="cine_4_b">تذكرة QR توصلك على واتساب فور الاعتماد · جاهزة للمسح عند البوابة.</p>
    </div>
</section>

{{-- =====================================================================
     Scene 7 — Hand-off into the available shows / booking surface
===================================================================== --}}
<section id="shows-grid"
         class="pt-cine-scene is-scene-shows"
         data-cine-scene="shows"
         aria-labelledby="pt-cine-shows-title">
    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-shows-a"></span>
        <span class="pt-cine-orb pt-cine-orb-shows-b"></span>
    </div>

    <div class="pt-cine-shows-head pt-cine-stagger">
        <span class="pt-cine-eyebrow">
            <span class="pt-live-dot pt-live-dot-emerald"></span>
            <span data-i18n="cine_shows_eyebrow">العروض المباشرة الآن</span>
        </span>
        <h2 id="pt-cine-shows-title" class="pt-cine-shows-title">
            <span class="pt-cine-line" data-i18n="shows_title">العروض المتاحة</span>
        </h2>
        <p class="pt-cine-shows-sub" data-i18n="shows_sub">اختر عرضك وابدأ الحجز.</p>

        <div class="pt-cine-shows-stats" role="list">
            <div class="pt-cine-shows-stat" role="listitem">
                <div class="pt-cine-shows-stat-num">{{ $shows->count() }}</div>
                <div class="pt-cine-shows-stat-label" data-i18n="hero_stat_shows_label">عرض متاح</div>
            </div>
            <div class="pt-cine-shows-stat" role="listitem">
                <div class="pt-cine-shows-stat-num">{{ max($totalSeats, 200) }}+</div>
                <div class="pt-cine-shows-stat-label" data-i18n="hero_stat_seats_label">مقعد جاهز</div>
            </div>
            <div class="pt-cine-shows-stat" role="listitem">
                <div class="pt-cine-shows-stat-num">QR</div>
                <div class="pt-cine-shows-stat-label" data-i18n="hero_stat_qr_label">تذكرة QR فورية</div>
            </div>
        </div>
    </div>

    @if($featured)
        @php $featuredHint = $showHint($featured); @endphp
        <article class="pt-cine-featured pt-cine-stagger"
                 aria-labelledby="pt-cine-featured-title"
                 data-pt-show-card
                 data-show-id="{{ $featured->id }}">
            <div class="pt-cine-featured-poster">
                @if($featured->poster_path)
                    {{-- W3#6: featured poster is above the fold — eager + high
                         priority so the cinematic hero paints fast on mobile.
                         Grid posters below stay lazy. --}}
                    <img src="{{ $featured->poster_path }}" alt="{{ $featured->title }}" loading="eager" decoding="async" fetchpriority="high">
                @else
                    <div class="pt-cine-featured-poster-empty" data-i18n="shows_no_poster">بدون بوستر</div>
                @endif
                <span class="pt-cine-featured-badge">
                    <span class="prism-dot prism-dot-amber"></span>
                    <span data-i18n="shows_eyebrow_featured">عرض مميز</span>
                </span>
                @if($featuredHint)
                    <span class="prism-ribbon prism-ribbon-{{ $featuredHint['kind'] }} pt-show-ribbon">
                        @if($featuredHint['kind'] === 'last')
                            <span data-i18n="ribbon_last_n"
                                  data-i18n-vars='{"n": {{ $featuredHint['n'] }} }'>آخر {{ $featuredHint['n'] }} مقاعد</span>
                        @elseif($featuredHint['kind'] === 'fast')
                            <span data-i18n="ribbon_selling_fast">يُحجز بسرعة</span>
                        @else
                            <span data-i18n="ribbon_trending">الأكثر طلبًا</span>
                        @endif
                    </span>
                @endif
                <button type="button"
                        class="prism-heart-btn pt-show-heart"
                        data-pt-fav="{{ $featured->id }}"
                        data-i18n-attr="aria-label:fav_save_aria"
                        aria-label="حفظ في المفضلة"
                        aria-pressed="false">
                    <span class="heart-glyph" aria-hidden="true"></span>
                </button>
            </div>
            <div class="pt-cine-featured-body">
                <h3 id="pt-cine-featured-title" class="pt-cine-featured-title">{{ $featured->title }}</h3>
                <p class="pt-cine-featured-desc">{{ $featured->description }}</p>

                <div class="pt-cine-featured-times">
                    @forelse($featured->showTimes->take(3) as $time)
                        <div class="pt-cine-featured-time">
                            <span class="pt-cine-featured-time-when">
                                {{ $time->date->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                            </span>
                            @php $chip = $priceChipFor($featured, $time); @endphp
                            <span class="pt-cine-featured-time-price">
                                @if (!empty($chip['label_key']))
                                    <span data-i18n="{{ $chip['label_key'] }}">{{ $chip['label'] }}</span>
                                @else
                                    {{ $chip['label'] }}
                                @endif
                                @if (!empty($chip['unit']))
                                    <span class="pt-cine-featured-time-unit">{{ $chip['unit'] }}</span>
                                @elseif (!empty($chip['starts_from']))
                                    <span class="pt-cine-featured-time-unit">· <span data-i18n="shows_from">من</span> {{ $chip['starts_from'] }} <span data-i18n="shows_egp">جنيه</span></span>
                                @endif
                            </span>
                        </div>
                    @empty
                        <div class="pt-cine-featured-empty" data-i18n="shows_no_times_card">لا توجد مواعيد متاحة حاليا.</div>
                    @endforelse
                </div>

                <a href="{{ route('shows.show', $featured) }}" class="pt-cine-cta-primary pt-cinema-magnet">
                    <span data-i18n="btn_details_book">تفاصيل وحجز</span>
                    <span class="pt-arrow-rtl" aria-hidden="true">←</span>
                </a>
            </div>
        </article>
    @endif

    @if($shows->isEmpty())
        <div class="pt-cine-shows-empty">
            <p>
                <span data-i18n="shows_empty_title">لا توجد عروض متاحة حاليا</span>.
                <span data-i18n="shows_empty_body">تابعنا — هنفعّل عروض جديدة قريبا.</span>
            </p>
        </div>
    @elseif($rest->count())
        <div class="pt-cine-shows-grid pt-cine-stagger">
            @foreach($rest as $show)
                @php $hint = $showHint($show); @endphp
                <article class="pt-cine-show-card pt-cinema-magnet"
                         data-pt-show-card
                         data-show-id="{{ $show->id }}">
                    @if($show->poster_path)
                        <a href="{{ route('shows.show', $show) }}" class="pt-cine-show-card-poster" aria-label="{{ $show->title }}">
                            <img src="{{ $show->poster_path }}" alt="{{ $show->title }}" loading="lazy" decoding="async">
                            <span class="pt-cine-show-card-veil" aria-hidden="true"></span>
                        </a>
                    @else
                        <div class="pt-cine-show-card-poster pt-cine-show-card-poster-empty" data-i18n="shows_no_poster">بدون بوستر</div>
                    @endif

                    @if($hint)
                        <span class="prism-ribbon prism-ribbon-{{ $hint['kind'] }} pt-show-ribbon">
                            @if($hint['kind'] === 'last')
                                <span data-i18n="ribbon_last_n"
                                      data-i18n-vars='{"n": {{ $hint['n'] }} }'>آخر {{ $hint['n'] }} مقاعد</span>
                            @elseif($hint['kind'] === 'fast')
                                <span data-i18n="ribbon_selling_fast">يُحجز بسرعة</span>
                            @else
                                <span data-i18n="ribbon_trending">الأكثر طلبًا</span>
                            @endif
                        </span>
                    @endif

                    <button type="button"
                            class="prism-heart-btn pt-show-heart"
                            data-pt-fav="{{ $show->id }}"
                            data-i18n-attr="aria-label:fav_save_aria"
                            aria-label="حفظ في المفضلة"
                            aria-pressed="false">
                        <span class="heart-glyph" aria-hidden="true"></span>
                    </button>

                    <div class="pt-cine-show-card-body">
                        <h3 class="pt-cine-show-card-title">{{ $show->title }}</h3>
                        <p class="pt-cine-show-card-desc">{{ $show->description }}</p>

                        <div class="pt-cine-show-card-foot">
                            <span class="pt-cine-show-card-times">
                                <span class="prism-dot prism-dot-emerald"></span>
                                {{ $show->showTimes->count() }} <span data-i18n="shows_pill_times">موعد متاح</span>
                            </span>
                            <a href="{{ route('shows.show', $show) }}" class="pt-cine-cta-mini">
                                <span data-i18n="btn_details_book">تفاصيل وحجز</span>
                                <span class="pt-arrow-rtl" aria-hidden="true">←</span>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

</div>{{-- /.pt-cine --}}

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ---------- Wave 1 — return-visit detection (QW#3) ----------
    // Increment a per-browser visit counter and reveal the "Skip to shows"
    // pill on visit 2+. Errors swallowed for incognito / disabled storage.
    try {
        var key = 'pt_visit_count';
        var raw = window.localStorage.getItem(key);
        var count = parseInt(raw, 10);
        if (!isFinite(count) || count < 0) count = 0;
        count += 1;
        window.localStorage.setItem(key, String(count));
        if (count >= 2) {
            document.querySelectorAll('[data-pt-skip-shows]').forEach(function (el) {
                el.classList.add('is-shown');
            });
        }
    } catch (e) { /* ignore */ }

    // Smooth scroll for the skip pill (anchor still works without JS).
    document.addEventListener('click', function (e) {
        var pill = e.target.closest('[data-pt-skip-shows]');
        if (!pill) return;
        var target = document.getElementById('shows-grid');
        if (!target) return;
        e.preventDefault();
        var prefersReduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        target.scrollIntoView({ behavior: prefersReduce ? 'auto' : 'smooth', block: 'start' });
    });

    // ---------- Wave 1 — heart favorites (QW#4) ----------
    var FAV_KEY = 'pt_fav_shows';
    function readFavs() {
        try {
            var raw = window.localStorage.getItem(FAV_KEY);
            var parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch (e) { return []; }
    }
    function writeFavs(list) {
        try { window.localStorage.setItem(FAV_KEY, JSON.stringify(list)); } catch (e) { /* ignore */ }
    }
    function syncHeartButtons() {
        var favs = readFavs();
        document.querySelectorAll('[data-pt-fav]').forEach(function (btn) {
            var id = btn.getAttribute('data-pt-fav');
            var on = favs.indexOf(String(id)) !== -1;
            btn.classList.toggle('is-fav', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            var i18nKey = on ? 'fav_unsave_aria' : 'fav_save_aria';
            btn.setAttribute('data-i18n-attr', 'aria-label:' + i18nKey);
            if (window.PT && typeof window.PT.t === 'function') {
                btn.setAttribute('aria-label', window.PT.t(i18nKey));
            }
        });
        updateFavPill(favs.length);
    }
    function updateFavPill(n) {
        var pill = document.querySelector('[data-pt-fav-pill]');
        if (!pill) return;
        if (n > 0) {
            pill.classList.add('is-shown');
            var counter = pill.querySelector('[data-pt-fav-count]');
            if (counter) counter.textContent = String(n);
        } else {
            pill.classList.remove('is-shown');
        }
    }
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-pt-fav]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        var id = String(btn.getAttribute('data-pt-fav') || '');
        if (!id) return;
        var favs = readFavs();
        var idx = favs.indexOf(id);
        var added;
        if (idx === -1) { favs.push(id); added = true; }
        else { favs.splice(idx, 1); added = false; }
        writeFavs(favs);
        syncHeartButtons();
        var t = (window.PT && typeof window.PT.t === 'function')
            ? window.PT.t(added ? 'fav_saved_toast' : 'fav_unsaved_toast')
            : (added ? 'Saved' : 'Removed');
        if (window.PT && typeof window.PT.toast === 'function') window.PT.toast(t, 1800);
        if (added && navigator.vibrate) { try { navigator.vibrate(8); } catch (e) {} }
    });

    // Re-run on load + after i18n changes (so aria labels follow language).
    syncHeartButtons();
    document.addEventListener('pt:langchange', syncHeartButtons);
})();
</script>
@endpush
