{{--
    Tiered bulk-discount offer banner.

    Surfaces the new tier ladder ("خصومات العيلة" 20% / "خصومات الكنائس"
    30–50%) before the booking starts (shows page, booking step 1, seat
    picker, final form). Premium glass surface with a subtle neon shimmer
    + a gift icon. The active tier chip lights up dynamically — JS calls
    `BulkDiscount.syncBanners(count)` on every selection change.

    Vars:
      $bulkDiscount = ['min_tickets' => 5, 'discount_percent' => 20,
                       'tiers' => [...]]
      $compact      = (bool) optional — render the smaller inline pill
                      variant for tight surfaces (seat picker, final
                      form). Defaults to false.
      $variant      = (string) optional — 'default' | 'subtle'. Subtle
                      drops the orb/sparkle for above-the-fold cards
                      where multiple shimmer surfaces would compete.

    All three variants are pure-CSS + a tiny snippet of JS for the
    active-tier sync; no animation libs, no heavy JS frameworks.
--}}
@php
    use App\Support\BookingPricing;
    $bd = $bulkDiscount ?? BookingPricing::toJs();
    $minTickets  = (int) ($bd['min_tickets'] ?? 5);
    $discountPct = (int) ($bd['discount_percent'] ?? 20);
    $tiers       = $bd['tiers'] ?? BookingPricing::TIERS;
    $compactView = (bool) ($compact ?? false);
    $variantView = $variant ?? 'default';
@endphp

<style>
    /* =====================================================================
       Tiered bulk-discount banner — premium, dark + light theme aware.
       Renders as a glass surface with a subtle gradient sweep + gift
       icon + a 4-cell tier ladder. The compact variant collapses the
       ladder into a single-row pill for tight surfaces.
    ===================================================================== */
    .bulk-discount-banner {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 14px 16px;
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
    .bulk-discount-banner[data-active-family="church"] {
        border-color: rgba(167,139,250,0.55);
        box-shadow:
            0 10px 32px -14px rgba(167,139,250,0.32),
            inset 0 1px 0 rgba(255,255,255,0.08);
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
    .bulk-discount-banner[data-active-family="church"]::after {
        background: radial-gradient(closest-side, rgba(167,139,250,0.28), rgba(167,139,250,0) 70%);
    }
    @keyframes bulkDiscountSweep {
        0%   { background-position: 100% 0%; }
        100% { background-position: -100% 0%; }
    }

    .bulk-discount-banner .bdb-head {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .bulk-discount-banner .bdb-icon {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(251,191,36,0.32), rgba(251,191,36,0.10));
        border: 1px solid rgba(251,191,36,0.55);
        font-size: 20px;
        line-height: 1;
        box-shadow: 0 0 18px rgba(251,191,36,0.30), inset 0 1px 0 rgba(255,255,255,0.10);
        animation: bulkDiscountWiggle 4s ease-in-out infinite;
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-icon {
        background: linear-gradient(135deg, rgba(167,139,250,0.34), rgba(167,139,250,0.10));
        border-color: rgba(167,139,250,0.60);
        box-shadow: 0 0 18px rgba(167,139,250,0.30), inset 0 1px 0 rgba(255,255,255,0.10);
    }
    @keyframes bulkDiscountWiggle {
        0%, 92%, 100% { transform: rotate(0deg); }
        94%           { transform: rotate(-8deg); }
        96%           { transform: rotate(6deg); }
        98%           { transform: rotate(-3deg); }
    }
    .bulk-discount-banner .bdb-body {
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
    .bulk-discount-banner[data-active-family="church"] .bdb-eyebrow {
        color: #ddd6fe;
    }
    .bulk-discount-banner .bdb-title {
        font-size: 14px;
        line-height: 1.45;
        font-weight: 800;
        color: #fef9c3;
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-title { color: #ede9fe; }
    .bulk-discount-banner .bdb-title b {
        font-weight: 900;
        background: linear-gradient(135deg, #fde047, #fbbf24, #f59e0b);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
        padding: 0 2px;
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-title b {
        background: linear-gradient(135deg, #c4b5fd, #a78bfa, #7c3aed);
        -webkit-background-clip: text;
                background-clip: text;
    }
    .bulk-discount-banner .bdb-sub {
        font-size: 11.5px;
        line-height: 1.55;
        color: rgba(254,243,199,0.78);
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-sub {
        color: rgba(221,214,254,0.78);
    }

    /* ============================ Tier ladder ============================
       4-cell grid showing every tier; the currently active tier chip
       lifts up + glows. On phones < 380 px the grid wraps to 2x2.
    ===================================================================== */
    .bulk-discount-banner .bdb-tiers {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 6px;
        margin: 0;
    }
    .bulk-discount-banner .bdb-tier {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        padding: 8px 6px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.10);
        background: linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
        color: rgba(254,243,199,0.74);
        transition: transform .25s var(--prism-ease, ease-out),
                    box-shadow .25s var(--prism-ease, ease-out),
                    border-color .25s var(--prism-ease, ease-out),
                    background .25s var(--prism-ease, ease-out);
        text-align: center;
        position: relative;
    }
    .bulk-discount-banner .bdb-tier-family-family {
        border-color: rgba(251,191,36,0.30);
    }
    .bulk-discount-banner .bdb-tier-family-church {
        border-color: rgba(167,139,250,0.30);
    }
    .bulk-discount-banner .bdb-tier-badge {
        font-size: 16px;
        line-height: 1;
    }
    .bulk-discount-banner .bdb-tier-min {
        font-size: 10px;
        font-weight: 700;
        opacity: 0.82;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.01em;
        display: inline-block;
    }
    .bulk-discount-banner .bdb-tier-pct {
        font-size: 13px;
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        background: linear-gradient(135deg, #fde047, #fbbf24);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
    }
    .bulk-discount-banner .bdb-tier-family-church .bdb-tier-pct {
        background: linear-gradient(135deg, #c4b5fd, #a78bfa);
        -webkit-background-clip: text;
                background-clip: text;
    }
    .bulk-discount-banner .bdb-tier[data-is-active] {
        transform: translateY(-2px);
        background: linear-gradient(135deg, rgba(251,191,36,0.22), rgba(251,191,36,0.08));
        border-color: rgba(251,191,36,0.70);
        color: #fef9c3;
        box-shadow:
            0 8px 22px -10px rgba(251,191,36,0.55),
            inset 0 1px 0 rgba(255,255,255,0.16);
    }
    .bulk-discount-banner .bdb-tier-family-church[data-is-active] {
        background: linear-gradient(135deg, rgba(167,139,250,0.28), rgba(167,139,250,0.10));
        border-color: rgba(167,139,250,0.70);
        color: #ede9fe;
        box-shadow:
            0 8px 22px -10px rgba(167,139,250,0.55),
            inset 0 1px 0 rgba(255,255,255,0.16);
    }

    @media (max-width: 379px) {
        .bulk-discount-banner .bdb-tiers {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 640px) {
        .bulk-discount-banner { padding: 16px 22px; gap: 16px; }
        .bulk-discount-banner .bdb-icon { width: 50px; height: 50px; font-size: 22px; }
        .bulk-discount-banner .bdb-title { font-size: 15px; }
        .bulk-discount-banner .bdb-sub { font-size: 12px; }
        .bulk-discount-banner .bdb-tier { padding: 10px 6px; }
        .bulk-discount-banner .bdb-tier-min { font-size: 11px; }
        .bulk-discount-banner .bdb-tier-pct { font-size: 14px; }
    }

    /* ============================ Compact variant ============================
       Single-row pill — used inside the seat picker and the final-form pages
       where a full banner would steal too much visual real estate.
    ============================================================================ */
    .bulk-discount-banner.is-compact {
        flex-direction: row;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
    }
    .bulk-discount-banner.is-compact .bdb-head {
        gap: 10px;
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
    .bulk-discount-banner.is-compact .bdb-tiers { display: none; }

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
        .bulk-discount-banner .bdb-icon,
        .bulk-discount-banner .bdb-tier {
            animation: none !important;
            transition: none !important;
        }
        .bulk-discount-banner .bdb-tier[data-is-active] { transform: none; }
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
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] {
        background:
            radial-gradient(120% 80% at 0% 0%, rgba(124,58,237,0.10) 0%, rgba(124,58,237,0) 55%),
            radial-gradient(120% 80% at 100% 100%, rgba(167,139,250,0.18) 0%, rgba(167,139,250,0) 55%),
            linear-gradient(180deg, rgba(250,245,255,0.97), rgba(237,233,254,0.86));
        border-color: rgba(124,58,237,0.32);
        color: #4c1d95;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-icon {
        background: linear-gradient(135deg, rgba(245,158,11,0.30), rgba(245,158,11,0.10));
        border-color: rgba(180,83,9,0.40);
        box-shadow: 0 0 14px rgba(245,158,11,0.30), inset 0 1px 0 rgba(255,255,255,0.70);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-icon {
        background: linear-gradient(135deg, rgba(167,139,250,0.30), rgba(167,139,250,0.10));
        border-color: rgba(124,58,237,0.40);
        box-shadow: 0 0 14px rgba(167,139,250,0.30), inset 0 1px 0 rgba(255,255,255,0.70);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-eyebrow { color: #b45309; }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-eyebrow { color: #6d28d9; }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-title { color: #78350f; }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-title { color: #4c1d95; }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-sub  { color: rgba(120,53,15,0.78); }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-sub { color: rgba(76,29,149,0.78); }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-title b {
        background: linear-gradient(135deg, #b45309, #d97706, #f59e0b);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-title b {
        background: linear-gradient(135deg, #6d28d9, #7c3aed, #a78bfa);
        -webkit-background-clip: text;
                background-clip: text;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-tier {
        background: linear-gradient(135deg, rgba(255,255,255,0.65), rgba(255,255,255,0.40));
        color: rgba(120,53,15,0.75);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-tier-family-church {
        color: rgba(76,29,149,0.75);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-tier[data-is-active] {
        background: linear-gradient(135deg, rgba(254,243,199,0.95), rgba(253,224,71,0.55));
        color: #78350f;
        border-color: rgba(180,83,9,0.55);
        box-shadow: 0 8px 22px -10px rgba(180,83,9,0.40), inset 0 1px 0 rgba(255,255,255,0.80);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-tier-family-church[data-is-active] {
        background: linear-gradient(135deg, rgba(237,233,254,0.95), rgba(196,181,253,0.55));
        color: #4c1d95;
        border-color: rgba(124,58,237,0.55);
        box-shadow: 0 8px 22px -10px rgba(124,58,237,0.40), inset 0 1px 0 rgba(255,255,255,0.80);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-tier-pct {
        background: linear-gradient(135deg, #b45309, #d97706);
        -webkit-background-clip: text;
                background-clip: text;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-tier-family-church .bdb-tier-pct {
        background: linear-gradient(135deg, #6d28d9, #7c3aed);
        -webkit-background-clip: text;
                background-clip: text;
    }
</style>

<div class="bulk-discount-banner {{ $compactView ? 'is-compact' : '' }} {{ $variantView === 'subtle' ? 'is-subtle' : '' }}"
     role="note"
     data-bulk-discount-banner
     data-min-tickets="{{ $minTickets }}"
     data-discount-percent="{{ $discountPct }}"
     data-active-family="none"
     aria-label="عرض الخصومات الجماعية المتدرجة">
    <div class="bdb-head">
        <span class="bdb-icon" aria-hidden="true">🎉</span>
        <span class="bdb-body">
            @unless($compactView)
                <span class="bdb-eyebrow" data-i18n="bulk_discount_eyebrow">عرض خاص · خصومات متدرجة</span>
            @endunless
            <span class="bdb-title">
                <span data-i18n="bulk_discount_title_a">احجز أكتر، وفّر أكتر — من</span>
                <b dir="ltr">{{ $minTickets }}</b>
                <span data-i18n="bulk_discount_title_b">تذاكر يبدأ الخصم بـ</span>
                <b dir="ltr">{{ $discountPct }}%</b>
                <span data-i18n="bulk_discount_title_c">ويوصل لـ</span>
                <b dir="ltr">50%</b>
            </span>
            @unless($compactView)
                <span class="bdb-sub" data-i18n="bulk_discount_sub">تطبَّق الخصومات تلقائياً: 🎁 خصومات العيلة من 5 تذاكر · ⛪ خصومات الكنائس من 10 تذاكر فأكثر.</span>
            @endunless
        </span>
    </div>

    @unless($compactView)
        <div class="bdb-tiers" aria-hidden="false">
            @foreach($tiers as $tier)
                <div class="bdb-tier bdb-tier-family-{{ $tier['family'] }}"
                     data-tier-chip="{{ (int) $tier['percent'] }}"
                     data-tier-family="{{ $tier['family'] }}"
                     title="من {{ (int) $tier['min'] }} تذاكر فأكثر">
                    <span class="bdb-tier-badge" aria-hidden="true">{{ $tier['badge'] }}</span>
                    <span class="bdb-tier-min" dir="ltr">{{ (int) $tier['min'] }}+</span>
                    <span class="bdb-tier-pct" dir="ltr">-{{ (int) $tier['percent'] }}%</span>
                </div>
            @endforeach
        </div>
    @endunless
</div>
