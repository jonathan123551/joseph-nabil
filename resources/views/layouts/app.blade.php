<!DOCTYPE html>
<html lang="ar" dir="rtl" data-pt-lang="ar">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Premium Tickets')</title>
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

        /* ------------- Sticky action bar ------------- */
        .pt-action-bar {
            position: fixed;
            left: 0; right: 0;
            bottom: 0;
            z-index: 45;
            padding: 10px 14px calc(10px + env(safe-area-inset-bottom)) 14px;
            transform: translateY(140%);
            opacity: 0;
            pointer-events: none;
            transition: transform .45s var(--prism-ease), opacity .35s var(--prism-ease);
        }
        .pt-action-bar.is-on {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        .pt-action-bar-inner {
            max-width: 920px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(20,24,38,0.78), rgba(8,10,20,0.88));
            border: 1px solid rgba(129,140,248,0.35);
            backdrop-filter: blur(22px) saturate(170%);
            -webkit-backdrop-filter: blur(22px) saturate(170%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 -16px 60px -20px rgba(0,0,0,0.75),
                0 0 30px rgba(129,140,248,0.18);
        }
        .pt-action-bar .pt-bar-summary {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .pt-action-bar .pt-bar-actions {
            flex: 0 0 auto;
            display: flex;
            gap: 8px;
        }
        .pt-action-bar .pt-bar-label {
            font-size: 10px;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: var(--prism-text-4);
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
            font-size: 11px;
            color: var(--prism-text-3);
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        @media (max-width: 480px) {
            .pt-action-bar-inner {
                gap: 8px;
                padding: 10px;
            }
            .pt-action-bar .pt-bar-total { font-size: 16px; }
            .pt-action-bar .pt-bar-actions .prism-btn,
            .pt-action-bar .pt-bar-actions .prism-btn-emerald,
            .pt-action-bar .pt-bar-actions .prism-btn-rose {
                padding: 8px 12px;
                font-size: 12px;
            }
        }

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
            transition: transform .35s var(--prism-ease);
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

        /* ------------- Page transition ------------- */
        @keyframes ptPageIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        main.pt-page { animation: ptPageIn .55s var(--prism-ease) both; }

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

    {{-- ============== Floating Top Bar ============== --}}
    <div class="pt-topbar-wrap" id="pt-topbar-wrap">
        <div class="pt-topbar" id="pt-topbar">
            {{-- Brand --}}
            <a href="{{ route('shows.index') }}" class="flex items-center gap-2.5 group" aria-label="Premium Tickets">
                <span class="prism-logo" style="width:34px;height:34px;">
                    <svg width="20" height="20" viewBox="0 0 64 64" fill="none" aria-hidden="true">
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
                </span>
                <div class="leading-tight hidden xs:block sm:block">
                    <div class="prism-wordmark" style="font-size:13px;" data-i18n="brand">PREMIUM</div>
                    <div class="prism-tagline" style="font-size:8.5px;" data-i18n="brand_tag">TICKETS</div>
                </div>
            </a>

            {{-- Nav center / right --}}
            <div class="flex items-center gap-1">
                <a href="{{ route('shows.index') }}"
                   class="pt-top-link {{ (request()->routeIs('shows.*') || request()->routeIs('home')) ? 'is-active' : '' }}"
                   data-i18n="nav_home">
                    الرئيسية
                </a>

                @auth
                    @if(auth()->user()->is_admin ?? false)
                        <a href="{{ route('admin.dashboard') }}"
                           class="pt-top-link {{ request()->routeIs('admin.*') ? 'is-active' : '' }}"
                           data-i18n="nav_admin">
                            لوحة التحكم
                        </a>
                    @endif
                @endauth
            </div>

            {{-- Right cluster: language toggle --}}
            <div class="flex items-center gap-2">
                <div class="pt-lang-toggle" id="pt-lang-toggle" role="group" aria-label="Language">
                    <span class="pt-lang-thumb" id="pt-lang-thumb"></span>
                    <button type="button" data-pt-lang="ar" aria-pressed="true">AR</button>
                    <button type="button" data-pt-lang="en" aria-pressed="false">EN</button>
                </div>
            </div>
        </div>
    </div>

    {{-- spacer keeps content below the floating bar --}}
    <div class="pt-topbar-spacer" aria-hidden="true"></div>

    {{-- ============== Main ============== --}}
    <main class="pt-page max-w-5xl mx-auto px-4 py-6 md:py-10 prism-fade-in" id="pt-main">
        @yield('content')
    </main>

    {{-- ============== Footer ============== --}}
    <footer class="prism-footer">
        <div class="max-w-5xl mx-auto px-4 py-6 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-[color:var(--prism-text-3)]">
            <div class="flex items-center gap-2">
                <span class="prism-wordmark text-[12px]" data-i18n="brand">PREMIUM</span>
                <span>© {{ now()->year }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="prism-pill" data-i18n="foot_fast">حجز فوري</span>
                <span class="prism-pill" data-i18n="foot_secure">دفع آمن</span>
                <span class="prism-pill" data-i18n="foot_qr">QR على واتساب</span>
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
        const updateTopbar = () => {
            if (!topbar) return;
            if (window.scrollY > 6) topbar.classList.add('is-scrolled');
            else topbar.classList.remove('is-scrolled');
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
                brand: 'PREMIUM', brand_tag: 'TICKETS',
                nav_home: 'الرئيسية', nav_admin: 'لوحة التحكم',
                foot_fast: 'حجز فوري', foot_secure: 'دفع آمن', foot_qr: 'QR على واتساب',
                bar_total: 'الإجمالي', bar_seats: 'المقاعد المختارة',
                btn_confirm: 'تأكيد الحجز',
                btn_cancel: 'إلغاء',
                btn_continue: 'متابعة',
                btn_approve: 'تأكيد الحجز',
                btn_reject:  'رفض الحجز',
                modal_processing: 'جارٍ إرسال الطلب...',
                modal_processing_body: 'برجاء الانتظار ثوانٍ دون إغلاق الصفحة.'
            },
            en: {
                brand: 'PREMIUM', brand_tag: 'TICKETS',
                nav_home: 'Home', nav_admin: 'Admin',
                foot_fast: 'Instant booking', foot_secure: 'Secure payment', foot_qr: 'QR via WhatsApp',
                bar_total: 'TOTAL', bar_seats: 'Selected seats',
                btn_confirm: 'Confirm booking',
                btn_cancel: 'Cancel',
                btn_continue: 'Continue',
                btn_approve: 'Approve booking',
                btn_reject:  'Reject booking',
                modal_processing: 'Sending request...',
                modal_processing_body: 'Please hold on a moment, do not close the page.'
            }
        };
        const langButtons = document.querySelectorAll('#pt-lang-toggle button[data-pt-lang]');
        const langThumb = document.getElementById('pt-lang-thumb');
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
            // move thumb
            if (langThumb) {
                const target = Array.from(langButtons).find(b => b.getAttribute('data-pt-lang') === lang);
                if (target) {
                    const wrap = target.parentElement.getBoundingClientRect();
                    const r    = target.getBoundingClientRect();
                    langThumb.style.width  = r.width + 'px';
                    const offset = r.left - wrap.left;
                    langThumb.style.transform = 'translateX(' + offset + 'px)';
                }
            }
            try { localStorage.setItem('pt-lang', lang); } catch(_){}
            document.dispatchEvent(new CustomEvent('pt:langchange', { detail: { lang } }));
        }
        langButtons.forEach(b => b.addEventListener('click', () => applyLang(b.getAttribute('data-pt-lang'))));
        let initLang = 'ar';
        try { initLang = localStorage.getItem('pt-lang') || 'ar'; } catch(_){}
        applyLang(initLang);
        // re-position thumb after fonts load
        window.addEventListener('load', () => applyLang(document.documentElement.getAttribute('data-pt-lang') || 'ar'));
        window.addEventListener('resize', () => applyLang(document.documentElement.getAttribute('data-pt-lang') || 'ar'));

        // ---------- expose API ----------
        window.PT = window.PT || {};
        window.PT.modal = { open, close };
        window.PT.toast = showToast;
        window.PT.observeReveals = observeReveals;
        window.PT.t = (k) => (I18N[document.documentElement.getAttribute('data-pt-lang') || 'ar'] || {})[k] || k;
        window.PT.lang = () => document.documentElement.getAttribute('data-pt-lang') || 'ar';
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
