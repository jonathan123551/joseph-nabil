<!DOCTYPE html>
<html lang="ar" dir="rtl" data-pt-lang="ar" data-pt-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'العابد')</title>
    {{-- Pages can opt into JS-driven title localization by declaring
         @section('headMeta') with a <meta name="pt-title-i18n"
         content="my_key" data-suffix="optional dynamic suffix">. The
         layout's applyLang() reads this tag and updates document.title
         on initial load and on every language toggle. --}}
    @yield('headMeta')
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060d" id="pt-theme-color">

    {{-- First-paint dark guarantee. This block paints the document
         shell dark before ANY external CSS resolves (Tailwind CDN,
         Google Fonts, the big Prism <style> further down). Without
         it, slow networks can flash the UA white default for a few
         frames before the inline Prism CSS is parsed. The whole
         site defaults to dark + Arabic so this matches the rest of
         the platform identity. --}}
    <style>
        html { background: #05060d; color: #f1f5fb; color-scheme: dark; }
        :root[data-pt-theme="light"] html,
        :root[data-pt-theme="light"] { color-scheme: light; }
    </style>

    {{-- ================= BRAND IDENTITY =================
         The mark is the official El3abed logo - a single PNG
         sized so it ships crisp from 24 px navbar marks up to 1024 px
         hero / OG renders.
         Pre-rendered favicon plates (logo on rounded-square dark plate)
         live in three sizes for the browser tab and iOS home-screen.
         All assets live under public/brand/ and are checked in so
         Railway serves them with no build step.
           - el3abed-logo.png        (1024x898, transparent — navbar / drawer / footer / hero)
           - apple-touch-icon.png    (180×180, painted mark on dark plate — iOS home-screen)
           - android-chrome-192.png
           - android-chrome-512.png
           - favicon-{16,32,48}.png  (browser tab — dark plate)
           - favicon.ico             (16+32+48 multi-res)
           - og-image.png            (1200×630 — social / OG card) --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=3">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=3">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}?v=3">

    {{-- Social / OG branding (consumed by WhatsApp, Twitter, etc.) --}}
    <meta property="og:image" content="{{ asset('brand/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('brand/og-image.png') }}">

    {{-- Theme + language bootstrap (runs synchronously BEFORE paint
         so there's no FOUC / RTL flash / theme flicker).

         Platform default = DARK + ARABIC. First-time visitors get
         dark mode and Arabic regardless of their OS color scheme
         preference, browser language, or User-Agent locale. The
         OS prefers-color-scheme hint is intentionally ignored —
         if an operator wants light mode they pick it once via the
         theme toggle and the choice persists in localStorage from
         then on. Same for English.

         Returning visitors keep whatever theme + lang they had
         picked previously. The localStorage keys are pt-theme
         ('dark' | 'light') and pt-lang ('ar' | 'en'). Any stored
         value outside those expected sets falls back to the
         platform default. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('pt-theme');
                var theme = (stored === 'light' || stored === 'dark') ? stored : 'dark';
                document.documentElement.setAttribute('data-pt-theme', theme);
                var meta = document.getElementById('pt-theme-color') || document.querySelector('meta[name="theme-color"]');
                if (meta) meta.setAttribute('content', theme === 'light' ? '#f4f1ea' : '#05060d');
            } catch (e) { /* keep dark default baked into <html> */ }
            try {
                var lang = localStorage.getItem('pt-lang');
                if (lang !== 'ar' && lang !== 'en') lang = 'ar';
                document.documentElement.setAttribute('data-pt-lang', lang);
                document.documentElement.setAttribute('lang', lang);
                document.documentElement.setAttribute('dir', lang === 'en' ? 'ltr' : 'rtl');
            } catch (e) { /* keep AR default baked into <html> */ }
        })();
    </script>

    {{-- Tailwind CSS — pre-built static stylesheet (replaces the dev-only
         cdn.tailwindcss.com Play CDN). The file is generated during the
         Docker image build by `npm run build` (see Dockerfile), which runs
         `tailwindcss -i resources/css/app.css -o public/build/app.css
         --minify`. The build scans every .blade.php / .js file under
         resources/ for utility classes and emits only those — so the
         output is small (~55 KB) and the same utilities currently in use
         render identically. Position is preserved (right where the
         <script> CDN tag used to sit) so the cascade order vs. the
         inline PRISM <style> block below is unchanged: PRISM still wins
         on conflicts. The static file gets long-lived Cache-Control via
         the nginx location block, so the browser downloads it once. --}}
    <link rel="stylesheet" href="{{ asset('build/app.css') }}">

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
        /* Light: dark green pill stays visually green but bumps to a
           "WhatsApp brand" emerald with proper contrast on cream. */
        :root[data-pt-theme="light"] .prism-share-wa {
            color: #064e3b;
            background: linear-gradient(135deg, rgba(4,120,87,0.16), rgba(5,150,105,0.10));
            border-color: rgba(4,120,87,0.45);
            box-shadow: 0 4px 14px -4px rgba(4,120,87,0.32);
        }
        :root[data-pt-theme="light"] .prism-share-wa:hover {
            background: linear-gradient(135deg, rgba(4,120,87,0.24), rgba(5,150,105,0.16));
            border-color: rgba(4,120,87,0.65);
        }
        :root[data-pt-theme="light"] .prism-share-wa .share-wa-icon {
            color: #047857;
        }

        /* Auto-pick best-seats button (seat picker side panel) */
        @keyframes prismAutoPickBreath {
            0%, 100% {
                box-shadow: 0 4px 18px rgba(245, 158, 11, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            }
            50% {
                box-shadow: 0 6px 26px rgba(245, 158, 11, 0.70), inset 0 1px 0 rgba(255, 255, 255, 0.4);
            }
        }
        .prism-auto-pick {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 10px 16px;
            border-radius: 14px;
            font-size: 13px; font-weight: 800;
            color: #0b0e1c;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
            border: 1px solid rgba(255, 255, 255, 0.45);
            box-shadow: 0 4px 18px rgba(245, 158, 11, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: transform .15s var(--prism-ease), background .15s var(--prism-ease), border-color .15s var(--prism-ease), box-shadow .2s var(--prism-ease);
            -webkit-tap-highlight-color: transparent;
            min-height: 44px;
            animation: prismAutoPickBreath 4.5s infinite ease-in-out;
            position: relative;
            overflow: hidden;
        }
        .prism-auto-pick:hover {
            transform: translateY(-1.5px);
            background: linear-gradient(135deg, #fcd34d 0%, #fbbf24 50%, #f59e0b 100%);
            border-color: rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }
        .prism-auto-pick:active {
            transform: translateY(0) scale(0.97);
            transition-duration: 80ms;
        }
        @media (prefers-reduced-motion: reduce) {
            .prism-auto-pick { animation: none; }
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
            /* Rebranded to "El3abed" (mixed-case proper noun) — the
               previous PREMIUM uppercase wordmark wanted aggressive
               tracking; a real name wants tight kerning + a hint of
               size to feel like a signature, not a label. */
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            font-size: 15.5px;
            letter-spacing: 0.005em;
            background: var(--prism-neon);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
            white-space: nowrap;
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
            /* CSS containment — tells the browser that no layout / paint
               changes inside the topbar can affect anything outside. Cuts
               first-paint CLS to zero for the floating bar (the wrap is
               already `position: fixed`, so the body below cannot shift,
               but inner reflows still trigger paint elsewhere without
               containment). */
            contain: layout paint;
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

        /* ---- Light-mode overrides: premium confirmation modal ----
           The singleton .pt-modal-root fires on customer booking confirm
           AND on every admin approve / reject / delete-booking. The dark
           navy card looks pasted-in on cream; we swap to a white-cream
           card with neutral border and dampened tonal glow so it integrates
           with the rest of the light theme. Backdrop scrim opacity matches
           the mobile drawer for consistency. */
        :root[data-pt-theme="light"] .pt-modal-backdrop {
            background: radial-gradient(ellipse 70% 60% at 50% 30%, rgba(99,102,241,0.06), transparent 60%),
                        rgba(15,23,42,0.28);
        }
        :root[data-pt-theme="light"] .pt-modal-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.98));
            border-color: rgba(15,23,42,0.12);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.7),
                0 36px 70px -20px rgba(15,23,42,0.32),
                0 0 40px rgba(99,102,241,0.10);
        }
        :root[data-pt-theme="light"] .pt-modal-icon {
            background: rgba(99,102,241,0.10);
            border-color: rgba(99,102,241,0.45);
            color: var(--prism-indigo);
            box-shadow: 0 0 24px rgba(99,102,241,0.18);
        }
        :root[data-pt-theme="light"] .pt-modal-icon.tone-success {
            background: rgba(16,185,129,0.10);
            border-color: rgba(16,185,129,0.50);
            color: var(--prism-emerald);
            box-shadow: 0 0 24px rgba(16,185,129,0.20);
        }
        :root[data-pt-theme="light"] .pt-modal-icon.tone-error {
            background: rgba(244,63,94,0.10);
            border-color: rgba(244,63,94,0.50);
            color: var(--prism-rose);
            box-shadow: 0 0 24px rgba(244,63,94,0.20);
        }
        :root[data-pt-theme="light"] .pt-modal-icon.tone-warn {
            background: rgba(245,158,11,0.12);
            border-color: rgba(245,158,11,0.50);
            color: var(--prism-gold);
            box-shadow: 0 0 24px rgba(245,158,11,0.18);
        }
        :root[data-pt-theme="light"] .pt-modal-spinner {
            border-color: rgba(99,102,241,0.18);
            border-top-color: var(--prism-indigo);
        }

        /* Generic in-button loading state. Add `.is-loading` to ANY button
           and an inline 14px spinner will appear before its text via a
           ::before pseudo-element. Cursor turns wait, pointer events are
           blocked so a frantic user can't trigger a double-submit even if
           something forgets to set [disabled]. Inherits currentColor so it
           tints itself correctly inside gold / indigo / rose buttons.
           Respects prefers-reduced-motion (replaces spin with a static
           dot so the state is still visually distinct). */
        .is-loading {
            position: relative;
            cursor: wait !important;
            pointer-events: none;
        }
        .is-loading::before {
            content: "";
            display: inline-block;
            width: 14px;
            height: 14px;
            margin-inline-end: 8px;
            vertical-align: -2px;
            border-radius: 999px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            opacity: 0.85;
            animation: ptSpin .8s linear infinite;
        }
        @media (prefers-reduced-motion: reduce) {
            .is-loading::before { animation: none; border-top-color: currentColor; opacity: 0.5; }
        }

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
        /* ---- Light-mode override: small flash pill ----
           Used by PT.toast() — favorites, etc. Swap dark navy pill for a
           white-cream surface with neutral border and soft shadow so it
           reads as a "subtle confirmation" rather than a dark intrusion. */
        :root[data-pt-theme="light"] .pt-toast {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(248,250,252,0.98));
            border-color: rgba(99,102,241,0.32);
            color: var(--prism-text);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.7),
                0 12px 32px -10px rgba(15,23,42,0.22),
                0 0 22px rgba(99,102,241,0.10);
        }

        /* ---- Light-mode override: /ticket/{ref} flash banner ----
           The inline banner in tickets/show.blade.php uses hardcoded
           Tailwind `*-200` text on `*-500/0.10` bg — emerald-200 / amber-200
           are light text colors meant for dark backgrounds, so they wash
           out on cream. We swap to deeper tones via the existing tokens.
           Targets the stable data attributes already set on the element
           so the view markup stays unchanged. Specificity (0,3,0) beats
           the Tailwind utility class (0,1,0) without needing !important. */
        :root[data-pt-theme="light"] [data-tkt-flash-success] {
            background: rgba(16,185,129,0.10);
            border-color: rgba(16,185,129,0.40);
            color: var(--prism-emerald);
        }
        :root[data-pt-theme="light"] [data-tkt-flash-warn] {
            background: rgba(245,158,11,0.10);
            border-color: rgba(245,158,11,0.40);
            color: var(--prism-gold);
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

        /* Compact seat chip — pairs each attendee's name with their
           assigned section + seat in admin views so operators can scan
           "name → seat" without mentally matching data elsewhere.
           Hall and balcony get distinct accent colors. */
        .pt-seat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: .01em;
            background: rgba(129,140,248,0.12);
            border: 1px solid rgba(129,140,248,0.4);
            color: #c7d2fe;
            white-space: nowrap;
        }
        .pt-seat-chip-hall {
            background: linear-gradient(135deg, rgba(34,211,238,0.14), rgba(129,140,248,0.10));
            border-color: rgba(34,211,238,0.40);
            color: #a5f3fc;
        }
        .pt-seat-chip-balcony {
            background: linear-gradient(135deg, rgba(251,191,36,0.14), rgba(192,132,252,0.10));
            border-color: rgba(251,191,36,0.40);
            color: #fde68a;
        }
        .pt-seat-chip .pt-seat-chip-seat {
            font-weight: 800;
            font-feature-settings: "tnum" 1;
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

        /* Cap backdrop-blur cost on small viewports. Multiple stacked
           blurs at 18–22px stutter iOS Safari's compositor on older
           iPhones; 12px gives the same "glass" perception at a fraction
           of the GPU cost. Applies only to the global chrome surfaces
           that always paint on top of the page (topbar, action bar,
           modals, drawers, the seat picker bottom CTA, the booking
           dock, the scanner sheet) — page-specific decorative blurs
           are untouched. Desktop sizes unchanged. */
        @media (max-width: 880px) {
            .pt-topbar,
            .pt-action-bar,
            .pt-modal-root,
            .pt-drawer,
            .pt-toast-overlay,
            .anba-dock,
            .anba-dock-inner,
            [data-anba-root] .mobile-cta,
            [data-anba-root] .anba-modal-backdrop,
            [data-anba-root] .canvas-fab,
            .scan-sheet {
                backdrop-filter: blur(12px) saturate(140%) !important;
                -webkit-backdrop-filter: blur(12px) saturate(140%) !important;
            }
        }

        /* ---------- LIGHT THEME ---------- */
        :root[data-pt-theme="light"] {
            --prism-bg-0: #f4f1ea;
            --prism-bg-1: #ece7dc;
            --prism-bg-2: #e3dccc;

            --prism-surface: rgba(255, 255, 255, 0.72);
            --prism-surface-strong: rgba(255, 255, 255, 0.88);
            --prism-surface-soft: rgba(15, 23, 42, 0.04);

            --prism-border: rgba(15, 23, 42, 0.13);
            --prism-border-strong: rgba(15, 23, 42, 0.22);
            --prism-border-neon: rgba(99, 102, 241, 0.34);

            --prism-text:   #0f172a;
            --prism-text-2: #334155;
            --prism-text-3: #475569;
            --prism-text-4: #576779;

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
            background: linear-gradient(180deg, rgba(255,255,255,0.82), rgba(255,255,255,0.64));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.90),
                0 20px 40px -18px rgba(15, 23, 42, 0.26),
                0 4px 8px -4px rgba(15, 23, 42, 0.10);
        }
        :root[data-pt-theme="light"] .prism-glass-strong {
            background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(252,250,245,0.90));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.97),
                0 24px 52px -20px rgba(15,23,42,0.28),
                0 4px 10px -4px rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] .prism-glow-border::before { opacity: 0.7; }

        /* Light: KPI / stat cards. Dark slate gradient is invisible on cream;
           switch to light glass with proper drop shadow so the admin
           dashboard reads cleanly. is-primary / is-positive / is-attention
           accent colours are preserved via border tints. */
        :root[data-pt-theme="light"] .prism-stat {
            background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(252,250,245,0.88));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 22px 44px -22px rgba(15,23,42,0.20),
                0 4px 10px -4px rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] .prism-stat:hover {
            border-color: rgba(79,70,229,0.42);
        }
        :root[data-pt-theme="light"] .prism-stat.is-primary {
            border-color: rgba(180,83,9,0.45);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 28px 56px -22px rgba(180,83,9,0.30),
                0 0 28px rgba(245,158,11,0.18);
        }
        :root[data-pt-theme="light"] .prism-stat.is-primary .prism-stat-label {
            color: #92400e;
        }
        :root[data-pt-theme="light"] .prism-stat.is-positive {
            border-color: rgba(4,120,87,0.42);
        }
        :root[data-pt-theme="light"] .prism-stat.is-positive .prism-stat-value {
            color: #047857;
        }
        :root[data-pt-theme="light"] .prism-stat.is-attention {
            border-color: rgba(8,145,178,0.45);
        }
        :root[data-pt-theme="light"] .prism-stat.is-attention .prism-stat-value {
            color: #0e7490;
        }

        /* Light: quick-action card (admin dashboard "Manage shows / showtimes
           / bookings" tiles). */
        :root[data-pt-theme="light"] .prism-quick-action {
            background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(252,250,245,0.86));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 22px 44px -22px rgba(15,23,42,0.22),
                0 4px 10px -4px rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] .prism-quick-action:hover {
            border-color: rgba(79,70,229,0.45);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 30px 60px -22px rgba(79,70,229,0.36),
                0 0 28px rgba(8,145,178,0.18);
        }
        :root[data-pt-theme="light"] .prism-quick-action-arrow {
            background: rgba(15,23,42,0.05);
            border-color: rgba(15,23,42,0.16);
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] .prism-quick-action:hover .prism-quick-action-arrow {
            background: rgba(79,70,229,0.14);
            border-color: rgba(79,70,229,0.45);
            color: var(--prism-text);
        }

        /* Light: clean tables (admin bookings list, shows list, etc).
           Dark-mode thead bg `rgba(13,16,28,0.92)` and indigo-tinted row
           hover are jarring on cream. */
        :root[data-pt-theme="light"] .prism-table-clean thead {
            background: rgba(252,250,245,0.95);
        }
        :root[data-pt-theme="light"] .prism-table-clean thead th {
            color: var(--prism-text-3);
            border-bottom-color: rgba(15,23,42,0.18);
        }
        :root[data-pt-theme="light"] .prism-table-clean tbody td {
            border-bottom-color: rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] .prism-table-clean tbody tr:hover td {
            background: rgba(79,70,229,0.06);
        }

        /* Light: floating action bar (admin booking approve/reject, anba
           seat picker mobile, etc). Dark slate gradient + transparent
           white chip backgrounds break on cream. */
        :root[data-pt-theme="light"] .pt-action-bar-inner {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(252,250,245,0.92));
            border-color: rgba(79,70,229,0.32);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 -16px 60px -22px rgba(15,23,42,0.22),
                0 0 36px rgba(79,70,229,0.14);
        }
        :root[data-pt-theme="light"] .pt-action-bar .pt-bar-chip {
            background: rgba(15,23,42,0.05);
            border-color: rgba(79,70,229,0.22);
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] .pt-action-bar .pt-bar-chip-gold {
            background: linear-gradient(135deg, rgba(245,158,11,0.16), rgba(245,158,11,0.06));
            border-color: rgba(180,83,9,0.45);
            color: #92400e;
        }
        :root[data-pt-theme="light"] .pt-action-bar .pt-bar-chip-muted {
            color: var(--prism-text-3);
            border-color: rgba(15,23,42,0.14);
        }

        /* Light: back chevron + ticket row (admin booking detail). */
        :root[data-pt-theme="light"] .pt-back-chevron {
            background: rgba(15,23,42,0.05);
            border-color: rgba(15,23,42,0.14);
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] .pt-back-chevron:hover {
            background: rgba(79,70,229,0.10);
            border-color: rgba(79,70,229,0.45);
            color: var(--prism-text);
        }
        :root[data-pt-theme="light"] .pt-ticket-row {
            background: rgba(15,23,42,0.03);
            border-color: rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] .pt-ticket-row:hover {
            background: rgba(79,70,229,0.06);
            border-color: rgba(79,70,229,0.32);
        }

        /* Light: ghost button — the dark-mode 0.04 white bg is invisible on cream */
        :root[data-pt-theme="light"] .prism-btn-ghost {
            background: rgba(15,23,42,0.04);
            border-color: rgba(15,23,42,0.14);
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] .prism-btn-ghost:hover {
            background: rgba(15,23,42,0.07);
            border-color: rgba(15,23,42,0.22);
            color: var(--prism-text);
        }

        /* Light: primary button — pastel gradient stays, but the indigo
           drop shadow is too pale on cream; bump it for proper lift. */
        :root[data-pt-theme="light"] .prism-btn {
            border-color: rgba(255,255,255,0.85);
            box-shadow:
                0 10px 24px -8px rgba(79,70,229,0.42),
                0 2px 4px -2px rgba(15,23,42,0.12),
                inset 0 1px 0 rgba(255,255,255,0.85);
        }
        :root[data-pt-theme="light"] .prism-btn:hover:not(:disabled) {
            box-shadow:
                0 16px 34px -8px rgba(79,70,229,0.58),
                0 0 22px rgba(8,145,178,0.22),
                inset 0 1px 0 rgba(255,255,255,0.85);
        }

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
           ADMIN POLISH — Reusable utilities + missing light-mode overrides.
           Goal: every admin CRUD page (bookings, shows, showtimes, settings)
           reads cleanly in BOTH themes without per-view inline color tweaks.
           ====================================================================*/

        /* ------------- Missing dark-mode component baselines ------------- */
        /* (These already exist in the dark theme — re-asserted here only when
           a light-mode override below depends on a specific base property.
           No new dark-mode visual changes.) */

        /* ------------- Light: filter toolbar / quick-stats strip ------------- */
        /* Their dark slate gradients (rgba(20,24,38,*)) read as a near-black
           rectangle on the cream background — breaking the "light glass" feel
           of every admin index page. */
        :root[data-pt-theme="light"] .prism-stat-strip {
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(252,250,245,0.86));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 20px 40px -22px rgba(15,23,42,0.18),
                0 4px 10px -4px rgba(15,23,42,0.08);
        }
        :root[data-pt-theme="light"] .prism-stat-strip > .prism-stat-strip-item {
            border-inline-end-color: rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] .prism-stat-strip-label {
            color: var(--prism-text-3);
        }

        :root[data-pt-theme="light"] .prism-toolbar {
            background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(252,250,245,0.90));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 20px 40px -22px rgba(15,23,42,0.20),
                0 4px 10px -4px rgba(15,23,42,0.08);
        }

        /* ------------- Light: segmented control ------------- */
        :root[data-pt-theme="light"] .prism-segment {
            background: rgba(15,23,42,0.04);
            border-color: rgba(15,23,42,0.14);
        }
        :root[data-pt-theme="light"] .prism-segment > label {
            color: var(--prism-text-3);
        }
        :root[data-pt-theme="light"] .prism-segment > label:hover { color: var(--prism-text-2); }
        :root[data-pt-theme="light"] .prism-segment > input[type="radio"]:checked + label {
            color: var(--prism-text);
            background: linear-gradient(135deg, rgba(8,145,178,0.14), rgba(124,58,237,0.14));
            border-color: rgba(79,70,229,0.45);
            box-shadow: 0 0 18px rgba(79,70,229,0.16);
        }

        /* ------------- Light: seat chips (admin booking rows) -------------
           Dark-mode chip text colors (#a5f3fc, #fde68a) disappear on cream.
           Switch to deep tints with matching tinted backgrounds. */
        :root[data-pt-theme="light"] .pt-seat-chip-hall {
            background: linear-gradient(135deg, rgba(8,145,178,0.14), rgba(79,70,229,0.08));
            border-color: rgba(8,145,178,0.40);
            color: #0e7490;
        }
        :root[data-pt-theme="light"] .pt-seat-chip-balcony {
            background: linear-gradient(135deg, rgba(180,83,9,0.14), rgba(124,58,237,0.08));
            border-color: rgba(180,83,9,0.40);
            color: #92400e;
        }
        :root[data-pt-theme="light"] .pt-seat-chip {
            background: rgba(79,70,229,0.10);
            border-color: rgba(79,70,229,0.32);
            color: #3730a3;
        }

        /* ------------- Light: form section card ------------- */
        /* Dark slate base on the admin shows / showtimes create/edit forms
           reads as a black bar on cream. Switch to a soft white panel. */
        :root[data-pt-theme="light"] .pt-form-section {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(252,250,245,0.90));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 22px 44px -22px rgba(15,23,42,0.20),
                0 4px 10px -4px rgba(15,23,42,0.08);
        }
        :root[data-pt-theme="light"] .pt-form-section-head {
            border-bottom-color: rgba(79,70,229,0.22);
        }
        :root[data-pt-theme="light"] .pt-form-section-head-icon {
            background: linear-gradient(135deg, rgba(8,145,178,0.16), rgba(124,58,237,0.16));
            border-color: rgba(79,70,229,0.40);
            color: var(--prism-text);
            box-shadow: 0 0 14px rgba(79,70,229,0.16);
        }
        :root[data-pt-theme="light"] .pt-file-zone {
            border-color: rgba(79,70,229,0.40);
        }

        /* Image preview card. Sits next to a .pt-file-zone and gives
           the operator an instant framed preview of the image they
           just picked (or the existing one on edit forms). Handles
           broken-image / load-error states inline with a hatched
           fallback so a dead CDN URL doesn't render a sad <img>. */
        .pt-image-preview {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 12px;
            padding: 10px;
            border-radius: 16px;
            background: rgba(8,10,20,0.45);
            border: 1px solid var(--prism-border);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
        }
        .pt-image-preview[hidden] { display: none; }
        .pt-image-preview-frame {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: rgba(0,0,0,0.45);
            aspect-ratio: 2 / 3;
            max-height: 360px;
        }
        .pt-image-preview-img {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: contain;
            background: transparent;
            transition: opacity .2s var(--prism-ease);
        }
        .pt-image-preview-fallback {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 8px;
            color: var(--prism-text-3);
            font-size: 12px;
            text-align: center;
            padding: 16px;
            background:
                repeating-linear-gradient(
                    45deg,
                    rgba(255,255,255,0.02),
                    rgba(255,255,255,0.02) 8px,
                    transparent 8px,
                    transparent 16px
                ),
                rgba(8,10,20,0.65);
        }
        .pt-image-preview-fallback[hidden] { display: none; }
        .pt-image-preview-fallback-icon { font-size: 28px; opacity: .85; }
        .pt-image-preview-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 11px;
            color: var(--prism-text-3);
            letter-spacing: .04em;
        }
        .pt-image-preview-meta-label {
            color: var(--prism-text-2);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .pt-image-preview-meta-detail {
            font-variant-numeric: tabular-nums;
            opacity: .85;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 60%;
        }
        :root[data-pt-theme="light"] .pt-image-preview {
            background: rgba(255,255,255,0.92);
            border-color: rgba(15,23,42,0.12);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 6px 18px -12px rgba(15,23,42,0.15);
        }
        :root[data-pt-theme="light"] .pt-image-preview-frame {
            background: rgba(15,23,42,0.04);
        }
        :root[data-pt-theme="light"] .pt-image-preview-fallback {
            color: #475569;
            background:
                repeating-linear-gradient(
                    45deg,
                    rgba(15,23,42,0.04),
                    rgba(15,23,42,0.04) 8px,
                    transparent 8px,
                    transparent 16px
                ),
                rgba(255,255,255,0.95);
        }

        /* =====================================================================
           Reusable admin utilities — replace repeated inline-styled patterns
           with semantic classes so they look right in BOTH themes.
           ====================================================================*/

        /* ------------- pt-alert: flash / validation / info banner -------------
           Replaces the repeated `style="background: rgba(...); border: 1px solid
           rgba(...); color: #xxxxxx"` blocks scattered across admin views. */
        .pt-alert {
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.55;
            border: 1px solid var(--prism-border);
            background: rgba(255,255,255,0.04);
            color: var(--prism-text-2);
        }
        .pt-alert + .pt-alert { margin-top: 10px; }
        .pt-alert-success {
            background: rgba(52,211,153,0.10);
            border-color: rgba(52,211,153,0.45);
            color: #6ee7b7;
        }
        .pt-alert-info {
            background: rgba(34,211,238,0.10);
            border-color: rgba(34,211,238,0.40);
            color: #a5f3fc;
        }
        .pt-alert-warn {
            background: rgba(251,191,36,0.10);
            border-color: rgba(251,191,36,0.40);
            color: #fcd34d;
        }
        .pt-alert-danger {
            background: rgba(244,63,94,0.10);
            border-color: rgba(251,113,133,0.45);
            color: #fda4af;
        }
        :root[data-pt-theme="light"] .pt-alert {
            background: rgba(15,23,42,0.04);
            border-color: rgba(15,23,42,0.14);
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] .pt-alert-success {
            background: rgba(4,120,87,0.10);
            border-color: rgba(4,120,87,0.40);
            color: #047857;
        }
        :root[data-pt-theme="light"] .pt-alert-info {
            background: rgba(8,145,178,0.10);
            border-color: rgba(8,145,178,0.40);
            color: #0e7490;
        }
        :root[data-pt-theme="light"] .pt-alert-warn {
            background: rgba(180,83,9,0.10);
            border-color: rgba(180,83,9,0.40);
            color: #92400e;
        }
        :root[data-pt-theme="light"] .pt-alert-danger {
            background: rgba(190,18,60,0.10);
            border-color: rgba(190,18,60,0.40);
            color: #be123c;
        }

        /* ------------- pt-mini-card: small inline data card -------------
           Used for hall/balcony/total capacity boxes, pricing splits, etc.
           Dark-mode token-driven colors stay; light-mode tints replace them. */
        .pt-mini-card {
            border-radius: 12px;
            padding: 10px 12px;
            border: 1px solid var(--prism-border);
            background: rgba(255,255,255,0.04);
        }
        .pt-mini-card-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--prism-text-3);
            display: block;
        }
        .pt-mini-card-value {
            font-weight: 700;
            font-size: 16px;
            font-family: "Space Grotesk", system-ui, sans-serif;
        }
        .pt-mini-card-gold    { background: rgba(251,191,36,0.08); border-color: rgba(251,191,36,0.32); }
        .pt-mini-card-gold .pt-mini-card-value    { color: var(--prism-gold); }
        .pt-mini-card-violet  { background: rgba(192,132,252,0.08); border-color: rgba(192,132,252,0.32); }
        .pt-mini-card-violet .pt-mini-card-value  { color: #c084fc; }
        .pt-mini-card-emerald { background: rgba(52,211,153,0.10); border-color: rgba(52,211,153,0.40); }
        .pt-mini-card-emerald .pt-mini-card-value { color: var(--prism-emerald); }
        .pt-mini-card-cyan    { background: rgba(34,211,238,0.10); border-color: rgba(34,211,238,0.40); }
        .pt-mini-card-cyan .pt-mini-card-value    { color: var(--prism-cyan); }
        .pt-mini-card-rose    { background: rgba(244,63,94,0.10); border-color: rgba(251,113,133,0.40); }
        .pt-mini-card-rose .pt-mini-card-value    { color: var(--prism-rose); }
        :root[data-pt-theme="light"] .pt-mini-card {
            background: rgba(15,23,42,0.03);
            border-color: rgba(15,23,42,0.12);
        }
        :root[data-pt-theme="light"] .pt-mini-card-gold {
            background: rgba(180,83,9,0.08);
            border-color: rgba(180,83,9,0.32);
        }
        :root[data-pt-theme="light"] .pt-mini-card-gold .pt-mini-card-value { color: #b45309; }
        :root[data-pt-theme="light"] .pt-mini-card-violet {
            background: rgba(124,58,237,0.08);
            border-color: rgba(124,58,237,0.32);
        }
        :root[data-pt-theme="light"] .pt-mini-card-violet .pt-mini-card-value { color: #7c3aed; }
        :root[data-pt-theme="light"] .pt-mini-card-emerald {
            background: rgba(4,120,87,0.08);
            border-color: rgba(4,120,87,0.32);
        }
        :root[data-pt-theme="light"] .pt-mini-card-emerald .pt-mini-card-value { color: #047857; }
        :root[data-pt-theme="light"] .pt-mini-card-cyan {
            background: rgba(8,145,178,0.08);
            border-color: rgba(8,145,178,0.32);
        }
        :root[data-pt-theme="light"] .pt-mini-card-cyan .pt-mini-card-value { color: #0e7490; }
        :root[data-pt-theme="light"] .pt-mini-card-rose {
            background: rgba(190,18,60,0.08);
            border-color: rgba(190,18,60,0.32);
        }
        :root[data-pt-theme="light"] .pt-mini-card-rose .pt-mini-card-value { color: #be123c; }

        /* ------------- pt-action-pill: tinted action button -------------
           Replaces inline-styled action buttons (edit / delete / view-times /
           seat-map / cancel) in admin shows + showtimes lists. CSS-only hover
           replaces inline onmouseover handlers. */
        .pt-action-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            min-height: 38px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            border: 1px solid var(--prism-border);
            background: rgba(255,255,255,0.06);
            color: var(--prism-text);
            cursor: pointer;
            transition:
                background .2s var(--prism-ease),
                border-color .2s var(--prism-ease),
                box-shadow .2s var(--prism-ease),
                transform .15s var(--prism-ease);
            -webkit-tap-highlight-color: transparent;
        }
        .pt-action-pill:hover {
            background: rgba(129,140,248,0.14);
            border-color: rgba(129,140,248,0.40);
        }
        .pt-action-pill:active { transform: scale(0.97); }

        .pt-action-pill-violet  { background: rgba(192,132,252,0.12); border-color: rgba(192,132,252,0.35); color: #ddd6fe; }
        .pt-action-pill-violet:hover  { background: rgba(192,132,252,0.22); box-shadow: 0 0 18px rgba(192,132,252,0.28); }
        .pt-action-pill-cyan    { background: rgba(34,211,238,0.12); border-color: rgba(34,211,238,0.40); color: #a5f3fc; }
        .pt-action-pill-cyan:hover    { background: rgba(34,211,238,0.22); box-shadow: 0 0 18px rgba(34,211,238,0.28); }
        .pt-action-pill-emerald { background: rgba(52,211,153,0.12); border-color: rgba(52,211,153,0.40); color: #6ee7b7; }
        .pt-action-pill-emerald:hover { background: rgba(52,211,153,0.22); box-shadow: 0 0 18px rgba(52,211,153,0.28); }
        .pt-action-pill-gold    { background: rgba(251,191,36,0.14); border-color: rgba(251,191,36,0.40); color: #fcd34d; }
        .pt-action-pill-gold:hover    { background: rgba(251,191,36,0.24); box-shadow: 0 0 18px rgba(251,191,36,0.28); }
        .pt-action-pill-rose    { background: rgba(244,63,94,0.12); border-color: rgba(251,113,133,0.40); color: #fda4af; }
        .pt-action-pill-rose:hover    { background: rgba(244,63,94,0.22); box-shadow: 0 0 18px rgba(244,63,94,0.28); }

        :root[data-pt-theme="light"] .pt-action-pill {
            background: rgba(15,23,42,0.04);
            border-color: rgba(15,23,42,0.14);
            color: var(--prism-text-2);
        }
        :root[data-pt-theme="light"] .pt-action-pill:hover {
            background: rgba(79,70,229,0.10);
            border-color: rgba(79,70,229,0.40);
            color: var(--prism-text);
        }
        :root[data-pt-theme="light"] .pt-action-pill-violet  {
            background: rgba(124,58,237,0.10); border-color: rgba(124,58,237,0.35); color: #6d28d9;
        }
        :root[data-pt-theme="light"] .pt-action-pill-violet:hover  {
            background: rgba(124,58,237,0.18); box-shadow: 0 0 18px rgba(124,58,237,0.22);
        }
        :root[data-pt-theme="light"] .pt-action-pill-cyan    {
            background: rgba(8,145,178,0.10); border-color: rgba(8,145,178,0.40); color: #0e7490;
        }
        :root[data-pt-theme="light"] .pt-action-pill-cyan:hover    {
            background: rgba(8,145,178,0.18); box-shadow: 0 0 18px rgba(8,145,178,0.22);
        }
        :root[data-pt-theme="light"] .pt-action-pill-emerald {
            background: rgba(4,120,87,0.10); border-color: rgba(4,120,87,0.40); color: #047857;
        }
        :root[data-pt-theme="light"] .pt-action-pill-emerald:hover {
            background: rgba(4,120,87,0.18); box-shadow: 0 0 18px rgba(4,120,87,0.22);
        }
        :root[data-pt-theme="light"] .pt-action-pill-gold    {
            background: rgba(180,83,9,0.10); border-color: rgba(180,83,9,0.40); color: #92400e;
        }
        :root[data-pt-theme="light"] .pt-action-pill-gold:hover    {
            background: rgba(180,83,9,0.18); box-shadow: 0 0 18px rgba(180,83,9,0.22);
        }
        :root[data-pt-theme="light"] .pt-action-pill-rose    {
            background: rgba(190,18,60,0.10); border-color: rgba(190,18,60,0.40); color: #be123c;
        }
        :root[data-pt-theme="light"] .pt-action-pill-rose:hover    {
            background: rgba(190,18,60,0.18); box-shadow: 0 0 18px rgba(190,18,60,0.22);
        }

        /* ------------- pt-thead-soft: subtle table head bg ------------- */
        /* Replaces inline `style="background: rgba(255,255,255,0.04)"` on
           admin tables (showtimes index) so it adapts to light mode. */
        .pt-thead-soft { background: rgba(255,255,255,0.04); }
        :root[data-pt-theme="light"] .pt-thead-soft {
            background: rgba(15,23,42,0.03);
        }

        /* ------------- pt-time-row: lightweight table-row hover ------------- */
        /* Used on admin/show_times/index where the table doesn't use
           .prism-table-clean so we need a standalone hover style. */
        .pt-time-row {
            border-top: 1px solid rgba(255,255,255,0.06);
            transition: background .15s ease;
        }
        .pt-time-row:hover { background: rgba(129,140,248,0.06); }
        :root[data-pt-theme="light"] .pt-time-row {
            border-top-color: rgba(15,23,42,0.08);
        }
        :root[data-pt-theme="light"] .pt-time-row:hover { background: rgba(79,70,229,0.06); }

        @media (prefers-reduced-motion: reduce) {
            .pt-action-pill { transition: none !important; }
            .pt-action-pill:active { transform: none !important; }
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
            background: linear-gradient(180deg, rgba(255,255,255,0.88), rgba(255,255,255,0.76));
            border-color: rgba(15,23,42,0.10);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.95),
                0 18px 38px -22px rgba(15,23,42,0.22),
                0 2px 4px rgba(15,23,42,0.08);
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
        /* Brand chip — the painted logo lives inside this slot,
           sized so the mark feels like a real brand anchor (52px on
           desktop, 44px on mobile) instead of a tiny decorative icon.
           The chip itself is intentionally near-invisible (subtle
           border, ~zero background) so the painted artwork carries
           the identity; the `.pt-brand-orb` glow behind + the
           drop-shadow on the image are what give it presence on dark
           surfaces. */
        .pt-brand-logo {
            position: relative;
            width: 52px; height: 52px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 14px;
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(255,255,255,0.06);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.06),
                0 0 32px rgba(129,140,248,0.22),
                0 0 18px rgba(232,196,118,0.10);
            transition: transform .4s var(--prism-ease), box-shadow .35s var(--prism-ease);
            overflow: visible;
            flex: 0 0 auto;
            padding: 3px;
        }
        :root[data-pt-theme="light"] .pt-brand-logo {
            background: rgba(15,23,42,0.035);
            border-color: rgba(15,23,42,0.08);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.92),
                0 0 32px rgba(79,70,229,0.16),
                0 0 18px rgba(212,166,78,0.14);
        }
        .pt-brand-logo svg { position: relative; z-index: 1; }
        /* Painted logo mark — the artwork has its color identity
           (gold serifs, teal J-descender, violet diagonal on the N)
           baked in, so no `fill` / `currentColor` plumbing. We fill
           the chip almost entirely (a tiny 3px chip padding above
           keeps the painted strokes from kissing the rounded
           corners) and lean on a real drop-shadow for crispness on
           dark surfaces — without the shadow, the painted strokes
           visually merge into the dark navbar background. */
        .pt-brand-mark-img {
            position: relative;
            z-index: 1;
            display: block;
            height: 54px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.55)) drop-shadow(0 0 8px rgba(232,196,118,0.22));
            transition: height 0.3s var(--prism-ease);
        }
        :root[data-pt-theme="light"] .pt-brand-mark-img {
            filter: drop-shadow(0 1px 2px rgba(20, 18, 40, 0.32)) drop-shadow(0 0 6px rgba(125, 75, 168, 0.16));
        }
        /* Cinematic glow halo behind the mark. Larger inset / more
           saturated mix so the painted artwork feels lit-from-behind
           rather than just placed on a chip. The slow drift keeps it
           alive without being distracting. */
        .pt-brand-orb {
            position: absolute;
            inset: -45%;
            background:
                radial-gradient(closest-side, rgba(232,196,118,0.30), transparent 65%),
                radial-gradient(closest-side, rgba(129,140,248,0.40), transparent 70%);
            filter: blur(14px);
            opacity: 0.7;
            animation: ptBrandOrb 7s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: 0;
        }
        @keyframes ptBrandOrb {
            0%   { transform: translate(-10%, -6%) scale(0.95); }
            100% { transform: translate(8%, 6%) scale(1.12); }
        }
        @media (hover: hover) {
            /* Painted artwork looks awkward when rotated (the strokes
               are hand-painted with a top-down light source) — so on
               hover we lift / brighten instead of tilting. */
            .pt-brand:hover .pt-brand-logo {
                transform: scale(1.06);
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.08),
                    0 0 44px rgba(129,140,248,0.34),
                    0 0 24px rgba(232,196,118,0.18);
            }
            .pt-brand:hover .pt-brand-orb { opacity: 0.95; }
        }
        .pt-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
            min-width: 0;
        }
        .pt-brand-wordmark {
            /* Rebranded to "El3abed" — mixed-case signature, no
               longer the uppercase wide-tracked PREMIUM wordmark.
               Slightly larger and tightly kerned so the new (longer)
               string occupies roughly the same optical width as the
               old 7-char PREMIUM token without crowding the brand
               chip or the right-side action cluster on iPhone widths. */
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.005em;
            background: var(--prism-neon);
            background-size: 220% 100%;
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
            animation: ptShimmerText 7s linear infinite;
            white-space: nowrap;
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
            /* Reserve enough width to fit the wider of AR/EN labels so the
               topbar doesn't reflow when applyLang() swaps the text on
               first paint. الرئيسية / Shows / لوحة التحكم all fit in 7ch
               comfortably; the gap is absorbed inside the pill. Reduces
               first-paint CLS on the topbar to zero. */
            min-width: 7ch;
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
            /* Mobile brand image height */
            .pt-brand-mark-img { height: 44px; }
            .pt-brand { gap: 9px; }
            .pt-brand-wordmark { font-size: 13.5px; letter-spacing: 0.005em; }
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
        .pt-drawer-brand img {
            height: 48px;
            width: auto;
            object-fit: contain;
            display: block;
        }
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
        :root[data-pt-theme="light"] .pt-hero-eyebrow { background: rgba(15,23,42,0.08); border-color: rgba(15,23,42,0.14); }
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
        :root[data-pt-theme="light"] .pt-hero-stat { background: rgba(15,23,42,0.06); border-color: rgba(15,23,42,0.12); }
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
        :root[data-pt-theme="light"] .pt-how-step-icon { background: rgba(15,23,42,0.08); border-color: rgba(15,23,42,0.14); }

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
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(255,255,255,0.76));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                0 22px 46px -22px rgba(15,23,42,0.26),
                0 4px 10px -4px rgba(15,23,42,0.10);
        }
        :root[data-pt-theme="light"] .pt-show-card:hover {
            border-color: rgba(79,70,229,0.34);
            box-shadow:
                0 32px 70px -28px rgba(15,23,42,0.30),
                0 0 24px rgba(79,70,229,0.16);
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
        :root[data-pt-theme="light"] .pt-show-time { background: rgba(15,23,42,0.06); border-color: rgba(15,23,42,0.12); }
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
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                0 24px 52px -22px rgba(15,23,42,0.26),
                0 4px 10px -4px rgba(15,23,42,0.10);
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
        .pt-footer-brand-col {
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        .pt-footer-brand-logo-img {
            height: 72px;
            width: auto;
            object-fit: contain;
            display: block;
        }
        @media (max-width: 720px) {
            .pt-footer-brand-col {
                justify-content: center;
                margin-bottom: 8px;
            }
            .pt-footer-brand-logo-img {
                height: 60px;
            }
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

        /* ====================================================================
           Footer · developer signature (Apple/A24 stamp)
           --------------------------------------------------------------------
           Final centered "studio stamp" at the bottom of every page.
           Redesigned away from the previous loud gold-gradient + glassy-pill
           version to a calm minimal mark — Apple-store / A24-credits energy.

           Visual structure:
             ── · ──                                  thin hairline + dot ornament
             Developed by                             sentence-case eyebrow (LTR)
             Jonathan Maged · © 2026                  monochrome cream wordmark (LTR)
             ENGINEERED FOR CINEMATIC EXPERIENCES     tracked-caps craft signature
             ⚆ Contact                                quiet inline text-link (icon + word only)

           Defensive note on the iPhone Safari "zoom-out goes weird" bug:
           the previous version had a 280px absolutely-positioned ::before
           ambient glow that could potentially escape the parent box at
           extreme pinch-out. The new design has no decorative paint, and
           the container uses `overflow: clip` for belt + braces so the
           signature can never contribute to horizontal scroll.
        ==================================================================== */
        .pt-foot-sig {
            margin-top: 40px;
            padding-top: 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            text-align: center;
            position: relative;
            /* Belt + braces against any decorative paint leaking out of
               the signature block (would have triggered horizontal
               scroll on iOS Safari at extreme pinch-out). */
            overflow: clip;
        }

        /* Ornament — thin horizontal hairlines flanking a centered
           interpunct. Tightened (narrower + paler) so it reads as a
           subtle anchor mark above the wordmark, not a divider. */
        .pt-foot-sig-ornament {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: clamp(120px, 28vw, 180px);
            margin-bottom: 4px;
            color: rgba(251,191,36,0.42);
        }
        .pt-foot-sig-ornament-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg,
                transparent 0%,
                rgba(251,191,36,0.32) 50%,
                transparent 100%);
        }
        .pt-foot-sig-ornament-dot {
            font-size: 14px;
            line-height: 1;
            opacity: 0.62;
        }

        /* Eyebrow — quiet "Developed by" above the wordmark.
           Sentence-case Latin (not tracked caps) so it contrasts the
           ALL-CAPS subtitle further down and sandwiches the name in
           two different typographic registers — Apple
           "Designed by Apple in California" energy. */
        .pt-foot-sig-eyebrow {
            margin: 0;
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: clamp(11px, 1.7vw, 12.5px);
            line-height: 1.4;
            font-weight: 400;
            letter-spacing: 0.04em;
            color: var(--prism-text-4);
            opacity: 0.62;
        }
        /* Tighten the gap between eyebrow + wordmark so the two read
           as one signature unit rather than two stacked lines. */
        .pt-foot-sig-eyebrow + .pt-foot-sig-name-row {
            margin-top: -6px;
        }

        /* Wordmark row — monochrome cream typography, locked LTR so the
           Latin name + © + year read in correct order inside the RTL doc
           (the dir="ltr" on the element flips the inline-flex direction). */
        .pt-foot-sig-name-row {
            display: inline-flex;
            align-items: baseline;
            gap: 14px;
            color: var(--prism-text);
        }
        .pt-foot-sig-name {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: clamp(16px, 2.6vw, 19px);
            font-weight: 500;
            letter-spacing: 0.015em;
        }
        .pt-foot-sig-meta {
            display: inline-flex;
            align-items: baseline;
            gap: 7px;
            color: var(--prism-text-3);
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: clamp(12px, 1.85vw, 13.5px);
            font-weight: 400;
            letter-spacing: 0.04em;
            opacity: 0.72;
        }
        .pt-foot-sig-sep {
            opacity: 0.7;
        }

        /* Calm Latin caption beneath the wordmark — uppercase small caps
           with generous tracking so it reads as Apple-style metadata,
           not as a tagline. Says "the developer made this for cinematic
           experiences" without ever repeating the name above. */
        .pt-foot-sig-sub {
            margin: 4px 0 2px;
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: clamp(10px, 1.55vw, 11px);
            line-height: 1.5;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--prism-text-4);
            opacity: 0.58;
        }

        /* Contact action — quiet inline text-link, NO pill chrome.
           Tiny outlined WhatsApp glyph + single 'Contact' word —
           no arrow, no Arabic label, no decorative wording.
           Hover lifts the gold accents without adding any chrome. */
        .pt-foot-sig-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding: 6px 4px;
            color: var(--prism-text-3);
            text-decoration: none;
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.04em;
            transition:
                color .22s var(--prism-ease),
                opacity .22s var(--prism-ease);
            -webkit-tap-highlight-color: transparent;
        }
        .pt-foot-sig-link-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: rgba(251,191,36,0.72);
            opacity: 0.85;
            transition: color .22s var(--prism-ease),
                        opacity .22s var(--prism-ease);
        }
        @media (hover: hover) {
            .pt-foot-sig-link:hover {
                color: var(--prism-text);
            }
            .pt-foot-sig-link:hover .pt-foot-sig-link-icon {
                opacity: 1;
                color: #fbbf24;
            }
        }
        .pt-foot-sig-link:focus-visible {
            outline: 2px solid rgba(251,191,36,0.45);
            outline-offset: 4px;
            border-radius: 4px;
        }

        /* Mobile: slightly tighter rhythm so the stamp doesn't crowd
           the iOS home indicator, but still keeps generous breathing
           room compared to the previous denser version. */
        @media (max-width: 480px) {
            .pt-foot-sig {
                margin-top: 32px;
                padding-top: 30px;
                gap: 14px;
            }
            .pt-foot-sig-ornament { width: clamp(100px, 32vw, 160px); }
            .pt-foot-sig-link { margin-top: 8px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .pt-foot-sig-link,
            .pt-foot-sig-link-icon { transition: none; }
        }

        /* Light-mode — gold accents shift to deeper amber on cream
           paper. The wordmark itself is the normal text color and
           inherits the light theme automatically (no override needed). */
        :root[data-pt-theme="light"] .pt-foot-sig-ornament {
            color: rgba(180,83,9,0.42);
        }
        :root[data-pt-theme="light"] .pt-foot-sig-ornament-line {
            background: linear-gradient(90deg,
                transparent 0%,
                rgba(180,83,9,0.32) 50%,
                transparent 100%);
        }
        :root[data-pt-theme="light"] .pt-foot-sig-link-icon {
            color: rgba(180,83,9,0.72);
        }
        @media (hover: hover) {
            :root[data-pt-theme="light"] .pt-foot-sig-link:hover .pt-foot-sig-link-icon {
                color: #b45309;
            }
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
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(255,255,255,0.72));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                0 26px 56px -22px rgba(15,23,42,0.26),
                0 4px 10px -4px rgba(15,23,42,0.10);
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
            /* Anchor-scroll buffer so the floating top-bar (~76-80px)
               doesn't clip the section's eyebrow / heading when a CTA
               like "تفاصيل العرض" jumps the user here via `#hash`. */
            scroll-margin-top: 96px;
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
        :root[data-pt-theme="light"] .pt-cine-scroll-cue { color: var(--prism-text-2); }
        :root[data-pt-theme="light"] .pt-cine-scroll-cue-line {
            background: linear-gradient(to bottom, transparent, rgba(15,23,42,0.55), transparent);
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
        :root[data-pt-theme="light"] .pt-cine-prologue-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.85), rgba(255,255,255,0.62));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                0 30px 80px -28px rgba(15,23,42,0.30),
                0 6px 14px -6px rgba(15,23,42,0.12),
                inset 0 1px 0 rgba(255,255,255,0.85);
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

        /* =====================================================================
           PR 4 — Cinematic homepage for "العباد"
           Show-specific scenes (hero / trailer / cast rail / story / how-to)
           that replace the old generic onboarding. All CSS-first, GPU-friendly,
           `prefers-reduced-motion: reduce` guarded at the bottom of the block.
           ===================================================================== */

        /* Shared section title (gold gradient) + sub */
        .pt-alebad-section-title {
            font-size: clamp(32px, 6.5vw, 56px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin: 0;
            color: var(--prism-text);
        }
        .pt-alebad-section-title-grad {
            background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 50%, #d97706 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .pt-alebad-section-sub {
            font-size: clamp(15px, 2.5vw, 17px);
            line-height: 1.7;
            color: var(--prism-text-3);
            max-width: 580px;
            margin: 10px 0 0;
        }
        .pt-live-dot-gold {
            background: #fbbf24 !important;
            box-shadow: 0 0 12px rgba(251,191,36,0.65);
        }

        /* ====================================================================
           Scene 0 — El3abed cinematic presentation
           Full-viewport studio-intro card that opens the homepage. Deep
           warm-black backdrop, ambient gold spotlight + drifting orbs, the
           3D gold "El3abed" mark as the centerpiece, a
           refined Arabic credit line, and a cinematic cross-fade bleed
           into the العباد hero immediately below.

           Performance notes:
             * The logo PNG (~445KB master, ~270KB mobile) is preloaded
               from the page's headMeta so its first paint matches the
               HTML arrival. mix-blend-mode: screen turns the image's
               near-black backdrop into "no-op" pixels at composite
               time — no alpha decoding, no extra GPU upload.
             * Spotlight + orb + breath animations are pure transform
               + opacity, which iOS Safari composites on the GPU
               without main-thread cost. No filter() animations.
             * All ambient motion stripped under prefers-reduced-motion.
        ==================================================================== */
        .pt-alebad-presents {
            position: relative;
            min-height: 100svh;
            overflow: hidden;
            padding: 96px 20px 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            isolation: isolate;
            background:
                radial-gradient(ellipse 80% 60% at 50% 42%,
                    #1c1409 0%,
                    #0d0a06 45%,
                    #050403 100%);
        }
        @supports not (height: 100svh) {
            .pt-alebad-presents { min-height: 100vh; }
        }
        @media (min-width: 768px) {
            .pt-alebad-presents { padding: 120px 64px 160px; }
        }

        .pt-alebad-presents-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
        .pt-alebad-presents-spotlight {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 55% 42% at 50% 40%,
                    rgba(212,175,90,0.22) 0%,
                    rgba(212,175,90,0.08) 35%,
                    rgba(212,175,90,0) 70%);
            animation: presentsSpotlightBreath 7s ease-in-out infinite;
            transform-origin: 50% 40%;
        }
        @keyframes presentsSpotlightBreath {
            0%, 100% { opacity: 0.88; transform: scale(1); }
            50%      { opacity: 1;    transform: scale(1.04); }
        }
        .pt-alebad-presents-vignette {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 50% 48%,
                    rgba(0,0,0,0) 35%,
                    rgba(0,0,0,0.5) 75%,
                    rgba(0,0,0,0.92) 100%);
        }
        .pt-alebad-presents .pt-cine-grain {
            opacity: 0.06;
        }

        .pt-alebad-presents-orbs {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
        }
        .pt-alebad-presents-orb {
            position: absolute;
            width: 520px; height: 520px;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.32;
            will-change: transform;
        }
        .pt-alebad-presents-orb-a {
            background: radial-gradient(circle,
                rgba(218,180,108,0.78) 0%,
                rgba(218,180,108,0) 70%);
            top: -180px;
            left: -140px;
            animation: presentsOrbA 18s ease-in-out infinite;
        }
        .pt-alebad-presents-orb-b {
            background: radial-gradient(circle,
                rgba(180,130,60,0.55) 0%,
                rgba(180,130,60,0) 70%);
            bottom: -220px;
            right: -160px;
            animation: presentsOrbB 22s ease-in-out infinite;
        }
        @keyframes presentsOrbA {
            0%, 100% { transform: translate3d(0,0,0); }
            50%      { transform: translate3d(36px, 24px, 0); }
        }
        @keyframes presentsOrbB {
            0%, 100% { transform: translate3d(0,0,0); }
            50%      { transform: translate3d(-32px, -28px, 0); }
        }

        .pt-alebad-presents-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 26px;
            text-align: center;
            max-width: 760px;
            width: 100%;
        }
        @media (min-width: 768px) {
            .pt-alebad-presents-content { gap: 36px; }
        }

        .pt-alebad-presents-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 7px 16px;
            border-radius: 999px;
            background: rgba(212,175,90,0.06);
            border: 1px solid rgba(212,175,90,0.22);
            color: #d4af5a;
            font-family: "Space Grotesk", "IBM Plex Sans", system-ui, sans-serif;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.42em;
            text-transform: uppercase;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }
        .pt-alebad-presents-eyebrow-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #d4af5a;
            box-shadow: 0 0 12px rgba(212,175,90,0.7);
            animation: presentsEyebrowDot 2.4s ease-in-out infinite;
        }
        @keyframes presentsEyebrowDot {
            0%, 100% { opacity: 1; }
            50%      { opacity: 0.4; }
        }

        /* Logo wrapper. Width clamps so the mark stays cinematic but
           readable on every viewport. */
        .pt-alebad-presents-mark {
            position: relative;
            display: block;
            width: 100%;
            max-width: clamp(280px, 86vw, 720px);
        }
        @media (min-width: 1024px) {
            .pt-alebad-presents-mark { max-width: 780px; }
        }
        .pt-alebad-presents-mark picture,
        .pt-alebad-presents-mark img {
            display: block;
            width: 100%;
            height: auto;
        }
        .pt-alebad-presents-mark img {
            /* The PNG has a dark backdrop baked in; screen-blend drops
               that backdrop so only the gold typography composites
               onto the scene's warm-black gradient. Looks pasted
               onto the scene rather than sitting in a black box. */
            mix-blend-mode: screen;
            filter: drop-shadow(0 30px 90px rgba(212,175,90,0.18));
            animation: presentsMarkBreath 9s ease-in-out infinite;
            transform-origin: 50% 50%;
        }
        @keyframes presentsMarkBreath {
            0%, 100% { transform: scale(1);     filter: drop-shadow(0 30px 90px rgba(212,175,90,0.18)); }
            50%      { transform: scale(1.012); filter: drop-shadow(0 36px 110px rgba(212,175,90,0.26)); }
        }
        /* Extra soft glow disc behind the logo. Sits in the parent's
           stacking context, BEHIND the image, so it amplifies the
           spotlight without changing the logo's blend mode. */
        .pt-alebad-presents-mark-glow {
            position: absolute;
            inset: 10% 14%;
            border-radius: 50%;
            background: radial-gradient(ellipse at center,
                rgba(212,175,90,0.22) 0%,
                rgba(212,175,90,0.08) 40%,
                rgba(212,175,90,0) 70%);
            filter: blur(40px);
            pointer-events: none;
            z-index: -1;
            animation: presentsMarkGlow 7s ease-in-out infinite;
        }
        @keyframes presentsMarkGlow {
            0%, 100% { opacity: 0.9;  transform: scale(1); }
            50%      { opacity: 1;    transform: scale(1.06); }
        }

        .pt-alebad-presents-tagline {
            font-family: "IBM Plex Sans Arabic", "IBM Plex Sans",
                "Segoe UI", system-ui, sans-serif;
            font-size: clamp(14.5px, 2.4vw, 17px);
            line-height: 1.85;
            color: rgba(245,232,200,0.78);
            letter-spacing: 0.01em;
            max-width: 580px;
            margin: 0;
        }
        .pt-alebad-presents-tagline-name {
            font-weight: 600;
            background: linear-gradient(135deg,
                #f6e4ab 0%,
                #d4af5a 50%,
                #a37a2c 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #d4af5a; /* fallback */
        }
        .pt-alebad-presents-tagline-divider {
            color: rgba(212,175,90,0.5);
            margin: 0 0.3em;
        }

        .pt-alebad-presents-cue {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            color: rgba(212,175,90,0.7);
            font-family: "Space Grotesk", "IBM Plex Sans Arabic", system-ui, sans-serif;
            font-size: 10.5px;
            letter-spacing: 0.36em;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .pt-alebad-presents-cue-chevron {
            display: inline-block;
            font-size: 16px;
            line-height: 1;
            color: #d4af5a;
            animation: presentsCueDrift 2.2s ease-in-out infinite;
        }
        @keyframes presentsCueDrift {
            0%, 100% { transform: translateY(0);  opacity: 0.7; }
            50%      { transform: translateY(6px); opacity: 1;   }
        }

        /* Cross-fade bleed into Scene 1 (العباد hero). The bottom 28vh
           of the presents scene fades from warm-black into the hero's
           cool-black so the cut between scenes reads as a film
           cross-fade, not a section break. */
        .pt-alebad-presents-bleed {
            position: absolute;
            inset: auto 0 0 0;
            height: 28vh;
            background: linear-gradient(to bottom,
                rgba(5,4,3,0)    0%,
                rgba(5,4,3,0.45) 35%,
                rgba(5,6,13,0.88) 78%,
                #05060d 100%);
            z-index: 3;
            pointer-events: none;
        }

        @media (prefers-reduced-motion: reduce) {
            .pt-alebad-presents-spotlight,
            .pt-alebad-presents-mark img,
            .pt-alebad-presents-mark-glow,
            .pt-alebad-presents-eyebrow-dot,
            .pt-alebad-presents-cue-chevron,
            .pt-alebad-presents-orb {
                animation: none !important;
            }
        }

        /* ---------- Scene 1 — Hero ---------- */
        .pt-alebad-hero {
            position: relative;
            min-height: 100svh;
            overflow: hidden;
            padding: 88px 20px 56px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            isolation: isolate;
        }
        @supports not (height: 100svh) {
            .pt-alebad-hero { min-height: 100vh; }
        }
        @media (min-width: 768px) {
            .pt-alebad-hero { padding: 120px 64px 96px; }
        }

        .pt-alebad-hero-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }
        .pt-alebad-hero-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Zoom + offset so we frame ONLY the priest figure — the source
               poster has its own title artwork at the bottom and patriarchal
               blessing text at the top; we'd be fighting two title layers
               and unreadable text otherwise. Mobile pulls the figure into
               view via a wider scale; desktop relaxes it slightly. */
            object-position: 28% 50%;
            transform: scale(2.2);
            filter: saturate(1.05) contrast(1.04) brightness(0.95);
            opacity: 0.7;
            transition: transform 16s ease-out, opacity .8s var(--prism-ease);
        }
        @media (min-width: 768px) {
            .pt-alebad-hero-img {
                object-position: 30% 45%;
                transform: scale(1.8);
            }
        }
        @media (min-width: 1280px) {
            .pt-alebad-hero-img {
                object-position: 32% 45%;
                transform: scale(1.5);
            }
        }
        .pt-alebad-hero.is-active .pt-alebad-hero-img {
            transform: scale(2.05);
        }
        @media (min-width: 768px) {
            .pt-alebad-hero.is-active .pt-alebad-hero-img { transform: scale(1.7); }
        }
        @media (min-width: 1280px) {
            .pt-alebad-hero.is-active .pt-alebad-hero-img { transform: scale(1.42); }
        }
        .pt-alebad-hero-veil {
            position: absolute;
            inset: 0;
            background:
                /* Top dark band to obliterate any in-image text and keep the
                   eyebrow chip readable. */
                linear-gradient(180deg, rgba(5,6,13,0.85) 0%, rgba(5,6,13,0.35) 22%, rgba(5,6,13,0) 38%),
                /* Bottom dark band so CTAs sit on solid color. */
                linear-gradient(180deg, rgba(5,6,13,0) 55%, rgba(5,6,13,0.95) 100%),
                /* RTL — push extra darkness to the right (text side). */
                linear-gradient(to left, rgba(5,6,13,0) 0%, rgba(5,6,13,0.45) 60%, rgba(5,6,13,0.7) 100%);
        }
        .pt-alebad-hero-vignette {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(0,0,0,0) 45%, rgba(0,0,0,0.7) 100%);
            mix-blend-mode: multiply;
        }
        .pt-alebad-hero-orbs {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
        }

        .pt-alebad-hero-content {
            position: relative;
            z-index: 2;
            max-width: 720px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding-top: 12px;
        }
        @media (min-width: 1024px) {
            .pt-alebad-hero-content { max-width: 820px; gap: 22px; }
        }

        .pt-alebad-eyebrow {
            padding: 7px 14px;
            border: 1px solid rgba(251,191,36,0.32);
            background: rgba(251,191,36,0.08);
            color: #fbbf24;
            font-size: 11.5px;
            letter-spacing: 0.04em;
        }

        /* The hero `<h1>` is now a frame for the calligraphic title artwork
           (el3abed-title.png) instead of a stack of styled text. The
           `<h1>` itself acts purely as a positioning + glow container;
           the image is the visual title. The previous `.pt-alebad-hero-titletext`
           and `.pt-alebad-hero-sub` rules are gone because the elements
           they targeted no longer exist in the DOM. */
        .pt-alebad-hero-title {
            position: relative;
            margin: 4px 0 0;
            line-height: 0;
            color: #f9fafb;
        }
        .pt-alebad-hero-logo {
            display: block;
            width: clamp(220px, 56vw, 380px);
            height: auto;
            margin-inline-start: 0;
            /* Soft warm gold halo. `drop-shadow` follows the alpha edge of
               the calligraphy strokes instead of painting a rectangular
               glow box behind the image, which keeps the cinematic poster
               feel without flat halos. Stacked drop-shadows = tighter
               inner + softer outer for depth. */
            filter:
                drop-shadow(0 2px 6px rgba(0,0,0,0.55))
                drop-shadow(0 10px 38px rgba(251,191,36,0.18));
        }
        @media (min-width: 768px) {
            .pt-alebad-hero-logo {
                width: clamp(280px, 38vw, 460px);
            }
        }
        @media (min-width: 1280px) {
            .pt-alebad-hero-logo {
                width: clamp(360px, 32vw, 520px);
            }
        }

        .pt-alebad-hero-credit {
            display: flex;
            align-items: baseline;
            gap: 10px;
            font-size: 15px;
            color: var(--prism-text-3);
            margin: 0;
        }
        .pt-alebad-hero-credit-label {
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 10.5px;
            opacity: 0.7;
        }
        .pt-alebad-hero-credit-name {
            font-weight: 600;
            color: var(--prism-text);
            font-size: 17px;
            letter-spacing: -0.01em;
        }

        .pt-alebad-hero-tagline {
            font-size: clamp(15px, 2.5vw, 17px);
            line-height: 1.75;
            color: var(--prism-text-2);
            max-width: 540px;
            margin: 0;
        }

        .pt-alebad-hero-cta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
        }
        .pt-alebad-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 22px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            min-height: 48px;
            transition: transform .2s var(--prism-ease), background .2s var(--prism-ease), border-color .2s var(--prism-ease), box-shadow .2s var(--prism-ease);
        }
        .pt-alebad-cta-ghost {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.16);
            color: #f1f5fb;
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
        }
        .pt-alebad-cta-ghost:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.32);
            transform: translateY(-1px);
            color: #f1f5fb;
        }
        .pt-alebad-cta-play .pt-alebad-cta-play-glyph {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: rgba(251,191,36,0.15);
            border: 1px solid rgba(251,191,36,0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fbbf24;
            padding-left: 2px;
        }
        .pt-alebad-cta:active { transform: scale(0.97); }

        .pt-alebad-scroll-cue {
            margin-top: 28px;
        }

        /* ---------- Scene 2 — Trailer card ---------- */
        .pt-alebad-trailer {
            padding: 96px 20px 72px;
            text-align: center;
            isolation: isolate;
        }
        @media (min-width: 768px) {
            .pt-alebad-trailer { padding: 120px 64px 96px; }
        }

        .pt-alebad-trailer-head {
            position: relative;
            z-index: 2;
            max-width: 760px;
            margin: 0 auto 28px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            align-items: center;
        }

        .pt-alebad-trailer-card {
            position: relative;
            z-index: 2;
            display: block;
            max-width: 980px;
            margin: 8px auto 0;
            text-decoration: none;
            color: inherit;
        }

        /* The card itself now hosts the ambient cinematic glow that sits
           BEHIND the frame so the trailer reads as a piece of light
           rather than a pasted-in iframe. Two soft radial fills (warm
           gold above, cool cyan below) bloom outside the frame's
           rounded edges. */
        .pt-alebad-trailer-card::before {
            content: '';
            position: absolute;
            inset: -8% -6%;
            z-index: 0;
            background:
                radial-gradient(60% 50% at 50% 0%, rgba(251,191,36,0.22) 0%, rgba(251,191,36,0) 70%),
                radial-gradient(70% 60% at 50% 100%, rgba(34,211,238,0.18) 0%, rgba(34,211,238,0) 70%);
            filter: blur(36px);
            opacity: 0.85;
            pointer-events: none;
            transition: opacity .6s var(--prism-ease), transform .6s var(--prism-ease);
        }
        .pt-alebad-trailer-card.is-playing::before {
            opacity: 1;
            transform: scale(1.04);
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-alebad-trailer-card::before { transition: none; }
        }

        .pt-alebad-trailer-frame {
            position: relative;
            z-index: 1; /* sit above the card's ambient glow ::before */
            display: block;
            aspect-ratio: 16 / 9;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.12);
            background: #060810;
            box-shadow:
                0 32px 80px rgba(0,0,0,0.55),
                0 0 0 1px rgba(255,255,255,0.03),
                0 0 60px rgba(251,191,36,0.10);
            transition:
                transform .35s cubic-bezier(.2, 1.2, .2, 1),
                box-shadow .35s var(--prism-ease),
                border-color .35s var(--prism-ease);
            will-change: transform;
            /* The frame is the click target — make that obvious to
               users on every input modality (mouse, touch, keyboard). */
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .pt-alebad-trailer-frame:focus-visible {
            outline: 2px solid rgba(251,191,36,0.85);
            outline-offset: 4px;
        }
        .pt-alebad-trailer-frame:hover {
            transform: translateY(-4px) scale(1.008);
            box-shadow:
                0 44px 110px rgba(0,0,0,0.65),
                0 0 0 1px rgba(251,191,36,0.18),
                0 0 100px rgba(251,191,36,0.20);
            border-color: rgba(251,191,36,0.36);
        }
        @media (hover: none) {
            .pt-alebad-trailer-frame:hover { transform: none; }
        }
        /* While loading / playing: lock down the springy hover so the
           card doesn't fight the iframe's own UI. */
        .pt-alebad-trailer-card.is-playing .pt-alebad-trailer-frame {
            transform: none;
            cursor: default;
            border-color: rgba(251,191,36,0.20);
            box-shadow:
                0 50px 130px rgba(0,0,0,0.75),
                0 0 0 1px rgba(251,191,36,0.14),
                0 0 120px rgba(251,191,36,0.18);
        }

        .pt-alebad-trailer-thumb {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: 50% 30%;
            filter: saturate(1.05) contrast(1.05) brightness(0.78);
            transition: transform .6s var(--prism-ease), filter .3s var(--prism-ease), opacity .4s var(--prism-ease);
        }
        .pt-alebad-trailer-frame:hover .pt-alebad-trailer-thumb {
            transform: scale(1.04);
            filter: saturate(1.1) contrast(1.05) brightness(0.85);
        }

        .pt-alebad-trailer-veil {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(50% 50% at 50% 50%, rgba(0,0,0,0) 30%, rgba(0,0,0,0.55) 100%),
                linear-gradient(180deg, rgba(0,0,0,0) 60%, rgba(0,0,0,0.55) 100%);
            transition: opacity .4s var(--prism-ease);
        }

        .pt-alebad-trailer-play {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 86px; height: 86px;
            border-radius: 50%;
            background: rgba(251,191,36,0.95);
            color: #1f1300;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding-left: 4px;
            box-shadow: 0 24px 50px rgba(0,0,0,0.45), 0 0 0 4px rgba(251,191,36,0.18);
            transition: transform .25s var(--prism-ease), box-shadow .25s var(--prism-ease), opacity .3s var(--prism-ease);
        }
        @media (min-width: 768px) {
            .pt-alebad-trailer-play { width: 116px; height: 116px; }
        }
        .pt-alebad-trailer-frame:hover .pt-alebad-trailer-play {
            transform: translate(-50%, -50%) scale(1.06);
            box-shadow: 0 32px 70px rgba(0,0,0,0.55), 0 0 0 8px rgba(251,191,36,0.22);
        }
        .pt-alebad-trailer-frame:active .pt-alebad-trailer-play {
            transform: translate(-50%, -50%) scale(0.96);
        }

        .pt-alebad-trailer-pulse {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 86px; height: 86px;
            border-radius: 50%;
            border: 2px solid rgba(251,191,36,0.4);
            animation: alebadTrailerPulse 2.4s ease-out infinite;
            pointer-events: none;
            transition: opacity .3s var(--prism-ease);
        }
        @media (min-width: 768px) {
            .pt-alebad-trailer-pulse { width: 116px; height: 116px; }
        }
        @keyframes alebadTrailerPulse {
            0%   { opacity: 0.6; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(1.8); }
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-alebad-trailer-pulse { animation: none; opacity: 0.3; }
        }

        /* Loading state: spinner + caption ("...جارٍ التحميل") that
           overlays the frame while the FB plugin iframe is mounting.
           Hidden until JS adds `.is-loading`, hidden again once
           `.is-playing` (iframe has fired its load event). */
        .pt-alebad-trailer-loading {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: rgba(6,8,16,0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            color: var(--prism-text-2);
            font-size: 13px;
            letter-spacing: 0.06em;
            opacity: 0;
            visibility: hidden;
            transition: opacity .3s var(--prism-ease), visibility .3s;
            z-index: 4;
        }
        .pt-alebad-trailer-card.is-loading .pt-alebad-trailer-loading {
            opacity: 1;
            visibility: visible;
        }
        .pt-alebad-trailer-spinner {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid rgba(251,191,36,0.18);
            border-top-color: rgba(251,191,36,0.95);
            animation: alebadTrailerSpin .8s linear infinite;
        }
        @keyframes alebadTrailerSpin {
            to { transform: rotate(360deg); }
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-alebad-trailer-spinner { animation: none; }
        }

        /* Inline iframe: covers the whole frame, sits above the
           thumb/play/pulse layers. Fades in over .35s so the
           "poster → trailer" transition reads as cinematic rather
           than abrupt. */
        .pt-alebad-trailer-frame > iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            opacity: 0;
            transition: opacity .35s var(--prism-ease);
            z-index: 5;
        }
        .pt-alebad-trailer-card.is-playing .pt-alebad-trailer-frame > iframe {
            opacity: 1;
        }
        /* Once playing, fade out the poster/play/pulse/veil so the
           frame is uncluttered around the FB player chrome. */
        .pt-alebad-trailer-card.is-playing .pt-alebad-trailer-thumb,
        .pt-alebad-trailer-card.is-playing .pt-alebad-trailer-veil,
        .pt-alebad-trailer-card.is-playing .pt-alebad-trailer-play,
        .pt-alebad-trailer-card.is-playing .pt-alebad-trailer-pulse {
            opacity: 0;
            pointer-events: none;
        }

        .pt-alebad-trailer-meta {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 6px 0;
            font-size: 13px;
            color: var(--prism-text-3);
            letter-spacing: 0.04em;
        }
        .pt-alebad-trailer-meta-label {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.18em;
            opacity: 0.7;
        }
        .pt-alebad-trailer-meta-arrow {
            font-size: 14px;
            transition: transform .2s var(--prism-ease);
        }
        /* Fallback link reads as a low-emphasis "if the embed didn't
           work, here's the FB share URL" lifeline. Underline-on-hover
           reinforces it's a link (the meta strip otherwise has no
           other links). */
        .pt-alebad-trailer-fallback {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--prism-text-3);
            text-decoration: none;
            font-size: 12px;
            letter-spacing: 0.08em;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.10);
            background: rgba(255,255,255,0.02);
            transition: color .2s var(--prism-ease), border-color .2s var(--prism-ease), background-color .2s var(--prism-ease);
        }
        .pt-alebad-trailer-fallback:hover,
        .pt-alebad-trailer-fallback:focus-visible {
            color: #fbbf24;
            border-color: rgba(251,191,36,0.36);
            background: rgba(251,191,36,0.06);
            outline: none;
        }
        .pt-alebad-trailer-fallback:hover .pt-alebad-trailer-meta-arrow,
        .pt-alebad-trailer-fallback:focus-visible .pt-alebad-trailer-meta-arrow {
            transform: translate(2px, -2px);
        }
        /* If the FB embed never fires `load` within 6s, the JS sets
           `.is-stalled` on the card. We emphasise the fallback link so
           the user can still reach the trailer on Facebook directly. */
        .pt-alebad-trailer-card.is-stalled .pt-alebad-trailer-fallback {
            color: #fbbf24;
            border-color: rgba(251,191,36,0.36);
            background: rgba(251,191,36,0.08);
        }

        /* ---------- Scene 3 — Cast rail ---------- */
        .pt-alebad-cast {
            padding: 96px 0 72px;
            isolation: isolate;
        }
        @media (min-width: 768px) {
            .pt-alebad-cast { padding: 120px 0 96px; }
        }

        .pt-alebad-cast-head {
            position: relative;
            z-index: 2;
            padding: 0 20px;
            max-width: 980px;
            margin: 0 auto 22px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            align-items: flex-start;
        }
        @media (min-width: 768px) {
            .pt-alebad-cast-head { padding: 0 64px; margin-bottom: 28px; }
        }

        /* Cast rail v4 — desktop interaction + remove edge mask.
           v3 design retained where it worked (iOS proximity snap,
           native momentum), v4 fixes:
             1. Edge mask removed — was visually clipping the first
                and last card to transparency, reading as "cut off".
             2. Desktop now has visible nav arrows, mouse drag-to-
                scroll, vertical wheel → horizontal conversion,
                grab/grabbing cursors.
             3. Active-card .is-centered emphasis (fully-visible
                cards get a subtle scale + glow boost).
           See setupCastRailInteractions IIFE in app layout for the
           desktop wiring. Mobile path is untouched. */
        .pt-alebad-cast-rail-wrap {
            position: relative;
            z-index: 2;
            /* CRITICAL: The cast `<section>` is a column-flex container
               with `align-items: center` (inherited from
               `.pt-cine-scene`). Without an explicit width / align-self
               override, this flex item would `align-self: center` and
               shrink-to-fit its content width — which, for the rail
               below, is the natural width of every card laid out flat
               (8 cards × 300px + gaps ≈ 2700px). The section's
               `overflow: hidden` would then clip that to the viewport,
               but the inner `<ul>`'s `overflow-x: auto` would never
               engage (because the `<ul>`'s `scrollWidth === clientWidth`
               — both equal the natural content width). End result: rail
               is visually clipped at the section edges and totally
               un-scrollable on every input device (no swipe on touch,
               no drag on mouse, no wheel-to-horizontal). Pinning the
               wrap to the section's content box (`width: 100%` +
               `align-self: stretch`) restores a proper viewport for
               the rail so `overflow-x: auto` can do its job.
               `min-width: 0` lets the wrap shrink below its intrinsic
               content size if the section ever ends up in a narrower
               flex parent (defensive). */
            width: 100%;
            align-self: stretch;
            min-width: 0;
        }

        /* Inner stagger container — wraps the ul + hint so the cine
           stagger fades them in together. Arrows live OUTSIDE this
           inner so the stagger doesn't toggle their opacity (they
           manage their own hover-reveal). Same width-pinning rationale
           as the wrap above — keeps the inner from collapsing to its
           content width if anything in the cascade ever flips it into
           a flex/grid context. */
        .pt-alebad-cast-rail-inner {
            position: relative;
            width: 100%;
            min-width: 0;
        }

        .pt-alebad-cast-rail {
            list-style: none;
            margin: 0;
            /* Inline padding matches `scroll-padding-inline` so the
               start-snapped card lands flush against the scene's lead
               edge with breathing room rather than touching it. */
            padding: 12px 20px 32px;
            display: flex;
            gap: 16px;
            overflow-x: auto;
            overflow-y: hidden;
            /* `proximity` (NOT `mandatory`) is the magic snap mode for
               iOS — it only snaps when the scroll velocity slows on
               its own, NEVER fights momentum. Combined with no
               `scroll-snap-stop` on the cards, a flick coasts
               naturally and lands wherever momentum takes it, then
               settles softly onto the nearest card. Feels like a
               native iOS Photos carousel. */
            scroll-snap-type: x proximity;
            scroll-padding-inline: 20px;
            /* Native iOS momentum — REQUIRED on iOS Safari for the
               rubber-band / coast behavior we want. */
            -webkit-overflow-scrolling: touch;
            /* Horizontal containment so a hard flick at the rail's end
               doesn't bubble up and trigger the browser's
               back/forward swipe or rubber-band the whole page. */
            overscroll-behavior-inline: contain;
            /* Tell iOS this element scrolls horizontally only — stops
               iOS from interpreting tiny vertical wobble as page
               scroll mid-flick. */
            touch-action: pan-x;
            scrollbar-width: none;
        }
        @media (min-width: 768px) {
            .pt-alebad-cast-rail {
                padding: 12px 64px 36px;
                gap: 20px;
                scroll-padding-inline: 64px;
            }
        }
        .pt-alebad-cast-rail::-webkit-scrollbar { display: none; }

        .pt-alebad-cast-card {
            flex: 0 0 auto;
            /* Card width sized so a deliberate ~30-35% peek of the
               next card is always visible on a 390px viewport
               (390 - 20*2 padding - 16 gap = 334px of "rail viewport";
               card at ~62vw = 242px leaves ~92px = 28% peek). The
               peek is the affordance — no extra UI needed. */
            width: clamp(208px, 62vw, 252px);
            /* `start` snap parks the card flush against the scene's
               lead edge so the layout reads cleanly. `proximity` (set
               on the parent) makes this a SOFT suggestion, not a
               hard requirement, so iOS momentum is preserved. */
            scroll-snap-align: start;
            border-radius: 18px;
            overflow: hidden;
            background: var(--prism-surface);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            transition:
                transform .3s var(--prism-ease),
                box-shadow .3s var(--prism-ease),
                border-color .3s var(--prism-ease);
            transition-delay: calc(var(--i, 0) * 0ms);
        }
        @media (min-width: 768px) {
            .pt-alebad-cast-card { width: 280px; }
        }
        @media (min-width: 1024px) {
            .pt-alebad-cast-card { width: 300px; }
        }
        .pt-alebad-cast-card:hover {
            transform: translateY(-6px) scale(1.015);
            box-shadow: 0 32px 80px rgba(0,0,0,0.55), 0 0 60px rgba(251,191,36,0.14);
            border-color: rgba(251,191,36,0.32);
        }
        @media (hover: none) {
            .pt-alebad-cast-card:hover {
                transform: none;
                box-shadow: 0 20px 50px rgba(0,0,0,0.4);
                border-color: rgba(255,255,255,0.08);
            }
        }

        .pt-alebad-cast-poster {
            position: relative;
            display: block;
            aspect-ratio: 4 / 5;
            overflow: hidden;
            background: #0a0d18;
        }
        .pt-alebad-cast-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .6s var(--prism-ease);
        }
        .pt-alebad-cast-card:hover .pt-alebad-cast-poster img {
            transform: scale(1.04);
        }

        .pt-alebad-cast-veil {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0) 60%, rgba(0,0,0,0.65) 100%);
            pointer-events: none;
        }
        .pt-alebad-cast-glow {
            position: absolute;
            inset: 0;
            background: radial-gradient(80% 50% at 50% 100%, rgba(251,191,36,0.28) 0%, rgba(251,191,36,0) 70%);
            opacity: 0;
            transition: opacity .3s var(--prism-ease);
            pointer-events: none;
        }
        .pt-alebad-cast-card:hover .pt-alebad-cast-glow { opacity: 1; }

        .pt-alebad-cast-caption {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 14px 16px 16px;
        }
        .pt-alebad-cast-role {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #fbbf24;
            opacity: 0.85;
        }
        .pt-alebad-cast-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--prism-text);
            letter-spacing: -0.01em;
        }

        .pt-alebad-cast-rail-hint {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 4px 20px 0;
            text-align: center;
            color: var(--prism-text-4);
            font-size: 11.5px;
            letter-spacing: 0.06em;
            /* Fades out gracefully once the user has scrolled the
               rail (handled by JS adding .is-acknowledged). Pulsing
               forever after the user has already engaged would read
               as nagging. */
            transition: opacity .35s ease, transform .35s ease;
        }
        .pt-alebad-cast-rail-hint.is-acknowledged {
            opacity: 0;
            transform: translateY(-2px);
            pointer-events: none;
        }
        .pt-alebad-cast-rail-hint-chevron {
            display: inline-block;
            color: #fbbf24;
            font-weight: 700;
            font-size: 14px;
            /* Subtle "go" pulse on the chevron so the affordance feels
               alive but not nagging. Reduced-motion override below. */
            animation: alebadCastHintPulse 1.8s ease-in-out infinite;
        }
        @keyframes alebadCastHintPulse {
            0%, 100% { transform: translateX(0); opacity: 0.7; }
            50%      { transform: translateX(-4px); opacity: 1; }
        }
        html[dir="rtl"] .pt-alebad-cast-rail-hint-chevron {
            animation-name: alebadCastHintPulseRtl;
        }
        @keyframes alebadCastHintPulseRtl {
            0%, 100% { transform: translateX(0); opacity: 0.7; }
            50%      { transform: translateX(4px); opacity: 1; }
        }
        @media (min-width: 1024px) {
            .pt-alebad-cast-rail-hint { display: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-alebad-cast-rail-hint-chevron { animation: none; }
        }

        /* -- Cast rail arrows (v4). Positioned absolute inside the
              wrap, hover-revealed on `pointer: fine` devices, fully
              hidden on touch (touch uses native momentum + the
              pulsing "swipe" hint instead). RTL-aware: position
              flips via inset-inline-* and icon flips via scaleX(-1).
              `.is-disabled` is JS-toggled when the rail is at start
              or end so the affordance doesn't lie. -- */
        .pt-alebad-cast-arrow {
            position: absolute;
            /* Vertical center against the poster (not the whole card
               including caption). On desktop the wrap is roughly
               padding-top 12 + poster 350 + caption ~60 + padding-
               bottom 36 ≈ 458px and the poster center is ~187px =
               ~41% of wrap height — 40% lands the button center
               1-2px above poster center, visually correct. */
            top: 40%;
            transform: translateY(-50%);
            z-index: 5;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: rgba(15, 17, 26, 0.78);
            -webkit-backdrop-filter: blur(10px) saturate(140%);
                    backdrop-filter: blur(10px) saturate(140%);
            border: 1px solid rgba(251, 191, 36, 0.32);
            color: #fbbf24;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            pointer-events: none;
            transition: opacity .35s var(--prism-ease),
                        transform .2s var(--prism-ease),
                        background-color .2s var(--prism-ease),
                        border-color .2s var(--prism-ease),
                        box-shadow .2s var(--prism-ease);
            box-shadow: 0 12px 28px rgba(0,0,0,0.4),
                        inset 0 1px 0 rgba(255,255,255,0.06);
            padding: 0;
            appearance: none;
            -webkit-appearance: none;
            outline: none;
        }
        @media (min-width: 1024px) {
            .pt-alebad-cast-arrow { width: 52px; height: 52px; }
        }
        /* Hover-reveal: only on fine pointers. Touch never sees these. */
        @media (pointer: fine) {
            .pt-alebad-cast-rail-wrap:hover .pt-alebad-cast-arrow,
            .pt-alebad-cast-arrow:focus-visible {
                opacity: 1;
                pointer-events: auto;
            }
        }
        @media (pointer: coarse) {
            .pt-alebad-cast-arrow { display: none; }
        }
        .pt-alebad-cast-arrow:hover {
            transform: translateY(-50%) scale(1.08);
            background: rgba(251, 191, 36, 0.16);
            border-color: rgba(251, 191, 36, 0.6);
            box-shadow: 0 16px 36px rgba(0,0,0,0.55), 0 0 32px rgba(251,191,36,0.34);
        }
        .pt-alebad-cast-arrow:active {
            transform: translateY(-50%) scale(0.94);
        }
        .pt-alebad-cast-arrow:focus-visible {
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.45),
                        0 16px 36px rgba(0,0,0,0.55);
        }
        .pt-alebad-cast-arrow.is-disabled {
            opacity: 0 !important;
            pointer-events: none !important;
        }
        /* Logical positioning — LTR puts prev on left, next on right;
           RTL flips both automatically. */
        .pt-alebad-cast-arrow-prev { inset-inline-start: 12px; }
        .pt-alebad-cast-arrow-next { inset-inline-end: 12px; }
        @media (min-width: 1024px) {
            .pt-alebad-cast-arrow-prev { inset-inline-start: 24px; }
            .pt-alebad-cast-arrow-next { inset-inline-end: 24px; }
        }
        /* Icon orientation. Base SVG (the polyline) points LEFT.
           - LTR prev: leave default (←).
           - LTR next: flip to (→).
           - RTL prev: flip to (→).
           - RTL next: leave default (←). */
        .pt-alebad-cast-arrow svg {
            transition: transform .2s var(--prism-ease);
        }
        .pt-alebad-cast-arrow-next svg { transform: scaleX(-1); }
        html[dir="rtl"] .pt-alebad-cast-arrow-prev svg { transform: scaleX(-1); }
        html[dir="rtl"] .pt-alebad-cast-arrow-next svg { transform: scaleX(1); }

        /* -- Desktop grab/grabbing cursors + snap-disable during
              active drag. On touch this entire block is no-op
              (cursor properties don't render on touch and we never
              add `.is-grabbing` on touch pointers). -- */
        @media (pointer: fine) {
            .pt-alebad-cast-rail {
                cursor: grab;
            }
            .pt-alebad-cast-rail.is-grabbing {
                cursor: grabbing;
                /* Don't fight the active mouse drag — snap reactivates
                   on pointerup so the rail still settles to a card. */
                scroll-snap-type: none;
                scroll-behavior: auto;
            }
            .pt-alebad-cast-rail.is-grabbing .pt-alebad-cast-card {
                user-select: none;
                -webkit-user-select: none;
                /* Prevent hover state from flickering on cards as
                   the cursor slides across them during a drag. */
                pointer-events: none;
            }
        }

        /* -- Active-card emphasis. Whichever card the
              IntersectionObserver flags as ≥85% visible in the rail
              viewport gets a subtle scale + glow boost. Purely
              additive — degrades gracefully if no IO support. Only
              applies on devices that can hover, since on touch
              you're constantly mid-snap and the emphasis would
              flicker. -- */
        @media (hover: hover) {
            .pt-alebad-cast-card.is-centered {
                transform: translateY(-3px);
                box-shadow: 0 24px 60px rgba(0,0,0,0.5), 0 0 36px rgba(251,191,36,0.18);
                border-color: rgba(251, 191, 36, 0.24);
            }
            /* Hover always wins over centered (so explicit user
               intent reads as "this one"). */
            .pt-alebad-cast-card.is-centered:hover {
                transform: translateY(-6px) scale(1.015);
                box-shadow: 0 32px 80px rgba(0,0,0,0.55), 0 0 60px rgba(251,191,36,0.22);
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .pt-alebad-cast-arrow { transition: opacity .2s linear; }
            .pt-alebad-cast-arrow:hover,
            .pt-alebad-cast-arrow:active { transform: translateY(-50%); }
            .pt-alebad-cast-card.is-centered { transform: none; }
        }

        /* ---------- Scene 4 — Story ---------- */
        .pt-alebad-story {
            padding: 96px 24px 72px;
            text-align: center;
            isolation: isolate;
        }
        @media (min-width: 768px) {
            .pt-alebad-story { padding: 120px 64px 96px; }
        }

        .pt-alebad-story-content {
            position: relative;
            z-index: 2;
            max-width: 760px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
            align-items: center;
        }

        /* Removed in PR 16: .pt-alebad-story-title + .pt-alebad-story-quote-mark.
           The visible Story scene no longer carries the
           "في صحراءٍ ما، عاش رجلٌ تركَ الدنيا..." cinematic quote nor
           the القصة eyebrow chip — the saint's name itself (rendered
           via .pt-alebad-story-bio-headline lower in this file) is now
           the cinematic title of the scene. */

        .pt-alebad-story-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            max-width: 320px;
            margin: 10px 0;
        }
        .pt-alebad-story-divider-bar {
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent 0%, rgba(251,191,36,0.32) 50%, transparent 100%);
        }
        .pt-alebad-story-divider-mark {
            color: #fbbf24;
            font-size: 13px;
            opacity: 0.75;
        }

        .pt-alebad-story-body {
            font-size: clamp(15px, 2.6vw, 17px);
            line-height: 1.85;
            color: var(--prism-text-2);
            margin: 0;
            max-width: 620px;
        }

        .pt-alebad-story-credits {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: center;
            gap: 8px 12px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid rgba(255,255,255,0.06);
            font-size: 13px;
            color: var(--prism-text-3);
            width: 100%;
            max-width: 620px;
        }
        .pt-alebad-story-credit {
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
        }
        .pt-alebad-story-credit-label {
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-size: 10.5px;
            opacity: 0.7;
        }
        .pt-alebad-story-credit-value {
            color: var(--prism-text);
            font-weight: 600;
        }
        .pt-alebad-story-credit-sep {
            color: var(--prism-text-4);
            opacity: 0.5;
        }

        /* ---------- Scene 4 — Story: expandable "Making Of" credits panel
           A compact disclosure surface that lives beneath the inline 3-credit
           row. Collapsed state is intentionally tiny — a one-line teaser of a
           few headline names + a ghost toggle button — so the homepage's
           vertical rhythm is barely affected. Expanded state reveals grouped
           sections (إنتاج / بطولة / بالاشتراك / صنّاع العمل / سيناريو · إخراج)
           in a responsive grid.

           Height animation uses the `grid-template-rows: 0fr ↔ 1fr` trick
           rather than max-height with a guessed pixel value — the panel
           animates to its natural content height regardless of how the
           cast grid wraps on the current viewport. */
        .pt-alebad-story-more {
            width: 100%;
            max-width: 760px;
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }
        .pt-alebad-story-more-teaser {
            margin: 0;
            font-size: 13.5px;
            line-height: 1.55;
            color: var(--prism-text-3);
            opacity: 0.85;
            text-align: center;
            max-width: 560px;
            letter-spacing: 0.005em;
        }
        .pt-alebad-story-more-teaser-more {
            display: inline-block;
            margin-inline-start: 6px;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid rgba(251,191,36,0.28);
            color: #fbbf24;
            font-size: 11.5px;
            letter-spacing: 0.02em;
            opacity: 0.9;
        }

        .pt-alebad-story-more-toggle {
            appearance: none;
            -webkit-appearance: none;
            background: transparent;
            border: 1px solid rgba(251,191,36,0.28);
            border-radius: 999px;
            padding: 9px 18px;
            color: #fbbf24;
            font: inherit;
            font-size: 13px;
            letter-spacing: 0.04em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background 200ms ease, border-color 200ms ease, transform 200ms ease;
            min-height: 40px;
        }
        .pt-alebad-story-more-toggle:hover,
        .pt-alebad-story-more-toggle:focus-visible {
            background: rgba(251,191,36,0.08);
            border-color: rgba(251,191,36,0.5);
            outline: none;
        }
        .pt-alebad-story-more-toggle:focus-visible {
            box-shadow: 0 0 0 3px rgba(251,191,36,0.18);
        }
        .pt-alebad-story-more-toggle-chev {
            display: inline-block;
            font-size: 14px;
            line-height: 1;
            transition: transform 320ms cubic-bezier(.2,.7,.2,1);
        }
        .pt-alebad-story-more-toggle[aria-expanded="true"] .pt-alebad-story-more-toggle-chev {
            transform: rotate(180deg);
        }
        /* Label swap — show one of two labels depending on expanded state. */
        .pt-alebad-story-more-toggle [data-hide],
        .pt-alebad-story-more-toggle[aria-expanded="true"] [data-show] {
            display: none;
        }
        .pt-alebad-story-more-toggle[aria-expanded="true"] [data-hide] {
            display: inline;
        }

        /* Expandable panel. Uses grid 0fr → 1fr to animate to natural height. */
        .pt-alebad-story-panel {
            width: 100%;
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 420ms cubic-bezier(.2,.7,.2,1);
        }
        .pt-alebad-story-panel.is-open {
            grid-template-rows: 1fr;
        }
        .pt-alebad-story-panel-inner {
            min-height: 0;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-4px);
            transition: opacity 380ms ease, transform 380ms ease;
        }
        .pt-alebad-story-panel.is-open .pt-alebad-story-panel-inner {
            opacity: 1;
            transform: translateY(0);
            transition-delay: 80ms;
        }

        .pt-alebad-story-panel-body {
            display: flex;
            flex-direction: column;
            gap: 28px;
            padding: 28px 4px 4px;
            text-align: start;
        }
        @media (min-width: 768px) {
            .pt-alebad-story-panel-body {
                gap: 36px;
                padding-top: 36px;
            }
        }

        .pt-alebad-story-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .pt-alebad-story-group-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: #fbbf24;
            opacity: 0.85;
            text-align: center;
            justify-content: center;
        }
        .pt-alebad-story-group-title::before,
        .pt-alebad-story-group-title::after {
            content: "";
            flex: 1;
            max-width: 80px;
            height: 1px;
            background: linear-gradient(to right,
                transparent 0%, rgba(251,191,36,0.32) 50%, transparent 100%);
        }

        .pt-alebad-story-group-prod {
            margin: 0;
            font-size: clamp(14px, 2.4vw, 15.5px);
            color: var(--prism-text-2);
            line-height: 1.7;
            text-align: center;
        }
        .pt-alebad-story-group-prod-sub {
            display: inline-block;
            color: var(--prism-text-3);
            font-size: 0.92em;
            margin-inline-start: 4px;
        }
        .pt-alebad-story-group-lead {
            margin: 0;
            font-size: clamp(20px, 4.2vw, 26px);
            font-weight: 600;
            color: var(--prism-text);
            text-align: center;
            letter-spacing: -0.005em;
            background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 60%, #d4af5a 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Cast grid — responsive columns with calm typography */
        .pt-alebad-story-group-cast {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px 14px;
            font-size: 13.5px;
            color: var(--prism-text-2);
            line-height: 1.55;
        }
        @media (min-width: 520px) {
            .pt-alebad-story-group-cast {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (min-width: 920px) {
            .pt-alebad-story-group-cast {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }
        .pt-alebad-story-group-cast li {
            padding: 4px 0;
            text-align: start;
        }

        /* Crew grid — role label + name pairs as a 2-column layout. */
        .pt-alebad-story-group-crew {
            margin: 0;
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px 22px;
        }
        @media (min-width: 600px) {
            .pt-alebad-story-group-crew {
                grid-template-columns: 1fr 1fr;
            }
        }
        .pt-alebad-story-group-crew-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 6px 0;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .pt-alebad-story-group-crew-row:first-of-type,
        .pt-alebad-story-group-crew-row:nth-of-type(2) {
            border-top: 0;
        }
        @media (max-width: 599px) {
            .pt-alebad-story-group-crew-row:nth-of-type(2) {
                border-top: 1px solid rgba(255,255,255,0.05);
            }
        }
        .pt-alebad-story-group-crew-row dt {
            font-size: 10.5px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--prism-text-3);
            opacity: 0.75;
        }
        .pt-alebad-story-group-crew-row dd {
            margin: 0;
            font-size: 14px;
            color: var(--prism-text);
            font-weight: 500;
        }

        .pt-alebad-story-group--final .pt-alebad-story-group-stamp {
            display: flex;
            justify-content: center;
            align-items: baseline;
            gap: 12px;
            margin: 0;
            font-size: clamp(15px, 2.8vw, 18px);
            color: var(--prism-text);
            font-weight: 600;
        }
        .pt-alebad-story-group--final .pt-alebad-story-group-stamp > span:not([aria-hidden]) {
            background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 60%, #d4af5a 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .pt-alebad-story-group--final .pt-alebad-story-group-stamp > span[aria-hidden] {
            color: var(--prism-text-4);
            opacity: 0.55;
        }

        /* Reduced motion — instant toggle, no height/fade animation.
           The panel is still toggleable, just snaps open/closed instantly. */
        @media (prefers-reduced-motion: reduce) {
            .pt-alebad-story-panel,
            .pt-alebad-story-panel-inner,
            .pt-alebad-story-more-toggle-chev,
            .pt-alebad-story-more-toggle {
                transition: none !important;
            }
        }

        /* ====================================================================
           Story · biography disclosure
           --------------------------------------------------------------------
           Adds a layered cinematic biography surface above the Making-Of
           credits panel. The visible section above this point now reads:
             1. eyebrow القصة
             2. dramatic block-quote
             3. ✦ ornament divider
             4. short bio summary (name + dates + one-sentence framing)
             5. fade-preview teaser + اقرأ السيرة الكاملة ↓
           Tapping the toggle reveals 6 grouped sections of Arabic prose
           covering the saint's life. Speech is rendered as real <blockquote>
           with cinematic styling so the reader perceives quoted voices,
           not body prose.

           Visual design notes:
            - Group title styling is inherited from .pt-alebad-story-group-title
              (gold-traced hairline + small-caps amber) so the biography reads
              as part of the same Story scene language as the Making-Of panel.
            - Body prose uses line-height 2.05 — Arabic text with tashkeel
              and punctuation needs significantly more leading than Latin
              prose at the same size to avoid feeling cramped.
            - Quote blocks have an inline-start gold rail, a generous
              opening ❝ ornament, and a small-caps speaker label beneath —
              distinct enough from prose paragraphs that the reader doesn't
              have to parse who's speaking.
            - The "virgin" variant gets a slightly stronger gold tint to
              echo the spiritual weight of the Marian apparition scene.
        ==================================================================== */
        /* Promoted in PR 16: this rule now styles the section's <h2>
           — the cinematic title of the Story scene. Replaces the role
           the في صحراءٍ ما... cinematic quote used to play. */
        .pt-alebad-story-bio-headline {
            display: block;
            margin: 0;
            /* Match the headline weight of the cast section so the
               two cinematic titles read at the same hierarchical level. */
            font-size: clamp(22px, 4.6vw, 38px);
            font-weight: 700;
            line-height: 1.35;
            letter-spacing: 0.005em;
            text-align: center;
            /* Warm gold gradient — same palette as the El3abed intro
               eyebrow + the cast lead's emphasis stamp so the saint's
               name reads as the section's premium cinematic title. */
            background: linear-gradient(135deg,
                #fef3c7 0%,
                #fbbf24 55%,
                #d4af5a 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .pt-alebad-story-bio-headline-years {
            /* Dates sit on the same line as the name where the viewport
               allows; the smaller size + lighter weight makes them feel
               like a subtitle, not a co-equal label. */
            display: inline-block;
            margin-inline-start: 8px;
            font-size: 0.58em;
            font-weight: 500;
            letter-spacing: 0.04em;
            vertical-align: middle;
            opacity: 0.88;
            /* The parent <h2> uses background-clip:text + transparent text
               fill to render its gradient through the saint's name glyphs.
               That trick does NOT extend into an inline-block child — the
               child inherits the transparent text-fill but has no background
               of its own to clip through, so its glyphs render against the
               page's dark backdrop (a dark blue-gray rectangle next to the
               name). Re-applying the same gradient + background-clip here
               gives the years span its own paint source so the dates show
               in the same cream→gold→bronze gradient as the name. */
            background: linear-gradient(135deg,
                #fef3c7 0%,
                #fbbf24 55%,
                #d4af5a 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        @media (max-width: 480px) {
            /* On narrow phones the (1899–1965) span wraps to a second
               line so the name still reads on a single comfortable line. */
            .pt-alebad-story-bio-headline-years {
                display: block;
                margin: 6px 0 0;
                font-size: 0.62em;
            }
        }

        .pt-alebad-story-bio { max-width: 720px; }

        /* The teaser line above the gold pill — bridges the visible
           summary into the disclosure button. Slightly muted so the
           pill carries the emphasis. */
        .pt-alebad-story-bio-teaser { color: var(--prism-text-2); opacity: 0.9; }

        .pt-alebad-story-bio-panel-body {
            max-width: 680px;
            margin: 0 auto;
            gap: 32px;
        }
        @media (min-width: 768px) {
            .pt-alebad-story-bio-panel-body { gap: 44px; }
        }

        .pt-alebad-story-bio-prose {
            margin: 0;
            font-size: clamp(14.5px, 2.6vw, 16px);
            /* Arabic prose with tashkeel + punctuation needs noticeably
               more leading than Latin at the same size. 2.05 lets each
               line breathe without feeling double-spaced. */
            line-height: 2.05;
            color: var(--prism-text-2);
            text-align: start;
            padding-inline: 4px;
        }
        @media (min-width: 640px) {
            .pt-alebad-story-bio-prose { padding-inline: 12px; }
        }

        /* Cinematic blockquote — quoted speech inside the biography
           (Virgin Mary dialogue, letters). Visually distinct from
           prose so the reader perceives "this is a remembered voice". */
        .pt-alebad-story-bio-quote {
            position: relative;
            margin: 6px 0;
            padding: 14px 22px 14px 26px;
            border-inline-start: 2px solid rgba(251,191,36,0.42);
            background: linear-gradient(
                to inline-end,
                rgba(251,191,36,0.06) 0%,
                rgba(251,191,36,0.0) 70%);
            border-radius: 0 12px 12px 0;
            color: var(--prism-text);
        }
        html[dir="rtl"] .pt-alebad-story-bio-quote {
            border-radius: 12px 0 0 12px;
        }
        .pt-alebad-story-bio-quote::before {
            content: "❝";
            position: absolute;
            inset-inline-start: 4px;
            top: -4px;
            font-size: 22px;
            line-height: 1;
            color: #fbbf24;
            opacity: 0.55;
        }
        .pt-alebad-story-bio-quote > p {
            margin: 0;
            font-size: clamp(15px, 2.6vw, 17px);
            line-height: 1.95;
            font-style: italic;
            font-weight: 500;
            color: var(--prism-text);
        }
        .pt-alebad-story-bio-quote-speaker {
            display: block;
            margin-top: 10px;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #fbbf24;
            opacity: 0.85;
            font-style: normal;
        }
        /* Virgin Mary variant — stronger gold tint to echo the
           spiritual weight of the Marian apparition scene. */
        .pt-alebad-story-bio-quote--virgin {
            border-inline-start-color: rgba(251,191,36,0.7);
            background: linear-gradient(
                to inline-end,
                rgba(251,191,36,0.11) 0%,
                rgba(251,191,36,0.02) 80%);
        }
        .pt-alebad-story-bio-quote--virgin::before { opacity: 0.85; }

        /* Light-mode retones for the biography surface — pull the
           hardcoded gold into the amber/slate palette used by the
           rest of PR #9's light-mode pass so the section reads on
           the cream page background. */
        :root[data-pt-theme="light"] .pt-alebad-story-bio-headline {
            /* On cream the dark-mode cream→gold→bronze gradient washes out.
               Re-tone to a deeper amber→bronze ramp so the saint's name
               reads as a strong title against the page background. */
            background: linear-gradient(135deg,
                #b45309 0%,
                #92400e 60%,
                #78350f 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 1;
        }
        :root[data-pt-theme="light"] .pt-alebad-story-bio-headline-years {
            /* Mirror the dark-mode fix in light mode — give the years span
               its own amber→bronze gradient so the dates stay readable on
               cream paper (without this the inline-block child inherits
               transparent text-fill from the parent and renders invisible). */
            background: linear-gradient(135deg,
                #b45309 0%,
                #92400e 60%,
                #78350f 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.78;
        }
        :root[data-pt-theme="light"] .pt-alebad-story-bio-quote {
            border-inline-start-color: rgba(180,83,9,0.45);
            background: linear-gradient(
                to inline-end,
                rgba(180,83,9,0.07) 0%,
                rgba(180,83,9,0.0) 70%);
            color: var(--prism-text);
        }
        :root[data-pt-theme="light"] .pt-alebad-story-bio-quote::before {
            color: #b45309;
        }
        :root[data-pt-theme="light"] .pt-alebad-story-bio-quote-speaker {
            color: #b45309;
        }
        :root[data-pt-theme="light"] .pt-alebad-story-bio-quote--virgin {
            border-inline-start-color: rgba(180,83,9,0.7);
            background: linear-gradient(
                to inline-end,
                rgba(180,83,9,0.13) 0%,
                rgba(180,83,9,0.03) 80%);
        }

        /* ---------- Scene 5 — Showtimes (tweak existing) ---------- */
        .pt-alebad-shows .pt-cine-shows-title {
            font-size: clamp(32px, 6.5vw, 56px);
            letter-spacing: -0.02em;
        }
        .pt-alebad-shows .pt-cine-shows-stats {
            margin-top: 22px;
        }

        /* ---------- Scene 6 — How-to (condensed) ---------- */
        .pt-alebad-howto {
            padding: 96px 20px 96px;
            isolation: isolate;
        }
        @media (min-width: 768px) {
            .pt-alebad-howto { padding: 120px 64px 120px; }
        }
        .pt-alebad-howto-head {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 720px;
            margin: 0 auto 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }
        .pt-alebad-howto-grid {
            position: relative;
            z-index: 2;
            list-style: none;
            margin: 0 auto;
            padding: 0;
            display: grid;
            gap: 14px;
            max-width: 1080px;
            grid-template-columns: 1fr;
        }
        @media (min-width: 640px) {
            .pt-alebad-howto-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (min-width: 1024px) {
            .pt-alebad-howto-grid { grid-template-columns: repeat(4, 1fr); gap: 18px; }
        }
        .pt-alebad-howto-step {
            position: relative;
            padding: 22px 20px 24px;
            border-radius: 18px;
            background: var(--prism-surface);
            border: 1px solid rgba(255,255,255,0.08);
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow: hidden;
            transition: transform .25s var(--prism-ease), border-color .25s var(--prism-ease), background .25s var(--prism-ease);
        }
        .pt-alebad-howto-step::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(34,211,238,0.16), rgba(129,140,248,0.16), rgba(192,132,252,0.16));
            opacity: 0;
            transition: opacity .25s var(--prism-ease);
            border-radius: inherit;
            z-index: 0;
            pointer-events: none;
        }
        .pt-alebad-howto-step:hover {
            transform: translateY(-3px);
            border-color: rgba(255,255,255,0.18);
            background: var(--prism-surface-strong);
        }
        .pt-alebad-howto-step:hover::before { opacity: 1; }
        .pt-alebad-howto-step > * { position: relative; z-index: 1; }

        .pt-alebad-howto-num {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.22em;
            color: #fbbf24;
            opacity: 0.85;
        }
        .pt-alebad-howto-emoji {
            font-size: 28px;
            line-height: 1;
        }
        .pt-alebad-howto-title-lbl {
            font-size: 17px;
            font-weight: 700;
            color: var(--prism-text);
            letter-spacing: -0.01em;
        }
        .pt-alebad-howto-desc {
            font-size: 14px;
            line-height: 1.65;
            color: var(--prism-text-3);
        }

        /* Reduced-motion guard for all PR 4 scenes */
        @media (prefers-reduced-motion: reduce) {
            .pt-alebad-hero-img { transform: none !important; transition: none !important; }
            .pt-alebad-trailer-pulse { animation: none !important; opacity: 0 !important; }
            .pt-alebad-trailer-card:hover .pt-alebad-trailer-frame,
            .pt-alebad-trailer-card:hover .pt-alebad-trailer-thumb,
            .pt-alebad-trailer-card:hover .pt-alebad-trailer-play {
                transform: translate(-50%, -50%) !important;
            }
            .pt-alebad-trailer-card:hover .pt-alebad-trailer-frame { transform: none !important; }
            .pt-alebad-trailer-card:hover .pt-alebad-trailer-thumb { transform: none !important; }
            .pt-alebad-cast-card:hover { transform: none !important; }
            .pt-alebad-cast-card:hover .pt-alebad-cast-poster img { transform: none !important; }
            .pt-alebad-howto-step:hover { transform: none !important; }
            .pt-alebad-cta:active,
            .pt-alebad-cast-card:active { transform: none !important; }
        }

        /* =====================================================================
           Light-theme overrides for the cinematic homepage scenes.

           Design intent: "cinema lobby" light mode. The El3abed
           presents scene (Scene 0) and the العباد hero (Scene 1) keep
           their hardcoded dark backdrops — movie-poster contexts don't
           theme-switch. Scenes 2–6 (trailer, cast, story, showtimes,
           how-to) inherit the warm cream page background, so we
           re-balance their accents/text/borders for legibility on
           light surfaces:

             - Pale gold gradients on titles become darker amber so the
               text doesn't wash out on cream.
             - Eyebrow chips lose their gold-on-gold tint and pick up
               amber text + a soft amber border on white-ish bg.
             - Hardcoded `#fbbf24` accents (cast role, story divider,
               quote mark, dividers) shift to deeper amber `#b45309`.
             - Borders/backgrounds keyed on `rgba(255,255,255,X)` (which
               are invisible on cream) become tinted slate so the chip,
               card, and KPI frames actually show up.
             - A soft bottom bleed is added to the hero so the cut from
               dark hero → cream trailer reads as a film cross-fade.
           ===================================================================== */

        /* Section titles use a pale `#fef3c7 → #fbbf24 → #d97706` gradient
           that is unreadable on a cream page. Switch to a deeper amber
           ramp for light mode. */
        :root[data-pt-theme="light"] .pt-alebad-section-title-grad {
            background: linear-gradient(135deg, #b45309 0%, #92400e 50%, #78350f 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
        }

        /* Eyebrow chip — gold on gold disappears on cream. Use amber
           text + amber border + slightly tinted bg so the chip reads
           cleanly on cream. NOTE: this targets eyebrow chips in
           scenes 2–6 (cream bg). The hero (Scene 1) keeps its dark
           backdrop in light mode, so its eyebrow gets re-pinned to
           gold-on-dark further down. */
        :root[data-pt-theme="light"] .pt-alebad-eyebrow {
            color: #92400e;
            border-color: rgba(180,83,9,0.34);
            background: rgba(180,83,9,0.08);
        }

        /* Scene 1 — العباد hero.

           The hero backdrop is hardcoded dark in both themes (cool-black
           veil over the priest poster) because it's a cinematic image
           context. But the body-text classes inside the hero
           (`-credit`, `-tagline`) are keyed to `var(--prism-text-*)`,
           so in light mode they flip to dark slate and become invisible
           against the still-dark hero. Pin them to warm cream tones
           so the typography stays legible without breaking the
           cinematic atmosphere.

           Note: `.pt-alebad-hero-sub` is no longer in the DOM — the big
           gold title + small subtitle were both replaced by the official
           El3abed calligraphic logo artwork, so its light-mode rule is
           removed. */
        :root[data-pt-theme="light"] .pt-alebad-hero-credit {
            color: #cdb89a;
        }
        :root[data-pt-theme="light"] .pt-alebad-hero-credit-label {
            color: #cdb89a;
            opacity: 0.85;
        }
        :root[data-pt-theme="light"] .pt-alebad-hero-credit-name {
            color: #f5e8c8;
        }
        :root[data-pt-theme="light"] .pt-alebad-hero-tagline {
            color: #e6dcc4;
        }

        /* The eyebrow chip override above (for cream-bg scenes) would
           leave the hero's chip as dark-amber-on-amber over the dark
           hero — invisible. Restore gold-on-dark for just this
           instance. */
        :root[data-pt-theme="light"] .pt-alebad-hero .pt-alebad-eyebrow {
            color: #fbbf24;
            border-color: rgba(251,191,36,0.32);
            background: rgba(251,191,36,0.10);
        }

        /* Localized contrast backdrop behind the hero text content —
           a soft, blurred dark ellipse that strengthens text/background
           separation without flattening the hero into a single black
           rectangle. Sits at z-index -1 inside the content's stacking
           context, so it lives behind the typography but above the
           hero's veil/orbs/image. */
        :root[data-pt-theme="light"] .pt-alebad-hero-content::before {
            content: "";
            position: absolute;
            inset: -12% -8%;
            background: radial-gradient(
                ellipse 75% 65% at 35% 55%,
                rgba(5,6,13,0.55) 0%,
                rgba(5,6,13,0.28) 55%,
                rgba(5,6,13,0)    100%);
            z-index: -1;
            pointer-events: none;
            filter: blur(6px);
        }

        /* Hero-bottom cross-fade so the cut from the cool-black hero
           into the cream trailer scene reads as a film cross-fade
           instead of a hard edge. Kept slim (12vh) so it only fades
           the actual transition strip at the bottom — earlier versions
           used 22vh and painted on top of the CTA + tagline content. */
        :root[data-pt-theme="light"] .pt-alebad-hero::after {
            content: "";
            position: absolute;
            inset: auto 0 0 0;
            height: 12vh;
            background: linear-gradient(to bottom,
                rgba(5,6,13,0)        0%,
                rgba(45,36,22,0.32)   35%,
                rgba(176,148,98,0.60) 75%,
                #f4f1ea               100%);
            z-index: 4;
            pointer-events: none;
        }

        /* Scene 2 — Trailer.
           Section title + sub already cascade off `var(--prism-text*)`
           and adapt automatically. The fallback "افتح في فيسبوك" link
           is built on `rgba(255,255,255,*)` borders/backgrounds that
           are invisible on cream — switch to slate. */
        :root[data-pt-theme="light"] .pt-alebad-trailer-fallback {
            color: var(--prism-text-2);
            border-color: rgba(15,23,42,0.18);
            background: rgba(15,23,42,0.04);
        }
        :root[data-pt-theme="light"] .pt-alebad-trailer-fallback:hover,
        :root[data-pt-theme="light"] .pt-alebad-trailer-fallback:focus-visible {
            color: #b45309;
            border-color: rgba(180,83,9,0.42);
            background: rgba(180,83,9,0.08);
        }
        :root[data-pt-theme="light"] .pt-alebad-trailer-card.is-stalled .pt-alebad-trailer-fallback {
            color: #b45309;
            border-color: rgba(180,83,9,0.42);
            background: rgba(180,83,9,0.10);
        }

        /* Scene 3 — Cast.
           Cards keep their dark posters; they sit on the cream page
           with a slate border + softer shadow so they read as
           framed-on-the-page rather than floating. Role label gold
           shifts to a deeper amber so it's legible on the white-ish
           caption strip. */
        :root[data-pt-theme="light"] .pt-alebad-cast-card {
            border-color: rgba(15,23,42,0.10);
            box-shadow:
                0 18px 38px -16px rgba(15,23,42,0.32),
                0 4px 10px -4px rgba(15,23,42,0.14);
        }
        :root[data-pt-theme="light"] .pt-alebad-cast-card:hover {
            border-color: rgba(180,83,9,0.34);
            box-shadow:
                0 30px 60px -20px rgba(15,23,42,0.40),
                0 0 50px rgba(180,83,9,0.16);
        }
        @media (hover: none) {
            :root[data-pt-theme="light"] .pt-alebad-cast-card:hover {
                border-color: rgba(15,23,42,0.10);
                box-shadow:
                    0 18px 38px -16px rgba(15,23,42,0.32),
                    0 4px 10px -4px rgba(15,23,42,0.14);
            }
        }
        :root[data-pt-theme="light"] .pt-alebad-cast-role {
            color: #b45309;
            opacity: 1;
        }
        :root[data-pt-theme="light"] .pt-alebad-cast-rail-hint {
            color: var(--prism-text-3);
        }

        /* Scene 4 — Story.
           Divider bar + mark hardcode `#fbbf24` which is too light on
           cream; the credits' top rule is `rgba(255,255,255,0.06)`
           which is invisible against cream. Light-mode overrides for
           the now-removed .pt-alebad-story-quote-mark were pruned in
           PR 16 (the quote element no longer exists in the markup). */
        :root[data-pt-theme="light"] .pt-alebad-story-divider-bar {
            background: linear-gradient(to right,
                transparent 0%,
                rgba(180,83,9,0.34) 50%,
                transparent 100%);
        }
        :root[data-pt-theme="light"] .pt-alebad-story-divider-mark {
            color: #b45309;
            opacity: 0.78;
        }
        :root[data-pt-theme="light"] .pt-alebad-story-credits {
            border-top-color: rgba(15,23,42,0.12);
        }

        /* Expandable Making-Of panel — same hardcoded gold/cream tints as
           the rest of the Story scene won't read on the cream page bg, so
           re-tone everything to the amber/slate palette used by PR #9's
           light-mode pass. */
        :root[data-pt-theme="light"] .pt-alebad-story-more-teaser {
            color: var(--prism-text-3);
        }
        :root[data-pt-theme="light"] .pt-alebad-story-more-teaser-more {
            color: #92400e;
            border-color: rgba(180,83,9,0.34);
            background: rgba(180,83,9,0.06);
        }
        :root[data-pt-theme="light"] .pt-alebad-story-more-toggle {
            color: #92400e;
            border-color: rgba(180,83,9,0.42);
        }
        :root[data-pt-theme="light"] .pt-alebad-story-more-toggle:hover,
        :root[data-pt-theme="light"] .pt-alebad-story-more-toggle:focus-visible {
            background: rgba(180,83,9,0.08);
            border-color: rgba(180,83,9,0.65);
        }
        :root[data-pt-theme="light"] .pt-alebad-story-more-toggle:focus-visible {
            box-shadow: 0 0 0 3px rgba(180,83,9,0.16);
        }
        :root[data-pt-theme="light"] .pt-alebad-story-group-title {
            color: #92400e;
            opacity: 0.92;
        }
        :root[data-pt-theme="light"] .pt-alebad-story-group-title::before,
        :root[data-pt-theme="light"] .pt-alebad-story-group-title::after {
            background: linear-gradient(to right,
                transparent 0%, rgba(180,83,9,0.34) 50%, transparent 100%);
        }
        :root[data-pt-theme="light"] .pt-alebad-story-group-lead {
            background: linear-gradient(135deg, #b45309 0%, #92400e 60%, #78350f 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        :root[data-pt-theme="light"] .pt-alebad-story-group-crew-row {
            border-top-color: rgba(15,23,42,0.10);
        }
        @media (max-width: 599px) {
            :root[data-pt-theme="light"] .pt-alebad-story-group-crew-row:nth-of-type(2) {
                border-top-color: rgba(15,23,42,0.10);
            }
        }
        :root[data-pt-theme="light"] .pt-alebad-story-group--final .pt-alebad-story-group-stamp > span:not([aria-hidden]) {
            background: linear-gradient(135deg, #b45309 0%, #92400e 60%, #78350f 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Scene 5 — Showtimes.
           The shared `.pt-cine-shows-stats` KPI strip uses faint
           white-on-white tints and a pale cyan→indigo gradient on
           the numbers — both unreadable on cream. Switch backgrounds
           to slate alpha and the number gradient to a deeper indigo
           ramp for better contrast. */
        :root[data-pt-theme="light"] .pt-alebad-shows .pt-cine-shows-stat {
            background: rgba(15,23,42,0.04);
            border-color: rgba(15,23,42,0.12);
        }
        :root[data-pt-theme="light"] .pt-alebad-shows .pt-cine-shows-stat-num {
            background: linear-gradient(120deg, #0e7490 0%, #4338ca 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        :root[data-pt-theme="light"] .pt-alebad-shows .pt-cine-shows-stat-label {
            color: var(--prism-text-2);
        }

        /* =====================================================================
           WAVE 3 — Cinematic premium polish (show detail, showtimes,
           sticky CTA, transitions, mobile Safari)

           Pure CSS, GPU-friendly, prefers-reduced-motion guarded.
           Customer-flow only — no app-shell or drawer-heavy patterns.
           ===================================================================== */

        /* ---- W3#1: Cinematic show-detail hero ---- */
        .pt-show-hero {
            position: relative;
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid var(--prism-border);
            background: linear-gradient(180deg, rgba(8,10,20,0.92), rgba(5,6,13,0.96));
            isolation: isolate;
        }
        :root[data-pt-theme="light"] .pt-show-hero {
            background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(244,241,234,0.9));
        }
        .pt-show-hero-poster {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background-size: cover;
            background-position: center;
            background-color: rgba(8,10,20,0.6);
        }
        @media (max-width: 640px) {
            .pt-show-hero-poster { aspect-ratio: 4 / 5; }
        }
        .pt-show-hero-poster img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.02);
            transition: transform 1.2s var(--prism-ease);
            will-change: transform;
        }
        .pt-show-hero:hover .pt-show-hero-poster img {
            transform: scale(1.06);
        }
        .pt-show-hero-veil {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(180deg, rgba(5,6,13,0) 0%, rgba(5,6,13,0.55) 60%, rgba(5,6,13,0.92) 100%),
                radial-gradient(circle at 75% 0%, rgba(34,211,238,0.18), transparent 55%),
                radial-gradient(circle at 15% 100%, rgba(192,132,252,0.18), transparent 55%);
        }
        :root[data-pt-theme="light"] .pt-show-hero-veil {
            background:
                linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.55) 60%, rgba(244,241,234,0.92) 100%),
                radial-gradient(circle at 75% 0%, rgba(14,165,233,0.18), transparent 55%),
                radial-gradient(circle at 15% 100%, rgba(168,85,247,0.16), transparent 55%);
        }
        .pt-show-hero-grain {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.35;
            mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='220' height='220'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 0.6 0'/></filter><rect width='100%' height='100%' filter='url(%23n)' opacity='0.7'/></svg>");
        }
        :root[data-pt-theme="light"] .pt-show-hero-grain { opacity: 0.18; }

        .pt-show-hero-content {
            position: relative;
            z-index: 2;
            padding: 22px 22px 26px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        @media (min-width: 720px) {
            .pt-show-hero-content { padding: 32px 36px 38px; gap: 14px; }
        }
        .pt-show-hero-content::before {
            content: "";
            position: absolute;
            inset: 0;
            top: -80px;
            background: linear-gradient(180deg, rgba(5,6,13,0) 0%, rgba(5,6,13,0.7) 35%, rgba(5,6,13,0.95) 100%);
            pointer-events: none;
            z-index: -1;
        }
        :root[data-pt-theme="light"] .pt-show-hero-content::before {
            background: linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(244,241,234,0.7) 35%, rgba(244,241,234,0.95) 100%);
        }
        .pt-show-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-start;
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            padding: 5px 11px;
            border-radius: 999px;
            background: rgba(34,211,238,0.10);
            color: var(--prism-cyan);
            border: 1px solid rgba(34,211,238,0.35);
        }
        :root[data-pt-theme="light"] .pt-show-hero-eyebrow {
            background: rgba(14,165,233,0.10);
            color: #0369a1;
            border-color: rgba(14,165,233,0.35);
        }
        .pt-show-hero-eyebrow::before {
            content: "";
            width: 6px; height: 6px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 10px currentColor;
            animation: ptShowEyebrowPulse 2.4s ease-in-out infinite;
        }
        @keyframes ptShowEyebrowPulse {
            0%, 100% { opacity: 0.6; transform: scale(0.9); }
            50%      { opacity: 1;   transform: scale(1.15); }
        }
        .pt-show-hero-title {
            font-family: "Space Grotesk", "Cairo", system-ui, sans-serif;
            font-weight: 800;
            font-size: clamp(26px, 5.5vw, 44px);
            line-height: 1.1;
            letter-spacing: -0.01em;
            background: linear-gradient(180deg, var(--prism-text) 0%, color-mix(in oklab, var(--prism-text) 78%, var(--prism-cyan) 22%) 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
            margin: 0;
        }
        :root[data-pt-theme="light"] .pt-show-hero-title {
            background: linear-gradient(180deg, #0b1020 0%, #312e81 100%);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
        }
        .pt-show-hero-desc {
            color: var(--prism-text-2);
            font-size: 14px;
            line-height: 1.7;
            white-space: pre-line;
            max-width: 62ch;
        }
        @media (min-width: 720px) {
            .pt-show-hero-desc { font-size: 15px; }
        }
        .pt-show-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 4px;
        }

        /* ---- W3#2: Premium showtime cards ---- */
        .pt-time-section-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .pt-time-section-eyebrow {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--prism-text-3);
        }
        .pt-time-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        @media (min-width: 720px) {
            .pt-time-grid { grid-template-columns: 1fr 1fr; gap: 16px; }
        }
        .pt-time-card {
            position: relative;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 16px 18px 16px 22px;
            border-radius: 22px;
            border: 1px solid var(--prism-border);
            background: linear-gradient(180deg, rgba(20,24,38,0.60), rgba(8,10,20,0.78));
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            overflow: hidden;
            isolation: isolate;
            transition: transform .35s var(--prism-ease),
                        border-color .35s var(--prism-ease),
                        box-shadow .45s var(--prism-ease);
        }
        :root[data-pt-theme="light"] .pt-time-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(255,255,255,0.72));
            border-color: rgba(15,23,42,0.14);
            box-shadow:
                0 20px 40px -22px rgba(15,23,42,0.22),
                0 4px 8px -4px rgba(15,23,42,0.10);
        }
        @media (max-width: 640px) {
            .pt-time-card { grid-template-columns: auto 1fr; row-gap: 14px; padding: 14px 16px 14px 20px; }
            .pt-time-card .pt-time-cta-cell { grid-column: 1 / -1; }
        }
        .pt-time-card::before {
            /* leading-edge status rail */
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 0; bottom: 0;
            width: 4px;
            background: var(--pt-time-rail, var(--prism-cyan));
            box-shadow: 0 0 14px var(--pt-time-rail, var(--prism-cyan));
            border-end-start-radius: 22px;
            border-start-start-radius: 22px;
        }
        .pt-time-card[data-status="few"]   { --pt-time-rail: #fbbf24; }
        .pt-time-card[data-status="sold"]  { --pt-time-rail: #fb7185; }
        .pt-time-card[data-status="open"]  { --pt-time-rail: #34d399; }

        @media (hover: hover) {
            .pt-time-card:not([data-status="sold"]):hover {
                transform: translateY(-3px);
                border-color: rgba(129,140,248,0.42);
                box-shadow: 0 18px 60px -22px rgba(34,211,238,0.30),
                            0 8px 24px -12px rgba(129,140,248,0.30);
            }
        }
        .pt-time-card[data-status="sold"] {
            opacity: 0.65;
            filter: grayscale(0.35);
        }

        .pt-time-day {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            padding: 10px 6px;
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(34,211,238,0.10), rgba(129,140,248,0.10));
            border: 1px solid rgba(129,140,248,0.30);
        }
        :root[data-pt-theme="light"] .pt-time-day {
            background: linear-gradient(180deg, rgba(14,165,233,0.10), rgba(99,102,241,0.10));
            border-color: rgba(99,102,241,0.30);
        }
        .pt-time-day-num {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 800;
            font-size: 26px;
            line-height: 1;
            color: var(--prism-text);
            letter-spacing: -0.02em;
        }
        .pt-time-day-mon {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--prism-cyan);
            margin-top: 2px;
        }
        :root[data-pt-theme="light"] .pt-time-day-mon { color: #0369a1; }
        .pt-time-day-dow {
            font-size: 10px;
            color: var(--prism-text-3);
            margin-top: 4px;
            letter-spacing: 0.06em;
        }

        .pt-time-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }
        .pt-time-time {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: var(--prism-text);
            letter-spacing: 0.01em;
        }
        .pt-time-time-meridian {
            font-size: 11px;
            color: var(--prism-text-3);
            margin-inline-start: 4px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .pt-time-eta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: var(--prism-text-3);
            padding: 3px 9px;
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--prism-border);
            align-self: flex-start;
        }
        :root[data-pt-theme="light"] .pt-time-eta {
            background: rgba(0,0,0,0.04);
        }
        .pt-time-eta::before {
            content: "";
            width: 5px; height: 5px;
            border-radius: 50%;
            background: var(--prism-cyan);
            box-shadow: 0 0 8px currentColor;
        }
        .pt-time-price-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: baseline;
            font-size: 12.5px;
            color: var(--prism-text-3);
        }
        .pt-time-price-row .pt-time-price-from {
            font-size: 11px;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            color: var(--prism-text-3);
        }
        .pt-time-price-row .pt-time-price-amount {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 800;
            color: var(--prism-gold);
            font-size: 16px;
        }
        .pt-time-price-row .pt-time-price-currency {
            font-size: 10.5px;
            color: var(--prism-text-3);
            letter-spacing: 0.10em;
            text-transform: uppercase;
            margin-inline-start: -2px;
        }
        .pt-time-price-row .pt-time-price-sections {
            color: var(--prism-gold);
            font-weight: 600;
        }
        .pt-time-cta-cell {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        @media (max-width: 640px) {
            .pt-time-cta-cell { justify-content: stretch; }
            .pt-time-cta-cell > * { width: 100%; justify-content: center; }
        }

        /* Premium status badges (W3#4) */
        .pt-time-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid currentColor;
            background: color-mix(in oklab, currentColor 10%, transparent);
        }
        .pt-time-status[data-status="open"]  { color: #34d399; }
        .pt-time-status[data-status="few"]   { color: #fbbf24; }
        .pt-time-status[data-status="sold"]  { color: #fb7185; }
        .pt-time-status[data-status="few"]::before,
        .pt-time-status[data-status="open"]::before {
            content: "";
            width: 6px; height: 6px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 8px currentColor;
        }
        .pt-time-status[data-status="few"]::before { animation: ptTimeFewPulse 1.6s ease-in-out infinite; }
        @keyframes ptTimeFewPulse {
            0%, 100% { opacity: 0.5; transform: scale(0.85); }
            50%      { opacity: 1;   transform: scale(1.20); }
        }

        /* ---- W3#3: Sticky cinematic price/CTA bar (mobile, show detail) ---- */
        .pt-show-stickybar {
            position: fixed;
            inset-inline: 12px;
            bottom: max(12px, env(safe-area-inset-bottom));
            z-index: 38;
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px 10px 16px;
            border-radius: 18px;
            border: 1px solid rgba(212,175,55,0.40);
            background: linear-gradient(180deg, rgba(20,24,38,0.92), rgba(8,10,20,0.96));
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 18px 60px -22px rgba(212,175,55,0.40),
                        0 8px 24px -12px rgba(0,0,0,0.50);
            opacity: 0;
            transform: translateY(20px);
            transition: opacity .3s var(--prism-ease), transform .35s var(--prism-ease);
            pointer-events: none;
        }
        :root[data-pt-theme="light"] .pt-show-stickybar {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(244,241,234,0.96));
        }
        .pt-show-stickybar.is-shown {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        @media (max-width: 880px) {
            .pt-show-stickybar { display: flex; }
        }
        .pt-show-stickybar-info {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }
        .pt-show-stickybar-from {
            font-size: 10.5px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--prism-text-3);
        }
        .pt-show-stickybar-amount {
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-weight: 800;
            font-size: 16px;
            color: var(--prism-gold);
            line-height: 1;
        }
        .pt-show-stickybar-amount-currency {
            font-size: 11px;
            color: var(--prism-text-3);
            letter-spacing: 0.10em;
            text-transform: uppercase;
            margin-inline-start: 4px;
        }

        /* ---- W3#5: Premium page entry transition (lightweight) ---- */
        .pt-w3-pageenter {
            animation: ptW3PageEnter .55s var(--prism-ease) both;
        }
        @keyframes ptW3PageEnter {
            from { opacity: 0; transform: translateY(14px) scale(0.992); filter: blur(2px); }
            to   { opacity: 1; transform: translateY(0)    scale(1);     filter: blur(0); }
        }

        /* ---- W3#6: Image polish (skeleton, no CLS) ---- */
        .pt-img-frame {
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(110deg, rgba(255,255,255,0.04) 8%, rgba(255,255,255,0.10) 18%, rgba(255,255,255,0.04) 33%);
            background-size: 200% 100%;
            animation: ptImgShimmer 1.6s linear infinite;
        }
        :root[data-pt-theme="light"] .pt-img-frame {
            background:
                linear-gradient(110deg, rgba(0,0,0,0.04) 8%, rgba(0,0,0,0.08) 18%, rgba(0,0,0,0.04) 33%);
            background-size: 200% 100%;
        }
        .pt-img-frame.is-loaded {
            background: transparent;
            animation: none;
        }
        @keyframes ptImgShimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .pt-img-frame > img {
            opacity: 0;
            transition: opacity .45s var(--prism-ease);
        }
        .pt-img-frame.is-loaded > img {
            opacity: 1;
        }
        .pt-img-frame > img,
        .pt-show-hero-poster img {
            -webkit-touch-callout: none;
        }

        /* ---- W3#7: Booking-form premium polish (step meter, focus rings) ---- */
        .pt-step-meter {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            font-family: "Space Grotesk", system-ui, sans-serif;
        }
        .pt-step-meter-step {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--prism-text-3);
        }
        .pt-step-meter-step .pt-step-meter-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px; height: 22px;
            border-radius: 999px;
            border: 1px solid var(--prism-border);
            background: rgba(255,255,255,0.04);
            font-size: 11px;
        }
        .pt-step-meter-step.is-active .pt-step-meter-num {
            background: rgba(34,211,238,0.18);
            border-color: rgba(34,211,238,0.55);
            color: var(--prism-cyan);
            box-shadow: 0 0 12px rgba(34,211,238,0.35);
        }
        .pt-step-meter-step.is-active { color: var(--prism-text); }
        .pt-step-meter-step.is-done .pt-step-meter-num {
            background: rgba(52,211,153,0.16);
            border-color: rgba(52,211,153,0.50);
            color: #34d399;
        }
        .pt-step-meter-divider {
            flex: 0 0 24px;
            height: 1px;
            background: var(--prism-border);
        }

        /* Premium :focus-visible rings on customer-flow inputs/buttons */
        .pt-page input:focus-visible,
        .pt-page select:focus-visible,
        .pt-page textarea:focus-visible {
            outline: none;
            border-color: rgba(212,175,55,0.55);
            box-shadow: 0 0 0 3px rgba(212,175,55,0.18),
                        0 0 18px rgba(212,175,55,0.18);
        }
        .pt-page a:focus-visible,
        .pt-page button:focus-visible {
            outline: 2px solid rgba(34,211,238,0.55);
            outline-offset: 2px;
            border-radius: 12px;
        }
        /* Suppress the focus ring on touch (no keyboard) — iOS Safari quirk */
        @media (hover: none) {
            .pt-page a:focus:not(:focus-visible),
            .pt-page button:focus:not(:focus-visible) { outline: none; }
        }

        /* ---- W3#8: Thank-you cinematic close ---- */
        .pt-thx-hero {
            position: relative;
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid var(--prism-border);
            background: linear-gradient(180deg, rgba(20,24,38,0.85), rgba(5,6,13,0.95));
            padding: 28px 22px 30px;
            text-align: center;
            isolation: isolate;
        }
        :root[data-pt-theme="light"] .pt-thx-hero {
            background: linear-gradient(180deg, rgba(255,255,255,0.90), rgba(244,241,234,0.96));
        }
        .pt-thx-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 50% 0%, rgba(212,175,55,0.18), transparent 55%),
                radial-gradient(circle at 100% 100%, rgba(34,211,238,0.16), transparent 55%);
            pointer-events: none;
            z-index: -1;
        }
        .pt-thx-particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .pt-thx-particles span {
            position: absolute;
            width: 4px; height: 4px;
            border-radius: 50%;
            background: rgba(212,175,55,0.55);
            box-shadow: 0 0 12px currentColor;
            opacity: 0;
            animation: ptThxParticle 9s linear infinite;
        }
        .pt-thx-particles span:nth-child(1) { left: 12%; animation-delay: -0.4s; background: rgba(212,175,55,0.7); }
        .pt-thx-particles span:nth-child(2) { left: 28%; animation-delay: -2.1s; background: rgba(34,211,238,0.6); }
        .pt-thx-particles span:nth-child(3) { left: 46%; animation-delay: -3.8s; background: rgba(192,132,252,0.6); }
        .pt-thx-particles span:nth-child(4) { left: 64%; animation-delay: -5.6s; background: rgba(212,175,55,0.6); }
        .pt-thx-particles span:nth-child(5) { left: 82%; animation-delay: -7.3s; background: rgba(129,140,248,0.6); }
        @keyframes ptThxParticle {
            0%   { transform: translateY(110%) scale(0.5); opacity: 0; }
            10%  { opacity: 0.85; }
            85%  { opacity: 0.5; }
            100% { transform: translateY(-30%) scale(1); opacity: 0; }
        }
        .pt-thx-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: "Space Grotesk", system-ui, sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--prism-gold);
            position: relative;
            z-index: 1;
        }
        .pt-thx-ref {
            position: relative;
            z-index: 1;
            font-family: "Space Grotesk", monospace;
            font-weight: 800;
            font-size: clamp(22px, 5vw, 32px);
            letter-spacing: 0.10em;
            color: var(--prism-text);
            margin-top: 10px;
            display: inline-block;
            padding-bottom: 6px;
            background-image: linear-gradient(90deg, transparent, rgba(212,175,55,0.85), transparent);
            background-repeat: no-repeat;
            background-size: 60% 2px;
            background-position: 50% 100%;
        }

        /* ---- W3#9: Seat picker entry polish (no flash before canvas) ---- */
        .pt-seatpick-frame {
            animation: ptW3PageEnter .55s var(--prism-ease) both;
            animation-delay: .04s;
        }

        /* ---- W3#10: Mobile Safari polish — safe-area + smooth-scroll ---- */
        html {
            scroll-behavior: smooth;
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
        }
        .pt-page {
            padding-inline: max(16px, env(safe-area-inset-left)) max(16px, env(safe-area-inset-right));
        }
        @media (max-width: 880px) {
            /* Add bottom padding only when the show-detail sticky bar is
               rendered, to avoid the page CTA being covered by the bar.
               The .pt-route-show body class is set by shows/show.blade.php
               through the body_class section yielded into <body> above. */
            body.pt-route-show .pt-page { padding-bottom: 96px; }
        }

        /* Reduced-motion master guard for everything Wave 3 added */
        @media (prefers-reduced-motion: reduce) {
            .pt-show-hero-poster img { transform: none !important; transition: none !important; }
            .pt-show-hero-eyebrow::before,
            .pt-time-status[data-status="few"]::before,
            .pt-thx-particles span,
            .pt-img-frame { animation: none !important; }
            .pt-w3-pageenter,
            .pt-seatpick-frame { animation: none !important; }
            .pt-show-stickybar { transition: none !important; }
        }
    </style>

    {{-- Per-page styles. Views can push <style> blocks here via
         @push('styles') ... @endpush without having to inline them
         inside @section('content'). --}}
    @stack('styles')
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
        @include('partials._i18n_dictionary')
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
            // Only fire pt:langchange and rewrite document attributes if
            // the language actually changed. Callers used to invoke
            // applyLang(currentLang) on resize/load purely to re-position
            // the language-toggle thumb, but that re-dispatched
            // `pt:langchange`, which on Android Chrome (where every
            // keyboard open/close fires a `resize`) destroyed booking
            // form inputs mid-typing via their innerHTML='' rebuild and
            // collapsed the on-screen keyboard. Guard the heavy work
            // here so passive same-lang calls become cheap no-ops; the
            // dedicated repositionLangThumbs() handler below covers the
            // legitimate "viewport changed" use case.
            const prevLang = document.documentElement.getAttribute('data-pt-lang') || '';
            const langChanged = prevLang !== lang;
            document.documentElement.setAttribute('data-pt-lang', lang);
            document.documentElement.lang = lang;
            document.documentElement.dir  = (lang === 'en') ? 'ltr' : 'rtl';
            if (!langChanged) {
                // Already in this language — skip the heavy DOM rewrite
                // and the pt:langchange dispatch. Keep the thumb position
                // fresh because the resize that triggered us may have
                // changed the toggle's measured width.
                document.querySelectorAll('.pt-lang-toggle').forEach(group => moveThumbForGroup(group, lang));
                window.PT_LANG = lang;
                return;
            }
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
            // Page title — pages can declare a meta tag like
            //   <meta name="pt-title-i18n" content="key" data-suffix="...">
            // and document.title is rebuilt here in the active language.
            // The dynamic `data-suffix` (e.g. a show title) is appended
            // with " · " when present. Missing keys leave the existing
            // @section('title') string untouched.
            const titleMeta = document.querySelector('meta[name="pt-title-i18n"]');
            if (titleMeta) {
                const tk = titleMeta.getAttribute('content');
                const suffix = titleMeta.getAttribute('data-suffix') || '';
                if (tk && dict[tk] !== undefined) {
                    const base = interp(dict[tk], readVars(titleMeta));
                    document.title = suffix ? base + ' · ' + suffix : base;
                }
            }
            try { localStorage.setItem('pt-lang', lang); } catch(_){}
            window.PT_LANG = lang;
            document.dispatchEvent(new CustomEvent('pt:langchange', { detail: { lang } }));
        }
        window.PT_APPLY_LANG = applyLang;
        langButtons.forEach(b => b.addEventListener('click', () => applyLang(b.getAttribute('data-pt-lang'))));
        let initLang = 'ar';
        try { initLang = localStorage.getItem('pt-lang') || 'ar'; } catch(_){}
        applyLang(initLang);
        // Re-position the language-toggle thumb after fonts load + on
        // resize. This previously called applyLang(currentLang), but
        // Android Chrome's on-screen keyboard fires `resize` every
        // time it opens / closes, and applyLang used to dispatch
        // pt:langchange + walk every data-i18n element on each call.
        // Booking form listeners on pt:langchange rebuild their
        // attendee inputs via innerHTML='', which destroyed the
        // input the user was actively typing into and collapsed the
        // keyboard. The cheap thumb-reposition below is the only
        // thing we ever needed on resize. Wrapped in rAF so a burst
        // of resize events (Gboard appearance animation) collapses
        // to a single layout read per frame.
        
        function repositionLangThumbs() {
            const cur = document.documentElement.getAttribute('data-pt-lang') || 'ar';
            document.querySelectorAll('.pt-lang-toggle').forEach(group => moveThumbForGroup(group, cur));
        }
        window.addEventListener('load', repositionLangThumbs);
        let __ptResizeRaf = 0;
        window.addEventListener('resize', () => {
            if (__ptResizeRaf) cancelAnimationFrame(__ptResizeRaf);
            __ptResizeRaf = requestAnimationFrame(repositionLangThumbs);
        });

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
        // Platform default is dark — the OS prefers-color-scheme hint
        // is intentionally NOT wired up to applyTheme(). First-time
        // visitors always land on dark; if they want light they pick
        // it via the toggle and the choice persists in localStorage.

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

        // ---------- Trailer click-to-load embed ----------
        // First paint shows the cinematic poster-frame. Tap (or Enter/Space)
        // mounts an iframe pointing at the Facebook video plugin so the
        // trailer plays INLINE — never opens externally on the primary
        // path. The fallback link below the frame stays as the lifeline
        // if the embed silently fails. We add a 6s "loading watchdog":
        // if the iframe never fires `load`, we surface the fallback as
        // a more prominent visual hint (the loading caption itself
        // becomes the call-to-action).
        (function setupTrailerClickToLoad() {
            const cards = document.querySelectorAll('[data-pt-trailer-card]');
            if (!cards.length) return;

            cards.forEach((card) => {
                const frame    = card.querySelector('[data-pt-trailer-frame]');
                const embedUrl = card.getAttribute('data-pt-trailer-embed');
                if (!frame || !embedUrl) return;

                const play = () => {
                    if (card.dataset.loaded === '1') return;
                    card.dataset.loaded = '1';
                    card.classList.add('is-loading');

                    const iframe = document.createElement('iframe');
                    iframe.src   = embedUrl;
                    iframe.title = 'برومو مسرحية العباد';
                    iframe.setAttribute('allow', 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share');
                    iframe.setAttribute('allowfullscreen', 'true');
                    iframe.setAttribute('frameborder', '0');
                    iframe.setAttribute('scrolling', 'no');
                    iframe.setAttribute('loading', 'eager');

                    iframe.addEventListener('load', () => {
                        card.classList.remove('is-loading');
                        card.classList.add('is-playing');
                    }, { once: true });

                    // Watchdog: if the FB plugin never fires `load` (ad
                    // blocker, region block, private post, etc.) we let
                    // the loading veil persist but visually surface the
                    // fallback link beneath so the user is never
                    // stranded. 6s is conservative — FB's plugin
                    // normally fires `load` within 1-2s on broadband.
                    setTimeout(() => {
                        if (!card.classList.contains('is-playing')) {
                            card.classList.remove('is-loading');
                            card.classList.add('is-stalled');
                        }
                    }, 6000);

                    frame.appendChild(iframe);
                };

                frame.addEventListener('click', play);
                frame.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        play();
                    }
                });
            });
        })();

        // ---------- Cast rail v4 desktop interactions ----------
        // Touch (mobile/iPad) is fully handled by native momentum +
        // proximity snap from the CSS — this IIFE only wires the
        // DESKTOP-specific affordances:
        //   1. Arrow buttons → smooth scrollBy one card-width.
        //      Auto-disable when at the rail's start or end.
        //   2. Mouse drag-to-scroll (Netflix-rail style).
        //      Filtered to e.pointerType === 'mouse' so touch is
        //      untouched. Disables snap during the active drag via
        //      `.is-grabbing`; snap reactivates on pointerup so the
        //      rail still settles softly to the nearest card.
        //   3. Vertical wheel → horizontal scroll. Polite version:
        //      passes through to the page when the rail is at its
        //      start or end, so vertical page-scroll still works
        //      when the user has parked the rail at an edge.
        //   4. Active-card emphasis: IntersectionObserver tags the
        //      most-visible card with `.is-centered` for a subtle
        //      scale + glow boost (CSS does the styling).
        (function setupCastRailInteractions() {
            const wrap = document.querySelector('[data-pt-cast-rail-wrap]');
            if (!wrap) return;
            const rail = wrap.querySelector('[data-pt-cast-rail]');
            if (!rail) return;

            const prevBtn = wrap.querySelector('[data-pt-cast-arrow="prev"]');
            const nextBtn = wrap.querySelector('[data-pt-cast-arrow="next"]');

            // One scroll step = card width + gap. Read live so it
            // adapts to breakpoint changes without a reflow listener.
            const cardStep = () => {
                const card = rail.querySelector('.pt-alebad-cast-card');
                if (!card) return 280;
                const styles = window.getComputedStyle(rail);
                const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
                return card.getBoundingClientRect().width + gap;
            };

            const maxScroll = () => Math.max(0, rail.scrollWidth - rail.clientWidth);
            // `Math.abs` handles Firefox-RTL's negative scrollLeft so the
            // start/end detection works in both directions.
            const pos = () => Math.abs(rail.scrollLeft);

            const updateArrows = () => {
                const max = maxScroll();
                const p = pos();
                // If the rail doesn't actually overflow (e.g. very wide
                // viewport, few cards), hide both arrows — having them
                // visible but inert reads as broken UI.
                if (max < 4) {
                    if (prevBtn) prevBtn.classList.add('is-disabled');
                    if (nextBtn) nextBtn.classList.add('is-disabled');
                    return;
                }
                if (prevBtn) prevBtn.classList.toggle('is-disabled', p < 4);
                if (nextBtn) nextBtn.classList.toggle('is-disabled', p > max - 4);
            };

            // Arrow click → scroll to the NEXT card boundary, not just
            // `scrollBy(cardStep)`. With `scroll-snap-type: proximity`
            // active on the rail, a blind scrollBy can land between
            // snap points; the proximity snap then nudges the rail
            // back to the previous card after the smooth-scroll
            // settles, producing a visible jitter. Aligning explicitly
            // to a card boundary means the snap engine has nothing to
            // fight, so each arrow click resolves cleanly.
            const scrollDir = (dir) => {
                const step = cardStep();
                if (step <= 0) return;
                const max = maxScroll();
                const current = rail.scrollLeft;
                // Add a 4px deadband so a click on a card already
                // aligned advances to the next one, not "0 step".
                const target = dir > 0
                    ? Math.ceil((current + 4) / step) * step
                    : Math.floor((current - 4) / step) * step;
                rail.scrollTo({
                    left: Math.max(0, Math.min(max, target)),
                    behavior: 'smooth',
                });
            };
            if (prevBtn) prevBtn.addEventListener('click', () => scrollDir(-1));
            if (nextBtn) nextBtn.addEventListener('click', () => scrollDir(1));

            // Hide the "اسحب لاكتشاف باقي النجوم" hint once the user
            // has actually scrolled the rail. Pulsing forever would
            // read as nagging; one acknowledgment is enough.
            const hint = wrap.querySelector('.pt-alebad-cast-rail-hint');
            if (hint) {
                const hideHintIfMoved = () => {
                    if (pos() > 24) {
                        hint.classList.add('is-acknowledged');
                        rail.removeEventListener('scroll', hideHintIfMoved);
                    }
                };
                rail.addEventListener('scroll', hideHintIfMoved, { passive: true });
            }

            // Update on scroll (rAF-throttled) and on resize.
            let raf = null;
            const scheduleUpdate = () => {
                if (raf) return;
                raf = requestAnimationFrame(() => { raf = null; updateArrows(); });
            };
            rail.addEventListener('scroll', scheduleUpdate, { passive: true });
            window.addEventListener('resize', scheduleUpdate, { passive: true });
            // Initial paint — wait one frame so layout has settled.
            requestAnimationFrame(updateArrows);

            // Desktop-only behaviors (drag + wheel). Touch keeps the
            // native iOS Safari pipeline.
            const hasFinePointer = window.matchMedia &&
                window.matchMedia('(pointer: fine)').matches;
            if (hasFinePointer) {
                // -- Drag-to-scroll with inertia (mouse only) --
                let isDown = false;
                let startX = 0;
                let startScroll = 0;
                let hasMoved = false;
                let activePointerId = null;
                // Velocity tracker — exponential moving average over
                // the last few pointermove events, in cursor-pixels
                // per ms. On pointerup any residual velocity coasts
                // the scroll for ~400ms with cubic decay, giving the
                // rail a "throw" feel instead of stopping dead at
                // the last cursor position.
                let lastMoveX = 0;
                let lastMoveTime = 0;
                let velocity = 0;
                let inertiaRaf = null;

                const cancelInertia = () => {
                    if (inertiaRaf !== null) {
                        cancelAnimationFrame(inertiaRaf);
                        inertiaRaf = null;
                    }
                };

                rail.addEventListener('pointerdown', (e) => {
                    // Only handle mouse — touch keeps native momentum.
                    if (e.pointerType !== 'mouse') return;
                    // Ignore right/middle clicks.
                    if (e.button !== 0) return;
                    // Kill any in-flight inertia from a previous drag —
                    // a fresh grab should always feel responsive.
                    cancelInertia();
                    isDown = true;
                    hasMoved = false;
                    startX = e.clientX;
                    startScroll = rail.scrollLeft;
                    activePointerId = e.pointerId;
                    lastMoveX = e.clientX;
                    lastMoveTime = performance.now();
                    velocity = 0;
                    rail.classList.add('is-grabbing');
                    try { rail.setPointerCapture(e.pointerId); } catch (_) {}
                });

                rail.addEventListener('pointermove', (e) => {
                    if (!isDown) return;
                    const now = performance.now();
                    const dt = now - lastMoveTime;
                    const dx = e.clientX - startX;
                    if (Math.abs(dx) > 4) hasMoved = true;
                    if (dt > 0) {
                        const instantVel = (e.clientX - lastMoveX) / dt;
                        // EMA smoothing — 0.65 weight on history makes
                        // the velocity stable against single-frame jitter
                        // but still tracks acceleration.
                        velocity = velocity * 0.65 + instantVel * 0.35;
                    }
                    lastMoveX = e.clientX;
                    lastMoveTime = now;
                    // Pulling the cursor right (positive dx) means
                    // dragging the rail's content right = decreasing
                    // scrollLeft. Subtract dx to follow the cursor.
                    rail.scrollLeft = startScroll - dx;
                });

                const endDrag = (e) => {
                    if (!isDown) return;
                    isDown = false;
                    rail.classList.remove('is-grabbing');
                    if (activePointerId !== null) {
                        try { rail.releasePointerCapture(activePointerId); } catch (_) {}
                        activePointerId = null;
                    }

                    // Coast: if release velocity is non-trivial, apply
                    // cubic-decay inertia for a few frames. Threshold of
                    // 0.25 px/ms (= 250 px/s) suppresses inertia on
                    // intentional slow drags — only flick-style throws
                    // coast. ~12ms per frame at 60fps, multiplied
                    // through gives a natural feel similar to native
                    // iOS Photos.
                    if (Math.abs(velocity) > 0.25) {
                        let v = velocity * 16; // px per frame at 60fps
                        const decay = 0.93;
                        const tick = () => {
                            v *= decay;
                            rail.scrollLeft -= v;
                            if (Math.abs(v) > 0.4) {
                                inertiaRaf = requestAnimationFrame(tick);
                            } else {
                                inertiaRaf = null;
                            }
                        };
                        inertiaRaf = requestAnimationFrame(tick);
                    }
                    velocity = 0;
                    // If the user actually dragged (not just clicked),
                    // suppress the synthetic click that follows so
                    // any future click handlers on cards don't fire.
                    if (hasMoved) {
                        const suppress = (ev) => {
                            ev.stopPropagation();
                            ev.preventDefault();
                        };
                        rail.addEventListener('click', suppress, {
                            capture: true, once: true
                        });
                        // Failsafe: detach within a microtask if no
                        // click ever fires.
                        setTimeout(() => {
                            rail.removeEventListener('click', suppress, { capture: true });
                        }, 40);
                    }
                };
                rail.addEventListener('pointerup', endDrag);
                rail.addEventListener('pointercancel', endDrag);

                // -- Wheel-to-horizontal (mouse wheel users) --
                // Trackpads that already generate deltaX pass through
                // untouched. Mouse wheels (deltaY only) get converted.
                // When the rail has reached an edge in the requested
                // direction, we let the wheel bubble up so the page
                // can scroll vertically — otherwise the rail would
                // "trap" page scroll on long pages.
                rail.addEventListener('wheel', (e) => {
                    if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
                    if (e.deltaY === 0) return;
                    const max = maxScroll();
                    const cur = rail.scrollLeft;
                    const atEnd   = e.deltaY > 0 && cur >= max - 0.5;
                    const atStart = e.deltaY < 0 && cur <= 0.5;
                    if (atEnd || atStart) return;
                    e.preventDefault();
                    rail.scrollLeft = cur + e.deltaY;
                }, { passive: false });
            }

            // -- Active-card emphasis --
            // Tag whichever card is ≥85% visible inside the rail's
            // own viewport with `.is-centered`. CSS handles the
            // scale/glow boost (hover: hover only — touch would
            // flicker since you're constantly mid-snap).
            if ('IntersectionObserver' in window) {
                const cards = rail.querySelectorAll('.pt-alebad-cast-card');
                if (cards.length) {
                    const centerIO = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            entry.target.classList.toggle('is-centered',
                                entry.intersectionRatio >= 0.85);
                        });
                    }, {
                        root: rail,
                        threshold: [0.6, 0.85, 0.95],
                    });
                    cards.forEach((c) => centerIO.observe(c));
                }
            }
        })();

        // ---------- Scene 4 — Story: disclosure surfaces (bio + credits) ----------
        // Wires every gold ghost-pill toggle in the Story scene to its
        // panel via aria-controls. Animation is pure-CSS via the
        // grid-template-rows 0fr↔1fr trick — JS just flips:
        //   - aria-expanded on the button (drives label/chevron via CSS)
        //   - .is-open on the panel (drives the height + fade transition)
        // Keyboard handling is free because the trigger is a real <button>.
        // Supports two attribute styles so the new biography disclosure and
        // the legacy Making-Of credits disclosure can coexist without
        // re-flagging the existing markup:
        //   - new generic [data-pt-disclosure-toggle] / [data-pt-disclosure-panel]
        //   - legacy [data-pt-credits-toggle] / [data-pt-credits-panel]
        // No-op when there's no panel on the page (every other route).
        (function setupStoryDisclosures() {
            const toggles = document.querySelectorAll(
                '[data-pt-disclosure-toggle], [data-pt-credits-toggle]'
            );
            if (toggles.length === 0) return;

            toggles.forEach((toggle) => {
                const controlsId = toggle.getAttribute('aria-controls');
                // Two ways to locate the matching panel:
                //   1. preferred: button has aria-controls="<panel-id>"
                //   2. legacy:    nearest [data-pt-credits-panel] in the doc
                let panel = controlsId ? document.getElementById(controlsId) : null;
                if (!panel) {
                    panel = document.querySelector('[data-pt-credits-panel]');
                }
                if (!panel) return;

                const setOpen = (open) => {
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    panel.classList.toggle('is-open', open);
                };

                // Initial state: honor any pre-set aria-expanded from the
                // markup so SSR can ship an already-open variant.
                setOpen(toggle.getAttribute('aria-expanded') === 'true');

                toggle.addEventListener('click', () => {
                    const next = toggle.getAttribute('aria-expanded') !== 'true';
                    setOpen(next);
                });
            });
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
