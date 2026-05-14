<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Premium Tickets · اختار مقعدك')</title>
    {{-- viewport-fit=cover lets the page extend behind iOS notch / nav bar
         so the seat map can truly fill the screen without scrolling. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060d">

    {{-- JN monogram favicon. See public/brand/ for the full set; this
         fullscreen layout only needs the SVG variant since fullscreen
         contexts don't render in tab strips that would benefit from
         the .ico fallback. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/apple-touch-icon.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>

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
