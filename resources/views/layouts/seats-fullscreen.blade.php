<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Joseph Nabil · اختار مقعدك')</title>
    {{-- viewport-fit=cover lets the page extend behind iOS notch / nav bar
         so the seat map can truly fill the screen without scrolling. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060d">

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
</body>
</html>
