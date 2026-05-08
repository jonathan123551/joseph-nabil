<!DOCTYPE html>
<html lang="ar" dir="rtl" data-pt-lang="ar" data-pt-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Premium Tickets')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060d" id="pt-theme-color">

    {{-- Inline SVG favicon — neutral premium identity --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%2322d3ee'/><stop offset='0.5' stop-color='%23818cf8'/><stop offset='1' stop-color='%23c084fc'/></linearGradient></defs><path d='M32 6 L56 20 L46 56 L18 56 L8 20 Z' fill='none' stroke='url(%23g)' stroke-width='3' stroke-linejoin='round'/><path d='M32 6 L32 56 M8 20 L56 20 M18 56 L46 56' stroke='url(%23g)' stroke-width='1.5' opacity='0.6'/></svg>">

    {{-- Theme + language bootstrap (runs before paint to avoid FOUC / RTL flash) --}}
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
            try {
                var lang = localStorage.getItem('pt-lang');
                if (lang !== 'ar' && lang !== 'en') lang = 'ar';
                document.documentElement.setAttribute('data-pt-lang', lang);
                document.documentElement.setAttribute('lang', lang);
                document.documentElement.setAttribute('dir', lang === 'en' ? 'ltr' : 'rtl');
            } catch (e) { /* keep AR default */ }
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

        /* ------------- Wave 1: copy / share / heart / ribbons ------------- */
        /* Copyable value: tap target with subtle hover lift + flash on copy */
        .prism-copyable {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 10px;
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            border: 1px dashed rgba(129,140,248,0.35);
            color: var(--prism-text);
            font-weight: 700;
            cursor: pointer;
            transition: background .15s var(--prism-ease), border-color .15s var(--prism-ease), transform .15s var(--prism-ease);
            min-height: 36px;
            -webkit-tap-highlight-color: transparent;
        }
        .prism-copyable:hover { background: rgba(129,140,248,0.10); border-color: rgba(129,140,248,0.6); }
        .prism-copyable:active { transform: scale(0.97); }
        .prism-copyable .copy-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px;
            border-radius: 6px;
            background: rgba(34,211,238,0.14);
            border: 1px solid rgba(34,211,238,0.35);
            color: #67e8f9;
            font-size: 12px;
            transition: background .15s var(--prism-ease), color .15s var(--prism-ease);
        }
        .prism-copyable.is-copied { border-color: rgba(52,211,153,0.7); background: rgba(16,185,129,0.10); }
        .prism-copyable.is-copied .copy-icon {
            background: rgba(16,185,129,0.18);
            border-color: rgba(52,211,153,0.7);
            color: #6ee7b7;
        }

        /* Heart-to-favorite button (used on show cards) */
        .prism-heart-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px;
            border-radius: 999px;
            background: rgba(8,10,20,0.55);
            border: 1px solid rgba(255,255,255,0.10);
            color: rgba(255,255,255,0.78);
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            transition: transform .15s var(--prism-ease), background .15s var(--prism-ease), border-color .15s var(--prism-ease), color .15s var(--prism-ease);
            -webkit-tap-highlight-color: transparent;
        }
        .prism-heart-btn:hover { transform: scale(1.08); background: rgba(244,63,94,0.18); border-color: rgba(251,113,133,0.5); color: #fecdd3; }
        .prism-heart-btn[aria-pressed="true"] {
            background: rgba(244,63,94,0.20);
            border-color: rgba(251,113,133,0.7);
            color: #fb7185;
            box-shadow: 0 0 14px rgba(251,113,133,0.35);
        }
        .prism-heart-btn[aria-pressed="true"] .heart-glyph::before { content: '♥'; }
        .prism-heart-btn .heart-glyph::before { content: '♡'; }
        @media (prefers-reduced-motion: reduce) {
            .prism-heart-btn:hover { transform: none; }
        }

        /* Show-card ribbon (selling fast / last N / trending) */
        .prism-ribbon {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px; font-weight: 800;
            letter-spacing: .04em;
            line-height: 1.2;
            white-space: nowrap;
            border: 1px solid rgba(255,255,255,0.18);
            color: var(--prism-text);
            background: rgba(8,10,20,0.55);
            box-shadow: 0 4px 14px -4px rgba(2,6,23,0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .prism-ribbon-trending {
            background: linear-gradient(135deg, rgba(34,211,238,0.30), rgba(129,140,248,0.30));
            border-color: rgba(129,140,248,0.55);
            color: #e0e7ff;
        }
        .prism-ribbon-fast {
            background: linear-gradient(135deg, rgba(245,158,11,0.32), rgba(251,113,133,0.20));
            border-color: rgba(251,191,36,0.55);
            color: #fef3c7;
        }
        .prism-ribbon-last {
            background: linear-gradient(135deg, rgba(244,63,94,0.36), rgba(190,18,60,0.28));
            border-color: rgba(251,113,133,0.65);
            color: #ffe4e6;
            animation: prismRibbonPulse 2.4s ease-in-out infinite;
        }
        @keyframes prismRibbonPulse {
            0%, 100% { box-shadow: 0 4px 14px -4px rgba(2,6,23,0.6), 0 0 0 0 rgba(251,113,133,0); }
            50%      { box-shadow: 0 4px 14px -4px rgba(2,6,23,0.6), 0 0 0 6px rgba(251,113,133,0.18); }
        }
        @media (prefers-reduced-motion: reduce) {
            .prism-ribbon-last { animation: none; }
        }

        /* Skip-to-shows pill (homepage intro for return visitors) */
        .prism-skip-pill {
            display: none;
            position: absolute;
            inset-inline-start: 50%;
            bottom: clamp(24px, 6vh, 60px);
            transform: translateX(-50%);
            z-index: 5;
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 12px; font-weight: 700;
            letter-spacing: .04em;
            color: #e0e7ff;
            background: linear-gradient(135deg, rgba(34,211,238,0.22), rgba(129,140,248,0.22));
            border: 1px solid rgba(129,140,248,0.55);
            box-shadow: 0 8px 24px -8px rgba(129,140,248,0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            text-decoration: none;
            opacity: 0;
            transition: transform .2s var(--prism-ease), background .2s var(--prism-ease), opacity .2s var(--prism-ease);
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .prism-skip-pill.is-shown,
        body.is-return-visitor .prism-skip-pill { display: inline-flex; opacity: 1; }
        .prism-skip-pill:hover { transform: translateX(-50%) translateY(-2px); background: linear-gradient(135deg, rgba(34,211,238,0.32), rgba(129,140,248,0.32)); }
        :root[dir="rtl"] .prism-skip-pill { transform: translateX(50%); }
        :root[dir="rtl"] .prism-skip-pill:hover { transform: translateX(50%) translateY(-2px); }

        /* WhatsApp share button — small green pill, fits inline next to other meta pills */
        .prism-share-wa {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px; font-weight: 700;
            color: #d1fae5;
            background: linear-gradient(135deg, rgba(16,185,129,0.22), rgba(5,150,105,0.18));
            border: 1px solid rgba(52,211,153,0.55);
            box-shadow: 0 4px 14px -4px rgba(16,185,129,0.4);
            text-decoration: none;
            transition: transform .15s var(--prism-ease), background .15s var(--prism-ease), border-color .15s var(--prism-ease);
            -webkit-tap-highlight-color: transparent;
            line-height: 1.2;
        }
        .prism-share-wa:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, rgba(16,185,129,0.32), rgba(5,150,105,0.26));
            border-color: rgba(52,211,153,0.85);
        }
        .prism-share-wa .share-wa-icon {
            display: inline-flex; align-items: center; justify-content: center;
            color: #6ee7b7;
        }
        @media (prefers-reduced-motion: reduce) {
            .prism-share-wa:hover { transform: none; }
        }

        /* Auto-pick best-seats button (seat picker side panel) */
        .prism-auto-pick {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 9px 14px;
            border-radius: 12px;
            font-size: 12px; font-weight: 700;
            color: #fef3c7;
            background: linear-gradient(135deg, rgba(245,158,11,0.20), rgba(251,191,36,0.10));
            border: 1px solid rgba(251,191,36,0.55);
            box-shadow: 0 4px 14px -4px rgba(245,158,11,0.45);
            cursor: pointer;
            transition: transform .15s var(--prism-ease), background .15s var(--prism-ease), border-color .15s var(--prism-ease);
            -webkit-tap-highlight-color: transparent;
            min-height: 38px;
        }
        .prism-auto-pick:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, rgba(245,158,11,0.30), rgba(251,191,36,0.16));
            border-color: rgba(251,191,36,0.8);
        }
        .prism-auto-pick:active { transform: scale(0.98); }
        @media (prefers-reduced-motion: reduce) {
            .prism-auto-pick:hover { transform: none; }
        }

        /* Saved shows pill in topbar (subtle counter, currently unused but
           reserved for the §6.1 hamburger-sheet wave) */
        .prism-fav-counter-pill {
            display: none;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px; font-weight: 700;
            color: #fecdd3;
            background: rgba(244,63,94,0.14);
            border: 1px solid rgba(251,113,133,0.45);
            white-space: nowrap;
        }
        .prism-fav-counter-pill.is-shown,
        .prism-fav-counter-pill[data-count]:not([data-count="0"]) { display: inline-flex; }

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
        /* Native CSS sticky pins the bar to the viewport bottom while the
           page is scrolling, then settles at the bar's natural position
           (end of the yielded content) once the user scrolls past it. The
           browser handles pin/settle on the compositor — no main-thread
           scroll math, no jitter when iOS Safari's URL bar collapses or on
           momentum scroll. Same approach as the customer checkout dock
           (PR #34). Note: switching to sticky escapes the containing-block
           trap caused by `main.pt-page` having a transform animation —
           with `position: fixed` the bar was anchored to main's bottom
           instead of the viewport, which is why it only became visible
           near the bottom of the page. */
        .pt-action-bar {
            position: sticky;
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

        /* ------------- Premium center-screen toast -------------
           Lightweight glass success/error popup. Used after admin actions
           that don't navigate away (e.g. resend ticket). Subtle radial
           scrim, springy card entrance, animated icon, auto-dismiss +
           tap-to-dismiss. Avoids generic flash banners and intrusive
           fullscreen modals. */
        .pt-toast-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 110;
            padding: 24px;
            pointer-events: none;
            opacity: 0;
            transition: opacity .25s var(--prism-ease);
            background: radial-gradient(circle at center,
                rgba(2,6,23,0.36) 0%,
                rgba(2,6,23,0.0) 65%);
        }
        /* Class-selector specificity matches `[hidden]` (both 0,1,0), so the
           user-agent rule for [hidden] would otherwise tie. Explicit rule
           guarantees the toast stays hidden until JS opens it. */
        .pt-toast-overlay[hidden] { display: none !important; }
        .pt-toast-overlay.is-on {
            opacity: 1;
            pointer-events: auto;
        }
        .pt-toast-card {
            background: linear-gradient(180deg, rgba(20,24,38,0.94), rgba(8,10,20,0.97));
            border: 1px solid rgba(52,211,153,0.45);
            border-radius: 22px;
            padding: 22px 28px 20px;
            max-width: 320px;
            width: 100%;
            text-align: center;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.07),
                0 30px 60px -20px rgba(0,0,0,0.7),
                0 0 50px rgba(52,211,153,0.22);
            backdrop-filter: blur(22px) saturate(180%);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            transform: translateY(8px) scale(.96);
            opacity: 0;
            transition:
                transform .35s cubic-bezier(.2,1.2,.2,1),
                opacity   .25s var(--prism-ease);
        }
        .pt-toast-overlay.is-on .pt-toast-card {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .pt-toast-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(52,211,153,0.26), rgba(34,211,238,0.20));
            border: 1px solid rgba(52,211,153,0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 24px rgba(52,211,153,0.35);
        }
        .pt-toast-icon svg {
            width: 28px;
            height: 28px;
            stroke: var(--prism-emerald);
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .pt-toast-icon svg path {
            stroke-dasharray: 32;
            stroke-dashoffset: 32;
            animation: ptCheckDraw .55s var(--prism-ease) .12s forwards;
        }
        @keyframes ptCheckDraw { to { stroke-dashoffset: 0; } }
        .pt-toast-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--prism-text);
            line-height: 1.35;
            margin-bottom: 4px;
        }
        .pt-toast-msg {
            font-size: 13px;
            color: var(--prism-text-2);
            line-height: 1.45;
        }
        /* Error variant */
        .pt-toast-card.is-error {
            border-color: rgba(244,63,94,0.45);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.07),
                0 30px 60px -20px rgba(0,0,0,0.7),
                0 0 50px rgba(244,63,94,0.22);
        }
        .pt-toast-card.is-error .pt-toast-icon {
            background: linear-gradient(135deg, rgba(244,63,94,0.26), rgba(251,113,133,0.20));
            border-color: rgba(244,63,94,0.55);
            box-shadow: 0 0 24px rgba(244,63,94,0.35);
        }
        .pt-toast-card.is-error .pt-toast-icon svg {
            stroke: var(--prism-rose);
        }
        :root[data-pt-theme="light"] .pt-toast-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.98));
            border-color: rgba(16,185,129,0.45);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.7),
                0 30px 60px -20px rgba(15,23,42,0.30),
                0 0 50px rgba(16,185,129,0.18);
        }
        :root[data-pt-theme="light"] .pt-toast-overlay {
            background: radial-gradient(circle at center,
                rgba(15,23,42,0.18) 0%,
                rgba(15,23,42,0.0) 65%);
        }
        :root[data-pt-theme="light"] .pt-toast-card.is-error {
            border-color: rgba(244,63,94,0.50);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.7),
                0 30px 60px -20px rgba(15,23,42,0.30),
                0 0 50px rgba(244,63,94,0.20);
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-toast-card,
            .pt-toast-overlay { transition: opacity .15s linear; transform: none; }
            .pt-toast-overlay.is-on .pt-toast-card { transform: none; }
            .pt-toast-icon svg path {
                animation: none;
                stroke-dashoffset: 0;
            }
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

        /* ========================================================
           Admin form helpers
           Reusable building blocks for the admin create/edit
           forms (shows + show times). Provides consistent
           card-based grouping, comfortable touch ergonomics, and
           a sticky save bar on mobile so primary actions stay
           reachable on long forms.
           ======================================================== */

        /* A grouped form section card (basic info, pricing, scheduling…). */
        .pt-form-section {
            background:
                linear-gradient(180deg, rgba(20,24,38,0.55), rgba(8,10,20,0.65));
            border: 1px solid var(--prism-border);
            border-radius: 18px;
            padding: 18px 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: relative;
            isolation: isolate;
        }
        .pt-form-section + .pt-form-section { margin-top: 14px; }

        @media (min-width: 640px) {
            .pt-form-section { padding: 22px; gap: 16px; }
        }

        /* Eyebrow row above section content — small icon + title + optional hint. */
        .pt-form-section-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 6px;
            border-bottom: 1px dashed rgba(129,140,248,0.18);
        }
        .pt-form-section-head-icon {
            width: 28px; height: 28px; border-radius: 9px;
            display: inline-flex; align-items: center; justify-content: center;
            background:
                linear-gradient(135deg, rgba(34,211,238,0.18), rgba(192,132,252,0.18));
            border: 1px solid rgba(129,140,248,0.45);
            color: var(--prism-text);
            font-size: 14px;
            box-shadow: 0 0 14px rgba(129,140,248,0.18);
        }
        .pt-form-section-head-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--prism-text);
            letter-spacing: 0.01em;
        }
        .pt-form-section-head-sub {
            font-size: 11px;
            color: var(--prism-text-3);
            margin-inline-start: auto;
        }

        /* Single column on mobile, two columns at ≥ sm. Used for date/time
           rows, price/total rows, etc. */
        .pt-form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        @media (min-width: 640px) {
            .pt-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        }
        .pt-form-grid-3 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        @media (min-width: 640px) {
            .pt-form-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        /* Label/input/helper triplet. The label has a bit more breathing
           room and the helper text sits muted below the input. */
        .pt-form-field { display: flex; flex-direction: column; gap: 6px; }
        .pt-form-field-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--prism-text-2);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .pt-form-field-label .pt-form-req { color: var(--prism-rose); font-weight: 700; }
        .pt-form-helper {
            font-size: 11px;
            color: var(--prism-text-3);
            line-height: 1.55;
        }

        /* Drop-zone style file upload. Wraps a native <input type="file">. */
        .pt-file-zone {
            position: relative;
            border-radius: 14px;
            border: 1px dashed rgba(129,140,248,0.45);
            background:
                radial-gradient(120% 80% at 50% 0%, rgba(34,211,238,0.06), transparent 60%),
                rgba(8,10,20,0.45);
            padding: 18px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-align: center;
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease);
            min-height: 120px;
        }
        .pt-file-zone:hover, .pt-file-zone:focus-within {
            border-color: rgba(34,211,238,0.6);
            background:
                radial-gradient(120% 80% at 50% 0%, rgba(34,211,238,0.12), transparent 60%),
                rgba(8,10,20,0.55);
        }
        .pt-file-zone-icon {
            width: 38px; height: 38px; border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, rgba(34,211,238,0.22), rgba(192,132,252,0.22));
            border: 1px solid rgba(129,140,248,0.45);
            color: var(--prism-text);
        }
        .pt-file-zone-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--prism-text);
        }
        .pt-file-zone-sub {
            font-size: 11px;
            color: var(--prism-text-3);
        }
        .pt-file-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        /* Form action bar.
           - On mobile: docks to the bottom of the viewport with a glass
             pill, respecting safe-area insets, so the primary save CTA is
             always reachable on long forms.
           - On ≥ md: collapses to an inline row at the natural form
             position. */
        .pt-form-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .pt-form-actions > .pt-form-actions-primary {
            margin-inline-start: auto;
        }
        .pt-form-actions-sticky {
            position: sticky;
            bottom: 0;
            z-index: 30;
            margin: 4px -4px 0 -4px;
            padding: 10px 12px calc(10px + env(safe-area-inset-bottom)) 12px;
            background:
                linear-gradient(180deg, rgba(8,10,20,0) 0%, rgba(8,10,20,0.85) 35%, rgba(8,10,20,0.95) 100%);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
            border-top: 1px solid rgba(129,140,248,0.22);
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .pt-form-actions-sticky > * { flex: 1; min-height: 48px; }
        @media (min-width: 768px) {
            .pt-form-actions-sticky {
                position: static;
                margin: 0;
                padding: 0;
                background: none;
                border: none;
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }
            .pt-form-actions-sticky > * { flex: initial; }
            .pt-form-actions-sticky .pt-form-actions-primary { margin-inline-start: auto; }
        }

        /* Theater-type radio cards. Larger touch targets, glow when checked. */
        .pt-radio-group {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        @media (min-width: 480px) {
            .pt-radio-group { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        .pt-radio-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(8,10,20,0.45);
            border: 1px solid var(--prism-border);
            color: var(--prism-text);
            cursor: pointer;
            transition: border-color .2s var(--prism-ease), background .2s var(--prism-ease), box-shadow .2s var(--prism-ease);
        }
        .pt-radio-card:hover { border-color: var(--prism-border-strong); background: rgba(129,140,248,0.06); }
        .pt-radio-card input[type="radio"] { width: 18px; height: 18px; accent-color: #818cf8; }
        .pt-radio-card:has(input:checked) {
            border-color: rgba(129,140,248,0.7);
            background: linear-gradient(135deg, rgba(34,211,238,0.10), rgba(192,132,252,0.10)), rgba(8,10,20,0.6);
            box-shadow: 0 0 0 3px rgba(129,140,248,0.12), 0 8px 30px -12px rgba(34,211,238,0.35);
        }

        /* Inline status switch — used on show-time edit. Same stop motion as
           the existing custom switch but tidier and bigger touch area. */
        .pt-switch-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 56px;
            padding: 10px 14px;
            border-radius: 14px;
            background: rgba(8,10,20,0.45);
            border: 1px solid var(--prism-border);
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

        /* ========================================================
           RTL/LTR helpers — only impact LTR mode so Arabic stays
           visually identical to current production.
        ======================================================== */
        /* Direction-aware text alignment: stays right in RTL, flips left in LTR. */
        .pt-rtl-text { text-align: right; }
        :root[dir="ltr"] .pt-rtl-text,
        :root[data-pt-lang="en"] .pt-rtl-text { text-align: left; }

        /* Decorative arrow (e.g. "→ back") that should visually point the
           same way regardless of direction. AR keeps the original glyph,
           LTR flips it. */
        .pt-arrow-rtl { display: inline-block; }
        :root[dir="ltr"] .pt-arrow-rtl,
        :root[data-pt-lang="en"] .pt-arrow-rtl { transform: scaleX(-1); }

        /* List bullets that hug the start edge: 5px padding + dot pinned to
           the right in RTL, mirrored to the left in LTR. */
        .pt-rtl-bullet { padding-right: 1.25rem; }
        .pt-rtl-bullet::before { right: 0; }
        :root[dir="ltr"] .pt-rtl-bullet,
        :root[data-pt-lang="en"] .pt-rtl-bullet {
            padding-right: 0;
            padding-left: 1.25rem;
        }
        :root[dir="ltr"] .pt-rtl-bullet::before,
        :root[data-pt-lang="en"] .pt-rtl-bullet::before {
            right: auto;
            left: 0;
        }

        /* ============================================================
           CINEMATIC HOMEPAGE LAYER (.pt-cinema-*)
           Scoped to homepage only — opt-in via classes on the
           homepage view. Pure CSS, GPU-friendly transforms, with
           prefers-reduced-motion fallback.
        ============================================================ */

        /* Ambient atmosphere wrapping the hero — large soft radial
           orbs that drift slowly, plus a faint grid line. Does not
           intercept pointer events. */
        .pt-cinema-atmos {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
            border-radius: inherit;
        }
        .pt-cinema-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.55;
            mix-blend-mode: screen;
            will-change: transform, opacity;
            animation: ptCinemaOrb 18s ease-in-out infinite;
        }
        :root[data-pt-theme="light"] .pt-cinema-orb {
            opacity: 0.35;
            mix-blend-mode: multiply;
            filter: blur(70px);
        }
        .pt-cinema-orb-a {
            width: 420px; height: 420px;
            top: -120px; inset-inline-start: -100px;
            background: radial-gradient(circle, rgba(34,211,238,0.55), rgba(34,211,238,0) 70%);
            animation-duration: 22s;
        }
        .pt-cinema-orb-b {
            width: 360px; height: 360px;
            bottom: -120px; inset-inline-end: -80px;
            background: radial-gradient(circle, rgba(192,132,252,0.50), rgba(192,132,252,0) 70%);
            animation-duration: 26s;
            animation-delay: -6s;
        }
        .pt-cinema-orb-c {
            width: 280px; height: 280px;
            top: 30%; inset-inline-start: 55%;
            background: radial-gradient(circle, rgba(129,140,248,0.40), rgba(129,140,248,0) 70%);
            animation-duration: 20s;
            animation-delay: -12s;
        }
        @keyframes ptCinemaOrb {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
            33%      { transform: translate3d(28px, -18px, 0) scale(1.05); }
            66%      { transform: translate3d(-22px, 24px, 0) scale(0.95); }
        }

        /* Subtle particle dust — tiny CSS-only specks that drift
           upward. Cheap, GPU-only, no JS. */
        .pt-cinema-particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 1;
        }
        .pt-cinema-particles span {
            position: absolute;
            width: 3px; height: 3px;
            border-radius: 50%;
            background: var(--prism-cyan);
            opacity: 0;
            box-shadow: 0 0 8px currentColor;
            animation: ptCinemaParticle 12s linear infinite;
        }
        .pt-cinema-particles span:nth-child(1)  { left: 8%;  animation-delay: -1s;  background: rgba(34,211,238,0.7); }
        .pt-cinema-particles span:nth-child(2)  { left: 18%; animation-delay: -3s;  background: rgba(129,140,248,0.7); }
        .pt-cinema-particles span:nth-child(3)  { left: 28%; animation-delay: -5s;  background: rgba(192,132,252,0.65); }
        .pt-cinema-particles span:nth-child(4)  { left: 42%; animation-delay: -7s;  background: rgba(34,211,238,0.55); }
        .pt-cinema-particles span:nth-child(5)  { left: 55%; animation-delay: -9s;  background: rgba(129,140,248,0.7); }
        .pt-cinema-particles span:nth-child(6)  { left: 65%; animation-delay: -2s;  background: rgba(192,132,252,0.65); }
        .pt-cinema-particles span:nth-child(7)  { left: 75%; animation-delay: -4s;  background: rgba(34,211,238,0.7); }
        .pt-cinema-particles span:nth-child(8)  { left: 85%; animation-delay: -6s;  background: rgba(129,140,248,0.6); }
        .pt-cinema-particles span:nth-child(9)  { left: 92%; animation-delay: -8s;  background: rgba(192,132,252,0.6); }
        .pt-cinema-particles span:nth-child(10) { left: 35%; animation-delay: -10s; background: rgba(34,211,238,0.5); }
        @keyframes ptCinemaParticle {
            0%   { transform: translateY(110%) scale(0.6); opacity: 0; }
            10%  { opacity: 0.8; }
            85%  { opacity: 0.5; }
            100% { transform: translateY(-30%) scale(1); opacity: 0; }
        }

        /* Soft floating motion for hero copy/art — barely perceptible,
           expensive-feel idle animation. */
        .pt-cinema-float {
            animation: ptCinemaFloat 7s ease-in-out infinite;
            will-change: transform;
        }
        .pt-cinema-float-slow {
            animation: ptCinemaFloat 11s ease-in-out infinite;
            will-change: transform;
        }
        @keyframes ptCinemaFloat {
            0%, 100% { transform: translate3d(0, 0, 0); }
            50%      { transform: translate3d(0, -6px, 0); }
        }

        /* Scroll storytelling — 4 alternating glass cards with deep
           glassmorphism, layered depth, and subtle hover lift. */
        .pt-cinema-story {
            position: relative;
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }
        @media (min-width: 720px) {
            .pt-cinema-story { gap: 22px; }
        }
        .pt-cinema-story::before {
            content: "";
            position: absolute;
            inset-inline-start: 50%;
            top: 6%;
            bottom: 6%;
            width: 1px;
            background: linear-gradient(180deg,
                rgba(34,211,238,0) 0%,
                rgba(34,211,238,0.30) 18%,
                rgba(129,140,248,0.30) 50%,
                rgba(192,132,252,0.30) 82%,
                rgba(192,132,252,0) 100%);
            opacity: 0;
            pointer-events: none;
            transform: translateX(-50%);
        }
        @media (min-width: 880px) {
            .pt-cinema-story::before { opacity: 0.65; }
        }
        :root[data-pt-theme="light"] .pt-cinema-story::before {
            background: linear-gradient(180deg,
                rgba(14,165,233,0) 0%,
                rgba(14,165,233,0.25) 18%,
                rgba(99,102,241,0.25) 50%,
                rgba(168,85,247,0.25) 82%,
                rgba(168,85,247,0) 100%);
        }

        .pt-cinema-step {
            position: relative;
            border-radius: 24px;
            border: 1px solid var(--prism-border);
            background:
                linear-gradient(180deg, rgba(20,24,38,0.55), rgba(8,10,20,0.72));
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            padding: 22px 22px 24px;
            overflow: hidden;
            transition: transform .5s var(--prism-ease), border-color .35s var(--prism-ease), box-shadow .5s var(--prism-ease);
            will-change: transform;
        }
        :root[data-pt-theme="light"] .pt-cinema-step {
            background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.65));
        }
        @media (min-width: 880px) {
            .pt-cinema-step { padding: 28px 28px 32px; width: min(540px, 92%); }
            .pt-cinema-step:nth-child(odd)  { justify-self: start; }
            .pt-cinema-step:nth-child(even) { justify-self: end; }
        }

        /* Soft neon edge that brightens on hover */
        .pt-cinema-step::before {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(135deg,
                rgba(34,211,238,0.55),
                rgba(129,140,248,0.55),
                rgba(192,132,252,0.55));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
                    mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            opacity: 0.45;
            pointer-events: none;
            transition: opacity .4s var(--prism-ease);
        }
        /* Inner glow blob anchored to top-right (or top-left in LTR) */
        .pt-cinema-step::after {
            content: "";
            position: absolute;
            inset-inline-end: -60px;
            top: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(34,211,238,0.25), rgba(34,211,238,0) 70%);
            pointer-events: none;
            opacity: 0.6;
            transition: opacity .5s var(--prism-ease), transform .8s var(--prism-ease);
        }
        .pt-cinema-step:nth-child(2)::after { background: radial-gradient(circle, rgba(129,140,248,0.30), rgba(129,140,248,0) 70%); }
        .pt-cinema-step:nth-child(3)::after { background: radial-gradient(circle, rgba(192,132,252,0.30), rgba(192,132,252,0) 70%); }
        .pt-cinema-step:nth-child(4)::after { background: radial-gradient(circle, rgba(52,211,153,0.28), rgba(52,211,153,0) 70%); }

        @media (hover: hover) {
            .pt-cinema-step:hover {
                transform: translateY(-4px);
                border-color: rgba(129,140,248,0.40);
                box-shadow:
                    0 18px 60px -20px rgba(34,211,238,0.30),
                    0 8px 24px -12px rgba(129,140,248,0.30);
            }
            .pt-cinema-step:hover::before { opacity: 0.85; }
            .pt-cinema-step:hover::after  { opacity: 0.95; transform: scale(1.10); }
        }

        .pt-cinema-step-head {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 10px;
        }
        .pt-cinema-step-num {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.22em;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(34,211,238,0.12);
            color: var(--prism-cyan);
            border: 1px solid rgba(34,211,238,0.35);
        }
        .pt-cinema-step:nth-child(2) .pt-cinema-step-num {
            background: rgba(129,140,248,0.12);
            color: var(--prism-text);
            border-color: rgba(129,140,248,0.45);
        }
        .pt-cinema-step:nth-child(3) .pt-cinema-step-num {
            background: rgba(192,132,252,0.12);
            color: #e9d5ff;
            border-color: rgba(192,132,252,0.45);
        }
        .pt-cinema-step:nth-child(4) .pt-cinema-step-num {
            background: rgba(52,211,153,0.14);
            color: #a7f3d0;
            border-color: rgba(52,211,153,0.45);
        }
        :root[data-pt-theme="light"] .pt-cinema-step:nth-child(3) .pt-cinema-step-num { color: #7e22ce; }
        :root[data-pt-theme="light"] .pt-cinema-step:nth-child(4) .pt-cinema-step-num { color: #047857; }

        .pt-cinema-step-emoji {
            font-size: 28px;
            line-height: 1;
            filter: drop-shadow(0 4px 14px rgba(34,211,238,0.35));
            transition: transform .5s var(--prism-ease);
        }
        @media (hover: hover) {
            .pt-cinema-step:hover .pt-cinema-step-emoji { transform: scale(1.10) rotate(-4deg); }
        }
        .pt-cinema-step-title {
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-weight: 800;
            font-size: clamp(18px, 2.4vw, 22px);
            letter-spacing: -0.01em;
            color: var(--prism-text);
            margin-top: 2px;
        }
        .pt-cinema-step-body {
            margin-top: 8px;
            font-size: 13.5px;
            line-height: 1.65;
            color: var(--prism-text-3);
            max-width: 46ch;
        }

        /* Visual mock — small abstract mockups that hint at each step */
        .pt-cinema-step-visual {
            position: relative;
            margin-top: 16px;
            height: 88px;
            border-radius: 14px;
            border: 1px solid var(--prism-border);
            background:
                linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.0));
            overflow: hidden;
        }
        :root[data-pt-theme="light"] .pt-cinema-step-visual {
            background: linear-gradient(180deg, rgba(15,23,42,0.04), rgba(15,23,42,0));
        }
        .pt-cinema-step-visual::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                repeating-linear-gradient(90deg,
                    rgba(255,255,255,0.04) 0 1px, transparent 1px 16px),
                repeating-linear-gradient(0deg,
                    rgba(255,255,255,0.04) 0 1px, transparent 1px 16px);
            opacity: 0.6;
            pointer-events: none;
        }
        :root[data-pt-theme="light"] .pt-cinema-step-visual::before {
            background:
                repeating-linear-gradient(90deg,
                    rgba(15,23,42,0.06) 0 1px, transparent 1px 16px),
                repeating-linear-gradient(0deg,
                    rgba(15,23,42,0.06) 0 1px, transparent 1px 16px);
        }
        .pt-cinema-step-visual-row {
            position: absolute;
            inset-inline-start: 14px;
            inset-inline-end: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .pt-cinema-step-visual-row.is-row-1 { top: 16px; }
        .pt-cinema-step-visual-row.is-row-2 { top: 44px; }
        .pt-cinema-step-visual-bar {
            height: 8px; border-radius: 4px;
            background: linear-gradient(90deg, rgba(34,211,238,0.55), rgba(129,140,248,0.55));
            box-shadow: 0 0 14px rgba(34,211,238,0.35);
            animation: ptCinemaScan 3.6s ease-in-out infinite;
            transform-origin: left center;
        }
        .pt-cinema-step:nth-child(2) .pt-cinema-step-visual-bar { background: linear-gradient(90deg, rgba(129,140,248,0.6), rgba(192,132,252,0.6)); }
        .pt-cinema-step:nth-child(3) .pt-cinema-step-visual-bar { background: linear-gradient(90deg, rgba(192,132,252,0.6), rgba(244,114,182,0.55)); }
        .pt-cinema-step:nth-child(4) .pt-cinema-step-visual-bar { background: linear-gradient(90deg, rgba(52,211,153,0.6), rgba(34,211,238,0.6)); }
        @keyframes ptCinemaScan {
            0%, 100% { transform: scaleX(0.55); opacity: 0.6; }
            50%      { transform: scaleX(1);    opacity: 1;   }
        }
        .pt-cinema-step-visual-bar.is-bar-a { width: 56%; }
        .pt-cinema-step-visual-bar.is-bar-b { width: 30%; opacity: 0.65; }
        .pt-cinema-step-visual-bar.is-bar-c { width: 70%; }
        .pt-cinema-step-visual-bar.is-bar-d { width: 22%; opacity: 0.55; }

        /* Layered tilt for the available shows cards. Adds gentle 3D
           perspective on hover; ignored on touch. */
        @media (hover: hover) {
            .pt-show-card.pt-cinema-tilt {
                transition:
                    transform .5s var(--prism-ease),
                    border-color .25s var(--prism-ease),
                    box-shadow .5s var(--prism-ease);
                will-change: transform;
                transform-style: preserve-3d;
            }
            .pt-show-card.pt-cinema-tilt:hover {
                transform: translateY(-6px) rotateX(2.5deg) rotateY(-2deg);
                box-shadow:
                    0 24px 60px -22px rgba(34,211,238,0.32),
                    0 12px 28px -12px rgba(129,140,248,0.28);
            }
            .pt-show-card.pt-cinema-tilt:hover .pt-show-poster img {
                transform: scale(1.06);
            }
        }

        /* Premium reveal upgrade — slightly richer than .pt-reveal,
           used for the cinematic hero/story sections. */
        .pt-cinema-reveal {
            opacity: 0;
            transform: translateY(28px) scale(0.985);
            transition: opacity .9s var(--prism-ease), transform .9s var(--prism-ease);
            will-change: opacity, transform;
        }
        .pt-cinema-reveal.is-in {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .pt-cinema-reveal-l { transform: translate3d(-32px, 18px, 0) scale(0.985); }
        .pt-cinema-reveal-l.is-in { transform: translate3d(0, 0, 0) scale(1); }
        .pt-cinema-reveal-r { transform: translate3d(32px, 18px, 0) scale(0.985); }
        .pt-cinema-reveal-r.is-in { transform: translate3d(0, 0, 0) scale(1); }

        /* Reduce / disable cinematic motion for users who prefer it
           and on coarse-pointer devices that struggle with blur. */
        @media (prefers-reduced-motion: reduce) {
            .pt-cinema-orb,
            .pt-cinema-particles span,
            .pt-cinema-float,
            .pt-cinema-float-slow,
            .pt-cinema-step-visual-bar { animation: none !important; }
            .pt-cinema-reveal,
            .pt-cinema-reveal-l,
            .pt-cinema-reveal-r {
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }
        }
        /* On low-end mobile, soften blurs to keep frame rate up. */
        @media (max-width: 540px) {
            .pt-cinema-orb { filter: blur(40px); opacity: 0.45; }
            .pt-cinema-step { backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
        }

        /* ============================================================
           CINEMATIC HOMEPAGE LAYER v2 (motion upgrade)
           Adds: hero spotlight cursor glow, scroll-parallax orbs,
           staggered hero entrance reveal, pointer-tracked 3D tilt,
           glass-reflection sheen sweep, magnetic CTAs, and rich
           per-card storytelling mocks (poster shimmer, seat grid
           pulse, upload arc, QR reveal sweep). All scoped behind
           hover + reduced-motion + touch guards.
        ============================================================ */

        /* Hero scoped spotlight cursor glow — soft radial light tracks
           the pointer across .pt-hero only. Fades when pointer leaves. */
        .pt-cinema-spot {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 2;
            opacity: 0;
            transition: opacity .35s var(--prism-ease);
            background:
                radial-gradient(360px circle at var(--pt-spot-x, 50%) var(--pt-spot-y, 50%),
                    rgba(34,211,238,0.16),
                    rgba(129,140,248,0.10) 28%,
                    transparent 60%);
            mix-blend-mode: screen;
        }
        .pt-cinema-spot.is-on { opacity: 1; }
        :root[data-pt-theme="light"] .pt-cinema-spot {
            background:
                radial-gradient(360px circle at var(--pt-spot-x, 50%) var(--pt-spot-y, 50%),
                    rgba(14,165,233,0.20),
                    rgba(99,102,241,0.12) 28%,
                    transparent 60%);
            mix-blend-mode: multiply;
        }

        /* Scroll parallax for hero orbs — JS writes --pt-parallax,
           and we recompose the existing orb keyframe so the wandering
           drift continues on top of the parallax offset. */
        @keyframes ptCinemaOrb {
            0%, 100% { transform: translate3d(0, var(--pt-parallax, 0px), 0) scale(1); }
            33%      { transform: translate3d(28px, calc(var(--pt-parallax, 0px) - 18px), 0) scale(1.05); }
            66%      { transform: translate3d(-22px, calc(var(--pt-parallax, 0px) + 24px), 0) scale(0.95); }
        }

        /* Staggered hero entrance — eyebrow, title, sub, CTAs, stats
           cascade in with blur+scale. JS adds .is-in on first frame. */
        .pt-cinema-stagger > * {
            opacity: 0;
            transform: translateY(14px) scale(0.97);
            filter: blur(8px);
            transition: opacity .9s var(--prism-ease),
                        transform .9s var(--prism-ease),
                        filter   .9s var(--prism-ease);
            will-change: opacity, transform, filter;
        }
        .pt-cinema-stagger.is-in > * {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: blur(0);
        }
        .pt-cinema-stagger.is-in > *:nth-child(1) { transition-delay: .04s; }
        .pt-cinema-stagger.is-in > *:nth-child(2) { transition-delay: .14s; }
        .pt-cinema-stagger.is-in > *:nth-child(3) { transition-delay: .26s; }
        .pt-cinema-stagger.is-in > *:nth-child(4) { transition-delay: .38s; }
        .pt-cinema-stagger.is-in > *:nth-child(5) { transition-delay: .50s; }

        /* 3D pointer-tracked tilt on storytelling cards. JS sets --pt-rx,
           --pt-ry, --pt-ty; CSS composes the transform. Only when
           .is-tilting is present, so it cleanly composes with reveals. */
        @media (hover: hover) {
            .pt-cinema-step.is-tilting {
                transform:
                    perspective(900px)
                    rotateX(var(--pt-rx, 0deg))
                    rotateY(var(--pt-ry, 0deg))
                    translateY(var(--pt-ty, -4px));
                transition: transform .12s linear,
                            border-color .25s var(--prism-ease),
                            box-shadow .5s var(--prism-ease);
                border-color: rgba(129,140,248,0.45);
                box-shadow:
                    0 22px 70px -22px rgba(34,211,238,0.34),
                    0 12px 32px -14px rgba(129,140,248,0.32);
            }
            .pt-cinema-step.is-tilting::before { opacity: 0.95; }
            .pt-cinema-step.is-tilting::after  { opacity: 0.95; transform: scale(1.12); }
            .pt-cinema-step.is-tilting .pt-cinema-step-emoji { transform: scale(1.10) rotate(-4deg); }
        }

        /* Glass sheen sweep — diagonal highlight bar travels across
           the card once per hover. */
        .pt-cinema-step-sheen {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            border-radius: inherit;
            z-index: 4;
        }
        .pt-cinema-step-sheen::before {
            content: "";
            position: absolute;
            top: -50%;
            inset-inline-start: -120%;
            width: 60%;
            height: 200%;
            background: linear-gradient(115deg,
                transparent 35%,
                rgba(255,255,255,0.18) 50%,
                transparent 65%);
            transform: translate3d(0, 0, 0);
            transition: transform 1s var(--prism-ease), opacity .25s var(--prism-ease);
            opacity: 0;
        }
        @media (hover: hover) {
            .pt-cinema-step:hover .pt-cinema-step-sheen::before {
                transform: translate3d(280%, 0, 0);
                opacity: 1;
            }
        }

        /* Magnetic hover — subtle pull toward the cursor. JS writes
           --pt-mx and --pt-my and toggles .is-magnet. We use the
           standalone CSS `translate` property (composed AFTER any
           `transform` from existing :hover rules) so the magnet
           layers on top of the existing hover lift instead of
           fighting it on specificity. */
        .pt-cinema-magnet {
            transition: translate .25s var(--prism-ease);
            will-change: translate;
        }
        .pt-cinema-magnet.is-magnet {
            translate: var(--pt-mx, 0px) var(--pt-my, 0px);
        }

        /* ====================================
           Per-card storytelling mocks (Card 1..4)
        ==================================== */

        /* Card 1 — three poster panels floating up/down out of phase. */
        .pt-cinema-mock-posters {
            position: absolute;
            inset: 0;
        }
        .pt-cinema-mock-poster {
            position: absolute;
            top: 12px; bottom: 12px;
            width: 24%;
            border-radius: 8px;
            box-shadow: 0 0 22px -6px rgba(34,211,238,0.45);
            animation: ptCinemaPosterFloat 4.6s ease-in-out infinite;
            opacity: 0.9;
        }
        .pt-cinema-mock-poster::after {
            content: "";
            position: absolute;
            inset: 22% 18% 18% 18%;
            background: linear-gradient(180deg, rgba(255,255,255,0.18), transparent 60%);
            border-radius: 4px;
        }
        .pt-cinema-mock-poster.is-p1 {
            inset-inline-start: 8%;
            animation-delay: 0s;
            background: linear-gradient(160deg, rgba(34,211,238,0.55), rgba(129,140,248,0.42));
        }
        .pt-cinema-mock-poster.is-p2 {
            inset-inline-start: 38%;
            animation-delay: -1.5s;
            background: linear-gradient(160deg, rgba(129,140,248,0.55), rgba(192,132,252,0.42));
        }
        .pt-cinema-mock-poster.is-p3 {
            inset-inline-start: 68%;
            animation-delay: -3s;
            background: linear-gradient(160deg, rgba(192,132,252,0.55), rgba(244,114,182,0.40));
        }
        @keyframes ptCinemaPosterFloat {
            0%, 100% { transform: translate3d(0, 0, 0); }
            50%      { transform: translate3d(0, -7px, 0); }
        }

        /* Card 2 — seat grid (10x4) with one row pulse-glowing as if
           seats are being selected. */
        .pt-cinema-mock-seats {
            position: absolute;
            inset: 14px 18px;
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 4px;
        }
        .pt-cinema-mock-seats span {
            border-radius: 3px;
            background: rgba(255,255,255,0.10);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
        }
        :root[data-pt-theme="light"] .pt-cinema-mock-seats span {
            background: rgba(15,23,42,0.10);
            box-shadow: inset 0 0 0 1px rgba(15,23,42,0.06);
        }
        .pt-cinema-mock-seats span.is-pick {
            background: rgba(34,211,238,0.55);
            box-shadow: 0 0 10px rgba(34,211,238,0.55);
            animation: ptCinemaSeatPulse 1.9s ease-in-out infinite;
        }
        .pt-cinema-mock-seats span.is-pick.is-late { animation-delay: -0.5s; }
        @keyframes ptCinemaSeatPulse {
            0%, 100% { background: rgba(34,211,238,0.55); box-shadow: 0 0 10px rgba(34,211,238,0.55); }
            50%      { background: rgba(129,140,248,0.70); box-shadow: 0 0 18px rgba(129,140,248,0.70); }
        }

        /* Card 3 — animated upload bar + check pulse on completion. */
        .pt-cinema-mock-upload {
            position: absolute;
            inset: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .pt-cinema-mock-upload-bar {
            position: relative;
            width: 70%; height: 6px;
            border-radius: 999px;
            background: rgba(255,255,255,0.10);
            overflow: hidden;
        }
        :root[data-pt-theme="light"] .pt-cinema-mock-upload-bar { background: rgba(15,23,42,0.10); }
        .pt-cinema-mock-upload-bar::after {
            content: "";
            position: absolute;
            inset-inline-start: 0; top: 0;
            width: 30%; height: 100%;
            background: linear-gradient(90deg, rgba(192,132,252,0.75), rgba(244,114,182,0.70));
            box-shadow: 0 0 14px rgba(192,132,252,0.6);
            animation: ptCinemaUploadFill 3.6s ease-in-out infinite;
            border-radius: 999px;
        }
        @keyframes ptCinemaUploadFill {
            0%   { width: 4%;   opacity: 0.6; }
            70%  { width: 96%;  opacity: 1;   }
            85%  { width: 100%; opacity: 1;   }
            100% { width: 100%; opacity: 0;   }
        }
        .pt-cinema-mock-upload-check {
            position: absolute;
            inset-inline-end: calc(15% - 12px);
            top: 50%;
            transform: translateY(-50%);
            width: 24px; height: 24px;
            border-radius: 50%;
            background: rgba(52,211,153,0.18);
            border: 1px solid rgba(52,211,153,0.55);
            display: flex; align-items: center; justify-content: center;
            color: #6ee7b7;
            opacity: 0;
            animation: ptCinemaCheckPulse 3.6s ease-in-out infinite;
        }
        @keyframes ptCinemaCheckPulse {
            0%, 80% { opacity: 0; transform: translateY(-50%) scale(0.7); }
            85%     { opacity: 1; transform: translateY(-50%) scale(1.18); box-shadow: 0 0 18px rgba(52,211,153,0.55); }
            95%     { opacity: 1; transform: translateY(-50%) scale(1); }
            100%    { opacity: 0; transform: translateY(-50%) scale(1); }
        }

        /* Card 4 — QR module grid (8x4) that fills with stagger,
           plus a horizontal light sweep across the grid. */
        .pt-cinema-mock-qr {
            position: absolute;
            inset: 12px;
            border-radius: 6px;
            overflow: hidden;
            background: rgba(15,23,42,0.18);
        }
        :root[data-pt-theme="light"] .pt-cinema-mock-qr { background: rgba(15,23,42,0.05); }
        .pt-cinema-mock-qr-grid {
            position: absolute;
            inset: 0;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 2px;
            padding: 4px;
        }
        .pt-cinema-mock-qr-grid span {
            background: rgba(52,211,153,0.55);
            border-radius: 1px;
            opacity: 0;
            animation: ptCinemaQrFill 4.4s ease-in-out infinite;
        }
        @keyframes ptCinemaQrFill {
            0%, 100% { opacity: 0; transform: scale(0.65); }
            10%      { opacity: 0.95; transform: scale(1); }
            65%      { opacity: 0.95; transform: scale(1); }
            80%      { opacity: 0.4; }
        }
        .pt-cinema-mock-qr-sweep {
            position: absolute;
            inset: 0;
            background: linear-gradient(115deg,
                transparent 30%, rgba(255,255,255,0.45) 50%, transparent 70%);
            transform: translate3d(-100%, 0, 0);
            animation: ptCinemaQrSweep 4.4s linear infinite;
            mix-blend-mode: screen;
            pointer-events: none;
        }
        @keyframes ptCinemaQrSweep {
            0%, 60%  { transform: translate3d(-100%, 0, 0); }
            80%      { transform: translate3d(120%, 0, 0); }
            100%     { transform: translate3d(120%, 0, 0); }
        }

        /* Reduced motion v2 — freeze new motion classes too. */
        @media (prefers-reduced-motion: reduce) {
            .pt-cinema-spot { display: none; }
            .pt-cinema-mock-poster,
            .pt-cinema-mock-seats span.is-pick,
            .pt-cinema-mock-upload-bar::after,
            .pt-cinema-mock-upload-check,
            .pt-cinema-mock-qr-grid span,
            .pt-cinema-mock-qr-sweep,
            .pt-cinema-step-sheen::before {
                animation: none !important;
                transition: none !important;
            }
            .pt-cinema-stagger > * {
                opacity: 1 !important;
                transform: none !important;
                filter: none !important;
                transition: none !important;
            }
            .pt-cinema-step.is-tilting {
                transform: none !important;
            }
            .pt-cinema-magnet,
            .pt-cinema-magnet.is-magnet {
                transform: none !important;
            }
        }

        /* ============================================================
           CINEMATIC HOMEPAGE LAYER v3 (full-screen scene shell)
           Mobile-first scroll-driven story. Each scene is 100svh and
           animates in via IntersectionObserver — JS toggles .is-active
           on the section. The floating nav fades out while the intro
           scene is in view for a true full-screen opener. All effects
           scoped under body.is-pt-cine so other pages are untouched.
        ============================================================ */

        /* Floating nav fades out while intro is visible */
        body.is-pt-cine .pt-topbar-wrap {
            transition: opacity .55s var(--prism-ease), transform .55s var(--prism-ease);
        }
        body.is-pt-cine.has-cine-intro-active .pt-topbar-wrap {
            opacity: 0;
            pointer-events: none;
            transform: translateY(-12px);
        }

        /* Container — homepage only */
        body.is-pt-cine .pt-cine {
            position: relative;
            isolation: isolate;
        }

        /* Edge-to-edge bleed under the existing layout container */
        body.is-pt-cine main.prism-stage,
        body.is-pt-cine .prism-container,
        body.is-pt-cine .pt-app-shell-main {
            padding-inline: 0 !important;
            max-width: none !important;
        }

        /* Scene shell — 100svh full-screen sections */
        .pt-cine-scene {
            position: relative;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 96px 22px 80px;
            overflow: hidden;
            isolation: isolate;
        }
        @supports not (height: 100svh) {
            .pt-cine-scene { min-height: 100vh; }
        }
        @media (min-width: 768px) {
            .pt-cine-scene { padding: 120px 64px 96px; }
        }

        /* Backgrounds */
        .pt-cine-bg {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .pt-cine-orb {
            position: absolute;
            width: 60vmin;
            height: 60vmin;
            border-radius: 999px;
            filter: blur(60px);
            opacity: 0.5;
            will-change: transform, opacity;
            animation: ptCineOrbDrift 14s ease-in-out infinite;
        }
        .pt-cine-orb-a { background: radial-gradient(circle, #22d3ee 0%, transparent 70%); top: -10%; inset-inline-start: -10%; }
        .pt-cine-orb-b { background: radial-gradient(circle, #818cf8 0%, transparent 70%); bottom: -15%; inset-inline-end: -15%; animation-delay: -4s; }
        .pt-cine-orb-c { background: radial-gradient(circle, #c084fc 0%, transparent 70%); top: 30%; inset-inline-end: 10%; opacity: 0.35; animation-delay: -8s; width: 40vmin; height: 40vmin; }
        .pt-cine-orb-d { background: radial-gradient(circle, #34d399 0%, transparent 70%); top: 18%; inset-inline-start: 5%; }
        .pt-cine-orb-e { background: radial-gradient(circle, #f472b6 0%, transparent 70%); bottom: 18%; inset-inline-end: -5%; opacity: 0.35; animation-delay: -6s; }
        .pt-cine-orb-step-a { background: radial-gradient(circle, #f59e0b 0%, transparent 70%); bottom: -8%; inset-inline-end: -5%; opacity: 0.4; }
        .pt-cine-orb-step-b { background: radial-gradient(circle, #22d3ee 0%, transparent 70%); top: -5%; inset-inline-start: -10%; opacity: 0.42; }
        .pt-cine-orb-step-c { background: radial-gradient(circle, #34d399 0%, transparent 70%); bottom: -10%; inset-inline-end: -5%; opacity: 0.38; }
        .pt-cine-orb-step-d { background: radial-gradient(circle, #c084fc 0%, transparent 70%); top: 8%; inset-inline-end: 0%; opacity: 0.42; }
        .pt-cine-orb-shows-a { background: radial-gradient(circle, #818cf8 0%, transparent 70%); top: -5%; inset-inline-start: -5%; }
        .pt-cine-orb-shows-b { background: radial-gradient(circle, #34d399 0%, transparent 70%); bottom: -10%; inset-inline-end: -10%; opacity: 0.35; }

        @keyframes ptCineOrbDrift {
            0%, 100% { transform: translate3d(0,0,0) scale(1); }
            50%      { transform: translate3d(20px,-30px,0) scale(1.06); }
        }

        .pt-cine-grain {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.04) 0px, transparent 1.5px),
                radial-gradient(circle at 70% 80%, rgba(255,255,255,0.03) 0px, transparent 1.5px);
            background-size: 6px 6px, 8px 8px;
            opacity: 0.55;
            pointer-events: none;
        }

        /* Particles */
        .pt-cine-particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
        }
        .pt-cine-particles span {
            position: absolute;
            width: 3px;
            height: 3px;
            border-radius: 999px;
            background: rgba(255,255,255,0.6);
            box-shadow: 0 0 8px rgba(255,255,255,0.6);
            animation: ptCineParticleFloat 10s linear infinite;
            opacity: 0;
        }
        .pt-cine-particles span:nth-child(1)  { top: 10%; left: 8%;  animation-delay: -1s;  animation-duration: 12s; }
        .pt-cine-particles span:nth-child(2)  { top: 80%; left: 20%; animation-delay: -3s;  animation-duration: 14s; }
        .pt-cine-particles span:nth-child(3)  { top: 30%; left: 75%; animation-delay: -5s;  animation-duration: 11s; }
        .pt-cine-particles span:nth-child(4)  { top: 60%; left: 50%; animation-delay: -7s;  animation-duration: 13s; }
        .pt-cine-particles span:nth-child(5)  { top: 20%; left: 90%; animation-delay: -2s;  animation-duration: 15s; }
        .pt-cine-particles span:nth-child(6)  { top: 70%; left: 5%;  animation-delay: -4s;  animation-duration: 12s; }
        .pt-cine-particles span:nth-child(7)  { top: 45%; left: 30%; animation-delay: -6s;  animation-duration: 14s; }
        .pt-cine-particles span:nth-child(8)  { top: 15%; left: 60%; animation-delay: -8s;  animation-duration: 11s; }
        .pt-cine-particles span:nth-child(9)  { top: 85%; left: 75%; animation-delay: -9s;  animation-duration: 13s; }
        .pt-cine-particles span:nth-child(10) { top: 50%; left: 95%; animation-delay: -10s; animation-duration: 15s; }
        @keyframes ptCineParticleFloat {
            0%   { opacity: 0; transform: translate3d(0,0,0); }
            20%  { opacity: 0.8; }
            50%  { transform: translate3d(20px,-30px,0); }
            80%  { opacity: 0.4; }
            100% { opacity: 0; transform: translate3d(40px,-60px,0); }
        }

        /* Scene-entry stagger — children cascade in once .is-active is set */
        .pt-cine-stagger > * {
            opacity: 0;
            transform: translateY(22px) scale(0.97);
            filter: blur(8px);
            transition:
                opacity .8s var(--prism-ease),
                transform .8s var(--prism-ease),
                filter .8s var(--prism-ease);
            transition-delay: .04s;
        }
        .pt-cine-stagger > *:nth-child(2) { transition-delay: .14s; }
        .pt-cine-stagger > *:nth-child(3) { transition-delay: .26s; }
        .pt-cine-stagger > *:nth-child(4) { transition-delay: .38s; }
        .pt-cine-stagger > *:nth-child(5) { transition-delay: .50s; }
        .pt-cine-stagger > *:nth-child(6) { transition-delay: .62s; }
        .pt-cine-scene.is-active .pt-cine-stagger > * {
            opacity: 1;
            transform: none;
            filter: none;
        }

        /* Intro */
        .pt-cine-intro-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            text-align: center;
            max-width: 720px;
            width: 100%;
        }
        .pt-cine-brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.12);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .pt-cine-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--prism-text-2);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .pt-cine-intro-title {
            font-family: 'Space Grotesk', 'Tajawal', sans-serif;
            font-size: clamp(38px, 9vw, 76px);
            line-height: 1.04;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--prism-text);
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .pt-cine-line { display: block; }
        .pt-cine-grad {
            background: linear-gradient(120deg, #22d3ee 0%, #818cf8 50%, #c084fc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }
        .pt-cine-intro-sub {
            color: var(--prism-text-2);
            font-size: clamp(14px, 2.6vw, 17px);
            line-height: 1.6;
            margin: 0;
            max-width: 540px;
        }
        .pt-cine-scroll-cue {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-top: 18px;
            color: var(--prism-text-3);
            font-size: 10.5px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .pt-cine-scroll-cue-line {
            display: block;
            width: 1px;
            height: 32px;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.6), transparent);
            animation: ptCineCue 2s ease-in-out infinite;
        }
        @keyframes ptCineCue {
            0%, 100% { transform: scaleY(0.5); opacity: 0.4; }
            50%      { transform: scaleY(1);   opacity: 1; }
        }

        /* Prologue card */
        .pt-cine-prologue-card {
            position: relative;
            z-index: 2;
            max-width: 600px;
            width: 100%;
            padding: 28px 22px;
            border-radius: 26px;
            background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.015));
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 30px 80px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.08);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        @media (min-width: 768px) {
            .pt-cine-prologue-card { padding: 48px 44px; }
        }
        .pt-cine-prologue-title {
            font-family: 'Space Grotesk', 'Tajawal', sans-serif;
            font-size: clamp(30px, 6vw, 52px);
            line-height: 1.06;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--prism-text);
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .pt-cine-prologue-body {
            color: var(--prism-text-2);
            font-size: clamp(14px, 2.6vw, 17px);
            line-height: 1.65;
            margin: 0;
        }
        .pt-cine-prologue-tags {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pt-cine-prologue-tag {
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--prism-text-2);
            font-size: 12px;
            font-weight: 500;
        }

        /* Step scenes */
        .pt-cine-scene.is-scene-step {
            display: grid;
            grid-template-rows: 1fr auto;
            align-items: center;
            justify-items: center;
            gap: 28px;
            padding-top: 110px;
        }
        .pt-cine-step-num.pt-cine-step-num-bg {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(140px, 36vw, 280px);
            line-height: 0.85;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: rgba(255,255,255,0.04);
            -webkit-text-stroke: 1px rgba(255,255,255,0.07);
            pointer-events: none;
            position: absolute;
            z-index: 0;
            top: 50%;
            inset-inline-start: 50%;
            transform: translate(-50%, -50%);
            user-select: none;
        }
        .pt-cine-step-stage {
            position: relative;
            z-index: 2;
            width: min(420px, 88vw);
            aspect-ratio: 4 / 3;
            border-radius: 26px;
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.012));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 30px 80px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.08);
            padding: 24px;
            display: grid;
            place-items: center;
            overflow: hidden;
        }
        .pt-cine-step-stage > * { width: 100%; height: 100%; }
        /* The mock containers come from v2 — make them fill the new bigger stage */
        .pt-cine-step-stage .pt-cinema-mock-posters,
        .pt-cine-step-stage .pt-cinema-mock-seats,
        .pt-cine-step-stage .pt-cinema-mock-upload,
        .pt-cine-step-stage .pt-cinema-mock-qr {
            width: 100%;
            height: 100%;
        }
        .pt-cine-step-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            text-align: center;
            max-width: 540px;
        }
        .pt-cine-step-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--prism-text-3);
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .pt-cine-step-emoji { font-size: 16px; line-height: 1; }
        .pt-cine-step-title {
            font-family: 'Space Grotesk', 'Tajawal', sans-serif;
            font-size: clamp(28px, 5vw, 44px);
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--prism-text);
            margin: 0;
        }
        .pt-cine-step-body {
            color: var(--prism-text-2);
            font-size: clamp(14px, 2.4vw, 16px);
            line-height: 1.6;
            margin: 0;
        }

        /* Shows scene */
        .pt-cine-scene.is-scene-shows {
            align-items: stretch;
            padding: 96px 22px;
        }
        @media (min-width: 768px) {
            .pt-cine-scene.is-scene-shows { padding: 120px 56px; }
        }
        .pt-cine-shows-head {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            text-align: center;
            margin: 0 auto 32px;
            max-width: 760px;
        }
        .pt-cine-shows-title {
            font-family: 'Space Grotesk', 'Tajawal', sans-serif;
            font-size: clamp(34px, 7vw, 60px);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--prism-text);
            margin: 0;
        }
        .pt-cine-shows-sub {
            color: var(--prism-text-2);
            font-size: clamp(14px, 2.4vw, 16px);
            margin: 0;
        }
        .pt-cine-shows-stats {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 6px;
        }
        .pt-cine-shows-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 16px;
            border-radius: 14px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            min-width: 88px;
        }
        .pt-cine-shows-stat-num {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 19px;
            font-weight: 800;
            background: linear-gradient(120deg, #22d3ee, #818cf8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .pt-cine-shows-stat-label {
            color: var(--prism-text-3);
            font-size: 11px;
            margin-top: 2px;
        }

        /* Featured */
        .pt-cine-featured {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
            margin: 0 auto 36px;
            max-width: 1080px;
            width: 100%;
            border-radius: 26px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.012));
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
        }
        @media (min-width: 768px) {
            .pt-cine-featured { grid-template-columns: 1.1fr 1fr; }
        }
        .pt-cine-featured-poster {
            position: relative;
            aspect-ratio: 16 / 10;
            overflow: hidden;
        }
        @media (min-width: 768px) {
            .pt-cine-featured-poster { aspect-ratio: auto; min-height: 420px; }
        }
        .pt-cine-featured-poster img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
        }
        .pt-cine-featured-poster-empty {
            height: 100%;
            display: grid;
            place-items: center;
            color: var(--prism-text-4);
            background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
        }
        .pt-cine-featured-badge {
            position: absolute;
            top: 14px;
            inset-inline-start: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.12);
            color: var(--prism-text);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .pt-cine-featured-body {
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        @media (min-width: 768px) {
            .pt-cine-featured-body { padding: 32px; }
        }
        .pt-cine-featured-title {
            font-family: 'Space Grotesk', 'Tajawal', sans-serif;
            font-size: clamp(22px, 3.5vw, 28px);
            font-weight: 800;
            color: var(--prism-text);
            margin: 0;
            line-height: 1.18;
        }
        .pt-cine-featured-desc {
            color: var(--prism-text-2);
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            white-space: pre-line;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .pt-cine-featured-times {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .pt-cine-featured-time {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 12px;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            font-size: 13px;
            color: var(--prism-text-2);
            gap: 12px;
        }
        .pt-cine-featured-time-when { flex: 1; }
        .pt-cine-featured-time-price { color: var(--prism-text); font-weight: 600; }
        .pt-cine-featured-time-unit { font-size: 10px; opacity: 0.7; margin-inline-start: 4px; }
        .pt-cine-featured-empty { color: var(--prism-text-4); font-size: 13px; }
        .pt-cine-cta-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 14px;
            background: linear-gradient(120deg, #22d3ee, #818cf8 50%, #c084fc);
            color: #0b0c14;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 12px 28px rgba(99,102,241,0.35);
            transition: transform .2s var(--prism-ease), box-shadow .2s var(--prism-ease);
            align-self: flex-start;
        }
        .pt-cine-cta-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(99,102,241,0.45);
        }

        /* Show grid */
        .pt-cine-shows-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            max-width: 1080px;
            width: 100%;
            margin: 0 auto;
        }
        @media (min-width: 600px) { .pt-cine-shows-grid { grid-template-columns: 1fr 1fr; } }
        @media (min-width: 1024px) { .pt-cine-shows-grid { grid-template-columns: 1fr 1fr 1fr; } }
        .pt-cine-show-card {
            position: relative;
            border-radius: 22px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(255,255,255,0.045), rgba(255,255,255,0.012));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            transition: transform .3s var(--prism-ease), box-shadow .3s var(--prism-ease), border-color .3s var(--prism-ease);
            display: flex;
            flex-direction: column;
        }
        .pt-cine-show-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 28px 70px rgba(0,0,0,0.45);
            border-color: rgba(34,211,238,0.3);
        }
        .pt-cine-show-card-poster {
            display: block;
            position: relative;
            aspect-ratio: 16 / 10;
            overflow: hidden;
        }
        .pt-cine-show-card-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .6s var(--prism-ease);
        }
        .pt-cine-show-card:hover .pt-cine-show-card-poster img {
            transform: scale(1.06);
        }
        .pt-cine-show-card-poster-empty {
            display: grid;
            place-items: center;
            color: var(--prism-text-4);
            background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
        }
        .pt-cine-show-card-veil {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.6), transparent 60%);
            pointer-events: none;
        }
        .pt-cine-show-card-body {
            padding: 14px 16px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
        }
        .pt-cine-show-card-title {
            font-family: 'Space Grotesk', 'Tajawal', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--prism-text);
            margin: 0;
            line-height: 1.2;
        }
        .pt-cine-show-card-desc {
            color: var(--prism-text-2);
            font-size: 12.5px;
            line-height: 1.5;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .pt-cine-show-card-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            gap: 8px;
        }
        .pt-cine-show-card-times {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--prism-text-3);
            font-size: 11px;
        }
        .pt-cine-cta-mini {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            border-radius: 10px;
            background: rgba(34,211,238,0.12);
            border: 1px solid rgba(34,211,238,0.25);
            color: var(--prism-text);
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            transition: background .2s var(--prism-ease);
        }
        .pt-cine-cta-mini:hover { background: rgba(34,211,238,0.2); }
        .pt-cine-shows-empty {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 48px 24px;
            color: var(--prism-text-2);
        }

        /* Mobile-first softening */
        @media (max-width: 540px) {
            .pt-cine-orb { filter: blur(50px); opacity: 0.4; }
            .pt-cine-particles span { width: 2px; height: 2px; }
            .pt-cine-step-num.pt-cine-step-num-bg { font-size: clamp(140px, 38vw, 200px); }
        }

        /* Touch devices: kill the show-card lift on tap-and-release */
        @media (hover: none) {
            .pt-cine-show-card:hover { transform: none; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
            .pt-cine-show-card:hover .pt-cine-show-card-poster img { transform: none; }
        }

        /* Reduced-motion: freeze the new layer */
        @media (prefers-reduced-motion: reduce) {
            .pt-cine-orb,
            .pt-cine-particles span,
            .pt-cine-scroll-cue-line {
                animation: none !important;
            }
            .pt-cine-stagger > * {
                opacity: 1 !important;
                transform: none !important;
                filter: none !important;
                transition: none !important;
            }
            .pt-cine-show-card:hover { transform: none !important; }
            body.is-pt-cine.has-cine-intro-active .pt-topbar-wrap {
                opacity: 1;
                pointer-events: auto;
                transform: none;
            }
        }
    </style>
</head>
<body class="prism-stage min-h-screen @yield('body_class')">

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
        // Comprehensive bilingual dictionary. Arabic preserves the existing
        // copy verbatim, English is human-tuned for native quality. Keys are
        // flat to keep templates terse.
        const I18N = {
            ar: {
                /* ===== brand / nav / footer / theme / lang chrome ===== */
                brand: 'PREMIUM', brand_tag: 'TICKETS · STAGE',
                nav_home: 'الرئيسية', nav_shows: 'العروض', nav_admin: 'لوحة التحكم',
                foot_fast: 'حجز فوري', foot_secure: 'دفع آمن', foot_qr: 'QR على واتساب',
                foot_about: 'منصة حجز تذاكر مسرح مصرية، مصممة لتجربة فاخرة وسريعة على الموبايل والديسكتوب.',
                foot_quick: 'روابط سريعة', foot_legal: 'الدعم',
                theme_label: 'الوضع', theme_light: 'فاتح', theme_dark: 'داكن',
                theme_toggle_aria: 'تبديل الوضع',
                lang_label: 'اللغة', menu_open: 'افتح القائمة', menu_close: 'إغلاق القائمة',
                primary_nav: 'القائمة الرئيسية', mobile_nav: 'قائمة الموبايل',

                /* ===== sticky bar / generic CTAs / modals / toasts ===== */
                bar_total: 'الإجمالي', bar_seats: 'المقاعد المختارة',
                btn_confirm: 'تأكيد الحجز', btn_cancel: 'إلغاء', btn_continue: 'متابعة',
                btn_approve: 'تأكيد الحجز', btn_reject:  'رفض الحجز',
                btn_back: 'رجوع', btn_save: 'حفظ', btn_save_changes: 'حفظ التغييرات',
                btn_book_now: 'احجز الآن', btn_details_book: 'تفاصيل وحجز',
                btn_back_shows: 'رجوع لكل العروض',
                modal_processing: 'جارٍ إرسال الطلب...',
                modal_processing_body: 'برجاء الانتظار ثوانٍ دون إغلاق الصفحة.',
                modal_confirm_title: 'تأكيد',

                /* ===== homepage hero ===== */
                cta_browse: 'تصفح العروض',
                hero_eyebrow: 'حجز مباشر · المسرح المصري',
                hero_title_a: 'احجز تجربتك',
                hero_title_b: 'على المسرح',
                hero_sub: 'منصة حجز سلسة وأنيقة: تختار العرض، تحجز مقعدك من الخريطة المباشرة، تدفع بأمان، وتستقبل تذكرتك بكود QR على واتساب.',
                hero_cta_primary: 'تصفح العروض', hero_cta_secondary: 'كيف يعمل؟',
                hero_stat_shows_label: 'عرض متاح',
                hero_stat_seats_label: 'مقعد جاهز',
                hero_stat_qr_label: 'تذكرة QR فورية',
                trust_instant: 'حجز فوري', trust_secure: 'دفع آمن',
                trust_qr: 'QR على واتساب', trust_mobile: 'يعمل على الموبايل',
                trust_247: 'متاح 24/7', trust_seat: 'اختيار مقعد مباشر',
                how_title: 'كيف تحجز تذكرتك',
                how_sub: 'ثلاث خطوات بسيطة من الاختيار حتى الواتساب.',
                how_sub_4: 'أربع خطوات سينمائية من الاختيار حتى الواتساب.',
                how_1_t: 'اختر العرض', how_1_b: 'استعرض العروض المتاحة واختر الموعد المناسب.',
                how_2_t: 'احجز مقعدك', how_2_b: 'اختر مقعدك من خريطة القاعة المباشرة وادفع بأمان.',
                how_3_t: 'استقبل التذكرة', how_3_b: 'تذكرة QR تصلك على واتساب في أقل من دقيقة.',
                cine_1_t: 'اختر عرضك',
                cine_1_b: 'تصفح العروض المباشرة واختر الموعد اللي يناسبك بلمسة واحدة.',
                cine_2_t: 'اختر مقعدك',
                cine_2_b: 'خريطة مباشرة للصالة توريلك المتاح لحظة بلحظة عشان تحجز مقعدك بثقة.',
                cine_3_t: 'ارفع التحويل',
                cine_3_b: 'حوّل على المحفظة أو InstaPay وارفع صورة التحويل بثواني داخل تدفق آمن وأنيق.',
                cine_4_t: 'استلم تذكرتك',
                cine_4_b: 'تذكرة QR توصلك على واتساب فور الاعتماد · جاهزة للمسح عند البوابة.',
                // Cinematic homepage v3 (full-screen scenes)
                cine_intro_eyebrow: 'PREMIUM TICKETS',
                cine_intro_line_a: 'اكتشف',
                cine_intro_line_b: 'حجز التذاكر',
                cine_intro_line_c: 'بشكل مختلف.',
                cine_intro_sub: 'تجربة سينمائية للحجز على الموبايل · من اختيار العرض حتى تذكرة الـQR على واتساب.',
                cine_scroll_cue: 'اسحب للأسفل',
                cine_prologue_eyebrow: 'أهلا بك',
                cine_prologue_title_a: 'حجز',
                cine_prologue_title_b: 'من نوع تاني.',
                cine_prologue_body: 'اختر العرض، احجز مقعدك من الخريطة المباشرة، ادفع بأمان، وتسلّم تذكرتك بكود QR على واتساب — كل ده من الموبايل.',
                cine_prologue_tag_1: 'سينمائي',
                cine_prologue_tag_2: 'مباشر',
                cine_prologue_tag_3: 'آمن',
                cine_step_eyebrow_1: 'الخطوة الأولى',
                cine_step_eyebrow_2: 'الخطوة الثانية',
                cine_step_eyebrow_3: 'الخطوة الثالثة',
                cine_step_eyebrow_4: 'الخطوة الرابعة',
                cine_shows_eyebrow: 'العروض المباشرة الآن',
                shows_title: 'العروض المتاحة', shows_sub: 'اختر عرضك وابدأ الحجز.',
                shows_eyebrow_featured: 'عرض مميز',
                shows_pill_times: 'موعد متاح',
                shows_pill_times_one: 'موعد واحد متاح',
                shows_pill_no_times: 'لا توجد مواعيد',
                shows_no_poster: 'بدون بوستر',
                shows_from: 'من',
                shows_per_seat: 'جنيه / مقعد',
                shows_per_ticket: 'جنيه / تذكرة',
                shows_starts_from: 'تبدأ من',
                shows_egp: 'جنيه',
                shows_section_balcony_hall: 'بلكون / صالة',
                shows_status_available: 'متاح للحجز',
                shows_status_few: 'تبقّى',
                shows_status_few_suffix: 'تذكرة',
                shows_status_sold: 'Sold Out',
                shows_no_times_card: 'لا توجد مواعيد متاحة حاليا.',
                shows_empty_title: 'لا توجد عروض متاحة حاليا',
                shows_empty_body: 'تابعنا — هنفعّل عروض جديدة قريبا.',

                /* ===== show details page ===== */
                show_pill_kind: 'عرض مسرحي',
                show_pill_online: 'حجز إلكتروني',
                show_pill_qr: 'تذكرة QR',
                show_times_title: 'المواعيد المتاحة',
                show_prices_label: 'الأسعار:',
                show_price_label: 'سعر التذكرة:',
                show_no_times: 'لا توجد مواعيد متاحة حاليًا لهذا العرض.',

                /* ===== booking step 1 (anba section pick) ===== */
                step_section: 'القسم', step_seat: 'المقعد', step_confirm: 'التأكيد',
                pick_section_title: 'اختار القسم',
                pick_section_sub: 'حدد القسم اللي عايز تحجز فيه',
                section_hall: 'الصالة', section_hall_en: 'Hall',
                section_balcony: 'البلكون', section_balcony_en: 'Balcony',
                section_hall_meta: 'اختار مقعدك من خريطة الصالة',
                section_soon: 'قريبًا',
                pay_eyebrow: '💸 ادفع قيمة التذكرة على',
                pay_wallet: '📱 محفظة',
                pay_insta: '⚡ InstaPay',

                /* ===== seat picker (anba) ===== */
                seat_back: 'رجوع',
                seat_admin_title: 'إدارة المقاعد',
                seat_pick_title: 'اختار مقعدك',
                seat_zoom_out: 'تصغير', seat_zoom_reset: 'إعادة', seat_zoom_in: 'تكبير',
                seat_map: 'خريطة المقاعد',
                seat_canvas_aria: 'خريطة مقاعد الصالة',
                seat_gesture_hint: 'استخدم إصبعين للتكبير والتحريك',
                seat_legend_hint_user: 'اسحب للتنقل · قرّب بإصبعين أو بضغطة مزدوجة · المقاعد ذات الـ✕ مخصصة للإدارة',
                seat_legend_hint_admin: 'اسحب للتنقل · قرّب بإصبعين أو بضغطة مزدوجة · اضغط على أي مقعد لحظره أو فك حظره',
                seat_admin_mode: 'وضع الإدارة',
                seat_per_seat: 'جنيه / مقعد',
                seat_step1_pay: 'خطوة 1 · حوّل قيمة الحجز',
                seat_wallet: 'محفظة',
                seat_admin_panel_title: 'إدارة المقاعد',
                seat_user_panel_title: 'اختار مقاعدك',
                seat_admin_instructions: 'اضغط على أي مقعد لحظره أو فك حظره. التغييرات تُحفظ بالضغط على زر الحفظ بالأسفل.',
                seat_user_instructions: 'اضغط على أي مقعد للاختيار. ممكن تختار أكثر من مقعد. اضغط مرة تانية لإلغاء الاختيار.',
                seat_legend_available: 'متاح',
                seat_legend_selected: 'مختار',
                seat_legend_reserved: 'محجوز',
                seat_legend_admin: 'إدارة',
                seat_selected_label: 'المقاعد المختارة',
                seat_none_selected: 'لم تختر أي مقعد بعد',
                seat_total: 'الإجمالي',
                seat_save_changes: 'حفظ التغييرات',
                seat_complete_booking: 'إكمال الحجز',
                seat_back_shows_admin: 'رجوع لإدارة العروض',
                seat_back_section: 'الرجوع لاختيار القسم',
                seat_pending_changes: 'تغييرات معلَّقة',
                seat_chip_selected: 'المختار',
                seat_chip_seat: 'مقعد',

                /* ===== booking form (step 3) ===== */
                book_show_details: 'تفاصيل العرض',
                book_step1_title: 'خطوة 1: حوّل قيمة التذكرة',
                book_step1_desc: 'حوّل {amount} جنيه على أحد الأرقام التالية:',
                book_step2_title: 'خطوة 2: ارفع Screenshot وكمّل البيانات',
                book_seats_title: 'مقاعدك',
                book_change_seats: 'تغيير المقاعد',
                book_attendees_title: 'بيانات الحضور',
                book_attendees_desc: 'اكتب الاسم ورقم الواتساب للشخص اللي هيستلم كل مقعد.',
                book_seat_label: 'مقعد',
                book_name: 'الاسم', book_name_ph: 'مثال: مينا جورج',
                book_phone: 'واتساب', book_phone_ph: '01XXXXXXXXX',
                book_required: 'مطلوب',
                book_attendee_n: 'بيانات الشخص رقم',
                book_screenshot_title: '📸 Screenshot التحويل',
                book_screenshot_desc: 'ارفع صورة شاشة التحويل بصيغة PNG أو JPG.',
                book_upload_click: 'اضغط لرفع الصورة',
                book_upload_replace: 'استبدال الصورة',
                book_upload_remove: 'إزالة',
                book_total: 'الإجمالي',
                book_total_x_seats: '{n} مقعد',
                book_continue_cta: 'إكمال الحجز',
                book_dock_eyebrow: 'إجمالي الحجز',
                book_dock_total: 'الإجمالي',
                book_dock_hint_missing: 'كمّل بيانات الحضور وارفع Screenshot التحويل.',
                book_tickets_count: '👥 عدد التذاكر',
                book_screenshot_legacy: '📸 Screenshot التحويل',
                book_send_request: 'إرسال طلب الحجز',
                book_no_seats_redirect: 'مفيش مقاعد مختارة، رجّعناك لاختيار المقاعد.',
                book_invalid_session: 'انتهت الجلسة، اختار مقاعدك من جديد.',

                /* ===== bookings/create non-anba form ===== */
                book_form_show_details: '🎭 تفاصيل العرض',
                book_step1_pay_title: 'خطوة 1: حوّل قيمة التذكرة',
                book_step1_pay_desc_a: 'حوّل',
                book_step1_pay_desc_b: 'جنيه على أحد الأرقام التالية:',
                book_form_person_label: '👤 بيانات الشخص رقم',
                book_form_name_ph: 'اسم الشخص {n}',
                book_form_phone_ph: 'رقم موبايل واتساب {n}',
                book_no_tickets_alert: '❌ لا يوجد تذاكر متاحة، المتاح: {n}',
                book_sending: 'جارِ الإرسال...',

                /* ===== bookings/form (final attendee form) ===== */
                form_add_edit_seats: 'إضافة / تعديل المقاعد',
                form_chips_hint: 'اضغط × على أي مقعد لإلغاء اختياره، أو اضغط "إضافة / تعديل المقاعد" للرجوع لخريطة المقاعد.',
                form_loading_seats: 'جارٍ تحميل المقاعد المختارة...',
                form_chips_empty: 'لم يعد هناك مقاعد مختارة',
                form_chip_remove_aria: 'إلغاء {label}',
                form_steps_title: '📌 خطوات إكمال الحجز',
                form_step1_a: 'حوّل قيمة الحجز',
                form_step1_b: 'على المحفظة أو InstaPay الموضحة بالأسفل.',
                form_step2: 'التقط صورة (Screenshot) لإيصال التحويل وارفعها في الخانة المخصصة.',
                form_step3: 'اكتب اسم ورقم واتساب لكل شخص بترتيب المقاعد المحجوزة.',
                form_step4_a: 'اضغط',
                form_step4_b: 'هنراجع الطلب ونرسل التذاكر على رقم الواتساب خلال',
                form_step4_24h: '24 ساعة',
                form_step4_max: 'كحد أقصى.',
                form_attendees_title: '👥 بيانات الحضور',
                form_attendees_hint: 'اكتب اسم ورقم واتساب لكل مقعد',
                form_screenshot: 'إيصال التحويل',
                form_required: 'مطلوب',
                form_name_label: 'الاسم',
                form_phone_label: 'رقم واتساب',
                form_dock_aria: 'ملخص الحجز',
                form_dock_hint: 'اكمل الحقول المطلوبة',
                form_confirm_btn: 'تأكيد الحجز',
                form_at_least_one: '❌ من فضلك اختر مقعد واحد على الأقل',
                form_confirm_modal_title: 'تأكيد الحجز',
                form_confirm_modal_body: 'هتقدم طلب الحجز للمراجعة. لما يتأكد، هتوصلك التذكرة على واتساب.',
                form_confirm_ok: 'تأكيد',
                form_confirm_cancel: 'إلغاء',

                /* ===== bookings/thankyou ===== */
                thx_title: 'تم إرسال طلب الحجز بنجاح',
                thx_thanks_prefix: 'شكرًا يا',
                thx_ref_label: 'رقم الحجز',
                thx_total_label: 'إجمالي المبلغ',
                thx_next_step: 'الخطوة الجاية',
                thx_step1_html: 'يتم <span class="text-[color:var(--prism-text)] font-semibold">مراجعة عملية الدفع</span> والتأكد من التحويل.',
                thx_step2_html: 'بعد <span class="text-[color:var(--prism-emerald)] font-semibold">تأكيد الحجز</span>، سيتم إرسال <span class="text-[color:var(--prism-text)] font-semibold">التذكرة</span> مباشرة على <span class="text-[color:var(--prism-text)] font-semibold">رقم الواتساب المسجل</span>.',
                thx_step3_html: 'عملية المراجعة قد تستغرق بحد أقصى <span class="text-[color:var(--prism-text)] font-semibold">24 ساعة</span>.',
                thx_footer_html: 'لو في أي مشكلة في التحويل أو البيانات، هنتواصل معاك قبل رفض الطلب.<br>متقلقش، طلبك محفوظ على السيستم ✨',
                thx_back_home: 'رجوع للصفحة الرئيسية',
                thx_countdown_label: 'يبدأ العرض خلال',
                thx_countdown_now: 'العرض بدأ! 🎉',
                thx_countdown_days: 'يوم',
                thx_countdown_hours: 'س',
                thx_countdown_mins: 'د',
                thx_add_calendar: 'أضف للتقويم',
                thx_share_wa: 'مشاركة عبر واتساب',
                thx_share_wa_text: 'حجزت تذكرة لـ "{title}" يوم {date} 🎭',
                common_egp: 'جنيه',

                /* ===== Wave 1 quick wins (copy / share / favorites / ribbons) ===== */
                copy_aria: 'نسخ',
                copy_done: 'تم النسخ ✓',
                copy_failed: 'تعذر النسخ',
                fav_save_aria: 'حفظ في المفضلة',
                fav_unsave_aria: 'إزالة من المفضلة',
                fav_saved_toast: 'تمت الإضافة للمفضلة',
                fav_unsaved_toast: 'تمت الإزالة من المفضلة',
                fav_pill: 'المحفوظة',
                ribbon_trending: 'الأكثر طلبًا',
                ribbon_selling_fast: 'يُحجز بسرعة',
                ribbon_last_n: 'آخر {n} مقاعد',
                share_wa: 'مشاركة عبر واتساب',
                share_wa_text: 'احجز تذكرتك لـ "{title}" 🎭',
                shows_skip_pill: 'تخطّي إلى العروض ↓',
                seat_auto_pick: 'اختر أفضل المقاعد',
                seat_auto_pick_done: 'تم اختيار أفضل المقاعد',
                seat_auto_pick_none: 'لا توجد مقاعد متجاورة كافية',
                seat_auto_pick_prompt: 'كم مقعد تريد؟',

                /* ===== auth pages ===== */
                auth_admin_pill: 'دخول الأدمن',
                auth_admin_title: 'دخول الأدمن',
                auth_admin_subtitle: 'سجّل دخولك للوحة التحكم',
                auth_email: 'البريد الإلكتروني',
                auth_password: 'كلمة المرور',
                auth_password_confirm: 'تأكيد كلمة المرور',
                auth_name: 'الاسم',
                auth_login_btn: 'دخول',
                auth_register_btn: 'حساب جديد',
                auth_register_title: 'إنشاء حساب',
                auth_forgot_password: 'نسيت كلمة المرور؟',
                auth_reset_pill: 'إعادة تعيين كلمة المرور',
                auth_reset_title: 'إعادة تعيين كلمة المرور',
                auth_reset_btn: 'إعادة تعيين كلمة المرور',
                auth_send_reset_link: 'إرسال رابط إعادة التعيين',
                auth_confirm_pwd_title: 'تأكيد كلمة المرور',
                auth_confirm_pwd_subtitle: 'من فضلك أكّد كلمة المرور قبل المتابعة.',
                auth_verify_title: 'تأكيد البريد الإلكتروني',
                auth_verify_resent: 'تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني.',
                auth_verify_check_email: 'قبل المتابعة، تحقق من بريدك الإلكتروني للحصول على رابط التأكيد.',
                auth_verify_didnt_receive: 'إذا لم يصلك البريد',
                auth_verify_resend_link: 'اضغط هنا لإرسال رابط جديد',

                /* ===== thank you page ===== */
                thx_title: 'تم استلام طلبك',
                thx_sub: 'هنراجع التحويل ونرجعلك بتأكيد الحجز على واتساب.',
                thx_back: 'رجوع للرئيسية',

                /* ===== auth ===== */
                auth_admin_pill: 'دخول الأدمن',
                auth_admin_title: 'دخول الأدمن',
                auth_admin_sub: 'سجّل دخولك للوحة التحكم',
                auth_email: 'البريد الإلكتروني',
                auth_password: 'كلمة المرور',
                auth_confirm_password: 'تأكيد كلمة المرور',
                auth_login: 'دخول',
                auth_register: 'حساب جديد',
                auth_register_pill: 'حساب جديد',
                auth_name: 'الاسم',
                auth_verify_title: 'تأكيد البريد الإلكتروني',
                auth_verify_resent: 'تم إرسال رابط تحقق جديد إلى بريدك.',
                auth_verify_body: 'برجاء فتح بريدك للضغط على رابط التحقق.',
                auth_verify_didnt: 'لو ما وصلكش الإيميل،',
                auth_verify_request: 'اضغط هنا لإرسال إيميل جديد',
                auth_reset_pill: 'إعادة تعيين كلمة المرور',
                auth_reset_title: 'إعادة تعيين كلمة المرور',
                auth_reset_send: 'أرسل رابط إعادة التعيين',
                auth_confirm_title: 'تأكيد كلمة المرور',
                auth_confirm_sub: 'برجاء تأكيد كلمة المرور قبل المتابعة.',
                auth_confirm_btn: 'تأكيد كلمة المرور',
                auth_forgot: 'نسيت كلمة المرور؟',

                /* ===== admin: dashboard ===== */
                admin_dash_pill: 'لوحة تحكم الأدمن',
                admin_dash_title: 'لوحة تحكم الأدمن',
                admin_dash_sub: 'من هنا تقدر تتابع نبض العروض، الحجوزات، والتذاكر اللي طلعت للجمهور.',
                admin_kpi_revenue: 'إجمالي الإيرادات المعتمدة',
                admin_kpi_revenue_desc: 'مجموع التذاكر المعتمدة على كل المواعيد.',
                admin_kpi_revenue_currency: 'EGP',
                admin_kpi_pending: 'قيد المراجعة',
                admin_kpi_pending_unit: 'يحتاج مراجعة',
                admin_kpi_pending_desc: 'طلبات حجز محتاجة Screenshot والاعتماد.',
                admin_kpi_section: 'المؤشرات العامة',
                admin_kpi_shows: 'عدد العروض',
                admin_kpi_shows_desc: 'إجمالي العروض المنشورة في السيستم.',
                admin_kpi_times: 'مواعيد العروض',
                admin_kpi_times_desc: 'عدد المرات اللي العروض هتتقدَّم فيها على المسرح.',
                admin_kpi_approved: 'التذاكر approved',
                admin_kpi_approved_desc: 'تذاكر لحجوزات اتأكدت واتقبلت، وطلع لها QR.',
                admin_kpi_remaining: 'التذاكر المتبقية',
                admin_kpi_remaining_desc: 'الفرق بين إجمالي التذاكر المخصصة لكل موعد وإجمالي التذاكر المعتمدة.',
                admin_quick: 'الإجراءات السريعة',
                admin_quick_shows: 'إدارة العروض',
                admin_quick_shows_pill: 'العروض المسرحية',
                admin_quick_shows_desc: 'إضافة عروض جديدة، تعديل التفاصيل، رفع البوسترات، وتفعيل/إخفاء العروض من الموقع.',
                admin_quick_bookings: 'إدارة الحجوزات',
                admin_quick_bookings_pill: 'الحجوزات والتحويلات',
                admin_quick_bookings_desc: 'مراجعة طلبات الحجز، التأكد من التحويلات، واعتماد التذاكر وإرسال الـ QR للحضور.',
                admin_quick_scanner: 'على الباب',
                admin_quick_scanner_pill: 'وضع Scan تذاكر QR',
                admin_quick_scanner_desc: 'افتح من موبايل المسؤول على باب المسرح، وامسح كود كل تذكرة عشان تتأكد إن الحجز صالح.',
                admin_table_title: 'المواعيد والتذاكر لكل عرض',
                admin_table_h_show: 'العرض',
                admin_table_h_date: 'التاريخ',
                admin_table_h_time: 'الساعة',
                admin_table_h_total: 'إجمالي',
                admin_table_h_approved: 'Approved',
                admin_table_h_pending: 'Pending',
                admin_table_h_remaining: 'المتبقي',
                admin_table_h_revenue: 'Revenue',
                admin_table_empty: 'لسه مفيش مواعيد متسجلة على السيستم.',
                admin_pay_settings_pill: 'إعدادات الدفع',
                admin_pay_settings_title: 'إعدادات الدفع',
                admin_pay_wallet: 'رقم المحفظة',
                admin_pay_insta: 'InstaPay',
                admin_pay_help: 'هتظهر في صفحة الدفع للعميل عشان يحوّل عليها.',
                admin_pay_save: 'حفظ بيانات التحويل',

                /* ===== admin: shows index/edit/create ===== */
                admin_shows_title: 'إدارة العروض',
                admin_shows_pill: 'إدارة العروض',
                admin_shows_add: '+ إضافة عرض',
                admin_shows_back: 'رجوع',
                admin_shows_empty: 'لا يوجد عروض حالياً.',
                admin_shows_active: 'فعال',
                admin_shows_hidden: 'مخفي',
                admin_shows_times: 'المواعيد',
                admin_shows_edit: 'تعديل',
                admin_shows_delete: 'حذف',
                admin_shows_delete_confirm: 'متأكد إنك عايز تحذف العرض؟',
                admin_show_create_title: 'إضافة عرض جديد',
                admin_show_edit_title: 'تعديل العرض',
                admin_show_back_to_list: 'رجوع لقائمة العروض',
                admin_show_data: 'بيانات العرض',
                admin_show_name: 'اسم العرض',
                admin_show_desc: 'وصف العرض',
                admin_show_desc_help: 'يظهر تحت اسم العرض في صفحة التفاصيل وعلى الكروت.',
                admin_show_pricing: 'نوع المسرح والأسعار',
                admin_show_anba_note: 'الأنبا رويس بيستخدم تسعير لكل فئة (بلكون / صالة). هتظهر أسعار التذاكر تحت.',
                admin_show_balcony_price: 'سعر تذكرة البلكون (EGP)',
                admin_show_hall_price: 'سعر تذكرة الصالة (EGP)',
                admin_show_poster: 'بوستر العرض',
                admin_show_poster_optional: 'اختياري',
                admin_show_poster_click: 'اضغط لاختيار صورة البوستر',
                admin_show_poster_format: 'PNG / JPG · ينصح بنسبة عمودية (2:3)',
                admin_show_poster_replace: 'استبدال البوستر',
                admin_show_ticket_design: 'تصميم التذكرة وموضع الـ QR',
                admin_show_ticket_design_desc: 'ارفع تصميم التذكرة (PNG / JPG)، وحدد مكان مربع الـ QR بالسحب على الصورة أو بالأرقام. لو ما رفعتش تصميم، النظام هيطلع QR لوحده بدون خلفية.',
                admin_show_ticket_file: 'ملف تصميم التذكرة',
                admin_show_ticket_upload: 'اضغط لرفع تصميم التذكرة',
                admin_show_ticket_upload_help: 'بعد الرفع تقدر تحرك مربع الـ QR وتغيّر حجمه على التصميم',
                admin_show_ticket_design_preview: 'تصميم التذكرة',
                admin_show_qr_x: 'X (من الشمال)',
                admin_show_qr_y: 'Y (من فوق)',
                admin_show_qr_size: 'حجم الـ QR',
                admin_show_qr_help: 'حرّك مربع الـ QR على الصورة بالفأرة أو اللمس، واسحب المربع الصغير في الركن لتكبير/تصغير الحجم. الأرقام بتتحوّل أوتوماتيك حسب موضعك على التصميم الأصلي (بالبكسل).',
                admin_show_visibility: 'الظهور',
                admin_show_visible: 'عرض هذا العرض على الموقع',
                admin_show_cancel: 'إلغاء',
                admin_show_create_btn: 'اضافه العرض',
                admin_show_save: 'حفظ التعديلات',

                /* ===== admin: show times ===== */
                admin_times_title: 'مواعيد العرض',
                admin_times_add: '+ إضافة موعد جديد',
                admin_times_empty: 'لا توجد مواعيد لهذا العرض حتى الآن.',
                admin_time_create_title: 'إضافة موعد جديد',
                admin_time_edit_title: 'تعديل موعد',
                admin_time_back_to_times: 'رجوع للمواعيد',
                admin_time_section_when: 'الموعد',
                admin_time_date: 'التاريخ',
                admin_time_time: 'الساعة',
                admin_time_section_pricing: 'السعر والتذاكر',
                admin_time_anba_pricing: 'الأسعار من العرض (لكل فئة)',
                admin_time_anba_help: 'هذا العرض يستخدم تسعير حسب القسم. عدّل الأسعار من صفحة تعديل العرض.',
                admin_time_total: 'إجمالي التذاكر',
                admin_time_price: 'سعر التذكرة (جنيه)',
                admin_time_status: 'الحالة',
                admin_time_state: 'حالة الموعد',
                admin_time_sold_out_help: 'لما تفعّل Sold Out، الموعد بيختفي من صفحات الحجز ومش هيقدر يحجزه أي حد.',

                /* ===== admin: bookings index/show ===== */
                admin_bookings_title: 'الحجوزات',
                admin_bookings_pill: 'إدارة الحجوزات',
                admin_bookings_back: 'رجوع',
                admin_bookings_filter: 'تصفية',
                admin_bookings_status_all: 'الكل',
                admin_bookings_status_pending: 'قيد المراجعة',
                admin_bookings_status_approved: 'مُعتمد',
                admin_bookings_status_rejected: 'مرفوض',
                admin_bookings_h_user: 'المستخدم',
                admin_bookings_h_show: 'العرض',
                admin_bookings_h_date: 'التاريخ',
                admin_bookings_h_seats: 'مقاعد',
                admin_bookings_h_amount: 'القيمة',
                admin_bookings_h_status: 'الحالة',
                admin_bookings_empty: 'لا توجد حجوزات.',
                admin_bookings_view: 'عرض',
                admin_booking_detail: 'تفاصيل الحجز',
                admin_booking_attendees: 'الحضور',
                admin_booking_screenshot: 'Screenshot التحويل',
                admin_booking_seat_pill: 'مقعد',
                admin_booking_resend: 'إعادة إرسال QR',
                admin_booking_resend_done: 'تم إرسال التذاكر مرة أخرى',
                admin_booking_resend_err: 'حصل خطأ، حاول مرة تانية',

                /* ===== admin: payments settings ===== */
                admin_payments_title: 'إعدادات التحويلات',
                admin_payments_back: 'رجوع للوحة التحكم',
                admin_payments_wallet: 'رقم المحفظة (اختياري)',
                admin_payments_wallet_eg: 'مثلاً: 0100xxxxxxx',
                admin_payments_insta: 'حساب InstaPay (اختياري)',
                admin_payments_insta_eg: 'مثلاً: EGxxxxxxxxxx أو email@domain.com',
                admin_payments_save: 'حفظ الإعدادات',

                /* ===== scanner ===== */
                scanner_pill: 'سكانر البوابة',
                scanner_title: '🎫 سكانر البوابة',
                scanner_back: 'رجوع',
                scanner_ready: 'جاهز للفحص',
                scanner_flash: '🔦 فلاش',
                scanner_restart: '🔄 إعادة',
                scanner_status_ok: '✅ دخول مسموح',
                scanner_status_used: '⚠️ مستخدمة',
                scanner_status_invalid: '❌ غير صالح',
                scanner_entered: 'دخل',
                scanner_flash_unsupported: 'الفلاش غير مدعوم',

                /* ===== validation / common ===== */
                err_required_name: 'لازم تكتب اسم',
                err_required_phone: 'لازم تكتب رقم واتساب',
                err_invalid_phone: 'رقم الواتساب لازم يكون 11 رقم ويبدأ بـ 01',
                err_required_screenshot: 'لازم ترفع صورة شاشة التحويل',
                err_select_seats: 'لازم تختار مقعد على الأقل',
                err_seat_taken: 'المقعد ده اتحجز قبلك، اختار غيره.',
                err_save_failed: 'مقدرتش أحفظ التعديلات، حاول تاني.',
                ok_seats_saved: 'تم حفظ التغييرات',
                ok_seat_blocked: 'المقعد اتحظر',
                ok_seat_unblocked: 'المقعد اتفتح',

                /* ===== common (shared across admin / auth) ===== */
                common_cancel: 'إلغاء',
                common_currency: 'جنيه',
                common_currency_short: 'ج.م',
                common_egp: 'جنيه',
                common_optional: '(اختياري)',
                common_ticket_word: 'تذكرة',

                /* ===== admin: console / dashboard ===== */
                adm_console_pill: 'لوحة التحكم',
                adm_console_eyebrow: 'PREMIUM · CONTROL',
                adm_dashboard_title: 'لوحة تحكم الأدمن',
                adm_dashboard_lede: 'من هنا تقدر تتابع نبض العروض، الحجوزات، والتذاكر اللي طلعت للجمهور.',
                adm_back: 'رجوع',
                adm_back_dashboard: 'رجوع للوحة التحكم',
                adm_back_shows_list: 'رجوع لقائمة العروض',
                adm_back_times: 'رجوع للمواعيد',
                adm_overview_title: 'نظرة عامة',
                adm_edit: 'تعديل',
                adm_delete: 'حذف',
                adm_seats: 'مقاعد',
                adm_seats_saved: 'إدارة المقاعد',
                adm_section_hall: 'الصالة',
                adm_section_balcony: 'البلكون',
                adm_revenue: 'الإيرادات',
                adm_tickets_approved: 'تذكرة معتمدة',

                /* ===== admin: KPI cards ===== */
                adm_kpi_revenue_label: 'إجمالي الإيرادات المعتمدة',
                adm_kpi_revenue_caption: 'من الحجوزات المعتمدة فقط',
                adm_kpi_pending: 'حجوزات بتنتظر مراجعتك',
                adm_kpi_pending_pill: 'يحتاج إجراء',
                adm_kpi_pending_caption: 'افتح القائمة وادخل وافق أو ارفض.',
                adm_kpi_approved: 'حجوزات معتمدة',
                adm_kpi_approved_caption: 'إجمالي الحجوزات اللي اتأكدت',
                adm_kpi_shows: 'عروض',
                adm_kpi_shows_caption: 'إجمالي العروض على المنصة',
                adm_kpi_showtimes: 'مواعيد',
                adm_kpi_showtimes_caption: 'مواعيد عرض مفعلة',
                adm_kpi_remaining: 'تذاكر متبقية',
                adm_kpi_remaining_caption: 'متاح للحجز الآن',

                /* ===== admin: status pills ===== */
                adm_status_pending: 'قيد المراجعة',
                adm_status_approved: 'معتمد',
                adm_status_rejected: 'مرفوض',
                adm_status_available: 'متاح',
                adm_status_sold_out: 'نفدت التذاكر',

                /* ===== admin: quick actions ===== */
                adm_quick_title: 'الأدوات السريعة',
                adm_quick_bookings_eyebrow: 'الحجوزات',
                adm_quick_bookings_title: 'إدارة الحجوزات',
                adm_quick_bookings_body: 'راجع، اعتمد، أو ارفض الحجوزات وأرسل التذاكر للعملاء.',
                adm_quick_shows_eyebrow: 'العروض',
                adm_quick_shows_title: 'إدارة العروض والمواعيد',
                adm_quick_shows_body: 'أضف عروض جديدة، عدّل التفاصيل، وأدِر المواعيد المتاحة.',
                adm_quick_scanner_eyebrow: 'البوابة',
                adm_quick_scanner_title: 'سكانر التذاكر',
                adm_quick_scanner_body: 'افتح كاميرا الموبايل وافحص تذاكر QR على الباب.',

                /* ===== admin: shows list ===== */
                adm_shows_pill: 'العروض',
                adm_shows_title: 'إدارة العروض',
                adm_shows_add: 'إضافة عرض جديد',
                adm_shows_times: 'المواعيد',
                adm_shows_empty: 'مفيش عروض لسه. اضغط "إضافة عرض جديد" عشان تبدأ.',
                adm_show_active: 'مفعل',
                adm_show_hidden: 'مخفي',

                /* ===== admin: show form (create / edit) ===== */
                adm_show_new_pill: 'عرض جديد',
                adm_show_new_title: 'إضافة عرض جديد',
                adm_show_edit_pill: 'تعديل العرض',
                adm_show_edit_title: 'تعديل بيانات العرض',
                adm_show_basic: 'البيانات الأساسية',
                adm_show_title_label: 'اسم العرض',
                adm_show_theater: 'المسرح',
                adm_show_description: 'وصف العرض',
                adm_show_description_helper: 'وصف مختصر يظهر في صفحة العرض.',
                adm_show_anba_helper: 'لو فعلت تسعير الصالة والبلكون، السعر هيتقسم تلقائيًا حسب القسم.',
                adm_show_anba_helper_short: 'تسعير منفصل للصالة والبلكون',
                adm_show_hall_price: 'سعر الصالة',
                adm_show_balcony_price: 'سعر البلكون',
                adm_show_visibility: 'الظهور',
                adm_show_visibility_label: 'مفعل للجمهور',
                adm_show_visibility_helper: 'لو شيلت العلامة، العرض هيختفي من الصفحة الرئيسية.',
                adm_show_poster: 'بوستر العرض',
                adm_show_poster_pick: 'اختر صورة',
                adm_show_poster_replace: 'تغيير الصورة',
                adm_show_poster_hint: 'PNG / JPG · أفضل أبعاد 1200×1600',
                adm_show_ticket_design: 'تصميم التذكرة',
                adm_show_ticket_design_helper: 'صورة الخلفية اللي بتطلع عليها بيانات التذكرة وكود QR.',
                adm_show_ticket_template_file: 'قالب التذكرة',
                adm_show_ticket_template_pick: 'اختر صورة قالب',
                adm_show_ticket_template_replace: 'تغيير القالب',
                adm_show_ticket_template_hint: 'PNG شفافة بأبعاد 2480×3508 (A4 @300dpi) لأفضل جودة.',
                adm_show_ticket_template_hint_short: 'PNG / JPG · أفضل أبعاد A4',
                adm_show_qr_helper: 'حدد مكان وحجم QR على القالب (بالبكسل).',
                adm_show_qr_x: 'موقع QR · X',
                adm_show_qr_y: 'موقع QR · Y',
                adm_show_qr_size: 'حجم QR',
                adm_show_create_btn: 'إضافة العرض',
                adm_show_save_btn: 'حفظ التغييرات',

                /* ===== admin: show times list ===== */
                adm_times_pill: 'المواعيد',
                adm_times_title: 'مواعيد العرض',
                adm_times_add: 'إضافة موعد جديد',
                adm_times_empty: 'مفيش مواعيد لسه. اضغط "إضافة موعد جديد".',
                adm_times_col_date: 'التاريخ',
                adm_times_col_time: 'الوقت',
                adm_times_col_total: 'إجمالي',
                adm_times_col_avail: 'المتاح',
                adm_times_col_avail_short: 'المتبقي',
                adm_times_col_price: 'السعر',
                adm_times_col_price_split: 'الأسعار',
                adm_times_col_status: 'الحالة',
                adm_times_col_actions: 'إجراءات',
                adm_showtimes_title: 'المواعيد',
                adm_showtimes_empty: 'لا توجد مواعيد لهذا العرض.',
                adm_th_date: 'التاريخ',
                adm_th_time: 'الوقت',
                adm_th_show: 'العرض',
                adm_th_total: 'إجمالي',
                adm_th_remaining: 'المتبقي',

                /* ===== admin: show time form (create / edit) ===== */
                adm_time_new_pill: 'موعد جديد',
                adm_time_new_title: 'إضافة موعد عرض',
                adm_time_edit_pill: 'تعديل الموعد',
                adm_time_edit_title: 'تعديل بيانات الموعد',
                adm_time_section_when: 'الموعد',
                adm_time_section_when_sub: 'حدد التاريخ والوقت اللي العرض هيتقدم فيه.',
                adm_time_section_pricing: 'التسعير وعدد التذاكر',
                adm_time_section_pricing_helper: 'حدد سعر التذكرة وعدد التذاكر المتاحة.',
                adm_time_section_pricing_split: 'تسعير الصالة والبلكون مفعل من العرض. عدّل الأسعار من بيانات العرض.',
                adm_time_ticket_price: 'سعر التذكرة',
                adm_time_total: 'إجمالي التذاكر',
                adm_time_available_now: 'المتاح حاليًا',
                adm_time_available_helper: 'اتركه فاضي عشان يستخدم نفس الإجمالي.',
                adm_time_available_placeholder: 'نفس الإجمالي',
                adm_time_section_status: 'الحالة',
                adm_time_status_label: 'متاح للحجز',
                adm_time_status_helper: 'لو شيلت العلامة الموعد هيختفي من الحجز.',
                adm_time_force_sold_out: 'إجبار "نفدت التذاكر"',
                adm_time_save_btn: 'حفظ',

                /* ===== admin: bookings list ===== */
                adm_bookings_eyebrow: 'الحجوزات',
                adm_bookings_title: 'إدارة الحجوزات',
                adm_bookings_search_placeholder: 'ابحث بالاسم أو الموبايل أو الكود...',
                adm_filter_all: 'الكل',
                adm_filter_pending: 'قيد المراجعة',
                adm_filter_approved: 'معتمد',
                adm_filter_rejected: 'مرفوض',
                adm_filter_all_times: 'كل المواعيد',
                adm_bk_col_guest: 'الضيف',
                adm_bk_col_show: 'العرض / الموعد',
                adm_bk_col_status: 'الحالة',
                adm_bk_col_ticket: 'تذاكر',
                adm_bk_col_actions: 'إجراءات',
                adm_bk_col_code: 'الكود',
                adm_bk_details: 'تفاصيل',
                adm_bk_no_match: 'مفيش حجوزات تطابق الفلاتر دي.',
                adm_bk_reset_filters: 'إعادة ضبط الفلاتر',

                /* ===== admin: booking detail (show) ===== */
                adm_bk_pill_prefix: 'حجز',
                adm_bk_tickets_title: '🎟️ التذاكر',
                adm_bk_tickets_word: 'تذاكر',
                adm_bk_received: 'تم الإرسال',
                adm_bk_not_received: 'لم يُرسل بعد',
                adm_bk_view_ticket: '🎫 عرض التذكرة',
                adm_bk_resend: 'إعادة إرسال',
                adm_bk_resend_ok: 'تم إرسال التذاكر مرة أخرى',
                adm_bk_resend_fail: 'حصل خطأ، حاول مرة تانية',
                adm_bk_summary_title: '📋 ملخص الحجز',
                adm_bk_summary_eyebrow: 'تفاصيل',
                adm_bk_count: 'عدد التذاكر',
                adm_bk_price: 'السعر',
                adm_bk_status: 'الحالة',
                adm_bk_status_approved: '✅ معتمد',
                adm_bk_status_rejected: '❌ مرفوض',
                adm_bk_status_pending: '⏳ قيد المراجعة',
                adm_bk_ref: 'كود الحجز',
                adm_bk_name: 'الاسم',
                adm_bk_phone: 'الموبايل',
                adm_bk_transfer_title: 'صورة التحويل',
                adm_bk_transfer_eyebrow: 'إثبات الدفع',
                adm_bk_delete_title: 'حذف الحجز؟',
                adm_bk_delete_body: 'هتمسح الحجز ده نهائيًا. مش هتقدر ترجعه.',
                adm_bk_delete_ok: 'حذف',
                adm_bk_delete_btn: '🗑️ حذف الحجز',
                adm_bk_actions_aria: 'إجراءات الحجز',
                adm_bk_pending_label: 'الحجز قيد المراجعة',
                adm_bk_reject_title: 'رفض الحجز؟',
                adm_bk_reject_body: 'العميل هيتبلغ بالرفض. متأكد؟',
                adm_bk_reject_ok: 'رفض',
                adm_bk_reject_btn: 'رفض',
                adm_bk_approve_title: 'اعتماد الحجز؟',
                adm_bk_approve_body: 'هيتم اعتماد الحجز وإرسال التذاكر للعميل.',
                adm_bk_approve_ok: 'اعتماد',
                adm_bk_approve_btn: 'اعتماد',

                /* ===== admin: payments settings ===== */
                adm_pay_pill: 'إعدادات التحويلات',
                adm_pay_title: '💳 إعدادات التحويلات',
                adm_pay_wallet_label: 'رقم المحفظة',
                adm_pay_wallet_hint: 'مثلاً: 0100xxxxxxx',
                adm_pay_insta_label: 'حساب InstaPay',
                adm_pay_insta_hint: 'مثلاً: EGxxxxxxxxxx أو email@domain.com',
                adm_pay_save: 'حفظ الإعدادات',
                adm_payments_eyebrow: 'وسائل الدفع',
                adm_payments_title: 'إعدادات التحويلات',
                adm_payments_hint: 'اضبط أرقام المحفظة و InstaPay اللي بيظهروا للعميل.',
                adm_payments_wallet: 'محفظة',
                adm_payments_save: 'حفظ',

                /* ===== admin: scanner ===== */
                adm_scanner_pill: 'سكانر البوابة',
                adm_scanner_title: '🎫 سكانر البوابة',
                adm_scanner_ready: 'جاهز للفحص',
                adm_scanner_flash: '🔦 فلاش',
                adm_scanner_restart: '🔄 إعادة',
                adm_scanner_ok: '✅ دخول مسموح',
                adm_scanner_used: '⚠️ مستخدمة',
                adm_scanner_invalid: '❌ غير صالح',
                adm_scanner_entered: 'دخل',
                adm_scanner_no_torch: 'الفلاش غير مدعوم',

                /* ===== auth ===== */
                auth_admin_pill: 'تسجيل الدخول',
                auth_admin_title: 'دخول الأدمن',
                auth_admin_subtitle: 'سجّل دخولك لإدارة العروض والحجوزات.',
                auth_email: 'البريد الإلكتروني',
                auth_password: 'كلمة المرور',
                auth_password_confirm: 'تأكيد كلمة المرور',
                auth_name: 'الاسم',
                auth_login_btn: 'تسجيل الدخول',
                auth_register_title: 'إنشاء حساب جديد',
                auth_register_btn: 'إنشاء الحساب',
                auth_forgot_password: 'نسيت كلمة المرور؟',
                auth_reset_pill: 'إعادة تعيين',
                auth_reset_title: 'إعادة تعيين كلمة المرور',
                auth_reset_btn: 'تحديث كلمة المرور',
                auth_send_reset_link: 'إرسال رابط الاستعادة',
                auth_confirm_pwd_title: 'تأكيد كلمة المرور',
                auth_confirm_pwd_subtitle: 'أكد كلمة المرور قبل المتابعة.',
                auth_verify_title: 'تأكيد بريدك الإلكتروني',
                auth_verify_check_email: 'افحص بريدك للحصول على رابط التفعيل.',
                auth_verify_resent: 'تم إرسال رابط جديد لبريدك.',
                auth_verify_didnt_receive: 'لم يصلك الرابط؟',
                auth_verify_resend_link: 'إرسال رابط جديد'
            },
            en: {
                /* ===== brand / nav / footer / theme / lang chrome ===== */
                brand: 'PREMIUM', brand_tag: 'TICKETS · STAGE',
                nav_home: 'Home', nav_shows: 'Shows', nav_admin: 'Admin',
                foot_fast: 'Instant booking', foot_secure: 'Secure payment', foot_qr: 'QR via WhatsApp',
                foot_about: 'Egyptian theater ticketing platform built for a fast, premium experience on mobile and desktop.',
                foot_quick: 'Quick links', foot_legal: 'Support',
                theme_label: 'Theme', theme_light: 'Light', theme_dark: 'Dark',
                theme_toggle_aria: 'Toggle theme',
                lang_label: 'Language', menu_open: 'Open menu', menu_close: 'Close menu',
                primary_nav: 'Primary', mobile_nav: 'Mobile',

                /* ===== sticky bar / generic CTAs / modals / toasts ===== */
                bar_total: 'TOTAL', bar_seats: 'Selected seats',
                btn_confirm: 'Confirm booking', btn_cancel: 'Cancel', btn_continue: 'Continue',
                btn_approve: 'Approve booking', btn_reject:  'Reject booking',
                btn_back: 'Back', btn_save: 'Save', btn_save_changes: 'Save changes',
                btn_book_now: 'Book now', btn_details_book: 'Details & booking',
                btn_back_shows: 'Back to all shows',
                modal_processing: 'Sending request...',
                modal_processing_body: 'Please hold on a moment, do not close the page.',
                modal_confirm_title: 'Confirm',

                /* ===== homepage hero ===== */
                cta_browse: 'Browse shows',
                hero_eyebrow: 'Live booking · Egyptian stage',
                hero_title_a: 'Book your seat',
                hero_title_b: 'on stage',
                hero_sub: 'A premium ticketing experience: pick a show, choose your seat from a live map, pay securely, and get your QR ticket on WhatsApp.',
                hero_cta_primary: 'Browse shows', hero_cta_secondary: 'How it works',
                hero_stat_shows_label: 'live shows',
                hero_stat_seats_label: 'seats ready',
                hero_stat_qr_label: 'instant QR tickets',
                trust_instant: 'Instant booking', trust_secure: 'Secure payment',
                trust_qr: 'QR via WhatsApp', trust_mobile: 'Mobile-first',
                trust_247: 'Available 24/7', trust_seat: 'Live seat picker',
                how_title: 'How it works',
                how_sub: 'Three simple steps from pick to WhatsApp.',
                how_sub_4: 'Four cinematic steps from picking a show to your QR ticket.',
                how_1_t: 'Pick a show', how_1_b: 'Browse available shows and choose your date.',
                how_2_t: 'Choose seats', how_2_b: 'Pick your seats from a live theater map and pay securely.',
                how_3_t: 'Receive ticket', how_3_b: 'A QR ticket arrives on WhatsApp in under a minute.',
                cine_1_t: 'Choose your show',
                cine_1_b: 'Browse the live lineup and pick a date that fits your night out — in one tap.',
                cine_2_t: 'Pick your seats',
                cine_2_b: 'A live theater map shows what\u2019s open in real time so you can lock in the perfect seats.',
                cine_3_t: 'Upload your transfer',
                cine_3_b: 'Transfer to wallet or InstaPay, drop the screenshot, and we take it from there — secure and elegant.',
                cine_4_t: 'Receive your QR ticket',
                cine_4_b: 'Your QR ticket lands on WhatsApp the moment we approve — ready to scan at the door.',
                // Cinematic homepage v3 (full-screen scenes)
                cine_intro_eyebrow: 'PREMIUM TICKETS',
                cine_intro_line_a: 'Discover',
                cine_intro_line_b: 'ticket booking',
                cine_intro_line_c: 'differently.',
                cine_intro_sub: 'A cinematic mobile booking experience — from picking a show to your QR ticket on WhatsApp.',
                cine_scroll_cue: 'Scroll down',
                cine_prologue_eyebrow: 'Welcome',
                cine_prologue_title_a: 'Booking',
                cine_prologue_title_b: 'like never before.',
                cine_prologue_body: 'Pick a show, book your seat from a live map, pay securely, and receive your QR ticket on WhatsApp — all from your phone.',
                cine_prologue_tag_1: 'Cinematic',
                cine_prologue_tag_2: 'Live',
                cine_prologue_tag_3: 'Secure',
                cine_step_eyebrow_1: 'Step one',
                cine_step_eyebrow_2: 'Step two',
                cine_step_eyebrow_3: 'Step three',
                cine_step_eyebrow_4: 'Step four',
                cine_shows_eyebrow: 'Live shows now',
                shows_title: 'Available shows', shows_sub: 'Pick a show and start booking.',
                shows_eyebrow_featured: 'Featured show',
                shows_pill_times: 'showtimes',
                shows_pill_times_one: '1 showtime',
                shows_pill_no_times: 'No showtimes',
                shows_no_poster: 'No poster',
                shows_from: 'From',
                shows_per_seat: 'EGP / seat',
                shows_per_ticket: 'EGP / ticket',
                shows_starts_from: 'starts from',
                shows_egp: 'EGP',
                shows_section_balcony_hall: 'Balcony / Hall',
                shows_status_available: 'Available',
                shows_status_few: 'Only',
                shows_status_few_suffix: 'left',
                shows_status_sold: 'Sold Out',
                shows_no_times_card: 'No showtimes available right now.',
                shows_empty_title: 'No shows available right now',
                shows_empty_body: 'Stay tuned — new shows are coming up soon.',

                /* ===== show details page ===== */
                show_pill_kind: 'Theater show',
                show_pill_online: 'Online booking',
                show_pill_qr: 'QR ticket',
                show_times_title: 'Available showtimes',
                show_prices_label: 'Prices:',
                show_price_label: 'Ticket price:',
                show_no_times: 'No showtimes available for this show right now.',

                /* ===== booking step 1 (anba section pick) ===== */
                step_section: 'Section', step_seat: 'Seat', step_confirm: 'Confirm',
                pick_section_title: 'Pick a section',
                pick_section_sub: 'Choose the section you want to book in',
                section_hall: 'Hall', section_hall_en: 'Hall',
                section_balcony: 'Balcony', section_balcony_en: 'Balcony',
                section_hall_meta: 'Pick your seat from the hall map',
                section_soon: 'Coming soon',
                pay_eyebrow: '💸 Pay your ticket via',
                pay_wallet: '📱 Wallet',
                pay_insta: '⚡ InstaPay',

                /* ===== seat picker (anba) ===== */
                seat_back: 'Back',
                seat_admin_title: 'Manage seats',
                seat_pick_title: 'Pick your seat',
                seat_zoom_out: 'Zoom out', seat_zoom_reset: 'Reset', seat_zoom_in: 'Zoom in',
                seat_map: 'Seat map',
                seat_canvas_aria: 'Hall seat map',
                seat_gesture_hint: 'Pinch with two fingers to zoom and pan',
                seat_legend_hint_user: 'Drag to pan · pinch or double-tap to zoom · seats marked ✕ are admin-only',
                seat_legend_hint_admin: 'Drag to pan · pinch or double-tap to zoom · tap any seat to block or unblock it',
                seat_admin_mode: 'Admin mode',
                seat_per_seat: 'EGP / seat',
                seat_step1_pay: 'Step 1 · pay your reservation',
                seat_wallet: 'Wallet',
                seat_admin_panel_title: 'Manage seats',
                seat_user_panel_title: 'Pick your seats',
                seat_admin_instructions: 'Tap any seat to block or unblock it. Changes are saved with the button below.',
                seat_user_instructions: 'Tap any seat to select it. You can pick more than one. Tap again to deselect.',
                seat_legend_available: 'Available',
                seat_legend_selected: 'Selected',
                seat_legend_reserved: 'Reserved',
                seat_legend_admin: 'Admin',
                seat_selected_label: 'Selected seats',
                seat_none_selected: 'No seats selected yet',
                seat_total: 'Total',
                seat_save_changes: 'Save changes',
                seat_complete_booking: 'Continue booking',
                seat_back_shows_admin: 'Back to shows',
                seat_back_section: 'Back to section',
                seat_pending_changes: 'Pending changes',
                seat_chip_selected: 'Selected',
                seat_chip_seat: 'seat',

                /* ===== booking form (step 3) ===== */
                book_show_details: 'Show details',
                book_step1_title: 'Step 1: pay your ticket',
                book_step1_desc: 'Send {amount} EGP to one of the numbers below:',
                book_step2_title: 'Step 2: upload screenshot and fill details',
                book_seats_title: 'Your seats',
                book_change_seats: 'Change seats',
                book_attendees_title: 'Attendee details',
                book_attendees_desc: 'Enter the name and WhatsApp number for each seat holder.',
                book_seat_label: 'Seat',
                book_name: 'Name', book_name_ph: 'e.g. Mina George',
                book_phone: 'WhatsApp', book_phone_ph: '01XXXXXXXXX',
                book_required: 'required',
                book_attendee_n: 'Attendee #',
                book_screenshot_title: '📸 Transfer screenshot',
                book_screenshot_desc: 'Upload a screenshot of your transfer (PNG or JPG).',
                book_upload_click: 'Click to upload',
                book_upload_replace: 'Replace image',
                book_upload_remove: 'Remove',
                book_total: 'Total',
                book_total_x_seats: '{n} seats',
                book_continue_cta: 'Confirm booking',
                book_dock_eyebrow: 'Booking total',
                book_dock_total: 'Total',
                book_dock_hint_missing: 'Fill in attendee details and upload a transfer screenshot.',
                book_tickets_count: '👥 Number of tickets',
                book_screenshot_legacy: '📸 Transfer screenshot',
                book_send_request: 'Send booking request',
                book_no_seats_redirect: 'No seats selected — sending you back to pick seats.',
                book_invalid_session: 'Session expired, please pick your seats again.',

                /* ===== bookings/create non-anba form ===== */
                book_form_show_details: '🎭 Show details',
                book_step1_pay_title: 'Step 1: pay your ticket',
                book_step1_pay_desc_a: 'Send',
                book_step1_pay_desc_b: 'EGP to one of the numbers below:',
                book_form_person_label: '👤 Attendee #',
                book_form_name_ph: 'Attendee {n} name',
                book_form_phone_ph: 'Attendee {n} WhatsApp number',
                book_no_tickets_alert: '❌ No tickets available. Max available: {n}',
                book_sending: 'Submitting...',

                /* ===== bookings/form (final attendee form) ===== */
                form_add_edit_seats: 'Add / change seats',
                form_chips_hint: 'Tap × on any seat to remove it, or tap "Add / change seats" to go back to the seat map.',
                form_loading_seats: 'Loading your selection...',
                form_chips_empty: 'No seats selected anymore',
                form_chip_remove_aria: 'Remove {label}',
                form_steps_title: '📌 How to complete your booking',
                form_step1_a: 'Send the total',
                form_step1_b: 'to one of the wallet / InstaPay numbers below.',
                form_step2: 'Take a screenshot of the transfer receipt and upload it in the field below.',
                form_step3: 'Enter a name and WhatsApp number for each attendee, in the same order as the seats.',
                form_step4_a: 'Tap',
                form_step4_b: '— we’ll review your request and send the tickets to the WhatsApp number within',
                form_step4_24h: '24 hours',
                form_step4_max: 'at most.',
                form_attendees_title: '👥 Attendee details',
                form_attendees_hint: 'Enter a name and WhatsApp number for each seat',
                form_screenshot: 'Transfer receipt',
                form_required: 'required',
                form_name_label: 'Name',
                form_phone_label: 'WhatsApp number',
                form_dock_aria: 'Booking summary',
                form_dock_hint: 'Please complete the required fields',
                form_confirm_btn: 'Confirm booking',
                form_at_least_one: '❌ Please select at least one seat',
                form_confirm_modal_title: 'Confirm your booking',
                form_confirm_modal_body: 'You’re submitting your booking for review. Once approved, your ticket will be sent on WhatsApp.',
                form_confirm_ok: 'Confirm',
                form_confirm_cancel: 'Cancel',

                /* ===== bookings/thankyou ===== */
                thx_title: 'Your booking request was sent successfully',
                thx_thanks_prefix: 'Thank you,',
                thx_ref_label: 'Booking reference',
                thx_total_label: 'Total amount',
                thx_next_step: 'What happens next',
                thx_step1_html: 'We’ll <span class="text-[color:var(--prism-text)] font-semibold">review your payment</span> and verify the transfer.',
                thx_step2_html: 'Once your <span class="text-[color:var(--prism-emerald)] font-semibold">booking is confirmed</span>, your <span class="text-[color:var(--prism-text)] font-semibold">ticket</span> will be sent directly to the <span class="text-[color:var(--prism-text)] font-semibold">WhatsApp number you registered</span>.',
                thx_step3_html: 'Review usually takes up to <span class="text-[color:var(--prism-text)] font-semibold">24 hours</span>.',
                thx_footer_html: 'If anything is wrong with the transfer or your details, we’ll reach out before rejecting the request.<br>Don’t worry — your booking is safely stored on our system ✨',
                thx_back_home: 'Back to home',
                thx_countdown_label: 'Show starts in',
                thx_countdown_now: 'The show has started! 🎉',
                thx_countdown_days: 'd',
                thx_countdown_hours: 'h',
                thx_countdown_mins: 'm',
                thx_add_calendar: 'Add to calendar',
                thx_share_wa: 'Share on WhatsApp',
                thx_share_wa_text: 'I just booked a ticket for "{title}" on {date} 🎭',
                common_egp: 'EGP',

                /* ===== Wave 1 quick wins (copy / share / favorites / ribbons) ===== */
                copy_aria: 'Copy',
                copy_done: 'Copied ✓',
                copy_failed: 'Copy failed',
                fav_save_aria: 'Save to favorites',
                fav_unsave_aria: 'Remove from favorites',
                fav_saved_toast: 'Added to favorites',
                fav_unsaved_toast: 'Removed from favorites',
                fav_pill: 'Saved',
                ribbon_trending: 'Trending',
                ribbon_selling_fast: 'Selling fast',
                ribbon_last_n: 'Last {n} seats',
                share_wa: 'Share on WhatsApp',
                share_wa_text: 'Book your ticket for "{title}" 🎭',
                shows_skip_pill: 'Skip to shows ↓',
                seat_auto_pick: 'Auto-pick best seats',
                seat_auto_pick_done: 'Best seats picked for you',
                seat_auto_pick_none: 'Not enough adjacent seats free',
                seat_auto_pick_prompt: 'How many seats?',

                /* ===== auth pages ===== */
                auth_admin_pill: 'Admin Access',
                auth_admin_title: 'Admin Login',
                auth_admin_subtitle: 'Sign in to access the dashboard',
                auth_email: 'Email address',
                auth_password: 'Password',
                auth_password_confirm: 'Confirm password',
                auth_name: 'Name',
                auth_login_btn: 'Sign in',
                auth_register_btn: 'Create account',
                auth_register_title: 'Create your account',
                auth_forgot_password: 'Forgot your password?',
                auth_reset_pill: 'Reset password',
                auth_reset_title: 'Reset your password',
                auth_reset_btn: 'Reset password',
                auth_send_reset_link: 'Send reset link',
                auth_confirm_pwd_title: 'Confirm password',
                auth_confirm_pwd_subtitle: 'Please confirm your password before continuing.',
                auth_verify_title: 'Verify your email address',
                auth_verify_resent: 'A fresh verification link has been sent to your email address.',
                auth_verify_check_email: 'Before continuing, please check your email for a verification link.',
                auth_verify_didnt_receive: 'If you didn’t receive the email,',
                auth_verify_resend_link: 'click here to request another',

                /* ===== thank you page ===== */
                thx_title: 'Booking received',
                thx_sub: 'We\u2019ll review the transfer and confirm your booking on WhatsApp shortly.',
                thx_back: 'Back to home',

                /* ===== auth ===== */
                auth_admin_pill: 'Admin Access',
                auth_admin_title: 'Admin sign in',
                auth_admin_sub: 'Sign in to the admin dashboard',
                auth_email: 'Email address',
                auth_password: 'Password',
                auth_confirm_password: 'Confirm password',
                auth_login: 'Sign in',
                auth_register: 'Register',
                auth_register_pill: 'Register',
                auth_name: 'Name',
                auth_verify_title: 'Verify your email',
                auth_verify_resent: 'A fresh verification link has been sent to your email.',
                auth_verify_body: 'Please check your email for a verification link before continuing.',
                auth_verify_didnt: 'Didn\u2019t receive the email?',
                auth_verify_request: 'Click here to send a new one',
                auth_reset_pill: 'Reset password',
                auth_reset_title: 'Reset password',
                auth_reset_send: 'Send reset link',
                auth_confirm_title: 'Confirm password',
                auth_confirm_sub: 'Please confirm your password before continuing.',
                auth_confirm_btn: 'Confirm password',
                auth_forgot: 'Forgot your password?',

                /* ===== admin: dashboard ===== */
                admin_dash_pill: 'Admin Dashboard',
                admin_dash_title: 'Admin dashboard',
                admin_dash_sub: 'Track your shows, bookings, and tickets — all in one place.',
                admin_kpi_revenue: 'Approved revenue',
                admin_kpi_revenue_desc: 'Sum of approved tickets across all showtimes.',
                admin_kpi_revenue_currency: 'EGP',
                admin_kpi_pending: 'Pending review',
                admin_kpi_pending_unit: 'need review',
                admin_kpi_pending_desc: 'Booking requests awaiting screenshot approval.',
                admin_kpi_section: 'Key metrics',
                admin_kpi_shows: 'Shows',
                admin_kpi_shows_desc: 'Total number of published shows.',
                admin_kpi_times: 'Showtimes',
                admin_kpi_times_desc: 'How many times your shows will run on stage.',
                admin_kpi_approved: 'Approved tickets',
                admin_kpi_approved_desc: 'Bookings that were approved and got a QR ticket.',
                admin_kpi_remaining: 'Remaining tickets',
                admin_kpi_remaining_desc: 'Difference between total seats per showtime and approved tickets.',
                admin_quick: 'Quick actions',
                admin_quick_shows: 'Manage shows',
                admin_quick_shows_pill: 'Theater shows',
                admin_quick_shows_desc: 'Add new shows, edit details, upload posters, and toggle visibility on the site.',
                admin_quick_bookings: 'Manage bookings',
                admin_quick_bookings_pill: 'Bookings & transfers',
                admin_quick_bookings_desc: 'Review booking requests, verify transfers, approve tickets, and send QR codes.',
                admin_quick_scanner: 'At the door',
                admin_quick_scanner_pill: 'QR ticket scanner',
                admin_quick_scanner_desc: 'Open this on the staff phone at the gate to scan and validate each ticket.',
                admin_table_title: 'Showtimes & tickets per show',
                admin_table_h_show: 'Show',
                admin_table_h_date: 'Date',
                admin_table_h_time: 'Time',
                admin_table_h_total: 'Total',
                admin_table_h_approved: 'Approved',
                admin_table_h_pending: 'Pending',
                admin_table_h_remaining: 'Remaining',
                admin_table_h_revenue: 'Revenue',
                admin_table_empty: 'No showtimes registered yet.',
                admin_pay_settings_pill: 'Payment settings',
                admin_pay_settings_title: 'Payment settings',
                admin_pay_wallet: 'Wallet number',
                admin_pay_insta: 'InstaPay',
                admin_pay_help: 'Shown to the customer on the payment page so they can transfer.',
                admin_pay_save: 'Save payment details',

                /* ===== admin: shows index/edit/create ===== */
                admin_shows_title: 'Manage shows',
                admin_shows_pill: 'Manage shows',
                admin_shows_add: '+ Add show',
                admin_shows_back: 'Back',
                admin_shows_empty: 'No shows yet.',
                admin_shows_active: 'Active',
                admin_shows_hidden: 'Hidden',
                admin_shows_times: 'Showtimes',
                admin_shows_edit: 'Edit',
                admin_shows_delete: 'Delete',
                admin_shows_delete_confirm: 'Are you sure you want to delete this show?',
                admin_show_create_title: 'Add a new show',
                admin_show_edit_title: 'Edit show',
                admin_show_back_to_list: 'Back to shows',
                admin_show_data: 'Show details',
                admin_show_name: 'Show name',
                admin_show_desc: 'Show description',
                admin_show_desc_help: 'Shown under the title on the details page and on cards.',
                admin_show_pricing: 'Theater type & prices',
                admin_show_anba_note: 'Anba Ruweis uses per-section pricing (Balcony / Hall). Ticket prices appear below.',
                admin_show_balcony_price: 'Balcony ticket price (EGP)',
                admin_show_hall_price: 'Hall ticket price (EGP)',
                admin_show_poster: 'Show poster',
                admin_show_poster_optional: 'Optional',
                admin_show_poster_click: 'Click to choose poster image',
                admin_show_poster_format: 'PNG / JPG · portrait ratio (2:3) recommended',
                admin_show_poster_replace: 'Replace poster',
                admin_show_ticket_design: 'Ticket design & QR placement',
                admin_show_ticket_design_desc: 'Upload a ticket design (PNG / JPG) and place the QR box by dragging or with numbers. If no design is uploaded, the system generates a plain QR.',
                admin_show_ticket_file: 'Ticket design file',
                admin_show_ticket_upload: 'Click to upload ticket design',
                admin_show_ticket_upload_help: 'After uploading you can move and resize the QR box on top of the design.',
                admin_show_ticket_design_preview: 'Ticket design',
                admin_show_qr_x: 'X (from left)',
                admin_show_qr_y: 'Y (from top)',
                admin_show_qr_size: 'QR size',
                admin_show_qr_help: 'Drag the QR box on the image, and use the corner handle to resize. Coordinates are computed automatically against the original artwork (in pixels).',
                admin_show_visibility: 'Visibility',
                admin_show_visible: 'Show this on the website',
                admin_show_cancel: 'Cancel',
                admin_show_create_btn: 'Create show',
                admin_show_save: 'Save changes',

                /* ===== admin: show times ===== */
                admin_times_title: 'Showtimes',
                admin_times_add: '+ Add showtime',
                admin_times_empty: 'No showtimes for this show yet.',
                admin_time_create_title: 'Add showtime',
                admin_time_edit_title: 'Edit showtime',
                admin_time_back_to_times: 'Back to showtimes',
                admin_time_section_when: 'Schedule',
                admin_time_date: 'Date',
                admin_time_time: 'Time',
                admin_time_section_pricing: 'Price & inventory',
                admin_time_anba_pricing: 'Prices from the show (per section)',
                admin_time_anba_help: 'This show uses per-section pricing. Edit prices from the show edit page.',
                admin_time_total: 'Total tickets',
                admin_time_price: 'Ticket price (EGP)',
                admin_time_status: 'Status',
                admin_time_state: 'Showtime status',
                admin_time_sold_out_help: 'When Sold Out is on, this showtime is hidden from booking pages.',

                /* ===== admin: bookings index/show ===== */
                admin_bookings_title: 'Bookings',
                admin_bookings_pill: 'Manage bookings',
                admin_bookings_back: 'Back',
                admin_bookings_filter: 'Filter',
                admin_bookings_status_all: 'All',
                admin_bookings_status_pending: 'Pending',
                admin_bookings_status_approved: 'Approved',
                admin_bookings_status_rejected: 'Rejected',
                admin_bookings_h_user: 'Customer',
                admin_bookings_h_show: 'Show',
                admin_bookings_h_date: 'Date',
                admin_bookings_h_seats: 'Seats',
                admin_bookings_h_amount: 'Amount',
                admin_bookings_h_status: 'Status',
                admin_bookings_empty: 'No bookings yet.',
                admin_bookings_view: 'View',
                admin_booking_detail: 'Booking detail',
                admin_booking_attendees: 'Attendees',
                admin_booking_screenshot: 'Transfer screenshot',
                admin_booking_seat_pill: 'Seat',
                admin_booking_resend: 'Resend QR',
                admin_booking_resend_done: 'Tickets sent again',
                admin_booking_resend_err: 'Something went wrong, please try again',

                /* ===== admin: payments settings ===== */
                admin_payments_title: 'Payment settings',
                admin_payments_back: 'Back to dashboard',
                admin_payments_wallet: 'Wallet number (optional)',
                admin_payments_wallet_eg: 'e.g. 0100xxxxxxx',
                admin_payments_insta: 'InstaPay account (optional)',
                admin_payments_insta_eg: 'e.g. EGxxxxxxxxxx or email@domain.com',
                admin_payments_save: 'Save settings',

                /* ===== scanner ===== */
                scanner_pill: 'Gate Scanner',
                scanner_title: '🎫 Gate Scanner',
                scanner_back: 'Back',
                scanner_ready: 'Ready to scan',
                scanner_flash: '🔦 Flash',
                scanner_restart: '🔄 Restart',
                scanner_status_ok: '✅ Allowed',
                scanner_status_used: '⚠️ Already used',
                scanner_status_invalid: '❌ Invalid',
                scanner_entered: 'Entered',
                scanner_flash_unsupported: 'Flash not supported',

                /* ===== validation / common ===== */
                err_required_name: 'Please enter a name',
                err_required_phone: 'Please enter a WhatsApp number',
                err_invalid_phone: 'WhatsApp number must be 11 digits and start with 01',
                err_required_screenshot: 'Please upload a transfer screenshot',
                err_select_seats: 'Please select at least one seat',
                err_seat_taken: 'This seat was just taken — please pick another.',
                err_save_failed: 'Could not save changes — please try again.',
                ok_seats_saved: 'Changes saved',
                ok_seat_blocked: 'Seat blocked',
                ok_seat_unblocked: 'Seat unblocked',

                /* ===== common (shared across admin / auth) ===== */
                common_cancel: 'Cancel',
                common_currency: 'EGP',
                common_currency_short: 'EGP',
                common_egp: 'EGP',
                common_optional: '(optional)',
                common_ticket_word: 'tickets',

                /* ===== admin: console / dashboard ===== */
                adm_console_pill: 'Admin Console',
                adm_console_eyebrow: 'PREMIUM · CONTROL',
                adm_dashboard_title: 'Admin dashboard',
                adm_dashboard_lede: 'Track shows, bookings, and the tickets that have gone out to your audience — all in one place.',
                adm_back: 'Back',
                adm_back_dashboard: 'Back to dashboard',
                adm_back_shows_list: 'Back to shows',
                adm_back_times: 'Back to show times',
                adm_overview_title: 'Overview',
                adm_edit: 'Edit',
                adm_delete: 'Delete',
                adm_seats: 'Seats',
                adm_seats_saved: 'Manage seats',
                adm_section_hall: 'Hall',
                adm_section_balcony: 'Balcony',
                adm_revenue: 'Revenue',
                adm_tickets_approved: 'tickets approved',

                /* ===== admin: KPI cards ===== */
                adm_kpi_revenue_label: 'Total approved revenue',
                adm_kpi_revenue_caption: 'From approved bookings only',
                adm_kpi_pending: 'Bookings awaiting your review',
                adm_kpi_pending_pill: 'Action needed',
                adm_kpi_pending_caption: 'Open the list to approve or reject.',
                adm_kpi_approved: 'Approved bookings',
                adm_kpi_approved_caption: 'Total confirmed bookings',
                adm_kpi_shows: 'Shows',
                adm_kpi_shows_caption: 'Total shows on the platform',
                adm_kpi_showtimes: 'Show times',
                adm_kpi_showtimes_caption: 'Active scheduled times',
                adm_kpi_remaining: 'Remaining tickets',
                adm_kpi_remaining_caption: 'Available for booking now',

                /* ===== admin: status pills ===== */
                adm_status_pending: 'Pending',
                adm_status_approved: 'Approved',
                adm_status_rejected: 'Rejected',
                adm_status_available: 'Available',
                adm_status_sold_out: 'Sold out',

                /* ===== admin: quick actions ===== */
                adm_quick_title: 'Quick actions',
                adm_quick_bookings_eyebrow: 'Bookings',
                adm_quick_bookings_title: 'Manage bookings',
                adm_quick_bookings_body: 'Review, approve, or reject bookings and send tickets to customers.',
                adm_quick_shows_eyebrow: 'Shows',
                adm_quick_shows_title: 'Manage shows & times',
                adm_quick_shows_body: 'Add new shows, edit details, and manage the available show times.',
                adm_quick_scanner_eyebrow: 'Gate',
                adm_quick_scanner_title: 'Ticket scanner',
                adm_quick_scanner_body: 'Open the camera and scan QR tickets right at the door.',

                /* ===== admin: shows list ===== */
                adm_shows_pill: 'Shows',
                adm_shows_title: 'Manage shows',
                adm_shows_add: 'Add new show',
                adm_shows_times: 'Show times',
                adm_shows_empty: 'No shows yet. Click "Add new show" to get started.',
                adm_show_active: 'Active',
                adm_show_hidden: 'Hidden',

                /* ===== admin: show form (create / edit) ===== */
                adm_show_new_pill: 'New show',
                adm_show_new_title: 'Add a new show',
                adm_show_edit_pill: 'Edit show',
                adm_show_edit_title: 'Edit show details',
                adm_show_basic: 'Basic information',
                adm_show_title_label: 'Show title',
                adm_show_theater: 'Theater',
                adm_show_description: 'Description',
                adm_show_description_helper: 'A short summary that appears on the show page.',
                adm_show_anba_helper: 'When hall and balcony pricing is enabled, the price is split automatically by section.',
                adm_show_anba_helper_short: 'Separate hall and balcony pricing',
                adm_show_hall_price: 'Hall price',
                adm_show_balcony_price: 'Balcony price',
                adm_show_visibility: 'Visibility',
                adm_show_visibility_label: 'Visible to the public',
                adm_show_visibility_helper: 'Uncheck to hide this show from the homepage.',
                adm_show_poster: 'Show poster',
                adm_show_poster_pick: 'Choose image',
                adm_show_poster_replace: 'Replace image',
                adm_show_poster_hint: 'PNG / JPG · ideal size 1200×1600',
                adm_show_ticket_design: 'Ticket design',
                adm_show_ticket_design_helper: 'The background image used to render ticket details and the QR code.',
                adm_show_ticket_template_file: 'Ticket template',
                adm_show_ticket_template_pick: 'Choose template image',
                adm_show_ticket_template_replace: 'Replace template',
                adm_show_ticket_template_hint: 'Transparent PNG at 2480×3508 (A4 @300dpi) for best results.',
                adm_show_ticket_template_hint_short: 'PNG / JPG · A4 sized for best results',
                adm_show_qr_helper: 'Set the position and size of the QR on the template (in pixels).',
                adm_show_qr_x: 'QR position · X',
                adm_show_qr_y: 'QR position · Y',
                adm_show_qr_size: 'QR size',
                adm_show_create_btn: 'Create show',
                adm_show_save_btn: 'Save changes',

                /* ===== admin: show times list ===== */
                adm_times_pill: 'Show times',
                adm_times_title: 'Show times',
                adm_times_add: 'Add new show time',
                adm_times_empty: 'No show times yet. Click "Add new show time".',
                adm_times_col_date: 'Date',
                adm_times_col_time: 'Time',
                adm_times_col_total: 'Total',
                adm_times_col_avail: 'Available',
                adm_times_col_avail_short: 'Remaining',
                adm_times_col_price: 'Price',
                adm_times_col_price_split: 'Prices',
                adm_times_col_status: 'Status',
                adm_times_col_actions: 'Actions',
                adm_showtimes_title: 'Show times',
                adm_showtimes_empty: 'This show has no times.',
                adm_th_date: 'Date',
                adm_th_time: 'Time',
                adm_th_show: 'Show',
                adm_th_total: 'Total',
                adm_th_remaining: 'Remaining',

                /* ===== admin: show time form (create / edit) ===== */
                adm_time_new_pill: 'New show time',
                adm_time_new_title: 'Add a show time',
                adm_time_edit_pill: 'Edit show time',
                adm_time_edit_title: 'Edit show time details',
                adm_time_section_when: 'When',
                adm_time_section_when_sub: 'Pick the date and start time of this performance.',
                adm_time_section_pricing: 'Pricing & ticket count',
                adm_time_section_pricing_helper: 'Set the ticket price and how many tickets are available.',
                adm_time_section_pricing_split: 'Hall / balcony pricing is enabled at the show level. Edit the prices from the show details.',
                adm_time_ticket_price: 'Ticket price',
                adm_time_total: 'Total tickets',
                adm_time_available_now: 'Available now',
                adm_time_available_helper: 'Leave empty to match the total.',
                adm_time_available_placeholder: 'Same as total',
                adm_time_section_status: 'Status',
                adm_time_status_label: 'Open for booking',
                adm_time_status_helper: 'When unchecked, this time will be hidden from booking.',
                adm_time_force_sold_out: 'Force "sold out"',
                adm_time_save_btn: 'Save',

                /* ===== admin: bookings list ===== */
                adm_bookings_eyebrow: 'Bookings',
                adm_bookings_title: 'Manage bookings',
                adm_bookings_search_placeholder: 'Search by name, phone, or code...',
                adm_filter_all: 'All',
                adm_filter_pending: 'Pending',
                adm_filter_approved: 'Approved',
                adm_filter_rejected: 'Rejected',
                adm_filter_all_times: 'All show times',
                adm_bk_col_guest: 'Guest',
                adm_bk_col_show: 'Show / time',
                adm_bk_col_status: 'Status',
                adm_bk_col_ticket: 'Tickets',
                adm_bk_col_actions: 'Actions',
                adm_bk_col_code: 'Code',
                adm_bk_details: 'Details',
                adm_bk_no_match: 'No bookings match these filters.',
                adm_bk_reset_filters: 'Reset filters',

                /* ===== admin: booking detail (show) ===== */
                adm_bk_pill_prefix: 'Booking',
                adm_bk_tickets_title: '🎟️ Tickets',
                adm_bk_tickets_word: 'tickets',
                adm_bk_received: 'Sent',
                adm_bk_not_received: 'Not sent yet',
                adm_bk_view_ticket: '🎫 View ticket',
                adm_bk_resend: 'Resend',
                adm_bk_resend_ok: 'Tickets sent again',
                adm_bk_resend_fail: 'Something went wrong, please try again',
                adm_bk_summary_title: '📋 Booking summary',
                adm_bk_summary_eyebrow: 'Details',
                adm_bk_count: 'Ticket count',
                adm_bk_price: 'Price',
                adm_bk_status: 'Status',
                adm_bk_status_approved: '✅ Approved',
                adm_bk_status_rejected: '❌ Rejected',
                adm_bk_status_pending: '⏳ Pending',
                adm_bk_ref: 'Booking code',
                adm_bk_name: 'Name',
                adm_bk_phone: 'Phone',
                adm_bk_transfer_title: 'Transfer screenshot',
                adm_bk_transfer_eyebrow: 'Payment proof',
                adm_bk_delete_title: 'Delete booking?',
                adm_bk_delete_body: 'This will permanently remove the booking. This cannot be undone.',
                adm_bk_delete_ok: 'Delete',
                adm_bk_delete_btn: '🗑️ Delete booking',
                adm_bk_actions_aria: 'Booking actions',
                adm_bk_pending_label: 'Booking pending review',
                adm_bk_reject_title: 'Reject booking?',
                adm_bk_reject_body: 'The customer will be notified that the booking was rejected. Are you sure?',
                adm_bk_reject_ok: 'Reject',
                adm_bk_reject_btn: 'Reject',
                adm_bk_approve_title: 'Approve booking?',
                adm_bk_approve_body: 'The booking will be approved and tickets will be sent to the customer.',
                adm_bk_approve_ok: 'Approve',
                adm_bk_approve_btn: 'Approve',

                /* ===== admin: payments settings ===== */
                adm_pay_pill: 'Payment settings',
                adm_pay_title: '💳 Payment settings',
                adm_pay_wallet_label: 'Wallet number',
                adm_pay_wallet_hint: 'e.g. 0100xxxxxxx',
                adm_pay_insta_label: 'InstaPay account',
                adm_pay_insta_hint: 'e.g. EGxxxxxxxxxx or email@domain.com',
                adm_pay_save: 'Save settings',
                adm_payments_eyebrow: 'Payment methods',
                adm_payments_title: 'Payment settings',
                adm_payments_hint: 'Configure the wallet and InstaPay account customers will see.',
                adm_payments_wallet: 'Wallet',
                adm_payments_save: 'Save',

                /* ===== admin: scanner ===== */
                adm_scanner_pill: 'Gate scanner',
                adm_scanner_title: '🎫 Gate scanner',
                adm_scanner_ready: 'Ready to scan',
                adm_scanner_flash: '🔦 Flash',
                adm_scanner_restart: '🔄 Restart',
                adm_scanner_ok: '✅ Allowed',
                adm_scanner_used: '⚠️ Already used',
                adm_scanner_invalid: '❌ Invalid',
                adm_scanner_entered: 'Entered',
                adm_scanner_no_torch: 'Flash is not supported',

                /* ===== auth ===== */
                auth_admin_pill: 'Sign in',
                auth_admin_title: 'Admin sign in',
                auth_admin_subtitle: 'Sign in to manage shows and bookings.',
                auth_email: 'Email',
                auth_password: 'Password',
                auth_password_confirm: 'Confirm password',
                auth_name: 'Name',
                auth_login_btn: 'Sign in',
                auth_register_title: 'Create a new account',
                auth_register_btn: 'Create account',
                auth_forgot_password: 'Forgot your password?',
                auth_reset_pill: 'Reset',
                auth_reset_title: 'Reset your password',
                auth_reset_btn: 'Update password',
                auth_send_reset_link: 'Send reset link',
                auth_confirm_pwd_title: 'Confirm your password',
                auth_confirm_pwd_subtitle: 'Please confirm your password before continuing.',
                auth_verify_title: 'Verify your email',
                auth_verify_check_email: 'Check your inbox for the activation link.',
                auth_verify_resent: 'A new link was sent to your email.',
                auth_verify_didnt_receive: 'Didn\u2019t get the link?',
                auth_verify_resend_link: 'Resend link'
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
        // Look up a translation key against the current dictionary, with simple
        // {placeholder} interpolation. Falls back to the AR dictionary, then to
        // the key itself, so missing keys never blow up the page.
        function ptT(key, vars) {
            const lang = document.documentElement.getAttribute('data-pt-lang') || 'ar';
            const dict = I18N[lang] || I18N.ar;
            let s = dict[key];
            if (s === undefined) s = (I18N.ar || {})[key];
            if (s === undefined) return key;
            if (vars && typeof s === 'string') {
                s = s.replace(/\{(\w+)\}/g, (m, k) => (vars[k] !== undefined ? vars[k] : m));
            }
            return s;
        }
        // Expose helpers globally so per-page JS (seat picker, booking form,
        // scanner, ...) can build dynamic strings in the active language.
        window.PT_I18N = I18N;
        window.PT_T    = ptT;
        function applyLang(lang) {
            const dict = I18N[lang] || I18N.ar;
            document.documentElement.setAttribute('data-pt-lang', lang);
            document.documentElement.lang = lang;
            document.documentElement.dir  = (lang === 'en') ? 'ltr' : 'rtl';
            // Read optional `data-i18n-vars='{"n": 5}'` JSON for placeholder
            // substitution in {n}-style templates. Returns an empty object on
            // missing/invalid JSON so the raw template still renders cleanly.
            const readVars = (el) => {
                const raw = el.getAttribute('data-i18n-vars');
                if (!raw) return null;
                try { return JSON.parse(raw); } catch (_) { return null; }
            };
            const interp = (s, vars) => {
                if (!vars || typeof s !== 'string') return s;
                return s.replace(/\{(\w+)\}/g, (m, k) => (vars[k] !== undefined ? vars[k] : m));
            };
            // Text content
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const k = el.getAttribute('data-i18n');
                if (dict[k] !== undefined) el.textContent = interp(dict[k], readVars(el));
            });
            // HTML content (for strings that include inline tags / line breaks)
            document.querySelectorAll('[data-i18n-html]').forEach(el => {
                const k = el.getAttribute('data-i18n-html');
                if (dict[k] !== undefined) el.innerHTML = interp(dict[k], readVars(el));
            });
            // Attribute translation. Encode as `data-i18n-attr="placeholder:key,title:key2"`.
            document.querySelectorAll('[data-i18n-attr]').forEach(el => {
                const spec = el.getAttribute('data-i18n-attr') || '';
                spec.split(',').forEach(pair => {
                    const [attr, key] = pair.split(':').map(s => s && s.trim());
                    if (!attr || !key) return;
                    if (dict[key] !== undefined) el.setAttribute(attr, dict[key]);
                });
            });
            // Update language toggle button states + thumb
            langButtons.forEach(b => {
                const on = b.getAttribute('data-pt-lang') === lang;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            document.querySelectorAll('.pt-lang-toggle').forEach(group => moveThumbForGroup(group, lang));
            try { localStorage.setItem('pt-lang', lang); } catch(_){}
            window.PT_LANG = lang;
            document.dispatchEvent(new CustomEvent('pt:langchange', { detail: { lang } }));
        }
        window.PT_APPLY_LANG = applyLang;
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

        // ---------- Wave 1: copy + share helpers ----------
        // Tap-to-copy: any element with [data-pt-copy="value"] copies that
        // value to clipboard on click and shows a toast. Falls back to
        // execCommand for older browsers / non-secure contexts.
        function ptCopyValue(value) {
            if (value == null) return Promise.reject();
            const text = String(value);
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            return new Promise((resolve, reject) => {
                try {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.top = '-1000px';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    const ok = document.execCommand('copy');
                    document.body.removeChild(ta);
                    ok ? resolve() : reject();
                } catch (e) { reject(e); }
            });
        }
        window.PT.copy = ptCopyValue;

        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-pt-copy]');
            if (!trigger) return;
            const value = trigger.getAttribute('data-pt-copy');
            if (!value) return;
            e.preventDefault();
            ptCopyValue(value).then(() => {
                showToast(window.PT.t('copy_done'));
                trigger.classList.add('is-copied');
                setTimeout(() => trigger.classList.remove('is-copied'), 1200);
            }).catch(() => {
                showToast(window.PT.t('copy_failed'));
            });
        });

        // Share helper — wa.me deep link, falls back to navigator.share
        // when available. Returns the wa.me URL so callers can also use
        // it as a plain href.
        window.PT.shareWA = function (text, url) {
            const body = (text || '') + (url ? (text ? ' ' : '') + url : '');
            return 'https://wa.me/?text=' + encodeURIComponent(body);
        };

        // ---------- cinematic homepage v2 (homepage-only motion) ----------
        // Wires up: hero spotlight cursor glow, scroll-parallax orbs,
        // staggered hero entrance reveal, pointer-tracked 3D tilt on
        // storytelling cards, and magnetic CTAs. All gated on hover +
        // fine pointer + reduced-motion preference. Listeners attach
        // only to homepage-scoped nodes; on other pages this block
        // does nothing because the targets don't exist.
        (function setupCinemaV2() {
            const hero = document.querySelector('.pt-hero');
            if (!hero) return; // homepage-only

            const reduceMQ = matchMedia('(prefers-reduced-motion: reduce)');
            const hoverMQ  = matchMedia('(hover: hover) and (pointer: fine)');

            // Hero entrance: mark .pt-cinema-stagger as .is-in next frame.
            // Always run this — even with reduced-motion the CSS already
            // collapses the transition, so this is a no-op visually.
            requestAnimationFrame(() => {
                document.querySelectorAll('.pt-cinema-stagger').forEach(el => {
                    el.classList.add('is-in');
                });
            });

            if (reduceMQ.matches) return;

            // Hero spotlight cursor glow
            const spot = hero.querySelector('.pt-cinema-spot');
            if (spot && hoverMQ.matches) {
                hero.addEventListener('pointermove', (e) => {
                    const r = hero.getBoundingClientRect();
                    const x = ((e.clientX - r.left) / r.width)  * 100;
                    const y = ((e.clientY - r.top)  / r.height) * 100;
                    spot.style.setProperty('--pt-spot-x', x + '%');
                    spot.style.setProperty('--pt-spot-y', y + '%');
                }, { passive: true });
                hero.addEventListener('pointerenter', () => spot.classList.add('is-on'));
                hero.addEventListener('pointerleave', () => spot.classList.remove('is-on'));
            }

            // Scroll parallax for hero ambient orbs (rAF-throttled)
            const orbs = hero.querySelectorAll('.pt-cinema-orb');
            if (orbs.length) {
                let ticking = false;
                const FACTORS = [0.55, 0.9, 0.7];
                const onScroll = () => {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(() => {
                        const y = Math.min(120, Math.max(-30, window.scrollY * 0.18));
                        orbs.forEach((orb, i) => {
                            const f = FACTORS[i] != null ? FACTORS[i] : 0.7;
                            orb.style.setProperty('--pt-parallax', (y * f).toFixed(1) + 'px');
                        });
                        ticking = false;
                    });
                };
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();
            }

            // 3D pointer-tracked tilt on storytelling cards
            if (hoverMQ.matches) {
                document.querySelectorAll('.pt-cinema-step').forEach(step => {
                    let raf = 0;
                    const apply = (px, py) => {
                        const rx = (0.5 - py) * 9;   // max ±4.5deg
                        const ry = (px - 0.5) * 12;  // max ±6deg
                        step.style.setProperty('--pt-rx', rx.toFixed(2) + 'deg');
                        step.style.setProperty('--pt-ry', ry.toFixed(2) + 'deg');
                        step.style.setProperty('--pt-ty', '-4px');
                    };
                    step.addEventListener('pointerenter', () => step.classList.add('is-tilting'));
                    step.addEventListener('pointermove', (e) => {
                        if (raf) return;
                        raf = requestAnimationFrame(() => {
                            const r = step.getBoundingClientRect();
                            apply((e.clientX - r.left) / r.width,
                                  (e.clientY - r.top)  / r.height);
                            raf = 0;
                        });
                    }, { passive: true });
                    step.addEventListener('pointerleave', () => {
                        step.classList.remove('is-tilting');
                        step.style.setProperty('--pt-rx', '0deg');
                        step.style.setProperty('--pt-ry', '0deg');
                        step.style.setProperty('--pt-ty', '0px');
                    });
                });
            }

            // Magnetic CTAs (homepage scope)
            if (hoverMQ.matches) {
                document.querySelectorAll('.pt-cinema-magnet').forEach(el => {
                    const MAX = 9;
                    let raf = 0;
                    el.addEventListener('pointerenter', () => el.classList.add('is-magnet'));
                    el.addEventListener('pointermove', (e) => {
                        if (raf) return;
                        raf = requestAnimationFrame(() => {
                            const r = el.getBoundingClientRect();
                            const x = ((e.clientX - r.left) / r.width  - 0.5) * 2;
                            const y = ((e.clientY - r.top)  / r.height - 0.5) * 2;
                            el.style.setProperty('--pt-mx', (x * MAX).toFixed(1) + 'px');
                            el.style.setProperty('--pt-my', (y * MAX * 0.55).toFixed(1) + 'px');
                            raf = 0;
                        });
                    }, { passive: true });
                    el.addEventListener('pointerleave', () => {
                        el.classList.remove('is-magnet');
                        el.style.setProperty('--pt-mx', '0px');
                        el.style.setProperty('--pt-my', '0px');
                    });
                });
            }
        })();

        // ---------- Cinematic homepage v3 (full-screen scene story) ----------
        // Activates each .pt-cine-scene as it enters the viewport (.is-active),
        // and tracks when the intro scene is in view so the floating nav can
        // fade out for a true full-screen opener. Homepage-scoped: silently
        // no-ops on every other page (no [data-pt-cine] root present).
        (function setupCinemaV3() {
            const root = document.querySelector('[data-pt-cine]');
            if (!root) return;

            const scenes = root.querySelectorAll('.pt-cine-scene');
            if (!scenes.length) return;

            // Intro is full-screen on first paint — flag the body so the
            // floating nav stays hidden until the user scrolls past it.
            const introScene = root.querySelector('.is-scene-intro');
            if (introScene) {
                document.body.classList.add('has-cine-intro-active');
            }

            // Activate first scene immediately so its stagger plays on load.
            requestAnimationFrame(() => {
                if (scenes[0]) scenes[0].classList.add('is-active');
            });

            // IntersectionObserver: mark each scene .is-active when it
            // crosses 35% visibility. We never remove the class once it's
            // set so re-scrolling up doesn't re-trigger the entrance.
            if ('IntersectionObserver' in window) {
                const sceneIO = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && entry.intersectionRatio >= 0.34) {
                            entry.target.classList.add('is-active');
                        }
                    });
                }, { threshold: [0.34, 0.6] });
                scenes.forEach((scene) => sceneIO.observe(scene));

                // Watch the intro scene: nav fades back in once it leaves.
                if (introScene) {
                    const introIO = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.intersectionRatio >= 0.45) {
                                document.body.classList.add('has-cine-intro-active');
                            } else {
                                document.body.classList.remove('has-cine-intro-active');
                            }
                        });
                    }, { threshold: [0, 0.2, 0.45, 0.8] });
                    introIO.observe(introScene);
                }
            } else {
                // No IO support — just activate everything so content shows.
                scenes.forEach((s) => s.classList.add('is-active'));
                document.body.classList.remove('has-cine-intro-active');
            }
        })();
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
            // i18nKeys lets callers translate title/body/labels at runtime
            // without committing English copy into the markup. Resolution
            // order: i18nKeys[field] (translated) → opts[field] (literal AR) → safe default.
            const k = (opts.i18nKeys || {});
            const tr = (key, fallback) => key ? window.PT.t(key) || fallback : fallback;
            const title = tr(k.title, opts.title)
                || (window.PT.lang() === 'en' ? 'Are you sure?' : 'هل أنت متأكد؟');
            const body  = tr(k.body, opts.body) || '';
            const okLabel = tr(k.okLabel, opts.okLabel)
                || (window.PT.lang() === 'en' ? 'Continue' : 'متابعة');
            const cancelLabel = tr(k.cancelLabel, opts.cancelLabel)
                || (window.PT.lang() === 'en' ? 'Cancel' : 'إلغاء');
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
