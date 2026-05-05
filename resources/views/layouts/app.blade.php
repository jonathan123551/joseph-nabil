<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'PRISM · Premium Booking')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060d">

    {{-- Inline SVG favicon — neutral premium identity --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%2322d3ee'/><stop offset='0.5' stop-color='%23818cf8'/><stop offset='1' stop-color='%23c084fc'/></linearGradient></defs><path d='M32 6 L56 20 L46 56 L18 56 L8 20 Z' fill='none' stroke='url(%23g)' stroke-width='3' stroke-linejoin='round'/><path d='M32 6 L32 56 M8 20 L56 20 M18 56 L46 56' stroke='url(%23g)' stroke-width='1.5' opacity='0.6'/></svg>">

    {{-- Tailwind CSS (CDN, like before) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Premium font stack --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =====================================================================
           PRISM · Premium Design System
           - Pure frontend redesign. No backend / routing / data changes.
           - Glassmorphism, neon accents, smooth animations, mobile-first.
           ===================================================================== */

        :root {
            --prism-bg-0: #05060d;
            --prism-bg-1: #090b16;
            --prism-bg-2: #0d1020;

            --prism-surface: rgba(20, 24, 38, 0.55);
            --prism-surface-strong: rgba(13, 16, 28, 0.78);
            --prism-surface-soft: rgba(255, 255, 255, 0.04);

            --prism-border: rgba(255, 255, 255, 0.08);
            --prism-border-strong: rgba(255, 255, 255, 0.14);
            --prism-border-neon: rgba(129, 140, 248, 0.32);

            --prism-text: #f1f5fb;
            --prism-text-2: #c2cad8;
            --prism-text-3: #8590a6;
            --prism-text-4: #6b7385;

            --prism-cyan: #22d3ee;
            --prism-indigo: #818cf8;
            --prism-violet: #c084fc;
            --prism-gold: #fbbf24;
            --prism-emerald: #34d399;
            --prism-rose: #fb7185;

            --prism-neon: linear-gradient(135deg, #22d3ee 0%, #818cf8 50%, #c084fc 100%);
            --prism-neon-soft: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(129,140,248,0.18) 50%, rgba(192,132,252,0.18));

            --prism-glow-sm: 0 0 14px rgba(129,140,248,0.35);
            --prism-glow-md: 0 0 24px rgba(129,140,248,0.45), 0 0 48px rgba(34,211,238,0.18);
            --prism-glow-gold: 0 0 18px rgba(251,191,36,0.45);

            --prism-radius: 22px;
            --prism-radius-sm: 14px;

            --prism-ease: cubic-bezier(.2,.7,.2,1);
        }

        * { -webkit-tap-highlight-color: transparent; }

        html, body {
            background: var(--prism-bg-0);
            color: var(--prism-text);
            font-family: "IBM Plex Sans Arabic", "Space Grotesk", system-ui, -apple-system, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* ------------- Ambient background ------------- */
        .prism-stage {
            position: relative;
            min-height: 100vh;
            background:
                radial-gradient(ellipse 80% 50% at 50% -10%, rgba(129,140,248,0.18), transparent 60%),
                radial-gradient(ellipse 60% 40% at 0% 10%, rgba(34,211,238,0.10), transparent 60%),
                radial-gradient(ellipse 60% 40% at 100% 5%, rgba(192,132,252,0.10), transparent 60%),
                radial-gradient(ellipse 70% 50% at 50% 110%, rgba(251,191,36,0.05), transparent 60%),
                linear-gradient(180deg, #05060d 0%, #07091a 60%, #05060d 100%);
            overflow-x: hidden;
        }
        .prism-stage::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                radial-gradient(circle at 1px 1px, rgba(255,255,255,0.06) 1px, transparent 0);
            background-size: 36px 36px;
            mask-image: radial-gradient(ellipse 60% 50% at 50% 0%, #000 0%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse 60% 50% at 50% 0%, #000 0%, transparent 70%);
            opacity: 0.5;
            z-index: 0;
        }
        .prism-stage > * { position: relative; z-index: 1; }

        /* ------------- Glass surfaces ------------- */
        .prism-glass {
            background:
                linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.72));
            border: 1px solid var(--prism-border);
            border-radius: var(--prism-radius);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.05),
                0 24px 48px -20px rgba(0,0,0,0.75);
        }
        .prism-glass-strong {
            background:
                linear-gradient(180deg, rgba(28,32,52,0.78), rgba(13,16,28,0.85));
            border: 1px solid var(--prism-border-strong);
            border-radius: var(--prism-radius);
            backdrop-filter: blur(22px) saturate(160%);
            -webkit-backdrop-filter: blur(22px) saturate(160%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 30px 60px -22px rgba(0,0,0,0.8);
        }
        .prism-glow-border {
            position: relative;
        }
        .prism-glow-border::before {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            padding: 1px;
            background: var(--prism-neon);
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
                    mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            opacity: 0.5;
            pointer-events: none;
        }

        /* ------------- Buttons ------------- */
        .prism-btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 13px;
            color: #0b0e1c;
            background: linear-gradient(135deg, #cffafe 0%, #c7d2fe 50%, #e9d5ff 100%);
            border: 1px solid rgba(255,255,255,0.4);
            box-shadow:
                0 8px 24px -8px rgba(129,140,248,0.6),
                inset 0 1px 0 rgba(255,255,255,0.6);
            transition: transform .2s var(--prism-ease), box-shadow .2s var(--prism-ease), filter .2s var(--prism-ease);
            position: relative;
            overflow: hidden;
            min-height: 44px;
        }
        .prism-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow:
                0 14px 32px -8px rgba(129,140,248,0.85),
                0 0 22px rgba(34,211,238,0.35),
                inset 0 1px 0 rgba(255,255,255,0.6);
            filter: brightness(1.05);
        }
        .prism-btn:active:not(:disabled) { transform: translateY(0); }
        .prism-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            box-shadow: none;
            filter: grayscale(0.3);
        }

        .prism-btn-ghost {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 500;
            font-size: 13px;
            color: var(--prism-text-2);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            transition: all .2s var(--prism-ease);
            min-height: 44px;
        }
        .prism-btn-ghost:hover {
            background: rgba(255,255,255,0.07);
            border-color: var(--prism-border-strong);
            color: var(--prism-text);
        }

        .prism-btn-gold {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            color: #1b1208;
            background: linear-gradient(180deg, #fde68a, #f59e0b);
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 8px 22px -6px rgba(245,158,11,0.55), inset 0 1px 0 rgba(255,255,255,0.55);
            transition: all .2s var(--prism-ease);
            min-height: 44px;
        }
        .prism-btn-gold:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -6px rgba(245,158,11,0.7), 0 0 22px rgba(251,191,36,0.4), inset 0 1px 0 rgba(255,255,255,0.55);
        }

        .prism-btn-emerald {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            color: #022c22;
            background: linear-gradient(180deg, #6ee7b7, #059669);
            border: 1px solid rgba(255,255,255,0.45);
            box-shadow: 0 8px 22px -6px rgba(16,185,129,0.55), inset 0 1px 0 rgba(255,255,255,0.55);
            transition: all .2s var(--prism-ease);
            min-height: 40px;
        }
        .prism-btn-emerald:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -6px rgba(16,185,129,0.7), 0 0 22px rgba(52,211,153,0.4), inset 0 1px 0 rgba(255,255,255,0.55);
        }

        .prism-btn-rose {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            color: #4c0519;
            background: linear-gradient(180deg, #fda4af, #be123c);
            border: 1px solid rgba(255,255,255,0.45);
            box-shadow: 0 8px 22px -6px rgba(244,63,94,0.55), inset 0 1px 0 rgba(255,255,255,0.55);
            transition: all .2s var(--prism-ease);
            min-height: 40px;
        }
        .prism-btn-rose:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -6px rgba(244,63,94,0.7), 0 0 22px rgba(251,113,133,0.4), inset 0 1px 0 rgba(255,255,255,0.55);
        }

        .prism-btn-cyan {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            color: #042f4a;
            background: linear-gradient(180deg, #67e8f9, #0891b2);
            border: 1px solid rgba(255,255,255,0.45);
            box-shadow: 0 8px 22px -6px rgba(34,211,238,0.55), inset 0 1px 0 rgba(255,255,255,0.55);
            transition: all .2s var(--prism-ease);
            min-height: 40px;
        }
        .prism-btn-cyan:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -6px rgba(34,211,238,0.7), 0 0 22px rgba(56,189,248,0.4), inset 0 1px 0 rgba(255,255,255,0.55);
        }

        .prism-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            font-size: 11px;
            color: var(--prism-text-2);
        }
        .prism-pill-neon {
            background: linear-gradient(135deg, rgba(34,211,238,0.12), rgba(192,132,252,0.12));
            border-color: rgba(129,140,248,0.45);
            color: #e0e7ff;
            box-shadow: 0 0 14px rgba(129,140,248,0.18);
        }
        .prism-pill-emerald {
            background: rgba(16,185,129,0.10);
            border-color: rgba(52,211,153,0.45);
            color: #6ee7b7;
        }
        .prism-pill-amber {
            background: rgba(251,191,36,0.10);
            border-color: rgba(251,191,36,0.40);
            color: #fcd34d;
        }
        .prism-pill-rose {
            background: rgba(244,63,94,0.10);
            border-color: rgba(251,113,133,0.40);
            color: #fda4af;
        }
        .prism-pill-sky {
            background: rgba(56,189,248,0.10);
            border-color: rgba(56,189,248,0.40);
            color: #7dd3fc;
        }

        /* ------------- Status dots / badges ------------- */
        .prism-dot { width: 8px; height: 8px; border-radius: 999px; display: inline-block; }
        .prism-dot-emerald { background: #34d399; box-shadow: 0 0 10px rgba(52,211,153,0.7); }
        .prism-dot-rose    { background: #fb7185; box-shadow: 0 0 10px rgba(251,113,133,0.7); }
        .prism-dot-amber   { background: #fbbf24; box-shadow: 0 0 10px rgba(251,191,36,0.7); }
        .prism-dot-sky     { background: #38bdf8; box-shadow: 0 0 10px rgba(56,189,248,0.7); }

        .prism-badge-emerald { background: rgba(16,185,129,0.10); color: #6ee7b7; border-color: rgba(52,211,153,0.45); }
        .prism-badge-rose    { background: rgba(244,63,94,0.10);  color: #fda4af; border-color: rgba(251,113,133,0.45); }
        .prism-badge-amber   { background: rgba(245,158,11,0.10); color: #fcd34d; border-color: rgba(251,191,36,0.45); }
        .prism-badge-sky     { background: rgba(56,189,248,0.10); color: #7dd3fc; border-color: rgba(56,189,248,0.45); }

        /* ------------- Inputs ------------- */
        .prism-input {
            width: 100%;
            background: rgba(8, 10, 20, 0.7);
            border: 1px solid var(--prism-border);
            color: var(--prism-text);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease), box-shadow .2s var(--prism-ease);
            min-height: 44px;
        }
        .prism-input:focus {
            border-color: rgba(129,140,248,0.6);
            outline: none;
            background: rgba(8, 10, 20, 0.9);
            box-shadow: 0 0 0 3px rgba(129,140,248,0.12), 0 0 18px rgba(129,140,248,0.18);
        }
        .prism-input::placeholder { color: var(--prism-text-4); }

        /* ------------- Brand / logo ------------- */
        .prism-logo {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 0 24px rgba(129,140,248,0.18);
            position: relative;
            transition: transform .3s var(--prism-ease);
        }
        .prism-logo:hover { transform: rotate(-6deg) scale(1.05); }
        .prism-wordmark {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.18em;
            background: var(--prism-neon);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
        }
        .prism-tagline {
            font-size: 9px;
            letter-spacing: 0.4em;
            color: var(--prism-text-4);
            text-transform: uppercase;
        }

        /* ------------- Section / page entrance ------------- */
        @keyframes prismFadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes prismFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes prismGlowPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(129,140,248,0.0); }
            50%      { box-shadow: 0 0 36px 0 rgba(129,140,248,0.35); }
        }
        @keyframes prismShimmer {
            0%   { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .prism-fade-in   { animation: prismFadeIn .5s var(--prism-ease) both; }
        .prism-fade-up   { animation: prismFadeUp .55s var(--prism-ease) both; }
        .prism-stagger > *      { animation: prismFadeUp .55s var(--prism-ease) both; }
        .prism-stagger > *:nth-child(1) { animation-delay: .04s; }
        .prism-stagger > *:nth-child(2) { animation-delay: .10s; }
        .prism-stagger > *:nth-child(3) { animation-delay: .16s; }
        .prism-stagger > *:nth-child(4) { animation-delay: .22s; }
        .prism-stagger > *:nth-child(5) { animation-delay: .28s; }
        .prism-stagger > *:nth-child(6) { animation-delay: .34s; }
        .prism-stagger > *:nth-child(7) { animation-delay: .40s; }
        .prism-stagger > *:nth-child(8) { animation-delay: .46s; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
            }
        }

        /* ------------- Card hover lift ------------- */
        .prism-card-hover {
            transition: transform .25s var(--prism-ease), border-color .25s var(--prism-ease), box-shadow .25s var(--prism-ease);
        }
        .prism-card-hover:hover {
            transform: translateY(-3px);
            border-color: rgba(129,140,248,0.45);
            box-shadow:
                0 24px 48px -22px rgba(129,140,248,0.45),
                0 0 36px rgba(34,211,238,0.10),
                inset 0 1px 0 rgba(255,255,255,0.06);
        }

        /* ------------- Navbar ------------- */
        .prism-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            background: linear-gradient(180deg, rgba(5,6,13,0.85), rgba(5,6,13,0.55));
            border-bottom: 1px solid var(--prism-border);
        }
        .prism-nav-link {
            display: inline-flex; align-items: center;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
            color: var(--prism-text-2);
            transition: all .18s var(--prism-ease);
            min-height: 36px;
        }
        .prism-nav-link:hover {
            color: var(--prism-text);
            background: rgba(255,255,255,0.05);
        }
        .prism-nav-link.active {
            color: var(--prism-text);
            background: linear-gradient(135deg, rgba(34,211,238,0.12), rgba(192,132,252,0.12));
            border: 1px solid rgba(129,140,248,0.45);
            box-shadow: 0 0 14px rgba(129,140,248,0.18);
        }

        /* ------------- Footer ------------- */
        .prism-footer {
            border-top: 1px solid var(--prism-border);
            background: linear-gradient(180deg, rgba(5,6,13,0.4), rgba(5,6,13,0.85));
            margin-top: 64px;
        }

        /* ------------- Scrollbar polish ------------- */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(129,140,248,0.4), rgba(34,211,238,0.4));
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(129,140,248,0.6), rgba(34,211,238,0.6));
        }

        /* ------------- Section utility ------------- */
        .prism-headline {
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        /* ------------- Mobile-first tweaks ------------- */
        @media (max-width: 640px) {
            .prism-glass, .prism-glass-strong {
                border-radius: 18px;
            }
        }

        /* ------------- Selection accent ------------- */
        ::selection { background: rgba(129,140,248,0.45); color: #fff; }

        /* ------------- Click ripple (subtle) ------------- */
        .prism-ripple { position: relative; overflow: hidden; }
        .prism-ripple::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at var(--rx,50%) var(--ry,50%), rgba(255,255,255,0.35), transparent 60%);
            opacity: 0;
            transition: opacity .4s var(--prism-ease);
            pointer-events: none;
        }
        .prism-ripple:active::after { opacity: 1; transition-duration: .05s; }
    </style>
</head>
<body class="prism-stage min-h-screen">

    {{-- ============== Navbar ============== --}}
    <header class="prism-nav">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-3">

            {{-- Brand --}}
            <a href="{{ route('shows.index') }}" class="flex items-center gap-3 group">
                <span class="prism-logo">
                    <svg width="22" height="22" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="prism-grad-nav" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#22d3ee"/>
                                <stop offset="0.5" stop-color="#818cf8"/>
                                <stop offset="1" stop-color="#c084fc"/>
                            </linearGradient>
                        </defs>
                        <path d="M32 6 L56 20 L46 56 L18 56 L8 20 Z"
                              fill="none" stroke="url(#prism-grad-nav)" stroke-width="3" stroke-linejoin="round"/>
                        <path d="M32 6 L32 56 M8 20 L56 20 M18 56 L46 56"
                              stroke="url(#prism-grad-nav)" stroke-width="1.5" opacity="0.55"/>
                    </svg>
                </span>
                <div class="leading-tight">
                    <div class="prism-wordmark">PRISM</div>
                    <div class="prism-tagline">Premium Tickets</div>
                </div>
            </a>

            {{-- Nav --}}
            <nav class="flex items-center gap-1">
                <a href="{{ route('shows.index') }}"
                   class="prism-nav-link {{ request()->routeIs('shows.*') || request()->routeIs('home') ? 'active' : '' }}">
                    الرئيسية
                </a>
            </nav>

        </div>
    </header>

    {{-- ============== Main ============== --}}
    <main class="max-w-5xl mx-auto px-4 py-6 md:py-10 prism-fade-in">
        @yield('content')
    </main>

    {{-- ============== Footer ============== --}}
    <footer class="prism-footer">
        <div class="max-w-5xl mx-auto px-4 py-6 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-[color:var(--prism-text-3)]">
            <div class="flex items-center gap-2">
                <span class="prism-wordmark text-[12px]">PRISM</span>
                <span>© {{ now()->year }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="prism-pill">حجز فوري</span>
                <span class="prism-pill">دفع آمن</span>
                <span class="prism-pill">QR على واتساب</span>
            </div>
        </div>
    </footer>

    {{-- Subtle ripple coordinates for any .prism-ripple elements --}}
    <script>
    (function () {
        document.addEventListener('pointerdown', function (e) {
            const t = e.target.closest('.prism-ripple');
            if (!t) return;
            const r = t.getBoundingClientRect();
            t.style.setProperty('--rx', ((e.clientX - r.left) / r.width * 100) + '%');
            t.style.setProperty('--ry', ((e.clientY - r.top)  / r.height * 100) + '%');
        }, { passive: true });
    })();
    </script>
</body>
</html>
