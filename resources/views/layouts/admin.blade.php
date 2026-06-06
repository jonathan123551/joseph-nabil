<!DOCTYPE html>
<html lang="ar" dir="rtl" data-pt-lang="ar" data-pt-theme="dark">
<head>
    @include('partials._app_head')

    {{-- ============== Admin chrome ==============
         A dedicated, minimal admin shell. The public marketing
         navbar / mobile drawer / footer are intentionally absent so
         /admin/* feels like a dashboard, not the storefront. A single
         slim (~52px) bar carries only the brand, the "لوحة التحكم"
         label and a logout action — nothing else. --}}
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
        .adm-bar-spacer { flex: 1 1 auto; }
        .adm-logout {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 34px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid var(--prism-border-strong, var(--prism-border));
            background: color-mix(in srgb, var(--prism-rose, #e11d48) 14%, transparent);
            color: var(--prism-text);
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: background .18s var(--prism-ease, ease), border-color .18s ease, transform .12s ease;
        }
        .adm-logout:hover {
            background: color-mix(in srgb, var(--prism-rose, #e11d48) 26%, transparent);
            border-color: var(--prism-rose, #e11d48);
        }
        .adm-logout:active { transform: translateY(1px); }
        .adm-logout svg { width: 15px; height: 15px; flex: none; }
        .adm-logout-label { display: inline; }
        @media (max-width: 380px) {
            .adm-logout-label { display: none; }
            .adm-logout { padding: 0 10px; }
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

        <span class="adm-bar-spacer" aria-hidden="true"></span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="adm-logout" data-i18n-attr="aria-label:nav_logout" aria-label="تسجيل الخروج">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <path d="M16 17l5-5-5-5"/>
                    <path d="M21 12H9"/>
                </svg>
                <span class="adm-logout-label" data-i18n="nav_logout">تسجيل الخروج</span>
            </button>
        </form>
    </header>

    {{-- ============== Main ============== --}}
    <main class="adm-main prism-fade-in" id="pt-main">
        @yield('content')
    </main>

    @include('partials._app_foot')
</body>
</html>
