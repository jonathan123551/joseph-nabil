<!DOCTYPE html>
<html lang="ar" dir="rtl" data-pt-lang="ar" data-pt-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'العابد · اختار مقعدك')</title>
    {{-- viewport-fit=cover lets the page extend behind iOS notch / nav bar
         so the seat map can truly fill the screen without scrolling. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060d">

    {{-- Early bootstrap: apply persisted theme + lang BEFORE the body
         renders so we never flash AR/dark on a user who chose EN/light.
         Mirrors the script in layouts/app.blade.php — keep these two in
         sync if the bootstrap rules change. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('pt-theme');
                var theme = (stored === 'light' || stored === 'dark') ? stored : 'dark';
                document.documentElement.setAttribute('data-pt-theme', theme);
                var meta = document.querySelector('meta[name="theme-color"]');
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

    {{-- JN monogram favicon. The brand mark is now the painted artwork
         (gold / teal / violet), so we serve the pre-rendered favicon
         plates rather than an inline SVG. See public/brand/ for the
         full asset set. --}}
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('brand/favicon-48.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('brand/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    {{-- Tailwind CSS — same pre-built static stylesheet as the main layout
         (replaces the dev-only cdn.tailwindcss.com Play CDN). See the
         long comment in resources/views/layouts/app.blade.php for the
         full rationale; this file just mirrors the swap so the
         fullscreen seat-picker shell uses the same compiled utilities
         as the rest of the app. --}}
    <link rel="stylesheet" href="{{ asset('build/app.css') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* PRISM seats-fullscreen shell — no nav, no footer, viewport-locked. */
        html, body {
            margin: 0;
            padding: 0;
            background: #05060d;
            color: #f1f5fb;
            font-family: "IBM Plex Sans Arabic", "Space Grotesk", system-ui, -apple-system, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
            /* lock the document to the viewport so the canvas can't push the
               page taller than the screen on mobile */
            height: 100dvh;
            width: 100vw;
            overflow: hidden;
            overscroll-behavior: none;
        }
        body {
            background:
                radial-gradient(ellipse 80% 50% at 50% -10%, rgba(129,140,248,0.20), transparent 60%),
                radial-gradient(ellipse 60% 40% at 0% 10%, rgba(34,211,238,0.12), transparent 60%),
                radial-gradient(ellipse 60% 40% at 100% 5%, rgba(192,132,252,0.12), transparent 60%),
                linear-gradient(180deg, #05060d 0%, #07091a 60%, #05060d 100%);
        }
        ::selection { background: rgba(129,140,248,0.45); color: #fff; }
        * { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body>
    @yield('content')

    {{-- i18n runtime — same dictionary + parser as the main app layout,
         so every data-i18n / data-i18n-attr key in the seat picker
         (popup + chrome) translates correctly when the user is on
         EN or AR. Without this the partial fell through to the
         English fallback text written between the tags. --}}
    <script>
        @include('partials._i18n_dictionary')

        function ptT(key, vars) {
            var lang = document.documentElement.getAttribute('data-pt-lang') || 'ar';
            var dict = I18N[lang] || I18N.ar;
            var s = dict[key];
            if (s === undefined) s = (I18N.ar || {})[key];
            if (s === undefined) return key;
            if (vars && typeof s === 'string') {
                s = s.replace(/\{(\w+)\}/g, function (m, k) { return vars[k] !== undefined ? vars[k] : m; });
            }
            return s;
        }
        window.PT_I18N = I18N;
        window.PT_T    = ptT;

        function readVars(el) {
            var raw = el.getAttribute('data-i18n-vars');
            if (!raw) return null;
            try { return JSON.parse(raw); } catch (_) { return null; }
        }
        function interp(s, vars) {
            if (!vars || typeof s !== 'string') return s;
            return s.replace(/\{(\w+)\}/g, function (m, k) { return vars[k] !== undefined ? vars[k] : m; });
        }
        function applySeatsLang(lang) {
            var dict = I18N[lang] || I18N.ar;
            document.documentElement.setAttribute('data-pt-lang', lang);
            document.documentElement.lang = lang;
            document.documentElement.dir  = (lang === 'en') ? 'ltr' : 'rtl';
            document.querySelectorAll('[data-i18n]').forEach(function (el) {
                var k = el.getAttribute('data-i18n');
                if (dict[k] !== undefined) el.textContent = interp(dict[k], readVars(el));
            });
            document.querySelectorAll('[data-i18n-html]').forEach(function (el) {
                var k = el.getAttribute('data-i18n-html');
                if (dict[k] !== undefined) el.innerHTML = interp(dict[k], readVars(el));
            });
            document.querySelectorAll('[data-i18n-attr]').forEach(function (el) {
                var spec = el.getAttribute('data-i18n-attr') || '';
                spec.split(',').forEach(function (pair) {
                    var parts = pair.split(':').map(function (s) { return s && s.trim(); });
                    var attr = parts[0], key = parts[1];
                    if (!attr || !key) return;
                    if (dict[key] !== undefined) el.setAttribute(attr, dict[key]);
                });
            });
            try { localStorage.setItem('pt-lang', lang); } catch (_) {}
            window.PT_LANG = lang;
            document.dispatchEvent(new CustomEvent('pt:langchange', { detail: { lang: lang } }));
        }
        window.PT_APPLY_LANG = applySeatsLang;

        var initLang = 'ar';
        try { initLang = localStorage.getItem('pt-lang') || 'ar'; } catch (_) {}
        if (initLang !== 'ar' && initLang !== 'en') initLang = 'ar';
        applySeatsLang(initLang);

        {{-- Cross-tab sync: if the user flips the locale on another tab
             (e.g. the home page) while the seat picker is open, mirror
             the change here. --}}
        window.addEventListener('storage', function (e) {
            if (e.key !== 'pt-lang') return;
            var v = e.newValue;
            if (v === 'ar' || v === 'en') applySeatsLang(v);
        });
    </script>
</body>
</html>
