<!DOCTYPE html>
<html lang="ar" dir="rtl" data-pt-lang="ar" data-pt-theme="dark">
<head>
    @include('partials._app_head')

    {{-- ============== Admin chrome ==============
         A dedicated, minimal admin shell. The public marketing
         navbar / mobile drawer / footer are intentionally absent so
         /admin/* feels like a dashboard, not the storefront. A single
         slim (~52px) bar carries only the brand and the
         "لوحة التحكم" label — nothing else. --}}
    <style>
        .adm-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            gap: 12px;
            height: 52px;
            padding: 0 clamp(12px, 3vw, 22px);
            padding-top: var(--pt-safe-top, 0px);
            padding-inline-start: max(clamp(12px, 3vw, 22px), var(--pt-safe-right, 0px));
            padding-inline-end: max(clamp(12px, 3vw, 22px), var(--pt-safe-left, 0px));
            background: color-mix(in srgb, var(--prism-surface) 88%, transparent);
            border-bottom: 1px solid var(--prism-border);
            backdrop-filter: saturate(140%) blur(14px);
            -webkit-backdrop-filter: saturate(140%) blur(14px);
        }
        .adm-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--prism-text);
            min-width: 0;
        }
        .adm-brand-mark {
            height: 30px;
            width: auto;
            display: block;
            flex: none;
        }
        .adm-title {
            font-weight: 700;
            font-size: clamp(14px, 3.4vw, 16px);
            letter-spacing: .2px;
            color: var(--prism-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .adm-main {
            width: 100%;
            max-width: 80rem;
            margin: 0 auto;
            padding: 16px clamp(12px, 3vw, 22px) calc(40px + var(--pt-safe-bottom, 0px));
        }
    </style>
</head>
<body class="prism-stage min-h-screen @yield('body_class')">

    {{-- ============== Slim admin top bar (~52px) ============== --}}
    <header class="adm-bar" role="banner">
        <a href="{{ route('admin.dashboard') }}" class="adm-brand" aria-label="لوحة التحكم">
            <img src="{{ asset('images/brand/el3abed-logo.png') }}"
                 alt="العابد"
                 class="adm-brand-mark"
                 loading="eager"
                 decoding="async">
            <span class="adm-title" data-i18n="nav_admin">لوحة التحكم</span>
        </a>
    </header>

    {{-- ============== Main ============== --}}
    <main class="adm-main prism-fade-in" id="pt-main">
        @yield('content')
    </main>

    @include('partials._app_foot')
</body>
</html>
