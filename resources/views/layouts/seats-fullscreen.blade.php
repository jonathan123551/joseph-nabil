<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'PRISM · اختار مقعدك')</title>
    {{-- viewport-fit=cover lets the page extend behind iOS notch / nav bar
         so the seat map can truly fill the screen without scrolling. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060d">

    {{-- Inline SVG favicon — neutral premium identity --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%2322d3ee'/><stop offset='0.5' stop-color='%23818cf8'/><stop offset='1' stop-color='%23c084fc'/></linearGradient></defs><path d='M32 6 L56 20 L46 56 L18 56 L8 20 Z' fill='none' stroke='url(%23g)' stroke-width='3' stroke-linejoin='round'/><path d='M32 6 L32 56 M8 20 L56 20 M18 56 L46 56' stroke='url(%23g)' stroke-width='1.5' opacity='0.6'/></svg>">

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
