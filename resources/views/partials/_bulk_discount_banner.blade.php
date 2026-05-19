{{--
    Bulk-discount offer banner.

    Surfaces the "احجز 5 تذاكر أو أكثر واحصل على خصم 20%" promotion
    before the booking starts (shows page, booking step 1, seat picker,
    final form). Premium glass surface with a subtle neon shimmer + a
    gift icon — visible but not annoying.

    Vars:
      $bulkDiscount = ['min_tickets' => 5, 'discount_percent' => 20]
      $compact      = (bool) optional — render the smaller inline pill
                      variant for tight surfaces (seat picker, final
                      form). Defaults to false.
      $variant      = (string) optional — 'default' | 'subtle'. Subtle
                      drops the orb/sparkle for above-the-fold cards
                      where multiple shimmer surfaces would compete.

    Both variants are pure-CSS, server-rendered, no JS dependencies.
--}}
@php
    $bd          = $bulkDiscount ?? ['min_tickets' => 5, 'discount_percent' => 20];
    $minTickets  = (int) ($bd['min_tickets'] ?? 5);
    $discountPct = (int) ($bd['discount_percent'] ?? 20);
    $compactView = (bool) ($compact ?? false);
    $variantView = $variant ?? 'default';
@endphp

<style>
    /* =====================================================================
       Bulk-discount banner — premium, dark + light theme aware.
       Renders as a glass surface with a subtle gradient sweep + gift
       icon. The compact variant is a single-row pill for tight surfaces.
    ===================================================================== */
    .bulk-discount-banner {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 18px;
        background:
            radial-gradient(120% 80% at 0% 0%, rgba(34,211,238,0.10) 0%, rgba(34,211,238,0) 55%),
            radial-gradient(120% 80% at 100% 100%, rgba(251,191,36,0.14) 0%, rgba(251,191,36,0) 55%),
            linear-gradient(135deg, rgba(20,24,38,0.72), rgba(8,10,20,0.82));
        border: 1px solid rgba(251,191,36,0.40);
        color: #fef3c7;
        overflow: hidden;
        box-shadow:
            0 10px 32px -14px rgba(251,191,36,0.30),
            inset 0 1px 0 rgba(255,255,255,0.06);
        animation: prismFadeUp .4s var(--prism-ease, ease-out) both;
    }
    .bulk-discount-banner::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg,
            transparent 0%,
            rgba(255,255,255,0.06) 40%,
            rgba(251,191,36,0.10) 50%,
            rgba(255,255,255,0.06) 60%,
            transparent 100%);
        background-size: 220% 100%;
        background-position: 100% 0%;
        animation: bulkDiscountSweep 6.5s linear infinite;
        pointer-events: none;
    }
    .bulk-discount-banner::after {
        content: "";
        position: absolute;
        top: -40%;
        left: -10%;
        width: 240px;
        height: 240px;
        border-radius: 999px;
        background: radial-gradient(closest-side, rgba(251,191,36,0.22), rgba(251,191,36,0) 70%);
        filter: blur(2px);
        pointer-events: none;
    }
    @keyframes bulkDiscountSweep {
        0%   { background-position: 100% 0%; }
        100% { background-position: -100% 0%; }
    }

    .bulk-discount-banner .bdb-icon {
        flex: 0 0 auto;
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(251,191,36,0.32), rgba(251,191,36,0.10));
        border: 1px solid rgba(251,191,36,0.55);
        font-size: 22px;
        line-height: 1;
        box-shadow: 0 0 18px rgba(251,191,36,0.30), inset 0 1px 0 rgba(255,255,255,0.10);
        animation: bulkDiscountWiggle 4s ease-in-out infinite;
    }
    @keyframes bulkDiscountWiggle {
        0%, 92%, 100% { transform: rotate(0deg); }
        94%           { transform: rotate(-8deg); }
        96%           { transform: rotate(6deg); }
        98%           { transform: rotate(-3deg); }
    }
    .bulk-discount-banner .bdb-body {
        position: relative;
        z-index: 1;
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
        text-align: right;
    }
    .bulk-discount-banner .bdb-eyebrow {
        font-size: 10px;
        letter-spacing: .18em;
        font-weight: 700;
        text-transform: uppercase;
        color: #fde68a;
        opacity: 0.9;
    }
    .bulk-discount-banner .bdb-title {
        font-size: 14px;
        line-height: 1.45;
        font-weight: 800;
        color: #fef9c3;
    }
    .bulk-discount-banner .bdb-title b {
        font-weight: 900;
        background: linear-gradient(135deg, #fde047, #fbbf24, #f59e0b);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
        padding: 0 2px;
    }
    .bulk-discount-banner .bdb-sub {
        font-size: 11.5px;
        line-height: 1.55;
        color: rgba(254,243,199,0.78);
    }

    @media (min-width: 640px) {
        .bulk-discount-banner { padding: 16px 22px; gap: 16px; }
        .bulk-discount-banner .bdb-icon { width: 52px; height: 52px; font-size: 24px; }
        .bulk-discount-banner .bdb-title { font-size: 15px; }
        .bulk-discount-banner .bdb-sub { font-size: 12px; }
    }

    /* ============================ Compact variant ============================
       Single-row pill — used inside the seat picker and the final-form pages
       where a full banner would steal too much visual real estate.
    ============================================================================ */
    .bulk-discount-banner.is-compact {
        gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
    }
    .bulk-discount-banner.is-compact .bdb-icon {
        width: 32px; height: 32px;
        font-size: 16px;
        border-radius: 999px;
    }
    .bulk-discount-banner.is-compact .bdb-body { gap: 0; }
    .bulk-discount-banner.is-compact .bdb-eyebrow { display: none; }
    .bulk-discount-banner.is-compact .bdb-title { font-size: 12.5px; }
    .bulk-discount-banner.is-compact .bdb-sub { display: none; }

    /* ============================== Subtle variant ============================
       Drops the moving sweep + orb; keeps tone but stays calm next to other
       glass surfaces.
    ============================================================================ */
    .bulk-discount-banner.is-subtle::before,
    .bulk-discount-banner.is-subtle::after { display: none; }

    /* ============================== Reduced motion ============================ */
    @media (prefers-reduced-motion: reduce) {
        .bulk-discount-banner,
        .bulk-discount-banner::before,
        .bulk-discount-banner .bdb-icon {
            animation: none !important;
        }
    }

    /* ============================== Light theme ============================ */
    :root[data-pt-theme="light"] .bulk-discount-banner {
        background:
            radial-gradient(120% 80% at 0% 0%, rgba(8,145,178,0.10) 0%, rgba(8,145,178,0) 55%),
            radial-gradient(120% 80% at 100% 100%, rgba(245,158,11,0.16) 0%, rgba(245,158,11,0) 55%),
            linear-gradient(180deg, rgba(255,251,235,0.96), rgba(254,243,199,0.85));
        border: 1px solid rgba(180,83,9,0.32);
        color: #78350f;
        box-shadow:
            0 12px 28px -16px rgba(180,83,9,0.22),
            0 2px 6px -2px rgba(180,83,9,0.10),
            inset 0 1px 0 rgba(255,255,255,0.90);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-icon {
        background: linear-gradient(135deg, rgba(245,158,11,0.30), rgba(245,158,11,0.10));
        border-color: rgba(180,83,9,0.40);
        box-shadow: 0 0 14px rgba(245,158,11,0.30), inset 0 1px 0 rgba(255,255,255,0.70);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-eyebrow { color: #b45309; }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-title { color: #78350f; }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-sub  { color: rgba(120,53,15,0.78); }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-title b {
        background: linear-gradient(135deg, #b45309, #d97706, #f59e0b);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
    }
</style>

<div class="bulk-discount-banner {{ $compactView ? 'is-compact' : '' }} {{ $variantView === 'subtle' ? 'is-subtle' : '' }}"
     role="note"
     data-bulk-discount-banner
     data-min-tickets="{{ $minTickets }}"
     data-discount-percent="{{ $discountPct }}"
     aria-label="عرض الخصم الجماعي">
    <span class="bdb-icon" aria-hidden="true">🎉</span>
    <span class="bdb-body">
        @unless($compactView)
            <span class="bdb-eyebrow" data-i18n="bulk_discount_eyebrow">عرض خاص</span>
        @endunless
        <span class="bdb-title">
            <span data-i18n="bulk_discount_title_a">احجز</span>
            <b dir="ltr">{{ $minTickets }}</b>
            <span data-i18n="bulk_discount_title_b">تذاكر أو أكثر واحصل على خصم</span>
            <b dir="ltr">{{ $discountPct }}%</b>
        </span>
        @unless($compactView)
            <span class="bdb-sub" data-i18n="bulk_discount_sub">يُطبَّق الخصم تلقائياً على إجمالي الحجز عند اختيار {{ $minTickets }} تذاكر أو أكثر.</span>
        @endunless
    </span>
</div>
