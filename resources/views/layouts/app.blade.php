<!DOCTYPE html>
<html lang="ar" dir="rtl" data-pt-lang="ar" data-pt-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Premium Tickets')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060d" id="pt-theme-color">

    {{-- Inline SVG favicon — neutral premium identity --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%2322d3ee'/><stop offset='0.5' stop-color='%23818cf8'/><stop offset='1' stop-color='%23c084fc'/></linearGradient></defs><path d='M32 6 L56 20 L46 56 L18 56 L8 20 Z' fill='none' stroke='url(%23g)' stroke-width='3' stroke-linejoin='round'/><path d='M32 6 L32 56 M8 20 L56 20 M18 56 L46 56' stroke='url(%23g)' stroke-width='1.5' opacity='0.6'/></svg>">

    {{-- Theme bootstrap (runs before paint to avoid FOUC) --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('pt-theme');
                var prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
                var theme = stored === 'light' || stored === 'dark'
                    ? stored
                    : (prefersLight ? 'light' : 'dark');
                document.documentElement.setAttribute('data-pt-theme', theme);
                var meta = document.getElementById('pt-theme-color') || document.querySelector('meta[name="theme-color"]');
                if (meta) meta.setAttribute('content', theme === 'light' ? '#f4f1ea' : '#05060d');
            } catch (e) { /* keep dark default */ }
        })();
    </script>

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

        /* ------------- Reveal on scroll ------------- */
        .pt-reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity .7s var(--prism-ease), transform .7s var(--prism-ease);
            will-change: opacity, transform;
        }
        .pt-reveal.is-in {
            opacity: 1;
            transform: translateY(0);
        }
        .pt-reveal.is-in.pt-reveal-stagger > *      { animation: prismFadeUp .65s var(--prism-ease) both; }
        .pt-reveal-stagger > *:nth-child(1) { animation-delay: .04s; }
        .pt-reveal-stagger > *:nth-child(2) { animation-delay: .10s; }
        .pt-reveal-stagger > *:nth-child(3) { animation-delay: .16s; }
        .pt-reveal-stagger > *:nth-child(4) { animation-delay: .22s; }
        .pt-reveal-stagger > *:nth-child(5) { animation-delay: .28s; }
        .pt-reveal-stagger > *:nth-child(6) { animation-delay: .34s; }
        .pt-reveal-stagger > *:nth-child(7) { animation-delay: .40s; }
        .pt-reveal-stagger > *:nth-child(8) { animation-delay: .46s; }

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

        /* ------------- Universal button press feedback -------------
           Every primary surface gets the same tactile micro-scale on
           tap that the floating action-bar buttons already use, plus a
           short transition so the press feels snappy. Hover transitions
           on each variant remain untouched. */
        .prism-btn:active,
        .prism-btn-emerald:active,
        .prism-btn-rose:active,
        .prism-btn-cyan:active,
        .prism-btn-gold:active,
        .prism-quick-action:active {
            transform: scale(0.97);
            transition-duration: 90ms;
        }

        /* ------------- Hover shimmer on premium CTAs -------------
           Single-pass diagonal shimmer that runs across .prism-btn-gold
           and .prism-btn-emerald on hover. Uses the existing prismShimmer
           keyframe + a ::after pseudo so we don't disturb existing
           background gradients on the buttons themselves. */
        .prism-btn-gold,
        .prism-btn-emerald {
            position: relative;
            overflow: hidden;
        }
        .prism-btn-gold::after,
        .prism-btn-emerald::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                100deg,
                transparent 30%,
                rgba(255,255,255,0.22) 50%,
                transparent 70%
            );
            background-size: 200% 100%;
            background-position: 200% 0;
            pointer-events: none;
            opacity: 0;
            border-radius: inherit;
        }
        .prism-btn-gold:hover::after,
        .prism-btn-emerald:hover::after {
            opacity: 1;
            animation: prismShimmer 1.4s ease-in-out 1;
        }

        /* ------------- Floating action-bar — gold chip breathing -------------
           Subtle 4.2 s opacity / shadow loop on the gold total chip so the
           floating action bar reads as 'alive' while the user is deciding.
           Only runs while the bar is on-screen (.is-on). */
        @keyframes prismChipBreath {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(251,191,36,0.0),
                            inset 0 0 0 0 rgba(251,191,36,0.0);
            }
            50% {
                box-shadow: 0 0 14px 0 rgba(251,191,36,0.28),
                            inset 0 0 8px 0 rgba(251,191,36,0.14);
            }
        }
        .pt-action-bar.is-on .pt-bar-chip-gold {
            animation: prismChipBreath 4.2s ease-in-out infinite;
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-action-bar.is-on .pt-bar-chip-gold,
            .prism-btn-gold:hover::after,
            .prism-btn-emerald:hover::after {
                animation: none;
            }
        }

        /* ------------- Floating top bar ------------- */
        .pt-topbar-wrap {
            position: fixed;
            top: 14px;
            left: 14px;
            right: 14px;
            z-index: 50;
            pointer-events: none;
            transition: top .35s var(--prism-ease);
        }
        .pt-topbar {
            pointer-events: auto;
            margin: 0 auto;
            max-width: 920px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 12px 8px 12px;
            border-radius: 999px;
            background:
                linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.72));
            border: 1px solid var(--prism-border);
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.05),
                0 18px 40px -22px rgba(0,0,0,0.75),
                0 0 22px rgba(129,140,248,0.10);
            transition: padding .25s var(--prism-ease), box-shadow .35s var(--prism-ease), border-color .35s var(--prism-ease);
        }
        .pt-topbar.is-scrolled {
            border-color: rgba(129,140,248,0.30);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 26px 60px -22px rgba(0,0,0,0.85),
                0 0 30px rgba(129,140,248,0.18);
        }
        .pt-topbar a, .pt-topbar button { -webkit-tap-highlight-color: transparent; }
        .pt-top-link {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--prism-text-2);
            transition: all .18s var(--prism-ease);
            min-height: 36px;
        }
        .pt-top-link:hover {
            color: var(--prism-text);
            background: rgba(255,255,255,0.06);
        }
        .pt-top-link.is-active {
            color: var(--prism-text);
            background: linear-gradient(135deg, rgba(34,211,238,0.14), rgba(192,132,252,0.14));
            border: 1px solid rgba(129,140,248,0.4);
            box-shadow: 0 0 14px rgba(129,140,248,0.22);
        }
        .pt-top-icon {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px;
            color: var(--prism-text-2);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            transition: all .18s var(--prism-ease);
        }
        .pt-top-icon:hover {
            color: var(--prism-text);
            background: rgba(255,255,255,0.08);
            border-color: var(--prism-border-strong);
            transform: translateY(-1px);
        }
        .pt-lang-toggle {
            display: inline-flex;
            align-items: center;
            background: rgba(8,10,20,0.55);
            border: 1px solid var(--prism-border);
            border-radius: 999px;
            padding: 3px;
            position: relative;
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 600;
            font-size: 11px;
            letter-spacing: 0.12em;
        }
        .pt-lang-toggle button {
            position: relative;
            z-index: 1;
            padding: 6px 10px;
            border-radius: 999px;
            color: var(--prism-text-3);
            transition: color .25s var(--prism-ease);
            min-width: 34px;
            min-height: 28px;
        }
        .pt-lang-toggle button.is-active { color: #0b0e1c; }
        .pt-lang-toggle .pt-lang-thumb {
            position: absolute;
            top: 3px; bottom: 3px;
            width: 38px;
            border-radius: 999px;
            background: linear-gradient(135deg, #cffafe, #c7d2fe 50%, #e9d5ff);
            box-shadow: 0 0 14px rgba(129,140,248,0.5);
            transition: transform .35s var(--prism-ease), width .35s var(--prism-ease);
            pointer-events: none;
        }

        /* spacer so first page section sits below floating topbar */
        .pt-topbar-spacer { height: 76px; }
        @media (max-width: 640px) {
            .pt-topbar-spacer { height: 70px; }
            .pt-topbar { padding: 6px 8px 6px 10px; gap: 4px; }
            .pt-top-link { padding: 6px 10px; font-size: 12px; }
        }

        /* legacy aliases (used by some pages) */
        .prism-nav { display: none; }
        .prism-nav-link {
            display: inline-flex; align-items: center;
            padding: 8px 14px; border-radius: 999px;
            font-size: 13px; font-weight: 500;
            color: var(--prism-text-2);
            transition: all .18s var(--prism-ease);
            min-height: 36px;
        }

        /* ------------- Sticky action bar -------------
           Single canonical CTA pattern. Springy entrance (slight overshoot),
           glass + neon language, min-height 48px tap targets, rich summary
           block (eyebrow / name·phone / chips). Used by:
             · admin booking detail (approve / reject)
             · public booking step 2 (continue)
             · future: anywhere we need a confirm-style sticky CTA. */
        .pt-action-bar {
            position: fixed;
            left: 0; right: 0;
            bottom: 0;
            z-index: 45;
            padding: 10px 14px calc(10px + env(safe-area-inset-bottom)) 14px;
            transform: translateY(140%);
            opacity: 0;
            pointer-events: none;
            transition:
                transform .48s cubic-bezier(.2, 1.2, .2, 1),
                opacity   .32s var(--prism-ease);
            will-change: transform;
        }
        .pt-action-bar.is-on {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        .pt-action-bar-inner {
            position: relative;
            max-width: 920px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 14px;
            border-radius: 22px;
            background:
                linear-gradient(180deg, rgba(20,24,38,0.92), rgba(8,10,20,0.96));
            border: 1px solid rgba(129,140,248,0.38);
            backdrop-filter: blur(22px) saturate(180%);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.07),
                0 -16px 60px -20px rgba(0,0,0,0.85),
                0 0 36px rgba(129,140,248,0.22);
        }
        /* Cyan→indigo→violet neon top edge so the bar reads cleanly even
           over busy content (admin sees this over the transfer screenshot). */
        .pt-action-bar-inner::before {
            content: "";
            position: absolute;
            top: -1px; left: 14px; right: 14px;
            height: 1px;
            background: linear-gradient(90deg,
                rgba(34,211,238,0)   0%,
                rgba(34,211,238,0.7) 14%,
                rgba(129,140,248,0.85) 50%,
                rgba(192,132,252,0.7) 86%,
                rgba(192,132,252,0)  100%);
            border-radius: 1px;
            pointer-events: none;
        }
        .pt-action-bar .pt-bar-summary {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .pt-action-bar .pt-bar-actions {
            flex: 0 0 auto;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .pt-action-bar .pt-bar-label {
            font-size: 10px;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--prism-text-3);
            font-weight: 700;
        }
        .pt-action-bar .pt-bar-total {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: var(--prism-text);
            background: var(--prism-neon);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .pt-action-bar .pt-bar-meta {
            font-size: 12px;
            color: var(--prism-text-2);
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-weight: 500;
        }
        .pt-action-bar .pt-bar-sep {
            color: var(--prism-text-4);
            margin: 0 4px;
        }
        .pt-action-bar .pt-bar-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .pt-action-bar .pt-bar-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(129,140,248,0.22);
            font-size: 11px;
            font-weight: 600;
            color: var(--prism-text-2);
            font-family: "Space Grotesk", system-ui, sans-serif;
            line-height: 1;
        }
        .pt-action-bar .pt-bar-chip-gold {
            background: linear-gradient(135deg, rgba(251,191,36,0.18), rgba(251,191,36,0.06));
            border-color: rgba(251,191,36,0.45);
            color: #fde68a;
        }
        .pt-action-bar .pt-bar-chip-muted {
            color: var(--prism-text-3);
            border-color: rgba(255,255,255,0.10);
        }
        /* Bigger tap targets — handoff said 48 px. Buttons inside the bar
           override the cramped sizing of small base buttons. */
        .pt-action-bar .pt-bar-actions .pt-bar-btn,
        .pt-action-bar .pt-bar-actions .prism-btn,
        .pt-action-bar .pt-bar-actions .prism-btn-emerald,
        .pt-action-bar .pt-bar-actions .prism-btn-rose {
            min-height: 48px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition:
                transform .15s var(--prism-ease),
                box-shadow .25s var(--prism-ease),
                filter    .15s var(--prism-ease);
        }
        .pt-action-bar .pt-bar-actions button:active { transform: scale(.97); }
        .pt-action-bar .pt-bar-actions .prism-btn-emerald:hover {
            filter: brightness(1.06);
            box-shadow:
                0 0 0 1px rgba(52,211,153,0.45) inset,
                0 0 24px rgba(16,185,129,0.45);
        }
        .pt-action-bar .pt-bar-actions .prism-btn-rose:hover {
            filter: brightness(1.06);
            box-shadow:
                0 0 0 1px rgba(251,113,133,0.5) inset,
                0 0 24px rgba(244,63,94,0.45);
        }

        @media (max-width: 560px) {
            .pt-action-bar-inner {
                gap: 8px;
                padding: 12px 12px;
                border-radius: 20px;
                flex-wrap: wrap;
            }
            .pt-action-bar .pt-bar-summary { flex: 1 1 100%; }
            .pt-action-bar .pt-bar-actions { flex: 1 1 100%; }
            .pt-action-bar .pt-bar-actions form { flex: 1; }
            .pt-action-bar .pt-bar-actions .pt-bar-btn,
            .pt-action-bar .pt-bar-actions .prism-btn-emerald,
            .pt-action-bar .pt-bar-actions .prism-btn-rose {
                width: 100%;
                padding: 10px 12px;
                font-size: 13px;
            }
            .pt-action-bar .pt-bar-total { font-size: 16px; }
            .pt-action-bar .pt-bar-meta-row { gap: 5px; }
            .pt-action-bar .pt-bar-chip { font-size: 10.5px; padding: 3px 8px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-action-bar { transition: opacity .2s linear; transform: translateY(0); }
            .pt-action-bar:not(.is-on) { transform: translateY(140%); }
        }

        /* ------------- Compact back chevron -------------
           Replaces the heavy "back" + page-header card on detail pages. */
        .pt-back-chevron {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px; height: 36px;
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            color: var(--prism-text-2);
            font-size: 16px;
            line-height: 1;
            transition: all .15s var(--prism-ease);
        }
        .pt-back-chevron:hover {
            background: rgba(129,140,248,0.12);
            border-color: rgba(129,140,248,0.5);
            color: var(--prism-text);
            box-shadow: 0 0 18px rgba(129,140,248,0.22);
        }
        .pt-back-chevron:active { transform: scale(.94); }

        /* ------------- Premium modal ------------- */
        .pt-modal-root {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s var(--prism-ease);
        }
        .pt-modal-root.is-open { opacity: 1; pointer-events: auto; }
        .pt-modal-backdrop {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 70% 60% at 50% 30%, rgba(34,211,238,0.10), transparent 60%),
                        rgba(2,4,12,0.72);
            backdrop-filter: blur(10px) saturate(120%);
            -webkit-backdrop-filter: blur(10px) saturate(120%);
        }
        .pt-modal-card {
            position: relative;
            width: 100%;
            max-width: 420px;
            border-radius: 22px;
            padding: 20px;
            background: linear-gradient(180deg, rgba(28,32,52,0.82), rgba(13,16,28,0.92));
            border: 1px solid rgba(129,140,248,0.4);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 36px 70px -20px rgba(0,0,0,0.85),
                0 0 40px rgba(129,140,248,0.22);
            transform: translateY(16px) scale(.96);
            transition: transform .42s cubic-bezier(.2, 1.2, .2, 1);
        }
        .pt-modal-root.is-open .pt-modal-card { transform: translateY(0) scale(1); }
        .pt-modal-icon {
            width: 56px; height: 56px;
            border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(129,140,248,0.18);
            border: 1px solid rgba(129,140,248,0.5);
            color: var(--prism-indigo);
            box-shadow: 0 0 30px rgba(129,140,248,0.35);
            margin-bottom: 14px;
        }
        .pt-modal-icon.tone-success {
            background: rgba(52,211,153,0.16);
            border-color: rgba(52,211,153,0.55);
            color: var(--prism-emerald);
            box-shadow: 0 0 30px rgba(52,211,153,0.4);
        }
        .pt-modal-icon.tone-error {
            background: rgba(244,63,94,0.16);
            border-color: rgba(251,113,133,0.55);
            color: var(--prism-rose);
            box-shadow: 0 0 30px rgba(244,63,94,0.4);
        }
        .pt-modal-icon.tone-warn {
            background: rgba(251,191,36,0.16);
            border-color: rgba(251,191,36,0.55);
            color: var(--prism-gold);
            box-shadow: 0 0 30px rgba(251,191,36,0.35);
        }
        .pt-modal-title {
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: var(--prism-text);
            margin-bottom: 6px;
        }
        .pt-modal-body {
            font-size: 13.5px;
            color: var(--prism-text-2);
            line-height: 1.7;
        }
        .pt-modal-actions {
            margin-top: 16px;
            display: flex; gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .pt-modal-spinner {
            width: 26px; height: 26px;
            border-radius: 999px;
            border: 2.5px solid rgba(129,140,248,0.25);
            border-top-color: var(--prism-indigo);
            animation: ptSpin .9s linear infinite;
        }
        @keyframes ptSpin { to { transform: rotate(360deg); } }

        /* ------------- Toast ------------- */
        .pt-toast {
            position: fixed;
            top: 84px;
            left: 50%;
            transform: translate(-50%, -10px);
            z-index: 90;
            padding: 10px 14px;
            border-radius: 999px;
            font-size: 13px;
            color: var(--prism-text);
            background: linear-gradient(180deg, rgba(20,24,38,0.92), rgba(13,16,28,0.95));
            border: 1px solid rgba(129,140,248,0.4);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.7), 0 0 22px rgba(129,140,248,0.22);
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s var(--prism-ease), transform .35s var(--prism-ease);
        }
        .pt-toast.is-on {
            opacity: 1;
            transform: translate(-50%, 0);
        }

        /* ------------- Page transition -------------
           Springy entrance on every route change so admin / booking
           flows feel like one cohesive product. The cubic-bezier
           overshoots slightly (1.2 on Y) so the page snaps in instead
           of just easing in. */
        @keyframes ptPageIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        main.pt-page { animation: ptPageIn .48s cubic-bezier(.2, 1.2, .2, 1) both; }

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

        /* =========================================================
           PR 2 — Hierarchy primitives (Stripe / Apple-feel).
           Small, composable building blocks that views use to
           establish strong visual hierarchy without one-off styles.
           ========================================================= */

        /* Eyebrow — small, all-caps, wide-tracked label that sits
           above any data point. Replaces the inline
           `text-[11px] uppercase letter-spacing:.18em` patterns. */
        .prism-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--prism-text-3);
        }
        .prism-eyebrow-strong {
            color: var(--prism-text-2);
        }

        /* Section title — display-style heading with a subtle neon
           underline accent. Use once per section; keeps the eye
           anchored while the content below stays calm. */
        .prism-section-title {
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-weight: 700;
            font-size: clamp(15px, 1.6vw, 18px);
            letter-spacing: -0.005em;
            color: var(--prism-text);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            padding-bottom: 2px;
        }
        .prism-section-title::after {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            bottom: -6px;
            width: 28px;
            height: 2px;
            border-radius: 2px;
            background: var(--prism-neon);
            opacity: .85;
        }
        .prism-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        /* Stat tile — disciplined, single-purpose KPI card.
           Variants: .is-primary (gold/hero), .is-positive
           (emerald), .is-attention (cyan, for actionable items),
           .is-muted (subdued). */
        .prism-stat {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 16px 18px 18px 18px;
            border-radius: var(--prism-radius);
            background: linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.72));
            border: 1px solid var(--prism-border);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.05),
                0 24px 48px -22px rgba(0,0,0,0.65);
            transition: border-color .25s var(--prism-ease), transform .25s var(--prism-ease);
        }
        .prism-stat:hover {
            border-color: rgba(129,140,248,0.32);
            transform: translateY(-2px);
        }
        .prism-stat-label {
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--prism-text-3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .prism-stat-value {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            font-size: clamp(22px, 2.4vw, 30px);
            line-height: 1.05;
            letter-spacing: -0.02em;
            color: var(--prism-text);
        }
        .prism-stat-caption {
            font-size: 11px;
            line-height: 1.5;
            color: var(--prism-text-3);
        }

        .prism-stat.is-primary {
            border-color: rgba(251,191,36,0.40);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 28px 56px -22px rgba(251,191,36,0.30),
                0 0 28px rgba(251,191,36,0.14);
        }
        .prism-stat.is-primary .prism-stat-value {
            font-size: clamp(28px, 3.2vw, 40px);
            color: var(--prism-gold);
        }
        .prism-stat.is-primary .prism-stat-label {
            color: #fde68a;
        }

        .prism-stat.is-positive { border-color: rgba(52,211,153,0.32); }
        .prism-stat.is-positive .prism-stat-value { color: var(--prism-emerald); }

        .prism-stat.is-attention { border-color: rgba(34,211,238,0.30); }
        .prism-stat.is-attention .prism-stat-value { color: var(--prism-cyan); }

        /* Decorative spark — subtle gradient accent in stat top-edge */
        .prism-stat::before {
            content: "";
            position: absolute;
            top: 0; left: 14px; right: 14px;
            height: 1px;
            border-radius: 1px;
            background: linear-gradient(90deg,
                rgba(34,211,238,0)  0%,
                rgba(34,211,238,0.4) 30%,
                rgba(192,132,252,0.4) 70%,
                rgba(192,132,252,0)  100%);
            opacity: 0;
            transition: opacity .3s var(--prism-ease);
        }
        .prism-stat:hover::before { opacity: 1; }

        /* Data row — clean key/value row used inside cards.
           Replaces ad-hoc `flex justify-between py-2 border-b`
           patterns. */
        .prism-data-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--prism-border);
            font-size: 13px;
        }
        .prism-data-row:last-child { border-bottom: none; }
        .prism-data-key {
            font-size: 11.5px;
            color: var(--prism-text-3);
            letter-spacing: 0.04em;
        }
        .prism-data-val {
            font-weight: 600;
            color: var(--prism-text);
            text-align: end;
        }
        .prism-data-val-gold  { color: var(--prism-gold); }
        .prism-data-val-emerald { color: var(--prism-emerald); }
        .prism-data-val-rose  { color: var(--prism-rose); }
        .prism-data-val-cyan  { color: var(--prism-cyan); }

        /* Section divider — thin neon line with a centered dot.
           Use sparingly to break a long page into logical chunks. */
        .prism-section-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 2px 12px 2px;
        }
        .prism-section-divider::before,
        .prism-section-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(129,140,248,0.28), transparent);
        }
        .prism-section-divider-dot {
            width: 6px; height: 6px; border-radius: 999px;
            background: var(--prism-neon);
            box-shadow: 0 0 10px rgba(129,140,248,0.6);
        }

        /* Toolbar — header strip above tables / lists. Holds an
           eyebrow + title on the start, search + filters on the
           end. Sticks to the top of the page on scroll on
           ≥ md viewports for fast scanning of long lists. */
        .prism-toolbar {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 10px 12px;
            border-radius: var(--prism-radius);
            background:
                linear-gradient(180deg, rgba(20,24,38,0.78), rgba(8,10,20,0.86));
            border: 1px solid var(--prism-border);
            backdrop-filter: blur(20px) saturate(150%);
            -webkit-backdrop-filter: blur(20px) saturate(150%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.05),
                0 18px 40px -22px rgba(0,0,0,0.65);
        }
        .prism-toolbar-end {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        @media (min-width: 768px) {
            .prism-toolbar-sticky {
                position: sticky;
                top: 78px;
                z-index: 30;
            }
        }
        /* Mobile (< 768px): stack toolbar children vertically so the
           search input, segmented control, and date filter never
           collide on a single row. Without this, .prism-toolbar's
           default row layout squeezes the search and the prism-
           toolbar-end onto the same line and they visibly overlap. */
        @media (max-width: 767px) {
            .prism-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .prism-toolbar > * {
                width: 100%;
                min-width: 0;
            }
            .prism-toolbar-end {
                justify-content: flex-start;
            }
            .prism-toolbar .prism-input {
                max-width: none !important;
                width: 100%;
            }
            .prism-toolbar .prism-segment {
                width: 100%;
                justify-content: space-between;
            }
            .prism-toolbar .prism-segment > label {
                flex: 1;
                min-width: 0;
            }
        }

        /* Segmented control — pill of mutually-exclusive options
           with a sliding fill. Use for status filters and any
           low-cardinality enum. Drives state via a hidden input;
           radios keep keyboard / a11y semantics. */
        .prism-segment {
            position: relative;
            display: inline-flex;
            align-items: center;
            background: rgba(8,10,20,0.6);
            border: 1px solid var(--prism-border);
            border-radius: 999px;
            padding: 3px;
            gap: 0;
            font-size: 11.5px;
            min-height: 36px;
        }
        .prism-segment > input[type="radio"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }
        .prism-segment > label {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            color: var(--prism-text-3);
            cursor: pointer;
            transition: color .25s var(--prism-ease);
            min-width: 64px;
            text-align: center;
            line-height: 1;
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
        }
        .prism-segment > label:hover { color: var(--prism-text-2); }
        .prism-segment > input[type="radio"]:checked + label {
            color: var(--prism-text);
            background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
            border: 1px solid rgba(129,140,248,0.45);
            box-shadow: 0 0 18px rgba(129,140,248,0.22);
        }

        /* Quick-action card — large, generous, used for the
           dashboard's "go here next" choices. Pure CSS hover
           replaces inline onmouseover scripts. */
        .prism-quick-action {
            position: relative;
            display: block;
            padding: 22px 22px 20px 22px;
            border-radius: var(--prism-radius);
            background: linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.72));
            border: 1px solid var(--prism-border);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.05),
                0 22px 44px -22px rgba(0,0,0,0.65);
            transition:
                transform .25s var(--prism-ease),
                border-color .25s var(--prism-ease),
                box-shadow .25s var(--prism-ease);
            text-decoration: none;
            overflow: hidden;
            isolation: isolate;
        }
        .prism-quick-action::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(120% 80% at 100% 0%, rgba(129,140,248,0.16), transparent 60%);
            opacity: 0;
            transition: opacity .3s var(--prism-ease);
            pointer-events: none;
            z-index: -1;
        }
        .prism-quick-action:hover {
            transform: translateY(-4px);
            border-color: rgba(129,140,248,0.55);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 30px 60px -22px rgba(129,140,248,0.45),
                0 0 28px rgba(34,211,238,0.18);
        }
        .prism-quick-action:hover::before { opacity: 1; }
        .prism-quick-action-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            color: var(--prism-text-2);
            transition: transform .25s var(--prism-ease), background .25s var(--prism-ease), color .25s var(--prism-ease);
        }
        .prism-quick-action:hover .prism-quick-action-arrow {
            transform: translateX(-4px);
            background: rgba(129,140,248,0.18);
            border-color: rgba(129,140,248,0.45);
            color: var(--prism-text);
        }
        [dir="rtl"] .prism-quick-action:hover .prism-quick-action-arrow {
            transform: translateX(4px);
        }

        /* Refined data table — calmer than the previous one.
           Drops zebra-stripes for a single border between rows;
           uses CSS hover instead of inline JS; sticky thead. */
        .prism-table-clean {
            width: 100%;
            font-size: 12.5px;
            color: var(--prism-text-2);
            border-collapse: separate;
            border-spacing: 0;
        }
        .prism-table-clean thead {
            position: sticky;
            top: 0;
            z-index: 1;
            background: rgba(13,16,28,0.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .prism-table-clean thead th {
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--prism-text-3);
            padding: 12px 14px;
            text-align: start;
            border-bottom: 1px solid var(--prism-border-strong);
        }
        .prism-table-clean tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--prism-border);
            transition: background .15s ease;
            vertical-align: middle;
        }
        .prism-table-clean tbody tr:last-child td { border-bottom: none; }
        .prism-table-clean tbody tr:hover td {
            background: rgba(129,140,248,0.06);
        }

        /* Quick-stats strip — minimal stat ribbon, used above
           lists / above tables. Each item is just an eyebrow + a
           value; no card chrome, just a thin divider between
           items. Zero visual weight, maximum information density. */
        .prism-stat-strip {
            display: flex;
            align-items: center;
            gap: 0;
            padding: 14px 18px;
            border-radius: var(--prism-radius);
            background: linear-gradient(180deg, rgba(20,24,38,0.45), rgba(8,10,20,0.55));
            border: 1px solid var(--prism-border);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .prism-stat-strip::-webkit-scrollbar { display: none; }
        .prism-stat-strip > .prism-stat-strip-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0 22px;
            border-inline-end: 1px solid var(--prism-border);
            min-width: max-content;
            white-space: nowrap;
        }
        .prism-stat-strip > .prism-stat-strip-item:first-child { padding-inline-start: 0; }
        .prism-stat-strip > .prism-stat-strip-item:last-child {
            padding-inline-end: 0;
            border-inline-end: none;
        }
        .prism-stat-strip-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--prism-text-3);
        }
        .prism-stat-strip-val {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: var(--prism-text);
            letter-spacing: -0.005em;
        }
        .prism-stat-strip-val-gold    { color: var(--prism-gold); }
        .prism-stat-strip-val-emerald { color: var(--prism-emerald); }
        .prism-stat-strip-val-cyan    { color: var(--prism-cyan); }
        .prism-stat-strip-val-rose    { color: var(--prism-rose); }

        /* List-item inside a card — used for tickets in admin booking
           detail, payments, etc. CSS-only hover (replaces the inline
           onmouseover / onmouseout that used to live in show.blade.php). */
        .pt-ticket-row {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            transition: background .2s var(--prism-ease), border-color .2s var(--prism-ease), transform .2s var(--prism-ease);
        }
        .pt-ticket-row:hover {
            background: rgba(129,140,248,0.06);
            border-color: rgba(129,140,248,0.3);
            transform: translateX(2px);
        }
        [dir="rtl"] .pt-ticket-row:hover {
            transform: translateX(-2px);
        }

        @media (prefers-reduced-motion: reduce) {
            .prism-stat,
            .prism-quick-action,
            .prism-quick-action-arrow,
            .prism-card-hover,
            .pt-ticket-row {
                transition: none !important;
            }
            .prism-stat:hover,
            .prism-quick-action:hover,
            .pt-ticket-row:hover {
                transform: none !important;
            }
        }

        /* =====================================================================
           PRISM v2 — Theme system (Light + Dark) and Cinematic Header
           - Adds first-class light mode via [data-pt-theme="light"] overrides.
           - Ground-up redesigned navbar with mobile drawer + theme toggle.
           - iOS/Safari-first: safe-area insets, dvh/svh, momentum scroll.
           ====================================================================*/

        :root {
            --pt-safe-top: env(safe-area-inset-top, 0px);
            --pt-safe-right: env(safe-area-inset-right, 0px);
            --pt-safe-bottom: env(safe-area-inset-bottom, 0px);
            --pt-safe-left: env(safe-area-inset-left, 0px);
        }

        /* Smooth global theme transition */
        html, body, .prism-stage,
        .prism-glass, .prism-glass-strong,
        .pt-topbar, .pt-drawer, .prism-footer {
            transition:
                background-color .35s var(--prism-ease),
                background .35s var(--prism-ease),
                color .35s var(--prism-ease),
                border-color .35s var(--prism-ease);
        }

        /* iOS smoother scrolling + better tap behavior */
        html { -webkit-text-size-adjust: 100%; }
        body { -webkit-overflow-scrolling: touch; }

        /* Inputs ≥ 16px to prevent iOS zoom on focus */
        input, select, textarea, .prism-input { font-size: 16px; }
        @media (min-width: 768px) {
            .prism-input { font-size: 14px; }
        }

        /* ---------- LIGHT THEME ---------- */
        :root[data-pt-theme="light"] {
            --prism-bg-0: #f4f1ea;
            --prism-bg-1: #ece7dc;
            --prism-bg-2: #e3dccc;

            --prism-surface: rgba(255, 255, 255, 0.72);
            --prism-surface-strong: rgba(255, 255, 255, 0.88);
            --prism-surface-soft: rgba(15, 23, 42, 0.04);

            --prism-border: rgba(15, 23, 42, 0.10);
            --prism-border-strong: rgba(15, 23, 42, 0.18);
            --prism-border-neon: rgba(99, 102, 241, 0.34);

            --prism-text:   #0f172a;
            --prism-text-2: #334155;
            --prism-text-3: #475569;
            --prism-text-4: #64748b;

            --prism-cyan:    #0891b2;
            --prism-indigo:  #4f46e5;
            --prism-violet:  #7c3aed;
            --prism-gold:    #b45309;
            --prism-emerald: #047857;
            --prism-rose:    #be123c;

            --prism-neon: linear-gradient(135deg, #0891b2 0%, #4f46e5 50%, #7c3aed 100%);
            --prism-neon-soft: linear-gradient(135deg, rgba(8,145,178,0.16), rgba(79,70,229,0.16) 50%, rgba(124,58,237,0.16));

            --prism-glow-sm: 0 0 14px rgba(79,70,229,0.18);
            --prism-glow-md: 0 0 24px rgba(79,70,229,0.22), 0 0 48px rgba(8,145,178,0.10);
            --prism-glow-gold: 0 0 18px rgba(245,158,11,0.28);
        }

        /* Light: stage background — warm off-white, subtle aurora */
        :root[data-pt-theme="light"] .prism-stage {
            background:
                radial-gradient(ellipse 80% 50% at 50% -10%, rgba(99,102,241,0.16), transparent 60%),
                radial-gradient(ellipse 60% 40% at 0% 10%, rgba(8,145,178,0.12), transparent 60%),
                radial-gradient(ellipse 60% 40% at 100% 5%, rgba(124,58,237,0.10), transparent 60%),
                radial-gradient(ellipse 70% 50% at 50% 110%, rgba(245,158,11,0.10), transparent 60%),
                linear-gradient(180deg, #f6f2ea 0%, #efe9dc 60%, #f4f1ea 100%);
        }
        :root[data-pt-theme="light"] .prism-stage::before {
            background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,0.07) 1px, transparent 0);
        }

        /* Light: glass surfaces */
        :root[data-pt-theme="light"] .prism-glass {
            background: linear-gradient(180deg, rgba(255,255,255,0.78), rgba(255,255,255,0.58));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.85),
                0 18px 38px -22px rgba(15, 23, 42, 0.18),
                0 1px 2px rgba(15, 23, 42, 0.04);
        }
        :root[data-pt-theme="light"] .prism-glass-strong {
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(252,250,245,0.88));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 22px 50px -22px rgba(15,23,42,0.22);
        }
        :root[data-pt-theme="light"] .prism-glow-border::before { opacity: 0.7; }

        /* Light: scrollbar */
        :root[data-pt-theme="light"] ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(79,70,229,0.34), rgba(8,145,178,0.34));
        }
        :root[data-pt-theme="light"] ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(79,70,229,0.55), rgba(8,145,178,0.55));
        }
        :root[data-pt-theme="light"] ::selection { background: rgba(79,70,229,0.30); color: #0f172a; }

        /* Light: footer */
        :root[data-pt-theme="light"] .prism-footer {
            background: linear-gradient(180deg, rgba(244,241,234,0.6), rgba(244,241,234,0.95));
            border-top-color: var(--prism-border);
        }

        /* Light: inputs */
        :root[data-pt-theme="light"] .prism-input {
            background: rgba(255,255,255,0.85);
            color: var(--prism-text);
        }
        :root[data-pt-theme="light"] .prism-input:focus {
            background: #ffffff;
            border-color: rgba(79,70,229,0.55);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.14), 0 0 18px rgba(79,70,229,0.16);
        }

        /* Light: pills + badges */
        :root[data-pt-theme="light"] .prism-pill { background: rgba(255,255,255,0.65); color: var(--prism-text-2); }
        :root[data-pt-theme="light"] .prism-pill-neon { color: #1e293b; }
        :root[data-pt-theme="light"] .prism-pill-emerald { color: #047857; }
        :root[data-pt-theme="light"] .prism-pill-amber  { color: #b45309; }
        :root[data-pt-theme="light"] .prism-pill-rose   { color: #be123c; }
        :root[data-pt-theme="light"] .prism-pill-sky    { color: #0369a1; }

        /* Light: brand logo bg */
        :root[data-pt-theme="light"] .prism-logo {
            background: rgba(15,23,42,0.04);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.85), 0 0 24px rgba(79,70,229,0.12);
        }

        /* =====================================================================
           v2 NAVBAR — replaces .pt-topbar look
           ====================================================================*/
        .pt-topbar-wrap {
            top: calc(10px + var(--pt-safe-top));
            left: calc(12px + var(--pt-safe-left));
            right: calc(12px + var(--pt-safe-right));
        }
        .pt-topbar {
            max-width: 1180px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 14px;
            padding: 8px 10px 8px 12px;
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(20,24,38,0.62), rgba(8,10,20,0.78));
            border: 1px solid var(--prism-border);
            backdrop-filter: blur(22px) saturate(170%);
            -webkit-backdrop-filter: blur(22px) saturate(170%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 18px 40px -22px rgba(0,0,0,0.75),
                0 0 22px rgba(129,140,248,0.10);
        }
        :root[data-pt-theme="light"] .pt-topbar {
            background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.72));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 18px 38px -22px rgba(15,23,42,0.18),
                0 1px 2px rgba(15,23,42,0.04);
        }
        .pt-topbar.is-scrolled {
            border-color: rgba(129,140,248,0.32);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.07),
                0 26px 60px -22px rgba(0,0,0,0.85),
                0 0 30px rgba(129,140,248,0.20);
        }
        :root[data-pt-theme="light"] .pt-topbar.is-scrolled {
            border-color: rgba(79,70,229,0.34);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,1),
                0 26px 60px -22px rgba(15,23,42,0.22),
                0 0 30px rgba(79,70,229,0.16);
        }

        /* aurora glow line under topbar (visible when scrolled) */
        .pt-topbar-aurora {
            position: absolute;
            left: 6%; right: 6%; bottom: -6px;
            height: 22px;
            background: radial-gradient(50% 100% at 50% 0%, rgba(129,140,248,0.55), transparent 70%);
            filter: blur(10px);
            opacity: 0;
            transition: opacity .35s var(--prism-ease);
            pointer-events: none;
        }
        .pt-topbar-wrap.is-scrolled .pt-topbar-aurora { opacity: 0.6; }
        :root[data-pt-theme="light"] .pt-topbar-aurora {
            background: radial-gradient(50% 100% at 50% 0%, rgba(79,70,229,0.45), transparent 70%);
        }

        /* Brand */
        .pt-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 4px 6px;
            border-radius: 14px;
            text-decoration: none;
            position: relative;
        }
        .pt-brand-logo {
            position: relative;
            width: 40px; height: 40px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 0 24px rgba(129,140,248,0.18);
            transition: transform .4s var(--prism-ease), box-shadow .35s var(--prism-ease);
            overflow: hidden;
            flex: 0 0 auto;
        }
        :root[data-pt-theme="light"] .pt-brand-logo {
            background: rgba(15,23,42,0.04);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.92), 0 0 24px rgba(79,70,229,0.14);
        }
        .pt-brand-logo svg { position: relative; z-index: 1; }
        .pt-brand-orb {
            position: absolute;
            inset: -25%;
            background: radial-gradient(closest-side, rgba(129,140,248,0.42), transparent 70%);
            filter: blur(8px);
            opacity: 0.55;
            animation: ptBrandOrb 6s ease-in-out infinite alternate;
            pointer-events: none;
        }
        @keyframes ptBrandOrb {
            0%   { transform: translate(-12%, -8%) scale(0.95); }
            100% { transform: translate(8%, 6%) scale(1.1); }
        }
        @media (hover: hover) {
            .pt-brand:hover .pt-brand-logo { transform: rotate(-4deg) scale(1.04); }
        }
        .pt-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
            min-width: 0;
        }
        .pt-brand-wordmark {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: 0.22em;
            background: var(--prism-neon);
            background-size: 220% 100%;
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
            animation: ptShimmerText 7s linear infinite;
        }
        @keyframes ptShimmerText {
            0%   { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
        .pt-brand-tag {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: 8.5px;
            letter-spacing: 0.34em;
            color: var(--prism-text-4);
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* Center nav */
        .pt-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2px;
            padding: 4px;
            border-radius: 999px;
            background: var(--prism-surface-soft);
            border: 1px solid transparent;
            justify-self: center;
        }
        .pt-nav-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
            color: var(--prism-text-2);
            text-decoration: none;
            min-height: 36px;
            transition: color .2s var(--prism-ease), background .2s var(--prism-ease), transform .2s var(--prism-ease);
        }
        @media (hover: hover) {
            .pt-nav-link:hover {
                color: var(--prism-text);
                background: rgba(255,255,255,0.06);
            }
            :root[data-pt-theme="light"] .pt-nav-link:hover {
                background: rgba(15,23,42,0.06);
            }
        }
        .pt-nav-link.is-active {
            color: var(--prism-text);
            background: linear-gradient(135deg, rgba(34,211,238,0.16), rgba(192,132,252,0.16));
            box-shadow: inset 0 0 0 1px rgba(129,140,248,0.45), 0 0 14px rgba(129,140,248,0.18);
        }
        :root[data-pt-theme="light"] .pt-nav-link.is-active {
            background: linear-gradient(135deg, rgba(8,145,178,0.14), rgba(124,58,237,0.14));
            box-shadow: inset 0 0 0 1px rgba(79,70,229,0.35), 0 0 14px rgba(79,70,229,0.14);
        }
        .pt-nav-link.is-active::after {
            content: "";
            position: absolute;
            left: 18%; right: 18%; bottom: -7px;
            height: 2px;
            border-radius: 2px;
            background: var(--prism-neon);
            box-shadow: 0 0 10px rgba(129,140,248,0.6);
        }
        .pt-nav-link-admin {
            background: linear-gradient(135deg, rgba(251,191,36,0.10), rgba(251,113,133,0.10));
        }

        /* Action cluster */
        .pt-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-self: end;
        }

        /* Theme toggle */
        .pt-theme-toggle {
            position: relative;
            width: 40px; height: 40px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.16);
            background: linear-gradient(180deg, rgba(20,24,38,0.95), rgba(8,10,20,0.95));
            color: #f5f7fb;
            z-index: 2;
            transition: all .25s var(--prism-ease);
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
            cursor: pointer;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 6px 20px -8px rgba(0,0,0,0.65);
        }
        :root[data-pt-theme="light"] .pt-theme-toggle {
            background: linear-gradient(180deg, #1f2542, #0f1428);
            border-color: rgba(15,23,42,0.30);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.10),
                0 6px 18px -6px rgba(15,23,42,0.35);
            color: #fde68a;
        }
        @media (hover: hover) {
            .pt-theme-toggle:hover {
                color: var(--prism-text);
                border-color: rgba(129,140,248,0.45);
                box-shadow: 0 0 18px rgba(129,140,248,0.22);
                transform: translateY(-1px);
            }
        }
        .pt-theme-icon {
            position: absolute;
            inset: 0;
            display: flex; align-items: center; justify-content: center;
            transition: transform .35s var(--prism-ease), opacity .25s var(--prism-ease);
            color: inherit;
        }
        .pt-theme-icon svg { width: 18px; height: 18px; display: block; }
        :root[data-pt-theme="dark"]  .pt-theme-icon-sun  { opacity: 1; transform: rotate(0deg) scale(1); }
        :root[data-pt-theme="dark"]  .pt-theme-icon-moon { opacity: 0; transform: rotate(60deg) scale(0.6); }
        :root[data-pt-theme="light"] .pt-theme-icon-sun  { opacity: 0; transform: rotate(-60deg) scale(0.6); }
        :root[data-pt-theme="light"] .pt-theme-icon-moon { opacity: 1; transform: rotate(0deg) scale(1); }

        /* Lang toggle (override defaults to fit cluster) */
        .pt-lang-toggle-desktop { display: inline-flex; }
        .pt-lang-toggle-mobile  { display: inline-flex; }
        @media (max-width: 880px) { .pt-lang-toggle-desktop { display: none; } }
        :root[data-pt-theme="light"] .pt-lang-toggle {
            background: rgba(15,23,42,0.04);
            border-color: var(--prism-border);
        }
        :root[data-pt-theme="light"] .pt-lang-toggle button { color: var(--prism-text-3); }
        :root[data-pt-theme="light"] .pt-lang-toggle button.is-active { color: #ffffff; }
        :root[data-pt-theme="light"] .pt-lang-toggle .pt-lang-thumb {
            background: linear-gradient(135deg, #0891b2, #4f46e5 50%, #7c3aed);
            box-shadow: 0 0 14px rgba(79,70,229,0.45);
        }

        /* Burger button (mobile only) */
        .pt-burger {
            display: none;
            width: 40px; height: 40px;
            border-radius: 999px;
            border: 1px solid var(--prism-border);
            background: rgba(255,255,255,0.04);
            color: var(--prism-text);
            align-items: center; justify-content: center;
            -webkit-tap-highlight-color: transparent;
            cursor: pointer;
        }
        :root[data-pt-theme="light"] .pt-burger { background: rgba(15,23,42,0.04); }
        @media (max-width: 880px) {
            .pt-burger { display: inline-flex; }
            .pt-nav { display: none; }
            .pt-brand-tag { display: none; }
            .pt-topbar { grid-template-columns: 1fr auto; gap: 8px; padding: 6px 6px 6px 8px; }
        }
        .pt-burger-bars {
            position: relative;
            width: 18px; height: 12px;
            display: inline-block;
        }
        .pt-burger-bars i {
            position: absolute;
            left: 0; right: 0;
            height: 2px;
            background: currentColor;
            border-radius: 2px;
            transition: transform .3s var(--prism-ease), opacity .2s var(--prism-ease), top .3s var(--prism-ease);
        }
        .pt-burger-bars i:nth-child(1) { top: 0; }
        .pt-burger-bars i:nth-child(2) { top: 5px; }
        .pt-burger-bars i:nth-child(3) { top: 10px; }
        body.pt-drawer-open .pt-burger-bars i:nth-child(1) { top: 5px; transform: rotate(45deg); }
        body.pt-drawer-open .pt-burger-bars i:nth-child(2) { opacity: 0; }
        body.pt-drawer-open .pt-burger-bars i:nth-child(3) { top: 5px; transform: rotate(-45deg); }

        /* spacer (override) */
        .pt-topbar-spacer { height: calc(80px + var(--pt-safe-top)); }
        @media (max-width: 640px) { .pt-topbar-spacer { height: calc(72px + var(--pt-safe-top)); } }

        /* =====================================================================
           Mobile Drawer
           ====================================================================*/
        .pt-drawer-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(5, 6, 13, 0.55);
            backdrop-filter: blur(8px) saturate(140%);
            -webkit-backdrop-filter: blur(8px) saturate(140%);
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s var(--prism-ease);
            z-index: 60;
        }
        :root[data-pt-theme="light"] .pt-drawer-backdrop { background: rgba(15,23,42,0.32); }
        body.pt-drawer-open .pt-drawer-backdrop { opacity: 1; pointer-events: auto; }

        .pt-drawer {
            position: fixed;
            top: 0; bottom: 0;
            inset-inline-start: 0;
            width: min(86vw, 360px);
            max-width: 100%;
            height: 100dvh;
            height: 100svh;
            display: flex;
            flex-direction: column;
            padding-top: var(--pt-safe-top);
            padding-bottom: var(--pt-safe-bottom);
            padding-inline-start: var(--pt-safe-left);
            background: linear-gradient(180deg, rgba(8,10,20,0.96), rgba(5,6,13,0.98));
            border-inline-end: 1px solid var(--prism-border);
            box-shadow: 0 30px 80px rgba(0,0,0,0.55);
            transform: translateX(-100%);
            transition: transform .35s var(--prism-ease);
            z-index: 70;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }
        html[dir="rtl"] .pt-drawer { transform: translateX(100%); }
        body.pt-drawer-open .pt-drawer { transform: translateX(0) !important; }
        :root[data-pt-theme="light"] .pt-drawer {
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,245,238,1));
            border-inline-end-color: var(--prism-border);
        }
        .pt-drawer-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 18px;
            border-bottom: 1px solid var(--prism-border);
        }
        .pt-drawer-brand { display: inline-flex; align-items: center; gap: 10px; }
        .pt-drawer-brand-text { display: flex; flex-direction: column; line-height: 1; }
        .pt-drawer-close {
            width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px;
            border: 1px solid var(--prism-border);
            background: rgba(255,255,255,0.04);
            color: var(--prism-text);
            -webkit-tap-highlight-color: transparent;
            cursor: pointer;
        }
        :root[data-pt-theme="light"] .pt-drawer-close { background: rgba(15,23,42,0.05); }

        .pt-drawer-nav { padding: 12px 14px; display: flex; flex-direction: column; gap: 4px; flex: 1 0 auto; }
        .pt-drawer-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 500;
            color: var(--prism-text-2);
            text-decoration: none;
            min-height: 48px;
            border: 1px solid transparent;
            transition: background .2s var(--prism-ease), color .2s var(--prism-ease), border-color .2s var(--prism-ease);
        }
        .pt-drawer-link svg { color: var(--prism-text-3); }
        .pt-drawer-link:active { background: rgba(255,255,255,0.06); }
        :root[data-pt-theme="light"] .pt-drawer-link:active { background: rgba(15,23,42,0.05); }
        .pt-drawer-link.is-active {
            color: var(--prism-text);
            background: linear-gradient(135deg, rgba(34,211,238,0.14), rgba(192,132,252,0.14));
            border-color: rgba(129,140,248,0.34);
        }
        :root[data-pt-theme="light"] .pt-drawer-link.is-active {
            background: linear-gradient(135deg, rgba(8,145,178,0.14), rgba(124,58,237,0.14));
            border-color: rgba(79,70,229,0.34);
        }
        .pt-drawer-link.is-active svg { color: var(--prism-text); }

        .pt-drawer-foot {
            padding: 14px 16px calc(18px + var(--pt-safe-bottom));
            border-top: 1px solid var(--prism-border);
            display: flex; flex-direction: column; gap: 12px;
            background: rgba(255,255,255,0.02);
        }
        :root[data-pt-theme="light"] .pt-drawer-foot { background: rgba(15,23,42,0.02); }
        .pt-drawer-row {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
        }
        .pt-drawer-row-label {
            font-size: 11px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--prism-text-4);
            font-weight: 600;
        }
        .pt-segment {
            display: inline-flex;
            background: rgba(8,10,20,0.55);
            border: 1px solid var(--prism-border);
            border-radius: 999px;
            padding: 3px;
        }
        :root[data-pt-theme="light"] .pt-segment {
            background: rgba(15,23,42,0.04);
        }
        .pt-segment button {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            color: var(--prism-text-3);
            transition: color .2s var(--prism-ease), background .2s var(--prism-ease);
            min-height: 32px;
            cursor: pointer;
        }
        .pt-segment button.is-active {
            color: #0b0e1c;
            background: linear-gradient(135deg, #cffafe, #c7d2fe 50%, #e9d5ff);
            box-shadow: 0 0 14px rgba(129,140,248,0.45);
        }
        :root[data-pt-theme="light"] .pt-segment button.is-active {
            color: #ffffff;
            background: linear-gradient(135deg, #0891b2, #4f46e5 50%, #7c3aed);
            box-shadow: 0 0 14px rgba(79,70,229,0.4);
        }
        .pt-drawer-cta { padding-top: 4px; }

        /* =====================================================================
           Homepage v2 — Cinematic hero, marquee, stats, how-it-works
           ====================================================================*/

        /* Hero stage */
        .pt-hero {
            position: relative;
            border-radius: 28px;
            padding: clamp(28px, 5vw, 56px) clamp(20px, 4vw, 44px);
            overflow: hidden;
            isolation: isolate;
            border: 1px solid var(--prism-border);
            background:
                radial-gradient(ellipse 60% 60% at 50% 110%, rgba(251,191,36,0.10), transparent 70%),
                linear-gradient(180deg, rgba(13,16,28,0.65), rgba(8,10,20,0.85));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 30px 80px -32px rgba(0,0,0,0.85);
        }
        :root[data-pt-theme="light"] .pt-hero {
            background:
                radial-gradient(ellipse 60% 60% at 50% 110%, rgba(245,158,11,0.10), transparent 70%),
                linear-gradient(180deg, rgba(255,255,255,0.92), rgba(252,250,245,0.85));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 30px 80px -36px rgba(15,23,42,0.20);
        }

        /* Twin spotlight beams from top corners */
        .pt-hero-beam {
            position: absolute;
            top: -10%;
            width: 60%;
            height: 130%;
            pointer-events: none;
            opacity: 0.55;
            mix-blend-mode: screen;
            filter: blur(2px);
            z-index: 0;
        }
        .pt-hero-beam-left {
            inset-inline-start: -8%;
            background:
                conic-gradient(from 200deg at 0% 0%,
                    rgba(34,211,238,0.55) 0deg,
                    rgba(34,211,238,0.0) 28deg,
                    transparent 360deg);
        }
        .pt-hero-beam-right {
            inset-inline-end: -8%;
            background:
                conic-gradient(from 110deg at 100% 0%,
                    rgba(192,132,252,0.55) 0deg,
                    rgba(192,132,252,0.0) 28deg,
                    transparent 360deg);
        }
        :root[data-pt-theme="light"] .pt-hero-beam { opacity: 0.35; mix-blend-mode: multiply; }
        :root[data-pt-theme="light"] .pt-hero-beam-left {
            background:
                conic-gradient(from 200deg at 0% 0%,
                    rgba(8,145,178,0.55) 0deg,
                    rgba(8,145,178,0.0) 28deg,
                    transparent 360deg);
        }
        :root[data-pt-theme="light"] .pt-hero-beam-right {
            background:
                conic-gradient(from 110deg at 100% 0%,
                    rgba(124,58,237,0.55) 0deg,
                    rgba(124,58,237,0.0) 28deg,
                    transparent 360deg);
        }

        /* Stage curtain decoration (bottom edge) */
        .pt-hero-curtain {
            position: absolute;
            inset: auto 0 0 0;
            height: 80px;
            background:
                linear-gradient(180deg, transparent, rgba(192,132,252,0.10) 60%, rgba(34,211,238,0.10) 100%);
            mask-image: linear-gradient(180deg, transparent, #000 80%);
            -webkit-mask-image: linear-gradient(180deg, transparent, #000 80%);
            pointer-events: none;
            z-index: 0;
        }

        /* Marquee dots above hero */
        .pt-hero-marquee {
            position: absolute;
            top: 14px;
            inset-inline-start: 0;
            inset-inline-end: 0;
            height: 6px;
            display: flex;
            justify-content: center;
            gap: 14px;
            opacity: 0.5;
        }
        .pt-hero-marquee i {
            width: 6px; height: 6px;
            border-radius: 999px;
            background: var(--prism-neon);
            box-shadow: 0 0 10px rgba(129,140,248,0.65);
            animation: ptMarqueeBlink 2.4s ease-in-out infinite;
        }
        .pt-hero-marquee i:nth-child(2) { animation-delay: .15s; }
        .pt-hero-marquee i:nth-child(3) { animation-delay: .30s; }
        .pt-hero-marquee i:nth-child(4) { animation-delay: .45s; }
        .pt-hero-marquee i:nth-child(5) { animation-delay: .60s; }
        .pt-hero-marquee i:nth-child(6) { animation-delay: .75s; }
        .pt-hero-marquee i:nth-child(7) { animation-delay: .90s; }
        @keyframes ptMarqueeBlink {
            0%, 100% { opacity: 0.4; transform: scale(0.8); }
            50%      { opacity: 1;   transform: scale(1.1); }
        }

        .pt-hero-inner {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            align-items: center;
        }
        @media (min-width: 880px) {
            .pt-hero-inner { grid-template-columns: 1.25fr 1fr; gap: 36px; }
        }

        .pt-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2em;
            color: var(--prism-text-2);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
        }
        :root[data-pt-theme="light"] .pt-hero-eyebrow { background: rgba(15,23,42,0.05); }
        .pt-hero-eyebrow .pt-live-dot {
            width: 7px; height: 7px;
            border-radius: 999px;
            background: #34d399;
            box-shadow: 0 0 10px rgba(52,211,153,0.7);
            animation: ptLiveDot 1.5s ease-in-out infinite;
        }
        @keyframes ptLiveDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.4; transform: scale(0.7); }
        }

        .pt-hero-title {
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.02em;
            font-size: clamp(30px, 5.4vw, 56px);
            margin: 14px 0 6px;
        }
        .pt-hero-title .pt-grad {
            background: var(--prism-neon);
            background-size: 220% 100%;
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
            animation: ptShimmerText 8s linear infinite;
        }
        .pt-hero-title .pt-strike {
            position: relative;
            display: inline-block;
        }
        .pt-hero-title .pt-strike::after {
            content: "";
            position: absolute;
            inset-inline-start: -4%;
            inset-inline-end: -4%;
            bottom: 6px;
            height: 10px;
            border-radius: 6px;
            background: var(--prism-neon-soft);
            z-index: -1;
            opacity: 0.8;
        }
        .pt-hero-sub {
            font-size: clamp(14px, 1.6vw, 18px);
            color: var(--prism-text-2);
            line-height: 1.55;
            max-width: 56ch;
        }

        .pt-hero-ctas {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }
        .pt-hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 22px;
        }
        .pt-hero-stat {
            position: relative;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--prism-border);
        }
        :root[data-pt-theme="light"] .pt-hero-stat { background: rgba(15,23,42,0.03); }
        .pt-hero-stat-num {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 800;
            font-size: clamp(20px, 3vw, 28px);
            line-height: 1;
            background: var(--prism-neon);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
        }
        .pt-hero-stat-label {
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--prism-text-3);
            margin-top: 6px;
        }

        /* Hero ticket-stub motif */
        .pt-hero-art {
            position: relative;
            display: none;
            align-self: stretch;
            min-height: 280px;
        }
        @media (min-width: 880px) {
            .pt-hero-art { display: block; }
        }
        .pt-ticket-stub {
            position: absolute;
            inset: 0;
            margin: auto;
            width: 100%;
            max-width: 360px;
            aspect-ratio: 5 / 7;
            border-radius: 26px;
            border: 1px solid var(--prism-border-strong);
            background:
                radial-gradient(ellipse 60% 40% at 30% 20%, rgba(34,211,238,0.30), transparent 60%),
                radial-gradient(ellipse 60% 40% at 70% 80%, rgba(192,132,252,0.30), transparent 60%),
                linear-gradient(160deg, rgba(20,24,38,0.85), rgba(8,10,20,0.95));
            box-shadow: 0 30px 80px -32px rgba(0,0,0,0.8);
            transform: rotate(-4deg);
            overflow: hidden;
            transition: transform .5s var(--prism-ease);
        }
        :root[data-pt-theme="light"] .pt-ticket-stub {
            background:
                radial-gradient(ellipse 60% 40% at 30% 20%, rgba(8,145,178,0.20), transparent 60%),
                radial-gradient(ellipse 60% 40% at 70% 80%, rgba(124,58,237,0.20), transparent 60%),
                linear-gradient(160deg, rgba(255,255,255,0.96), rgba(248,245,238,0.96));
            box-shadow: 0 30px 80px -32px rgba(15,23,42,0.30);
        }
        @media (hover: hover) {
            .pt-hero:hover .pt-ticket-stub { transform: rotate(-2deg) translateY(-4px); }
        }
        .pt-ticket-stub::before {
            content: "";
            position: absolute;
            top: 18%; bottom: 18%;
            inset-inline-start: 26%;
            width: 1px;
            background: repeating-linear-gradient(180deg, var(--prism-border) 0 6px, transparent 6px 12px);
        }
        .pt-ticket-stub::after {
            content: "";
            position: absolute;
            inset-inline-start: 26%;
            top: 18%;
            transform: translate(-50%, -50%);
            width: 18px; height: 18px;
            border-radius: 999px;
            background: var(--prism-bg-0);
            border: 1px solid var(--prism-border);
            box-shadow: 0 calc(64% - 10px) 0 0 var(--prism-bg-0), 0 calc(64% - 10px) 0 1px var(--prism-border);
        }
        .pt-ticket-stub-body {
            position: absolute;
            inset: 0;
            inset-inline-start: 26%;
            padding: 22px 22px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .pt-ticket-stub-side {
            position: absolute;
            inset: 0;
            inset-inline-end: 74%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pt-ticket-stub-side svg { opacity: 0.85; }
        .pt-ticket-stub-meta {
            display: flex; flex-direction: column; gap: 6px;
        }
        .pt-ticket-stub-row { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; }
        .pt-ticket-stub-label {
            font-size: 9px; letter-spacing: 0.24em; text-transform: uppercase;
            color: var(--prism-text-4);
        }
        .pt-ticket-stub-value {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            color: var(--prism-text);
            font-size: 14px;
        }
        .pt-ticket-stub-headline {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 800;
            color: var(--prism-text);
            letter-spacing: -0.01em;
            line-height: 1;
            font-size: clamp(22px, 3vw, 30px);
            background: var(--prism-neon);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .pt-ticket-stub-footer {
            display: flex; align-items: center; gap: 8px;
            font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase;
            color: var(--prism-text-3);
        }
        .pt-ticket-stub-qr {
            width: 56px; height: 56px;
            border-radius: 8px;
            background:
                conic-gradient(from 45deg, var(--prism-text) 0% 25%, transparent 25% 50%, var(--prism-text) 50% 75%, transparent 75% 100%);
            background-size: 8px 8px;
            border: 1px solid var(--prism-border);
            opacity: 0.85;
        }

        /* Trust marquee */
        .pt-trust {
            margin-top: 24px;
            border-radius: 16px;
            padding: 12px 0;
            background: var(--prism-surface-soft);
            border: 1px solid var(--prism-border);
            overflow: hidden;
            -webkit-mask-image: linear-gradient(90deg, transparent 0%, #000 8%, #000 92%, transparent 100%);
                    mask-image: linear-gradient(90deg, transparent 0%, #000 8%, #000 92%, transparent 100%);
        }
        .pt-trust-track {
            display: flex;
            gap: 36px;
            white-space: nowrap;
            animation: ptTrustScroll 28s linear infinite;
        }
        .pt-trust-track:hover { animation-play-state: paused; }
        .pt-trust-item {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;
            color: var(--prism-text-3);
            font-weight: 600;
        }
        .pt-trust-item svg { color: var(--prism-cyan); }
        @keyframes ptTrustScroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-trust-track { animation: none; }
        }

        /* How it works */
        .pt-how {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        @media (min-width: 720px) {
            .pt-how { grid-template-columns: repeat(3, 1fr); }
        }
        .pt-how-step {
            position: relative;
            padding: 18px 18px 20px;
            border-radius: 18px;
            background:
                linear-gradient(180deg, rgba(20,24,38,0.55), rgba(8,10,20,0.70));
            border: 1px solid var(--prism-border);
            transition: transform .35s var(--prism-ease), border-color .25s var(--prism-ease);
            min-height: 140px;
            overflow: hidden;
        }
        :root[data-pt-theme="light"] .pt-how-step {
            background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.65));
        }
        @media (hover: hover) {
            .pt-how-step:hover { transform: translateY(-3px); border-color: rgba(129,140,248,0.32); }
        }
        .pt-how-step::before {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            padding: 1px;
            background: var(--prism-neon-soft);
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
                    mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            opacity: 0.5;
            pointer-events: none;
        }
        .pt-how-step-num {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 800;
            font-size: 36px;
            line-height: 1;
            background: var(--prism-neon);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
            letter-spacing: -0.02em;
        }
        .pt-how-step-title {
            margin-top: 8px;
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            font-size: 17px;
            color: var(--prism-text);
        }
        .pt-how-step-body {
            margin-top: 6px;
            font-size: 13px;
            line-height: 1.55;
            color: var(--prism-text-3);
        }
        .pt-how-step-icon {
            position: absolute;
            inset-inline-end: 12px;
            top: 14px;
            width: 36px; height: 36px;
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            display: inline-flex; align-items: center; justify-content: center;
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] .pt-how-step-icon { background: rgba(15,23,42,0.04); }

        /* Section heading */
        .pt-section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }
        .pt-section-title {
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-weight: 800;
            letter-spacing: -0.01em;
            font-size: clamp(20px, 3vw, 28px);
            color: var(--prism-text);
        }
        .pt-section-sub {
            font-size: 13px;
            color: var(--prism-text-3);
            margin-top: 4px;
        }

        /* Show grid v2 polish */
        .pt-show-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 22px;
            border: 1px solid var(--prism-border);
            background:
                linear-gradient(180deg, rgba(20,24,38,0.55), rgba(8,10,20,0.70));
            padding: 14px;
            overflow: hidden;
            transition: transform .35s var(--prism-ease), border-color .25s var(--prism-ease), box-shadow .35s var(--prism-ease);
            min-height: 320px;
        }
        :root[data-pt-theme="light"] .pt-show-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.88), rgba(255,255,255,0.70));
            box-shadow: 0 18px 38px -22px rgba(15,23,42,0.18);
        }
        @media (hover: hover) {
            .pt-show-card:hover {
                transform: translateY(-4px);
                border-color: rgba(129,140,248,0.34);
                box-shadow: 0 30px 70px -32px rgba(0,0,0,0.6), 0 0 30px rgba(129,140,248,0.15);
            }
        }
        .pt-show-card-glow {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            padding: 1px;
            background: var(--prism-neon-soft);
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
                    mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            opacity: 0;
            transition: opacity .35s var(--prism-ease);
            pointer-events: none;
        }
        @media (hover: hover) {
            .pt-show-card:hover .pt-show-card-glow { opacity: 0.6; }
        }
        .pt-show-poster {
            position: relative;
            aspect-ratio: 3 / 4;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--prism-border);
            background: var(--prism-bg-1);
        }
        .pt-show-poster img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .6s var(--prism-ease);
        }
        @media (hover: hover) {
            .pt-show-card:hover .pt-show-poster img { transform: scale(1.04); }
        }
        .pt-show-poster-veil {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 50%, rgba(5,6,13,0.55) 100%);
            pointer-events: none;
        }
        :root[data-pt-theme="light"] .pt-show-poster-veil {
            background: linear-gradient(180deg, transparent 50%, rgba(15,23,42,0.45) 100%);
        }
        .pt-show-card-body { padding: 14px 4px 0; }
        .pt-show-card-title {
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: var(--prism-text);
            line-height: 1.25;
        }
        .pt-show-card-desc {
            margin-top: 6px;
            font-size: 13px;
            color: var(--prism-text-2);
            line-height: 1.55;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .pt-show-times {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .pt-show-time {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            font-size: 12px;
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] .pt-show-time { background: rgba(15,23,42,0.04); }
        .pt-show-time-price {
            color: var(--prism-gold);
            font-weight: 700;
            font-family: "Space Grotesk", system-ui, sans-serif;
        }
        :root[data-pt-theme="light"] .pt-show-time-price { color: #b45309; }
        .pt-show-card-foot {
            margin-top: 12px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Featured card layout */
        .pt-featured {
            position: relative;
            display: grid;
            grid-template-columns: 1fr;
            gap: 22px;
            border-radius: 26px;
            border: 1px solid var(--prism-border);
            padding: 18px;
            overflow: hidden;
            background:
                radial-gradient(ellipse 60% 60% at 0% 100%, rgba(34,211,238,0.16), transparent 60%),
                radial-gradient(ellipse 60% 60% at 100% 0%, rgba(192,132,252,0.16), transparent 60%),
                linear-gradient(180deg, rgba(20,24,38,0.55), rgba(8,10,20,0.78));
        }
        :root[data-pt-theme="light"] .pt-featured {
            background:
                radial-gradient(ellipse 60% 60% at 0% 100%, rgba(8,145,178,0.10), transparent 60%),
                radial-gradient(ellipse 60% 60% at 100% 0%, rgba(124,58,237,0.10), transparent 60%),
                linear-gradient(180deg, rgba(255,255,255,0.92), rgba(255,255,255,0.78));
            box-shadow: 0 22px 50px -24px rgba(15,23,42,0.18);
        }
        @media (min-width: 880px) {
            .pt-featured { grid-template-columns: minmax(0, 1.05fr) minmax(0, 1.25fr); padding: 22px; }
        }
        .pt-featured-poster {
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            aspect-ratio: 4 / 5;
            border: 1px solid var(--prism-border);
            background: var(--prism-bg-1);
        }
        .pt-featured-poster img { width: 100%; height: 100%; object-fit: cover; }
        .pt-featured-content { display: flex; flex-direction: column; gap: 12px; min-width: 0; }
        .pt-featured-title {
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-weight: 800;
            letter-spacing: -0.01em;
            font-size: clamp(22px, 3.5vw, 36px);
            color: var(--prism-text);
            line-height: 1.1;
        }

        /* Footer v2 */
        .pt-footer-grid {
            display: grid;
            gap: 22px;
            grid-template-columns: 1fr;
        }
        @media (min-width: 720px) {
            .pt-footer-grid { grid-template-columns: 1.4fr 1fr 1fr; }
        }
        .pt-footer-brand-text {
            font-size: 13px;
            line-height: 1.6;
            color: var(--prism-text-3);
            max-width: 38ch;
        }
        .pt-footer-col-title {
            font-size: 11px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--prism-text-4);
            font-weight: 700;
            margin-bottom: 10px;
        }
        .pt-footer-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--prism-text-2);
            font-size: 13px;
            text-decoration: none;
            padding: 4px 0;
            transition: color .18s var(--prism-ease);
        }
        @media (hover: hover) {
            .pt-footer-link:hover { color: var(--prism-text); }
        }

        /* iOS-safe: ensure content uses small viewport units when needed */
        .pt-min-svh { min-height: 100svh; }
        .pt-min-dvh { min-height: 100dvh; }

        /* Coarse pointer adjustments */
        @media (hover: none) {
            .prism-card-hover:hover,
            .pt-show-card:hover,
            .pt-how-step:hover,
            .pt-brand:hover .pt-brand-logo,
            .pt-theme-toggle:hover { transform: none !important; }
        }
    </style>
</head>
<body class="prism-stage min-h-screen">

    {{-- ============== Floating Cinematic Header ============== --}}
    <div class="pt-topbar-wrap" id="pt-topbar-wrap">
        {{-- Subtle aurora glow line under the bar when scrolled --}}
        <div class="pt-topbar-aurora" aria-hidden="true"></div>

        <header class="pt-topbar" id="pt-topbar" role="banner">
            {{-- Brand block --}}
            <a href="{{ route('shows.index') }}" class="pt-brand group" aria-label="Premium Tickets">
                <span class="pt-brand-logo" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 64 64" fill="none">
                        <defs>
                            <linearGradient id="pt-grad-nav" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#22d3ee"/>
                                <stop offset="0.5" stop-color="#818cf8"/>
                                <stop offset="1" stop-color="#c084fc"/>
                            </linearGradient>
                        </defs>
                        <path d="M32 6 L56 20 L46 56 L18 56 L8 20 Z" fill="none" stroke="url(#pt-grad-nav)" stroke-width="3" stroke-linejoin="round"/>
                        <path d="M32 6 L32 56 M8 20 L56 20 M18 56 L46 56" stroke="url(#pt-grad-nav)" stroke-width="1.5" opacity="0.55"/>
                    </svg>
                    <span class="pt-brand-orb" aria-hidden="true"></span>
                </span>
                <span class="pt-brand-text">
                    <span class="pt-brand-wordmark" data-i18n="brand">PREMIUM</span>
                    <span class="pt-brand-tag" data-i18n="brand_tag">TICKETS · STAGE</span>
                </span>
            </a>

            {{-- Center nav (desktop) --}}
            <nav class="pt-nav" aria-label="Primary">
                <a href="{{ route('shows.index') }}"
                   class="pt-nav-link {{ (request()->routeIs('shows.index') || request()->routeIs('home')) ? 'is-active' : '' }}"
                   data-i18n="nav_home">
                    <span>الرئيسية</span>
                </a>
                <a href="{{ route('shows.index') }}#shows-grid"
                   class="pt-nav-link"
                   data-i18n="nav_shows">
                    <span>العروض</span>
                </a>
                <a href="{{ route('team.apply') }}"
                   class="pt-nav-link {{ request()->routeIs('team.*') ? 'is-active' : '' }}"
                   data-i18n="nav_join">
                    <span>انضم للفريق</span>
                </a>
                @auth
                    @if(auth()->user()->is_admin ?? false)
                        <a href="{{ route('admin.dashboard') }}"
                           class="pt-nav-link pt-nav-link-admin {{ request()->routeIs('admin.*') ? 'is-active' : '' }}"
                           data-i18n="nav_admin">
                            <span>لوحة التحكم</span>
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
                <div class="pt-lang-toggle pt-lang-toggle-desktop" id="pt-lang-toggle" role="group" aria-label="Language">
                    <span class="pt-lang-thumb" id="pt-lang-thumb"></span>
                    <button type="button" data-pt-lang="ar" aria-pressed="true">AR</button>
                    <button type="button" data-pt-lang="en" aria-pressed="false">EN</button>
                </div>

                {{-- Mobile menu button --}}
                <button type="button"
                        class="pt-burger"
                        id="pt-burger"
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
                <span class="pt-brand-logo" aria-hidden="true" style="width:36px;height:36px;">
                    <svg width="20" height="20" viewBox="0 0 64 64" fill="none">
                        <defs>
                            <linearGradient id="pt-grad-drawer" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#22d3ee"/>
                                <stop offset="0.5" stop-color="#818cf8"/>
                                <stop offset="1" stop-color="#c084fc"/>
                            </linearGradient>
                        </defs>
                        <path d="M32 6 L56 20 L46 56 L18 56 L8 20 Z" fill="none" stroke="url(#pt-grad-drawer)" stroke-width="3" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div class="pt-drawer-brand-text">
                    <div class="prism-wordmark" style="font-size:14px;" data-i18n="brand">PREMIUM</div>
                    <div class="prism-tagline" style="font-size:9px;" data-i18n="brand_tag">TICKETS</div>
                </div>
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
            <a href="{{ route('team.apply') }}"
               class="pt-drawer-link {{ request()->routeIs('team.*') ? 'is-active' : '' }}"
               data-i18n="nav_join">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
                <span>انضم للفريق</span>
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
                <div>
                    <a href="{{ route('shows.index') }}" class="pt-brand" aria-label="Premium Tickets" style="padding: 0;">
                        <span class="pt-brand-logo" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 64 64" fill="none">
                                <defs>
                                    <linearGradient id="pt-grad-foot" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0" stop-color="#22d3ee"/>
                                        <stop offset="0.5" stop-color="#818cf8"/>
                                        <stop offset="1" stop-color="#c084fc"/>
                                    </linearGradient>
                                </defs>
                                <path d="M32 6 L56 20 L46 56 L18 56 L8 20 Z" fill="none" stroke="url(#pt-grad-foot)" stroke-width="3" stroke-linejoin="round"/>
                                <path d="M32 6 L32 56 M8 20 L56 20 M18 56 L46 56" stroke="url(#pt-grad-foot)" stroke-width="1.5" opacity="0.55"/>
                            </svg>
                        </span>
                        <span class="pt-brand-text">
                            <span class="pt-brand-wordmark" data-i18n="brand">PREMIUM</span>
                            <span class="pt-brand-tag" data-i18n="brand_tag">TICKETS · STAGE</span>
                        </span>
                    </a>
                    <p class="pt-footer-brand-text mt-3" data-i18n="foot_about">منصة حجز تذاكر مسرح مصرية، مصممة لتجربة فاخرة وسريعة على الموبايل والديسكتوب.</p>
                </div>

                {{-- Quick links --}}
                <div>
                    <div class="pt-footer-col-title" data-i18n="foot_quick">روابط سريعة</div>
                    <div class="flex flex-col">
                        <a class="pt-footer-link" href="{{ route('shows.index') }}" data-i18n="nav_home">الرئيسية</a>
                        <a class="pt-footer-link" href="{{ route('shows.index') }}#shows-grid" data-i18n="nav_shows">العروض</a>
                        <a class="pt-footer-link" href="{{ route('team.apply') }}" data-i18n="nav_join">انضم للفريق</a>
                    </div>
                </div>

                {{-- Trust signals --}}
                <div>
                    <div class="pt-footer-col-title" data-i18n="foot_legal">الدعم</div>
                    <div class="flex flex-wrap gap-2">
                        <span class="prism-pill" data-i18n="foot_fast">حجز فوري</span>
                        <span class="prism-pill" data-i18n="foot_secure">دفع آمن</span>
                        <span class="prism-pill" data-i18n="foot_qr">QR على واتساب</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
                 style="border-top: 1px solid var(--prism-border); color: var(--prism-text-3);">
                <div>© {{ now()->year }} <span class="prism-wordmark text-[11px]" data-i18n="brand">PREMIUM</span></div>
                <div class="opacity-70">v2.0 · {{ config('app.env') === 'production' ? 'live' : config('app.env') }}</div>
            </div>
        </div>
    </footer>

    {{-- ============== Premium modal (singleton) ============== --}}
    <div class="pt-modal-root" id="pt-modal-root" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="pt-modal-backdrop" data-pt-modal-close></div>
        <div class="pt-modal-card" id="pt-modal-card">
            <div class="pt-modal-icon" id="pt-modal-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h0"/></svg>
            </div>
            <div class="pt-modal-title" id="pt-modal-title">تأكيد</div>
            <div class="pt-modal-body"  id="pt-modal-body"></div>
            <div class="pt-modal-actions" id="pt-modal-actions"></div>
        </div>
    </div>

    {{-- ============== Toast ============== --}}
    <div class="pt-toast" id="pt-toast" role="status" aria-live="polite"></div>

    {{-- ============== JS: ripple, modal, lang toggle, scroll reveal, action bar ============== --}}
    <script>
    (function () {
        // ---------- ripple ----------
        document.addEventListener('pointerdown', function (e) {
            const t = e.target.closest('.prism-ripple');
            if (!t) return;
            const r = t.getBoundingClientRect();
            t.style.setProperty('--rx', ((e.clientX - r.left) / r.width * 100) + '%');
            t.style.setProperty('--ry', ((e.clientY - r.top)  / r.height * 100) + '%');
        }, { passive: true });

        // ---------- floating topbar scroll ----------
        const topbar = document.getElementById('pt-topbar');
        const topbarWrap = document.getElementById('pt-topbar-wrap');
        const updateTopbar = () => {
            if (!topbar) return;
            const scrolled = window.scrollY > 6;
            topbar.classList.toggle('is-scrolled', scrolled);
            if (topbarWrap) topbarWrap.classList.toggle('is-scrolled', scrolled);
        };
        window.addEventListener('scroll', updateTopbar, { passive: true });
        updateTopbar();

        // ---------- scroll reveal ----------
        const io = ('IntersectionObserver' in window) ? new IntersectionObserver((entries) => {
            entries.forEach(en => {
                if (en.isIntersecting) {
                    en.target.classList.add('is-in');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.08 }) : null;
        const observeReveals = () => {
            document.querySelectorAll('.pt-reveal:not(.is-in)').forEach(el => {
                if (io) io.observe(el);
                else el.classList.add('is-in');
            });
        };
        observeReveals();

        // ---------- modal API ----------
        const root = document.getElementById('pt-modal-root');
        const card = document.getElementById('pt-modal-card');
        const titleEl = document.getElementById('pt-modal-title');
        const bodyEl = document.getElementById('pt-modal-body');
        const iconEl = document.getElementById('pt-modal-icon');
        const actionsEl = document.getElementById('pt-modal-actions');
        const ICONS = {
            info:    '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h0"/></svg>',
            success: '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 L9 17 L4 12"/></svg>',
            error:   '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6 L18 18 M18 6 L6 18"/></svg>',
            warn:    '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L22 21 L2 21 Z"/><path d="M12 10v4M12 18h0"/></svg>',
            loading: '<div class="pt-modal-spinner"></div>'
        };
        function setTone(tone) {
            iconEl.classList.remove('tone-success','tone-error','tone-warn');
            if (tone === 'success') iconEl.classList.add('tone-success');
            else if (tone === 'error') iconEl.classList.add('tone-error');
            else if (tone === 'warn')  iconEl.classList.add('tone-warn');
            iconEl.innerHTML = ICONS[tone] || ICONS.info;
        }
        function open(opts) {
            opts = opts || {};
            const tone = opts.tone || 'info';
            setTone(tone);
            titleEl.textContent = opts.title || '';
            if (typeof opts.body === 'string') bodyEl.innerHTML = opts.body;
            else { bodyEl.innerHTML = ''; if (opts.body instanceof Node) bodyEl.appendChild(opts.body); }
            actionsEl.innerHTML = '';
            (opts.actions || []).forEach(a => {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = a.label || '';
                let cls = a.variant === 'ghost' ? 'prism-btn-ghost' :
                          a.variant === 'rose'  ? 'prism-btn-rose'  :
                          a.variant === 'emerald' ? 'prism-btn-emerald' :
                          a.variant === 'gold' ? 'prism-btn-gold' :
                          'prism-btn';
                b.className = cls + ' text-xs';
                b.addEventListener('click', () => {
                    let r = true;
                    if (typeof a.onClick === 'function') r = a.onClick();
                    if (r !== false) close();
                });
                actionsEl.appendChild(b);
            });
            root.classList.add('is-open');
            root.setAttribute('aria-hidden','false');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            root.classList.remove('is-open');
            root.setAttribute('aria-hidden','true');
            document.body.style.overflow = '';
        }
        root.addEventListener('click', (e) => {
            if (e.target.matches('[data-pt-modal-close]')) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && root.classList.contains('is-open')) close();
        });

        // ---------- toast ----------
        const toast = document.getElementById('pt-toast');
        let toastTimer = null;
        function showToast(message, ms) {
            toast.textContent = message;
            toast.classList.add('is-on');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('is-on'), ms || 2400);
        }

        // ---------- language toggle ----------
        const I18N = {
            ar: {
                brand: 'PREMIUM', brand_tag: 'TICKETS · STAGE',
                nav_home: 'الرئيسية', nav_shows: 'العروض', nav_join: 'انضم للفريق', nav_admin: 'لوحة التحكم',
                foot_fast: 'حجز فوري', foot_secure: 'دفع آمن', foot_qr: 'QR على واتساب',
                bar_total: 'الإجمالي', bar_seats: 'المقاعد المختارة',
                btn_confirm: 'تأكيد الحجز',
                btn_cancel: 'إلغاء',
                btn_continue: 'متابعة',
                btn_approve: 'تأكيد الحجز',
                btn_reject:  'رفض الحجز',
                modal_processing: 'جارٍ إرسال الطلب...',
                modal_processing_body: 'برجاء الانتظار ثوانٍ دون إغلاق الصفحة.',
                theme_label: 'الوضع', theme_light: 'فاتح', theme_dark: 'داكن',
                lang_label: 'اللغة', cta_browse: 'تصفح العروض',
                hero_eyebrow: 'حجز مباشر · المسرح المصري',
                hero_title_a: 'احجز تجربتك',
                hero_title_b: 'على المسرح',
                hero_sub: 'منصة حجز سلسة وأنيقة: تختار العرض، تحجز مقعدك من الخريطة المباشرة، تدفع بأمان، وتستقبل تذكرتك بكود QR على واتساب.',
                hero_cta_primary: 'تصفح العروض',
                hero_cta_secondary: 'كيف يعمل؟',
                hero_stat_shows_label: 'عرض متاح',
                hero_stat_seats_label: 'مقعد جاهز',
                hero_stat_qr_label: 'تذكرة QR فورية',
                trust_instant: 'حجز فوري', trust_secure: 'دفع آمن',
                trust_qr: 'QR على واتساب', trust_mobile: 'يعمل على الموبايل',
                trust_247: 'متاح 24/7', trust_seat: 'اختيار مقعد مباشر',
                how_title: 'كيف تحجز تذكرتك',
                how_sub: 'ثلاث خطوات بسيطة من الاختيار حتى الواتساب.',
                how_1_t: 'اختر العرض', how_1_b: 'استعرض العروض المتاحة واختر الموعد المناسب.',
                how_2_t: 'احجز مقعدك', how_2_b: 'اختر مقعدك من خريطة القاعة المباشرة وادفع بأمان.',
                how_3_t: 'استقبل التذكرة', how_3_b: 'تذكرة QR تصلك على واتساب في أقل من دقيقة.',
                shows_title: 'العروض المتاحة', shows_sub: 'اختر عرضك وابدأ الحجز.',
                foot_about: 'منصة حجز تذاكر مسرح مصرية، مصممة لتجربة فاخرة وسريعة على الموبايل والديسكتوب.',
                foot_quick: 'روابط سريعة', foot_legal: 'الدعم'
            },
            en: {
                brand: 'PREMIUM', brand_tag: 'TICKETS · STAGE',
                nav_home: 'Home', nav_shows: 'Shows', nav_join: 'Join Team', nav_admin: 'Admin',
                foot_fast: 'Instant booking', foot_secure: 'Secure payment', foot_qr: 'QR via WhatsApp',
                bar_total: 'TOTAL', bar_seats: 'Selected seats',
                btn_confirm: 'Confirm booking',
                btn_cancel: 'Cancel',
                btn_continue: 'Continue',
                btn_approve: 'Approve booking',
                btn_reject:  'Reject booking',
                modal_processing: 'Sending request...',
                modal_processing_body: 'Please hold on a moment, do not close the page.',
                theme_label: 'Theme', theme_light: 'Light', theme_dark: 'Dark',
                lang_label: 'Language', cta_browse: 'Browse shows',
                hero_eyebrow: 'Live booking · Egyptian stage',
                hero_title_a: 'Book your seat',
                hero_title_b: 'on stage',
                hero_sub: 'A premium ticketing experience: pick a show, choose your seat from a live map, pay securely, and get your QR ticket on WhatsApp.',
                hero_cta_primary: 'Browse shows',
                hero_cta_secondary: 'How it works',
                hero_stat_shows_label: 'live shows',
                hero_stat_seats_label: 'seats ready',
                hero_stat_qr_label: 'instant QR tickets',
                trust_instant: 'Instant booking', trust_secure: 'Secure payment',
                trust_qr: 'QR via WhatsApp', trust_mobile: 'Mobile-first',
                trust_247: 'Available 24/7', trust_seat: 'Live seat picker',
                how_title: 'How it works',
                how_sub: 'Three simple steps from pick to WhatsApp.',
                how_1_t: 'Pick a show', how_1_b: 'Browse available shows and choose your date.',
                how_2_t: 'Choose seats', how_2_b: 'Pick your seats from a live theater map and pay securely.',
                how_3_t: 'Receive ticket', how_3_b: 'A QR ticket arrives on WhatsApp in under a minute.',
                shows_title: 'Available shows', shows_sub: 'Pick a show and start booking.',
                foot_about: 'Egyptian theater ticketing platform built for a fast, premium experience on mobile and desktop.',
                foot_quick: 'Quick links', foot_legal: 'Support'
            }
        };
        // Match all lang toggle button groups (desktop + mobile drawer)
        const langButtons = document.querySelectorAll('.pt-lang-toggle button[data-pt-lang]');
        function moveThumbForGroup(group, lang) {
            const thumb = group.querySelector('.pt-lang-thumb');
            if (!thumb) return;
            const target = group.querySelector('button[data-pt-lang="' + lang + '"]');
            if (!target) return;
            const wrap = group.getBoundingClientRect();
            const r = target.getBoundingClientRect();
            thumb.style.width = r.width + 'px';
            const offset = r.left - wrap.left;
            thumb.style.transform = 'translateX(' + offset + 'px)';
        }
        function applyLang(lang) {
            const dict = I18N[lang] || I18N.ar;
            document.documentElement.setAttribute('data-pt-lang', lang);
            document.documentElement.lang = lang;
            document.documentElement.dir  = (lang === 'en') ? 'ltr' : 'rtl';
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const k = el.getAttribute('data-i18n');
                if (dict[k] !== undefined) el.textContent = dict[k];
            });
            langButtons.forEach(b => {
                const on = b.getAttribute('data-pt-lang') === lang;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            document.querySelectorAll('.pt-lang-toggle').forEach(group => moveThumbForGroup(group, lang));
            try { localStorage.setItem('pt-lang', lang); } catch(_){}
            document.dispatchEvent(new CustomEvent('pt:langchange', { detail: { lang } }));
        }
        langButtons.forEach(b => b.addEventListener('click', () => applyLang(b.getAttribute('data-pt-lang'))));
        let initLang = 'ar';
        try { initLang = localStorage.getItem('pt-lang') || 'ar'; } catch(_){}
        applyLang(initLang);
        // re-position thumb after fonts load + on resize
        window.addEventListener('load', () => applyLang(document.documentElement.getAttribute('data-pt-lang') || 'ar'));
        window.addEventListener('resize', () => applyLang(document.documentElement.getAttribute('data-pt-lang') || 'ar'));

        // ---------- theme toggle ----------
        function applyTheme(theme, persist) {
            theme = theme === 'light' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-pt-theme', theme);
            const meta = document.getElementById('pt-theme-color');
            if (meta) meta.setAttribute('content', theme === 'light' ? '#f4f1ea' : '#05060d');
            document.querySelectorAll('.pt-theme-segment button[data-pt-theme-set]').forEach(b => {
                const on = b.getAttribute('data-pt-theme-set') === theme;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            if (persist) { try { localStorage.setItem('pt-theme', theme); } catch(_){} }
            document.dispatchEvent(new CustomEvent('pt:themechange', { detail: { theme } }));
        }
        const themeBtn = document.getElementById('pt-theme-toggle');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const cur = document.documentElement.getAttribute('data-pt-theme') || 'dark';
                applyTheme(cur === 'dark' ? 'light' : 'dark', true);
            });
        }
        document.querySelectorAll('.pt-theme-segment button[data-pt-theme-set]').forEach(b => {
            b.addEventListener('click', () => applyTheme(b.getAttribute('data-pt-theme-set'), true));
        });
        // Sync segment with currently-active theme on load (the early bootstrap script already set the attribute)
        applyTheme(document.documentElement.getAttribute('data-pt-theme') || 'dark', false);
        // React to system pref changes only when user has not explicitly picked one
        try {
            const mq = window.matchMedia('(prefers-color-scheme: light)');
            const onMQ = (e) => {
                if (!localStorage.getItem('pt-theme')) applyTheme(e.matches ? 'light' : 'dark', false);
            };
            if (mq.addEventListener) mq.addEventListener('change', onMQ);
            else if (mq.addListener) mq.addListener(onMQ);
        } catch(_) {}

        // ---------- mobile drawer ----------
        const drawer = document.getElementById('pt-drawer');
        const drawerBackdrop = document.getElementById('pt-drawer-backdrop');
        const burger = document.getElementById('pt-burger');
        const drawerClose = document.getElementById('pt-drawer-close');
        function openDrawer() {
            if (!drawer) return;
            document.body.classList.add('pt-drawer-open');
            drawer.setAttribute('aria-hidden', 'false');
            if (burger) burger.setAttribute('aria-expanded', 'true');
            // lock background scroll while preserving position
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            if (!drawer) return;
            document.body.classList.remove('pt-drawer-open');
            drawer.setAttribute('aria-hidden', 'true');
            if (burger) burger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
        if (burger) burger.addEventListener('click', () => {
            if (document.body.classList.contains('pt-drawer-open')) closeDrawer(); else openDrawer();
        });
        if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeDrawer);
        if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.body.classList.contains('pt-drawer-open')) closeDrawer();
        });
        // Close drawer when a drawer link is tapped
        document.querySelectorAll('.pt-drawer-link').forEach(a => {
            a.addEventListener('click', () => closeDrawer());
        });
        // Close drawer when window is resized to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 880 && document.body.classList.contains('pt-drawer-open')) closeDrawer();
        });

        // ---------- expose API ----------
        window.PT = window.PT || {};
        window.PT.modal = { open, close };
        window.PT.toast = showToast;
        window.PT.observeReveals = observeReveals;
        window.PT.t = (k) => (I18N[document.documentElement.getAttribute('data-pt-lang') || 'ar'] || {})[k] || k;
        window.PT.lang = () => document.documentElement.getAttribute('data-pt-lang') || 'ar';
        window.PT.theme = () => document.documentElement.getAttribute('data-pt-theme') || 'dark';
        window.PT.setTheme = applyTheme;
    })();

    // ---------- intercept forms with data-pt-confirm ----------
    (function () {
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            const cfg = form.getAttribute('data-pt-confirm');
            if (!cfg || form.dataset.ptConfirmed === '1') return;
            e.preventDefault();
            let opts;
            try { opts = JSON.parse(cfg); } catch (_) { opts = { title: cfg }; }
            const tone = opts.tone || 'warn';
            const title = opts.title || (window.PT.lang() === 'en' ? 'Are you sure?' : 'هل أنت متأكد؟');
            const body  = opts.body  || '';
            const okLabel    = opts.okLabel    || (window.PT.lang() === 'en' ? 'Continue' : 'متابعة');
            const cancelLabel = opts.cancelLabel || (window.PT.lang() === 'en' ? 'Cancel' : 'إلغاء');
            const okVariant = opts.okVariant || 'emerald';
            window.PT.modal.open({
                tone, title, body,
                actions: [
                    { label: cancelLabel, variant: 'ghost' },
                    { label: okLabel, variant: okVariant, onClick: () => {
                        // show loading and then submit
                        window.PT.modal.open({
                            tone: 'info',
                            title: window.PT.t('modal_processing'),
                            body: window.PT.t('modal_processing_body'),
                            actions: []
                        });
                        // tell modal icon to show spinner
                        const ic = document.getElementById('pt-modal-icon');
                        ic.innerHTML = '<div class="pt-modal-spinner"></div>';
                        form.dataset.ptConfirmed = '1';
                        setTimeout(() => form.submit(), 60);
                        return false;
                    } }
                ]
            });
        });
    })();
    </script>

    @stack('scripts')
</body>
</html>
