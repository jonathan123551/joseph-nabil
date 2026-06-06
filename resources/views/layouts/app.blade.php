<!DOCTYPE html>
<html lang="ar" dir="rtl" data-pt-lang="ar" data-pt-theme="dark">
<head>
    @include('partials._app_head')
</head>
<body class="prism-stage min-h-screen @yield('body_class')">

    {{-- ============== Floating Cinematic Header ============== --}}
    <div class="pt-topbar-wrap" id="pt-topbar-wrap">
        {{-- Subtle aurora glow line under the bar when scrolled --}}
        <div class="pt-topbar-aurora" aria-hidden="true"></div>

        <header class="pt-topbar" id="pt-topbar" role="banner">
            {{-- Brand block --}}
            <a href="{{ route('shows.index') }}" class="pt-brand group" aria-label="العابد">
                <img src="{{ asset('images/brand/el3abed-logo.png') }}"
                     alt="العابد"
                     class="pt-brand-mark-img"
                     loading="eager"
                     decoding="async"
                     fetchpriority="high">
            </a>

            {{-- Center nav (desktop) --}}
            <nav class="pt-nav" data-i18n-attr="aria-label:primary_nav" aria-label="Primary">
                <a href="{{ route('shows.index') }}"
                   class="pt-nav-link {{ (request()->routeIs('shows.index') || request()->routeIs('home')) ? 'is-active' : '' }}">
                    <span data-i18n="nav_home">الرئيسية</span>
                </a>
                <a href="{{ route('shows.index') }}#shows-grid"
                   class="pt-nav-link">
                    <span data-i18n="nav_shows">العروض</span>
                </a>
                @auth
                    @if(auth()->user()->is_admin ?? false)
                        <a href="{{ route('admin.dashboard') }}"
                           class="pt-nav-link pt-nav-link-admin {{ request()->routeIs('admin.*') ? 'is-active' : '' }}">
                            <span data-i18n="nav_admin">لوحة التحكم</span>
                        </a>
                    @endif
                @endauth
            </nav>

            {{-- Right cluster --}}
            <div class="pt-actions">
                {{-- Theme toggle --}}
                <button type="button"
                        class="pt-theme-toggle"
                        id="pt-theme-toggle"
                        data-i18n-attr="aria-label:theme_toggle_aria,title:theme_toggle_aria"
                        aria-label="Toggle theme"
                        title="Toggle theme">
                    <span class="pt-theme-icon pt-theme-icon-sun" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4"/>
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                        </svg>
                    </span>
                    <span class="pt-theme-icon pt-theme-icon-moon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                    </span>
                </button>

                {{-- Lang toggle (desktop) --}}
                <div class="pt-lang-toggle pt-lang-toggle-desktop" id="pt-lang-toggle" role="group" data-i18n-attr="aria-label:lang_label" aria-label="Language">
                    <span class="pt-lang-thumb" id="pt-lang-thumb"></span>
                    <button type="button" data-pt-lang="ar" aria-pressed="true">AR</button>
                    <button type="button" data-pt-lang="en" aria-pressed="false">EN</button>
                </div>

                {{-- Mobile menu button --}}
                <button type="button"
                        class="pt-burger"
                        id="pt-burger"
                        data-i18n-attr="aria-label:menu_open"
                        aria-label="Open menu"
                        aria-controls="pt-drawer"
                        aria-expanded="false">
                    <span class="pt-burger-bars" aria-hidden="true"><i></i><i></i><i></i></span>
                </button>
            </div>
        </header>
    </div>

    {{-- ============== Mobile Drawer ============== --}}
    <div class="pt-drawer-backdrop" id="pt-drawer-backdrop" aria-hidden="true"></div>
    <aside class="pt-drawer" id="pt-drawer" role="dialog" aria-modal="true" aria-label="Menu" aria-hidden="true">
        <div class="pt-drawer-head">
            <div class="pt-drawer-brand">
                <img src="{{ asset('images/brand/el3abed-logo.png') }}"
                     alt="العابد"
                     loading="lazy"
                     decoding="async">
            </div>
            <button type="button" class="pt-drawer-close" id="pt-drawer-close" aria-label="Close menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6 L18 18 M18 6 L6 18"/></svg>
            </button>
        </div>

        <nav class="pt-drawer-nav" aria-label="Mobile">
            <a href="{{ route('shows.index') }}"
               class="pt-drawer-link {{ (request()->routeIs('shows.index') || request()->routeIs('home')) ? 'is-active' : '' }}"
               data-i18n="nav_home">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12 L12 3 L21 12"/><path d="M5 10v10h14V10"/></svg>
                <span>الرئيسية</span>
            </a>
            <a href="{{ route('shows.index') }}#shows-grid"
               class="pt-drawer-link"
               data-i18n="nav_shows">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 12h18"/></svg>
                <span>العروض</span>
            </a>
            @auth
                @if(auth()->user()->is_admin ?? false)
                    <a href="{{ route('admin.dashboard') }}"
                       class="pt-drawer-link {{ request()->routeIs('admin.*') ? 'is-active' : '' }}"
                       data-i18n="nav_admin">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
                        <span>لوحة التحكم</span>
                    </a>
                @endif
            @endauth
        </nav>

        <div class="pt-drawer-foot">
            <div class="pt-drawer-row">
                <span class="pt-drawer-row-label" data-i18n="theme_label">الوضع</span>
                <div class="pt-segment pt-theme-segment" id="pt-theme-segment" role="group" aria-label="Theme">
                    <button type="button" data-pt-theme-set="light">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                        <span data-i18n="theme_light">فاتح</span>
                    </button>
                    <button type="button" data-pt-theme-set="dark">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        <span data-i18n="theme_dark">داكن</span>
                    </button>
                </div>
            </div>
            <div class="pt-drawer-row">
                <span class="pt-drawer-row-label" data-i18n="lang_label">اللغة</span>
                <div class="pt-lang-toggle pt-lang-toggle-mobile" role="group" aria-label="Language">
                    <span class="pt-lang-thumb pt-lang-thumb-m"></span>
                    <button type="button" data-pt-lang="ar" aria-pressed="true">AR</button>
                    <button type="button" data-pt-lang="en" aria-pressed="false">EN</button>
                </div>
            </div>
            <div class="pt-drawer-cta">
                <a href="{{ route('shows.index') }}#shows-grid" class="prism-btn prism-ripple w-full justify-center" data-i18n="cta_browse">
                    تصفح العروض
                </a>
            </div>
        </div>
    </aside>

    {{-- spacer keeps content below the floating bar --}}
    <div class="pt-topbar-spacer" aria-hidden="true"></div>

    {{-- ============== Main ============== --}}
    <main class="pt-page max-w-5xl mx-auto px-4 py-6 md:py-10 prism-fade-in" id="pt-main">
        @yield('content')
    </main>

    {{-- ============== Footer (v2) ============== --}}
    <footer class="prism-footer" style="padding-bottom: calc(24px + var(--pt-safe-bottom));">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
            <div class="pt-footer-grid">
                {{-- Brand block --}}
                <div class="pt-footer-brand-col">
                    <a href="{{ route('shows.index') }}" class="pt-footer-brand-logo-link" aria-label="العابد">
                        <img src="{{ asset('images/brand/el3abed-logo.png') }}"
                             alt="العابد"
                             class="pt-footer-brand-logo-img"
                             loading="lazy"
                             decoding="async">
                    </a>
                </div>

                {{-- Quick links --}}
                <div>
                    <div class="pt-footer-col-title" data-i18n="foot_quick">روابط سريعة</div>
                    <div class="flex flex-col">
                        <a class="pt-footer-link" href="{{ route('shows.index') }}" data-i18n="nav_home">الرئيسية</a>
                        <a class="pt-footer-link" href="{{ route('shows.index') }}#shows-grid" data-i18n="nav_shows">العروض</a>
                    </div>
                </div>

                {{-- Trust signals --}}
                <div>
                    
                    <div class="flex flex-wrap gap-2">
                        <span class="prism-pill" data-i18n="foot_fast">حجز فوري</span>
                        <span class="prism-pill" data-i18n="foot_secure">دفع آمن</span>
                        <span class="prism-pill" data-i18n="foot_qr">QR على واتساب</span>
                    </div>
                </div>
            </div>

            {{-- ============== Developer signature (Apple/A24 stamp) =====
                 Final centered "studio stamp" at the bottom of every page.
                 Replaces the loud gold-gradient + glassy pill version with
                 a calm minimal mark — same visual vocabulary as the ✦
                 ornaments elsewhere on the homepage.

                 Layout (all centered, locked to LTR for the wordmark so
                 the Latin reads in correct order inside the RTL doc):

                   ── · ──                                 ← thin hairline ornament
                   Jonathan Maged · © 2026                 ← cream wordmark (LTR)
                   ENGINEERED FOR CINEMATIC EXPERIENCES    ← craft signature (no name)
                   ⚆ Contact                               ← icon + word only

                 The phone number stays in the href + aria-label so the
                 action is still tappable + screen-reader accessible,
                 but it doesn't clutter the visual signature.
            ============================================================ --}}
            <div class="pt-foot-sig"
                 style="border-top: 1px solid var(--prism-border);">
                {{-- Thin hairline + dot ornament above the wordmark —
                     same visual vocabulary as the ✦ ornament on the
                     Story scene. --}}
                <span class="pt-foot-sig-ornament" aria-hidden="true">
                    <span class="pt-foot-sig-ornament-line"></span>
                    <span class="pt-foot-sig-ornament-dot">·</span>
                    <span class="pt-foot-sig-ornament-line"></span>
                </span>

                {{-- Eyebrow — quiet "Developed by" above the wordmark,
                     same Apple "Designed by Apple in California" energy.
                     Sentence-case (not tracked caps) so it pairs with
                     the title-case wordmark beneath and contrasts the
                     ALL-CAPS subtitle further down. --}}
                <p class="pt-foot-sig-eyebrow" dir="ltr">Developed by</p>

                {{-- Wordmark row — forced LTR so the Latin name + © +
                     year always read in correct order inside the RTL
                     document (without this the row reverses to
                     "2026 © Jonathan Maged"). --}}
                <div class="pt-foot-sig-name-row" dir="ltr">
                    <span class="pt-foot-sig-name">Jonathan Maged</span>
                    <span class="pt-foot-sig-meta">
                        <span class="pt-foot-sig-sep" aria-hidden="true">©</span>
                        <span class="pt-foot-sig-year">{{ now()->year }}</span>
                    </span>
                </div>

                {{-- Calm Latin caption — uppercase small-caps with generous
                     tracking. Implies the developer role without repeating
                     the name (which already sits above as the wordmark). --}}
                <p class="pt-foot-sig-sub" dir="ltr">Engineered for cinematic experiences</p>

                {{-- Contact action — quiet inline text-link, NOT a pill.
                     Tiny outlined WhatsApp glyph + single 'Contact' label.
                     No arrow, no Arabic label, no visible phone number
                     — the phone number lives in the href + aria-label so
                     it's still tappable + accessible. --}}
                <a class="pt-foot-sig-link"
                   href="https://wa.me/201222356357"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Contact the developer via WhatsApp at 01222356357">
                    <span class="pt-foot-sig-link-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="13" height="13"
                             fill="none" stroke="currentColor" stroke-width="1.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.52 3.48A11.93 11.93 0 0 0 12 0C5.37 0 0 5.37 0 12c0 2.11.55 4.17 1.6 5.98L0 24l6.18-1.62A11.93 11.93 0 0 0 12 24c6.63 0 12-5.37 12-12 0-3.2-1.25-6.21-3.48-8.52z"/>
                            <path d="M17.43 14.45c-.28-.14-1.66-.82-1.92-.91-.26-.1-.45-.14-.64.14-.19.28-.74.91-.9 1.1-.17.19-.33.21-.61.07-.28-.14-1.19-.44-2.27-1.4-.84-.75-1.4-1.67-1.57-1.95-.16-.28-.02-.43.12-.57.13-.13.28-.33.42-.49.14-.16.19-.28.28-.47.09-.19.05-.35-.02-.49-.07-.14-.64-1.55-.88-2.12-.23-.55-.46-.48-.64-.49h-.55c-.19 0-.49.07-.75.35-.26.28-.99.97-.99 2.36 0 1.39 1.01 2.74 1.15 2.93.14.19 1.99 3.04 4.83 4.27.67.29 1.2.46 1.61.59.68.22 1.29.19 1.78.12.54-.08 1.66-.68 1.89-1.34.23-.66.23-1.22.16-1.34-.07-.12-.26-.19-.54-.33z"/>
                        </svg>
                    </span>
                    <span class="pt-foot-sig-link-label" dir="ltr">Contact</span>
                </a>
            </div>
        </div>
    </footer>

    @include('partials._app_foot')
</body>
</html>
