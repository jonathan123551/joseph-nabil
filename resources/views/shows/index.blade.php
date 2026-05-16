@extends('layouts.app')

@section('title', 'العابد · فيلم الراهب القمص بولس المقاري')
@section('headMeta')
    <meta name="pt-title-i18n" content="page_title_shows">
    {{-- Above-the-fold preloads — Safari + Chrome will start the
         downloads as soon as the HTML lands instead of after the
         layout pass. Cuts visible "no backdrop" time on iPhone
         Safari for both the Joseph Nabil presents card (first
         scene the user sees) and the العباد hero immediately below. --}}
    <link rel="preload" as="image"
          href="{{ asset('brand/joseph-nabil-director-720.png') }}"
          media="(max-width: 600px)"
          fetchpriority="high">
    <link rel="preload" as="image"
          href="{{ asset('brand/joseph-nabil-director.png') }}"
          media="(min-width: 601px)"
          fetchpriority="high">
    <link rel="preload" as="image" href="{{ asset('images/al-abed/hero.jpg') }}" fetchpriority="high">
@endsection

@section('body_class', 'is-pt-cine is-pt-alebad')

@section('content')

@php
    use App\Models\Show as ShowModel;

    $totalSeats = $shows->sum(function ($s) {
        return (int) $s->showTimes->sum('total_tickets');
    });
    $featured = $shows->first();
    $rest     = $shows->slice(1)->values();

    // Cast / credits for العباد. Cast is intentionally a static array in
    // the view (not a DB table) — it's tightly coupled to the production,
    // the same content for every visitor, and editable in one place. The
    // photo files live in public/images/al-abed/cast/.
    //
    // Each entry: file path, actor display name, role short-label. The
    // poster artwork itself already names the actor; the `role` is the
    // emotional/narrative position for the rail caption.
    $cast = [
        [
            'src'   => 'images/al-abed/cast/02-lotfy-labib.jpg',
            'name'  => 'لطفي لبيب',
            'role'  => 'الفنان القدير',
        ],
        [
            'src'   => 'images/al-abed/cast/03-ahmed-halawa.jpg',
            'name'  => 'أحمد حلاوة',
            'role'  => 'الفنان القدير',
        ],
        [
            'src'   => 'images/al-abed/cast/05-hanan-soliman.jpg',
            'name'  => 'حنان سليمان',
            'role'  => 'الفنانة القديرة',
        ],
        [
            'src'   => 'images/al-abed/cast/08-nagy-saad.jpg',
            'name'  => 'ناجي سعد',
            'role'  => 'الفنان القدير',
        ],
        [
            'src'   => 'images/al-abed/cast/07-fotouh-ahmed.jpg',
            'name'  => 'فتوح أحمد',
            'role'  => 'الفنان القدير',
        ],
        [
            'src'   => 'images/al-abed/cast/04-mohamed-radwan.jpg',
            'name'  => 'محمد رضوان',
            'role'  => 'الفنان القدير',
        ],
        [
            'src'   => 'images/al-abed/cast/06-ahmed-halawany.jpg',
            'name'  => 'أحمد الحلواني',
            'role'  => 'الفنان القدير',
        ],
        [
            'src'   => 'images/al-abed/cast/09-assem-samy.jpg',
            'name'  => 'عاصم سامي',
            'role'  => 'الفنان القدير',
        ],
        [
            'src'   => 'images/al-abed/cast/10-sameh-fekry.jpg',
            'name'  => 'سامح فكري',
            'role'  => 'الفنان',
        ],
        [
            'src'   => 'images/al-abed/cast/11-taher-elhakim.jpg',
            'name'  => 'طاهر الحكيم',
            'role'  => 'الفنان',
        ],
        [
            'src'   => 'images/al-abed/cast/01-father-boulos.jpg',
            'name'  => '   فريد النقراشي',
            'role'  => ' بطولة',
        ],
    ];

    // Trailer (Facebook video). We embed it inline via the Facebook
    // video plugin (no FB SDK script needed) and click-to-load so the
    // iframe only mounts when the user taps play — keeps the iPhone
    // Safari first paint fast and avoids a ~200KB SDK on initial load.
    //
    // IMPORTANT: the FB Video Plugin (`/plugins/video.php`) needs the
    // canonical `/watch/?v=<id>` form for the `href` query param.
    // Short share-links like `/share/v/<short>/` do NOT reliably
    // resolve inside the plugin iframe — they 30x off-site, the
    // plugin bails, and the trailer never plays inside the page.
    // That bug is why the previous embed appeared broken.
    //
    // Note: the base iframe URL is ALWAYS the plugin endpoint
    // (`/plugins/video.php`). It is NOT a /share/v/ URL — those are
    // only redirects to a canonical /watch URL and cannot host the
    // embedded player themselves. Setting the base to a /share/v/
    // URL produces a URL with two `?` separators (syntax error) and
    // a non-embeddable target.
    //
    // `$trailerUrl`      = canonical /watch URL, user-facing
    //                      fallback ("افتح في فيسبوك").
    // `$trailerEmbedUrl` = FB video plugin iframe src. Width is
    //                      requested at 1280 so the player renders
    //                      at full size inside our 16:9 frame. Plugin
    //                      docs only honour `href`, `show_text`,
    //                      `width`, `appId` — other params are
    //                      ignored (autoplay/mute are SDK methods,
    //                      not plugin params).
    $trailerVideoId  = '1816698382142571';
    $trailerUrl      = 'https://www.facebook.com/watch/?v=' . $trailerVideoId;
    $trailerEmbedUrl = 'https://www.facebook.com/plugins/video.php'
        . '?href=' . rawurlencode($trailerUrl)
        . '&show_text=false&width=1280';

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

    // Booking target for the hero "احجز الآن" CTA. We deliberately route
    // through the cinematic show-details page BEFORE the user picks a
    // showtime — the show page provides emotional context (poster,
    // synopsis, cast) so the booking decision feels intentional rather
    // than transactional. Only when there's no featured show at all do
    // we fall back to anchor-scrolling to the showtimes grid so the
    // homepage still works with zero data.
    //
    // Flow: Homepage → Show page → Choose showtime → Booking.
    $heroBookHref  = $featured ? route('shows.show', $featured) : '#shows-grid';
    $heroBookLabel = 'احجز الآن';
@endphp

<div class="pt-cine pt-alebad" data-pt-cine>

{{-- =====================================================================
     Scene 0 — Joseph Nabil cinematic presentation
     The true opener: a full-viewport studio-intro signature that
     emotionally frames the entire homepage as "A Joseph Nabil
     experience" BEFORE the user enters the world of العابد. Builds
     the feel of a real cinematic production — like a film-studio
     logo card at the start of a movie.

     Design notes:
       * Deep cinematic black backdrop with a soft warm-gold radial
         spotlight + ambient drifting orbs. Mirrors the lighting of
         the source logo art so the rendered gold blends with the
         scene rather than feeling pasted onto it.
       * The logo PNG has its near-black background baked in. We use
         `mix-blend-mode: screen` on the <img> so the dark frame
         drops out and only the gold typography + glow remain
         visible against the scene's backdrop. No alpha hacks
         required — the original file is preserved unmodified.
       * A bottom "bleed" gradient + the hero's own dark sky bleed
         INTO each other so the transition feels like a cross-fade
         in a film cut, not a hard section divider.
       * `is-scene-intro` moves from the hero to this scene so the
         floating nav stays hidden through the presents card and
         fades back in only once we enter the hero.
       * Tagline is intentionally short — a cinematic credit line,
         not an about-us paragraph.
===================================================================== --}}
<!-- <section class="pt-cine-scene is-scene-intro pt-alebad-presents"
         data-cine-scene="presents"
         aria-labelledby="pt-alebad-presents-eyebrow">

    <div class="pt-alebad-presents-bg" aria-hidden="true">
        <span class="pt-alebad-presents-spotlight"></span>
        <span class="pt-alebad-presents-vignette"></span>
        <span class="pt-cine-grain"></span>
    </div>

    {{-- Ambient gold orbs drifting slowly behind the logo. Cheap on
         GPU (just blurred radial gradients, no filter chains). --}}
    <div class="pt-alebad-presents-orbs" aria-hidden="true">
        <span class="pt-alebad-presents-orb pt-alebad-presents-orb-a"></span>
        <span class="pt-alebad-presents-orb pt-alebad-presents-orb-b"></span>
    </div>

    <div class="pt-alebad-presents-content pt-cine-stagger">
        <span id="pt-alebad-presents-eyebrow" class="pt-alebad-presents-eyebrow">
            <span class="pt-alebad-presents-eyebrow-dot" aria-hidden="true"></span>
            <span>A JOSEPH NABIL EXPERIENCE</span>
        </span>

        {{-- Logo. Mobile gets the 720px-wide downscaled variant
             (~270KB) via <picture><source>; desktop loads the
             1155×888 master (~445KB). The master image is
             intentionally preloaded above in headMeta. --}}
        <div class="pt-alebad-presents-mark">
            <span class="pt-alebad-presents-mark-glow" aria-hidden="true"></span>
            <picture>
                <source srcset="{{ asset('brand/joseph-nabil-director-720.png') }}"
                        media="(max-width: 600px)">
                <img src="{{ asset('brand/joseph-nabil-director.png') }}"
                     alt="Joseph Nabil — Director"
                     width="1155" height="888"
                     fetchpriority="high"
                     decoding="async">
            </picture>
        </div>

        <p class="pt-alebad-presents-tagline">
            تجربةٌ سينمائية يقدّمها المخرج
            <span class="pt-alebad-presents-tagline-name">جوزيف نبيل</span>
            <span class="pt-alebad-presents-tagline-divider" aria-hidden="true"> · </span>
            حيث تلتقي الصورة بالإحساس، ويلتقي الإنسان بالحكاية.
        </p>

        <span class="pt-alebad-presents-cue" aria-hidden="true">
            <span>ادخل إلى العالم</span>
            <span class="pt-alebad-presents-cue-chevron">↓</span>
        </span>
    </div>

    {{-- Cinematic bleed into the next scene (العباد hero). Bottom
         28vh fades from this scene's warm-black to the hero's
         cool-black via a layered gradient, so the cut between
         scenes reads as a cross-fade. --}}
    <div class="pt-alebad-presents-bleed" aria-hidden="true"></div>
</section> -->

{{-- =====================================================================
     Scene 1 — Cinematic hero · العباد
     Full-screen opener built around the production: a large atmospheric
     backdrop (the priest poster, already preloaded), dramatic typography,
     and three premium CTAs. The booking system is the secondary CTA; the
     primary feeling is "this is a real show".
===================================================================== --}}
<section class="pt-cine-scene pt-alebad-hero"
         data-cine-scene="hero"
         aria-labelledby="pt-alebad-hero-title">

    {{-- Backdrop. The hero image is intentionally a real <img> (not a
         CSS background-image) so the browser can apply preload + lazy
         decoding hints, and so screen readers can ignore it cleanly. --}}
    <div class="pt-alebad-hero-bg" aria-hidden="true">
        <img class="pt-alebad-hero-img"
             src="{{ asset('images/al-abed/hero.jpg') }}"
             alt=""
             loading="eager"
             decoding="async"
             fetchpriority="high">
        <span class="pt-alebad-hero-veil"></span>
        <span class="pt-alebad-hero-vignette"></span>
        <span class="pt-cine-grain"></span>
    </div>

    {{-- Ambient orbs reuse the existing cine palette. Aria-hidden so they
         don't pollute the accessibility tree. --}}
    <div class="pt-cine-bg pt-alebad-hero-orbs" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-a"></span>
        <span class="pt-cine-orb pt-cine-orb-b"></span>
    </div>

    <div class="pt-alebad-hero-content pt-cine-stagger">
        

        {{-- The big "العابد" gold text + the small "الراهب القمص بولس المقاري"
             subtitle that used to live here are intentionally removed and
             replaced by the official El3abed calligraphic title artwork.
             Both the title and the subtitle ("الراهب القمص بولس المقاري") are
             already baked into the artwork, so this swap preserves the same
             semantic content while making the hero feel like a film
             title card rather than a stack of styled text rows.

             The `<h1>` element is kept (with the same `id` used by the
             `aria-labelledby` on the parent <section>) so the page still
             exposes a top-level heading. The `<img>`'s `alt` text carries
             the full title for screen readers and SEO. --}}
        <h1 id="pt-alebad-hero-title" class="pt-alebad-hero-title">
            <img class="pt-alebad-hero-logo"
                 src="{{ asset('images/brand/el3abed-title.png') }}"
                 alt="العابد — الراهب القمص بولس المقاري"
                 width="1200"
                 height="996"
                 fetchpriority="high"
                 decoding="async" />
        </h1>
        <span class="pt-cine-eyebrow pt-alebad-eyebrow">
            <span class="pt-live-dot pt-live-dot-gold"></span>
            <span>تحت رعاية البابا تواضروس الثاني · بطريرك الكرازة المرقسية</span>
        </span>
        <p class="pt-alebad-hero-credit">
            <span class="pt-alebad-hero-credit-label">سيناريو و حوار </span>
            <span class="pt-alebad-hero-credit-name"> فريد النقراشي</span>
        </p>
        <p class="pt-alebad-hero-credit">
            <span class="pt-alebad-hero-credit-label">إخراج</span>
            <span class="pt-alebad-hero-credit-name">جوزيف نبيل</span>
        </p>
        
        <p class="pt-alebad-hero-tagline">
            رحلة روحية مستوحاة من سيرة الراهب القمص بولس المقاري — صلاة، صحراء، وعبور إلى السماء.
        </p>

        <div class="pt-alebad-hero-cta">
            <a href="{{ $heroBookHref }}"
               class="pt-alebad-cta pt-alebad-cta-primary prism-btn prism-btn-gold pt-cinema-magnet"
               data-i18n="btn_book_now">
                <span>{{ $heroBookLabel }}</span>
                <span class="pt-arrow-rtl" aria-hidden="true">←</span>
            </a>

            <a href="#pt-alebad-trailer"
               class="pt-alebad-cta pt-alebad-cta-ghost pt-alebad-cta-play"
               data-pt-smooth-anchor>
                <span class="pt-alebad-cta-play-glyph" aria-hidden="true">
                    <svg width="14" height="16" viewBox="0 0 14 16" fill="none">
                        <path d="M1 1.5v13L13 8 1 1.5Z" fill="currentColor"/>
                    </svg>
                </span>
                <span>شاهد البرومو</span>
            </a>

            {{-- "تفاصيل العرض" now anchor-scrolls to the story/cast/credits
                 section instead of jumping off to /shows/{id}. The user
                 stays in the cinematic homepage flow: trailer → cast →
                 story → credits, and only operational booking sends them
                 deeper. The scroll uses the existing
                 `data-pt-smooth-anchor` smooth-scroll hook and accounts
                 for the sticky topbar via the section's `scroll-margin-top`. --}}
            <a href="#pt-alebad-story"
               class="pt-alebad-cta pt-alebad-cta-ghost"
               data-pt-smooth-anchor>
                <span>تفاصيل العرض</span>
                <span class="pt-arrow-rtl" aria-hidden="true">←</span>
            </a>
        </div>

        <span class="pt-cine-scroll-cue pt-alebad-scroll-cue" aria-hidden="true">
            <span>اسحب للأسفل</span>
            <span class="pt-cine-scroll-cue-line"></span>
        </span>
    </div>
</section>

{{-- =====================================================================
     Scene 2 — Trailer / promo card
     Premium poster-frame that opens the Facebook reel in a new tab. We
     deliberately don't embed the FB SDK iframe (heavy, unreliable on
     iPhone Safari, depends on post permalink). The frame uses a still
     from the production's hero poster as a thumbnail; the play button
     + ambient glow + glass border do the cinematic lifting.
===================================================================== --}}
<section class="pt-cine-scene pt-alebad-trailer"
         id="pt-alebad-trailer"
         data-cine-scene="trailer"
         aria-labelledby="pt-alebad-trailer-title">

    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-d"></span>
    </div>

    <div class="pt-alebad-trailer-head pt-cine-stagger">
        <span class="pt-cine-eyebrow">
            <span class="pt-live-dot pt-live-dot-gold"></span>
            <span>البرومو الرسمي</span>
        </span>
        <h2 id="pt-alebad-trailer-title" class="pt-alebad-section-title">
            <span class="pt-alebad-section-title-grad">شاهد البرومو</span>
        </h2>
        
    </div>

    {{-- Trailer card is a click-to-load embed (NOT a link). The first
         paint is the cinematic poster-frame; tapping the play button
         swaps in an <iframe> pointing at the Facebook video plugin so
         the trailer plays INLINE inside the homepage. A separate
         "افتح في فيسبوك ↗" fallback link is rendered below the frame
         so users are never stranded if the iframe fails to load
         (private video, region block, ad-blocker, etc.). --}}
    <div class="pt-alebad-trailer-card pt-cine-stagger"
         data-pt-trailer-card
         data-pt-trailer-embed="{{ $trailerEmbedUrl }}">
        <div class="pt-alebad-trailer-frame"
             data-pt-trailer-frame
             role="button"
             tabindex="0"
             aria-label="انقر لتشغيل برومو مسرحية العباد">
            <span class="pt-alebad-trailer-thumb"
                  style="background-image:url('{{ asset('images/al-abed/cast/01-father-boulos.jpg') }}')"
                  aria-hidden="true"></span>
            <span class="pt-alebad-trailer-veil" aria-hidden="true"></span>
            <span class="pt-alebad-trailer-play" aria-hidden="true">
                <svg width="34" height="40" viewBox="0 0 34 40" fill="none">
                    <path d="M2 2.5v35L32 20 2 2.5Z" fill="currentColor"/>
                </svg>
            </span>
            <span class="pt-alebad-trailer-pulse" aria-hidden="true"></span>
            <span class="pt-alebad-trailer-loading" aria-hidden="true">
                <span class="pt-alebad-trailer-spinner"></span>
                <span>...جارٍ التحميل</span>
            </span>
        </div>
        <div class="pt-alebad-trailer-meta">
            <span class="pt-alebad-trailer-meta-label">برومو رسمي · انقر للتشغيل</span>
            <a class="pt-alebad-trailer-fallback"
               href="{{ $trailerUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="افتح البرومو في فيسبوك (إذا لم يبدأ التشغيل)">
                <span>افتح في فيسبوك</span>
                <span class="pt-alebad-trailer-meta-arrow" aria-hidden="true">↗</span>
            </a>
        </div>
    </div>
</section>

{{-- =====================================================================
     Scene 3 — Cast rail
     Horizontally-scrollable cinematic poster rail. CSS-only on mobile:
     `scroll-snap-type: x mandatory` ensures swipes always land on a
     card. Each card lazy-loads its poster and surfaces actor + role in
     a discreet caption that doesn't fight the poster artwork.
===================================================================== --}}
<section class="pt-cine-scene pt-alebad-cast"
         id="pt-alebad-cast"
         data-cine-scene="cast"
         aria-labelledby="pt-alebad-cast-title">

    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-e"></span>
    </div>

    <div class="pt-alebad-cast-head pt-cine-stagger">
        <span class="pt-cine-eyebrow">
            <span class="pt-live-dot"></span>
            <span>طاقم العمل</span>
        </span>
        <h2 id="pt-alebad-cast-title" class="pt-alebad-section-title">
            <span class="pt-alebad-section-title-grad">نجوم الفيلم</span>
        </h2>
        <p class="pt-alebad-section-sub">
            مجموعة من ألمع النجوم يجتمعون في عمل واحد.
        </p>
    </div>

    {{-- Cast rail v4. Lessons learned from previous passes:
         * Mandatory snap + snap-stop:always killed iOS native momentum
           (every flick forced a hard stop). Replaced with proximity
           snap so iOS coasts naturally and settles softly. v3.
         * The CSS `mask-image` edge fade was visually CLIPPING the
           edge cards to transparency — users read this as "cards
           are cut off". Removed entirely in v4. Cards now reach
           the literal edge of the scroller at full opacity. The
           rail's own padding handles breathing room.
         * Desktop had ZERO discoverable affordance — no arrows,
           no drag, no wheel-to-horizontal. v4 adds:
             - Gold circular nav arrows (hover-revealed, auto-
               disabled at ends, RTL-aware).
             - Mouse drag-to-scroll (pointer-fine only; touch keeps
               native momentum untouched).
             - Vertical wheel → horizontal scroll (passes through
               to the page at rail edges so page-scroll still
               works).
             - cursor: grab / grabbing cursors.
           All wired in setupCastRailInteractions IIFE in app layout.
         * Active-card emphasis: IntersectionObserver flags any
           fully-visible card with .is-centered for a subtle scale
           + glow boost.

         Markup notes:
         * Arrow buttons sit INSIDE the wrap but OUTSIDE the
           pt-cine-stagger so they don't get the stagger's
           opacity-toggle (they manage their own hover-reveal).
         * `data-pt-cast-rail-wrap` / `data-pt-cast-rail` /
           `data-pt-cast-arrow` are stable JS hooks. --}}
    <div class="pt-alebad-cast-rail-wrap" data-pt-cast-rail-wrap>
        <button type="button"
                class="pt-alebad-cast-arrow pt-alebad-cast-arrow-prev"
                data-pt-cast-arrow="prev"
                aria-label="السابق"
                aria-controls="pt-alebad-cast-rail-list">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none"
                 stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
        <button type="button"
                class="pt-alebad-cast-arrow pt-alebad-cast-arrow-next"
                data-pt-cast-arrow="next"
                aria-label="التالي"
                aria-controls="pt-alebad-cast-rail-list">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none"
                 stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>

        <div class="pt-alebad-cast-rail-inner pt-cine-stagger">
            <ul id="pt-alebad-cast-rail-list" class="pt-alebad-cast-rail" role="list" data-pt-cast-rail>
                @foreach($cast as $i => $member)
                    <li class="pt-alebad-cast-card" role="listitem" style="--i: {{ $i }}">
                        <span class="pt-alebad-cast-poster">
                            <img src="{{ asset($member['src']) }}"
                                 alt="{{ $member['role'] }} {{ $member['name'] }} — فيلم العابد"
                                 loading="lazy"
                                 decoding="async">
                            <span class="pt-alebad-cast-veil" aria-hidden="true"></span>
                            <span class="pt-alebad-cast-glow" aria-hidden="true"></span>
                        </span>
                        <span class="pt-alebad-cast-caption">
                            <span class="pt-alebad-cast-role">{{ $member['role'] }}</span>
                            <span class="pt-alebad-cast-name">{{ $member['name'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>

            <span class="pt-alebad-cast-rail-hint" aria-hidden="true">
                <span class="pt-alebad-cast-rail-hint-chevron">←</span>
                <span>اسحب لاكتشاف باقي النجوم</span>
            </span>
        </div>
    </div>
</section>

{{-- =====================================================================
     Scene 4 — Story
     Centered quote-style block. Deliberately short, evocative, and
     scoped to a single sentence + supporting paragraph so the section
     reads as cinematic copy, not a marketing pitch. The decorative
     cross-bar dividers echo the production's religious aesthetic.
===================================================================== --}}
<section class="pt-cine-scene pt-alebad-story"
         id="pt-alebad-story"
         data-cine-scene="story"
         aria-labelledby="pt-alebad-story-title">

    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-shows-a"></span>
        <span class="pt-cine-orb pt-cine-orb-shows-b"></span>
    </div>

    <div class="pt-alebad-story-content pt-cine-stagger">
        <span class="pt-cine-eyebrow">
            <span class="pt-live-dot pt-live-dot-gold"></span>
            <span>القصة</span>
        </span>

        <h2 id="pt-alebad-story-title" class="pt-alebad-story-title">
            <span class="pt-alebad-story-quote-mark" aria-hidden="true">”</span>
            في صحراءٍ ما، عاش رجلٌ تركَ الدنيا كلَّها ليبحثَ عن الله.
        </h2>

        <span class="pt-alebad-story-divider" aria-hidden="true">
            <span class="pt-alebad-story-divider-bar"></span>
            <span class="pt-alebad-story-divider-mark">✦</span>
            <span class="pt-alebad-story-divider-bar"></span>
        </span>

        <p class="pt-alebad-story-body">
            "العابد" فيلم مستوحى من سيرة الراهب القمص بولس المقاري — قصة عبور
            من ضوضاء العالم إلى صمت السماء، ومن انكسار الإنسان إلى عظمة الإيمان.
            عملٌ سينمائي على المسرح، يجمع نخبة من أعظم نجوم الدراما المصرية
            في رحلة روحية لا تُنسى.
        </p>

        <div class="pt-alebad-story-credits">
            <span class="pt-alebad-story-credit">
                <span class="pt-alebad-story-credit-label">إخراج</span>
                <span class="pt-alebad-story-credit-value">جوزيف نبيل</span>
            </span>
            <span class="pt-alebad-story-credit-sep" aria-hidden="true">·</span>
            <span class="pt-alebad-story-credit">
                <span class="pt-alebad-story-credit-label">سيناريو وحوار</span>
                <span class="pt-alebad-story-credit-value">فريد النقراشي </span>
            </span>
            <span class="pt-alebad-story-credit-sep" aria-hidden="true">·</span>
            <span class="pt-alebad-story-credit">
                <span class="pt-alebad-story-credit-label">موسيقى</span>
                <span class="pt-alebad-story-credit-value">عمانوئيل سعد</span>
            </span>
        </div>

        {{-- =============================================================
             Making Of / Full Credits — collapsed disclosure surface.
             Collapsed state shows only a one-line teaser of headline
             names + a ghost toggle button, so the homepage's vertical
             rhythm is barely affected. Tapping the toggle expands the
             panel to reveal grouped credit sections:
               1. إنتاج (production)
               2. بطولة (lead)
               3. بالاشتراك مع النجوم (supporting cast — 47 names)
               4. صنّاع العمل (crew — bilingual role labels)
               5. سيناريو وحوار · إخراج (final stamp)
             Height animation: grid 0fr → 1fr (no JS height measure).
             Reduced-motion safe (CSS strips transition). Wired by
             setupStoryCreditsExpand IIFE in app layout.
        ============================================================= --}}
        <div class="pt-alebad-story-more">
            <p class="pt-alebad-story-more-teaser">
                فريد النقراشي · أحمد حلاوة · لطفي لبيب · محمد رضوان
                <span class="pt-alebad-story-more-teaser-more">+44 آخرين</span>
            </p>

            <button type="button"
                    class="pt-alebad-story-more-toggle"
                    data-pt-credits-toggle
                    aria-controls="pt-alebad-story-credits-panel"
                    aria-expanded="false">
                <span data-show>عرض فريق العمل الكامل</span>
                <span data-hide>إخفاء فريق العمل</span>
                <span class="pt-alebad-story-more-toggle-chev" aria-hidden="true">⌄</span>
            </button>

            <div id="pt-alebad-story-credits-panel"
                 class="pt-alebad-story-panel"
                 data-pt-credits-panel>
                <div class="pt-alebad-story-panel-inner">
                    <div class="pt-alebad-story-panel-body">

                        {{-- إنتاج --}}
                        <section class="pt-alebad-story-group">
                            <h3 class="pt-alebad-story-group-title">إنتاج</h3>
                            <p class="pt-alebad-story-group-prod">
                                دير القديس العظيم الأنبا شنوده رئيس المتوحدين
                                <span class="pt-alebad-story-group-prod-sub">— الدير الأبيض، سوهاج</span>
                            </p>
                        </section>

                        {{-- بطولة الفنان --}}
                        <section class="pt-alebad-story-group">
                            <h3 class="pt-alebad-story-group-title">بطولة الفنان</h3>
                            <p class="pt-alebad-story-group-lead">فريد النقراشي</p>
                        </section>

                        {{-- بالاشتراك مع النجوم — 47 supporting cast --}}
                        <section class="pt-alebad-story-group">
                            <h3 class="pt-alebad-story-group-title">بالاشتراك مع النجوم</h3>
                            <ul class="pt-alebad-story-group-cast">
                                <li>الفنان القدير / أحمد حلاوة</li>
                                <li>الفنان القدير / لطفي لبيب</li>
                                <li>الفنان القدير / محمد رضوان</li>
                                <li>الفنان القدير / فتوح أحمد</li>
                                <li>الفنان القدير / ناجي سعد</li>
                                <li>الفنانة / حنان سليمان</li>
                                <li>الفنان / أحمد الحلواني</li>
                                <li>الفنان / طاهر أبو حطب</li>
                                <li>الفنان / مجدي شكري</li>
                                <li>الفنان / عاصم سامي</li>
                                <li>الفنان / حسان العربي</li>
                                <li>الفنان / علي الطيب</li>
                                <li>الفنان / حسان ترك</li>
                                <li>الفنان / مجدي فوزي</li>
                                <li>الفنان / جرجس فوزي</li>
                                <li>الفنان / سمير حسني</li>
                                <li>الفنان / سامح فكري</li>
                                <li>الفنان / ضياء شفيق</li>
                                <li>الفنان / يوسف حافظ</li>
                                <li>الفنان / عادل سمير</li>
                                <li>الفنان / محمود الفرماوي</li>
                                <li>الفنان / أمير تادرس</li>
                                <li>الفنانة / نجوى شفيق</li>
                                <li>الفنانة / صفاء صفوت</li>
                                <li>الفنان / ملاك جلال</li>
                                <li>الفنان / بولا الفريد</li>
                                <li>الفنان / رأفت فوزي</li>
                                <li>الفنان / جميل فتحي</li>
                                <li>الفنانة / أوديت صفوت</li>
                                <li>الفنان / باسم رؤوف</li>
                                <li>الفنان / دافيد سمير</li>
                                <li>الفنان / جوزيف نبيل</li>
                                <li>الفنان / أمير فهمي</li>
                                <li>الفنان / مينا جمال</li>
                                <li>الفنانة / ماري سميح</li>
                                <li>الفنان / أبنوب أليشع</li>
                                <li>الفنان / أرساني</li>
                                <li>الفنانة / ريتا الياس</li>
                                <li>الفنانة / ماري جرجس</li>
                                <li>الفنان / توني فريد النقراشي</li>
                                <li>الفنان / يوسف فريد النقراشي</li>
                                <li>الفنان / مينا هنري</li>
                                <li>الفنان / مصطفى حسن</li>
                                <li>الفنان / عزيز لويس</li>
                                <li>الفنانة / إيمان سالم</li>
                                <li>الفنانة / إسراء عصام</li>
                                <li>الفنان / عادل صدقي</li>
                                <li>الفنانة / تهاني كمال</li>
                                <li>الفنان / أسامة شاكر</li>
                            </ul>
                        </section>

                        {{-- صنّاع العمل — crew --}}
                        <section class="pt-alebad-story-group">
                            <h3 class="pt-alebad-story-group-title">صنّاع العمل</h3>
                            <dl class="pt-alebad-story-group-crew">
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>تصوير · D.O.P</dt>
                                    <dd>چوزيف لويس</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>مونتاج</dt>
                                    <dd>سامر ماضي</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>موسيقى تصويرية وألحان</dt>
                                    <dd>عمانوئيل سعد</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>إخراج فني · Art Director</dt>
                                    <dd>كمال مجدي</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>ألوان · Colorist</dt>
                                    <dd>متى رشدي</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>منتج فني</dt>
                                    <dd>رامي إبراهيم</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>أشعار</dt>
                                    <dd>رمزي بشارة</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>مكساج شريط الصوت</dt>
                                    <dd>جرجس صبحي</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>تصميم الأزياء</dt>
                                    <dd>مريام عدلي</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>مكياج</dt>
                                    <dd>عزيز صليب · چينو</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>مهندس صوت</dt>
                                    <dd>أحمد أبو ليلة</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>مخرج منفذ</dt>
                                    <dd>مورين مجدي · فليمون نبيل</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>مؤثرات بصرية · VFX</dt>
                                    <dd>أنطون ناجح</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>دعاية · Advertising</dt>
                                    <dd>ميللر عزت</dd>
                                </div>
                                <div class="pt-alebad-story-group-crew-row">
                                    <dt>إعلان البرومو · Trailer</dt>
                                    <dd>مينا سمير</dd>
                                </div>
                            </dl>
                        </section>

                        {{-- سيناريو · إخراج — final stamp --}}
                        <section class="pt-alebad-story-group pt-alebad-story-group--final">
                            <h3 class="pt-alebad-story-group-title">سيناريو وحوار · إخراج</h3>
                            <p class="pt-alebad-story-group-stamp">
                                <span>فريد النقراشي</span>
                                <span aria-hidden="true">·</span>
                                <span>چوزيف نبيل</span>
                            </p>
                        </section>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =====================================================================
     Scene 5 — Hand-off into the available shows / booking surface
     Same data plumbing as before (featured + grid), only the eyebrow
     copy is re-keyed for the new flow so the section reads as "pick
     your showtime" instead of generic "browse shows".
===================================================================== --}}
<section id="shows-grid"
         class="pt-cine-scene is-scene-shows pt-alebad-shows"
         data-cine-scene="shows"
         aria-labelledby="pt-cine-shows-title">
    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-shows-a"></span>
        <span class="pt-cine-orb pt-cine-orb-shows-b"></span>
    </div>

    <div class="pt-cine-shows-head pt-cine-stagger">
        <span class="pt-cine-eyebrow">
            <span class="pt-live-dot pt-live-dot-emerald"></span>
            <span data-i18n="cine_shows_eyebrow">المواعيد المتاحة الآن</span>
        </span>
        <h2 id="pt-cine-shows-title" class="pt-cine-shows-title pt-alebad-section-title">
            <span class="pt-alebad-section-title-grad" data-i18n="shows_title">احجز مقعدك</span>
        </h2>
        <p class="pt-cine-shows-sub" data-i18n="shows_sub">اختر العرض والموعد المناسب لك.</p>

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

{{-- =====================================================================
     Scene 6 — How it works (condensed)
     The old onboarding (4 full-screen step scenes) collapsed into one
     compact strip. Same emotional beats — choose / pick / pay / QR —
     but emotionally subordinate to the show, where it belongs.
===================================================================== --}}
<section class="pt-cine-scene pt-alebad-howto"
         id="pt-alebad-howto"
         data-cine-scene="howto"
         aria-labelledby="pt-alebad-howto-title">

    <div class="pt-cine-bg" aria-hidden="true">
        <span class="pt-cine-orb pt-cine-orb-step-a"></span>
    </div>

    <div class="pt-alebad-howto-head pt-cine-stagger">
        <span class="pt-cine-eyebrow">
            <span class="pt-live-dot"></span>
            <span>طريقة الحجز</span>
        </span>
        <h2 id="pt-alebad-howto-title" class="pt-alebad-section-title">
            <span class="pt-alebad-section-title-grad">أربع خطوات سريعة</span>
        </h2>
    </div>

    <ol class="pt-alebad-howto-grid pt-cine-stagger" role="list">
        <li class="pt-alebad-howto-step">
            <span class="pt-alebad-howto-num">01</span>
            <span class="pt-alebad-howto-emoji" aria-hidden="true">🎭</span>
            <span class="pt-alebad-howto-title-lbl">اختر العرض</span>
            <span class="pt-alebad-howto-desc">اختر الموعد الذي يناسبك من قائمة المواعيد المتاحة.</span>
        </li>
        <li class="pt-alebad-howto-step">
            <span class="pt-alebad-howto-num">02</span>
            <span class="pt-alebad-howto-emoji" aria-hidden="true">🪑</span>
            <span class="pt-alebad-howto-title-lbl">احجز مقعدك</span>
            <span class="pt-alebad-howto-desc">اختر مقعدك مباشرة من خريطة المسرح التفاعلية.</span>
        </li>
        <li class="pt-alebad-howto-step">
            <span class="pt-alebad-howto-num">03</span>
            <span class="pt-alebad-howto-emoji" aria-hidden="true">💳</span>
            <span class="pt-alebad-howto-title-lbl">أكّد الحجز</span>
            <span class="pt-alebad-howto-desc">ادفع بأمان وارفع إثبات التحويل في خطوة واحدة.</span>
        </li>
        <li class="pt-alebad-howto-step">
            <span class="pt-alebad-howto-num">04</span>
            <span class="pt-alebad-howto-emoji" aria-hidden="true">🎟️</span>
            <span class="pt-alebad-howto-title-lbl">تذكرة QR</span>
            <span class="pt-alebad-howto-desc">تستلم تذكرتك بكود QR على واتساب فوراً بعد الاعتماد.</span>
        </li>
    </ol>
</section>

</div>{{-- /.pt-cine.pt-alebad --}}

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

    // Smooth scroll for any in-page anchor on this page (skip pill, hero
    // "watch trailer" button, etc.). Falls back to default anchor jump if
    // reduced motion is requested.
    document.addEventListener('click', function (e) {
        var anchor = e.target.closest('[data-pt-smooth-anchor], [data-pt-skip-shows]');
        if (!anchor) return;
        var href = anchor.getAttribute('href') || '';
        if (!href.startsWith('#')) return;
        var target = document.querySelector(href);
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
