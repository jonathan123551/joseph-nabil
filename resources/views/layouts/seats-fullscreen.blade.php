<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'اختار مقعدك')</title>
    {{-- viewport-fit=cover lets the page extend behind iOS notch / nav bar
         so the seat map can truly fill the screen without scrolling. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="icon" type="image/png" href="{{ asset('images/sarkha-logo.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #020617;
            color: #e5e7eb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            /* lock the document to the viewport so the canvas can't push the
               page taller than the screen on mobile */
            height: 100dvh;
            width: 100vw;
            overflow: hidden;
            overscroll-behavior: none;
        }
        body {
            background:
                radial-gradient(circle at top, rgba(255,255,255,0.10), transparent 55%),
                radial-gradient(circle at 20% 0, rgba(251,191,36,0.18), transparent 60%),
                radial-gradient(circle at 80% 0, rgba(239,68,68,0.20), transparent 60%),
                #020617;
        }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
