{{--
    Tiered bulk-discount offer banner — v2 (hero + ladder + family
    segments).

    Visual hierarchy:
      1. Hero: a single huge gradient percentage ("up to 50%") + an
         emotional subline. This is the loudest pixel on the card.
      2. Family band: two labels positioned over their owning nodes —
         "🎁 خصومات العيلة" (warm gold) on the right (RTL start) and
         "⛪ خصومات الكنائس" (premium violet) on the left.
      3. Rail: a connected 4-node stepper. Active node lifts + glows;
         the next-tier node carries a "+N tickets" pip pulled from
         `data-tickets-to-next`. Two key tiers wear ribbon flags
         ("الأكثر طلباً" on 30%, "أعلى خصم" on 50%).
      4. Progress line: a thin bar that fills toward the current
         position using a CSS custom property `--bdb-progress`
         updated by `BulkDiscount.syncBanners()`.

    Vars:
      $bulkDiscount = ['min_tickets' => 5, 'discount_percent' => 20,
                       'tiers' => [...]]
      $compact      = (bool) — render the compact pill variant for
                      tight surfaces (seat picker, final form).
      $variant      = (string) 'default' | 'subtle'. Subtle drops the
                      sweep + orb.

    JS contract (kept identical to v1, so `_bulk_discount_js` doesn't
    need a rewrite):
      [data-bulk-discount-banner]        — root selector
      [data-tier-chip="N"]               — each node (N = percent)
      [data-is-active]                   — toggled by JS on active
      [data-active-family="family|church"] — toggled by JS on root
      [data-tickets-to-next]             — set by JS, drives the pip
      style="--bdb-progress: 0..1"       — set by JS, drives the fill

    Pure CSS + 0 JS deps. Respects prefers-reduced-motion.
--}}
@php
    use App\Support\BookingPricing;
    $bd = $bulkDiscount ?? BookingPricing::toJs();
    $minTickets  = (int) ($bd['min_tickets'] ?? 5);
    $maxDiscount = (int) collect($bd['tiers'] ?? BookingPricing::TIERS)->max('percent');
    $tiers       = $bd['tiers'] ?? BookingPricing::TIERS;
    $compactView = (bool) ($compact ?? false);
    $variantView = $variant ?? 'default';

    // Tiers that wear ribbon flags. Keyed by percent so the
    // template stays declarative even if we ever reshuffle TIERS.
    $popularPct  = 30;
    $bestPct     = $maxDiscount;
@endphp

<style>
    /* =====================================================================
       Tiered bulk-discount banner v2.

       Card surfaces (radial glows, glass borders, sweep keyframe) follow
       the existing PRISM tokens used elsewhere on the booking flow.
       Everything below `.bulk-discount-banner` is scoped to this file.
    ===================================================================== */
    .bulk-discount-banner {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: clamp(14px, 3.2vw, 18px);
        padding: clamp(16px, 4.5vw, 22px) clamp(14px, 4.5vw, 22px);
        border-radius: 22px;
        background:
            radial-gradient(120% 80% at 0% 0%,   rgba(34,211,238,0.08)  0%, rgba(34,211,238,0)  55%),
            radial-gradient(120% 80% at 100% 100%, rgba(251,191,36,0.16) 0%, rgba(251,191,36,0)  55%),
            linear-gradient(135deg, rgba(20,24,38,0.78), rgba(8,10,20,0.88));
        border: 1px solid rgba(251,191,36,0.40);
        color: #fef3c7;
        overflow: hidden;
        box-shadow:
            0 14px 40px -18px rgba(251,191,36,0.32),
            inset 0 1px 0 rgba(255,255,255,0.06);
        animation: prismFadeUp .4s var(--prism-ease, ease-out) both;
    }
    .bulk-discount-banner[data-active-family="church"] {
        border-color: rgba(167,139,250,0.55);
        box-shadow:
            0 14px 40px -18px rgba(167,139,250,0.36),
            inset 0 1px 0 rgba(255,255,255,0.08);
    }
    .bulk-discount-banner::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg,
            transparent 0%,
            rgba(255,255,255,0.05) 40%,
            rgba(251,191,36,0.09) 50%,
            rgba(255,255,255,0.05) 60%,
            transparent 100%);
        background-size: 220% 100%;
        background-position: 100% 0%;
        animation: bulkDiscountSweep 7s linear infinite;
        pointer-events: none;
    }
    .bulk-discount-banner::after {
        content: "";
        position: absolute;
        top: -45%;
        left: -12%;
        width: 280px;
        height: 280px;
        border-radius: 999px;
        background: radial-gradient(closest-side, rgba(251,191,36,0.18), rgba(251,191,36,0) 70%);
        filter: blur(2px);
        pointer-events: none;
    }
    .bulk-discount-banner[data-active-family="church"]::after {
        background: radial-gradient(closest-side, rgba(167,139,250,0.24), rgba(167,139,250,0) 70%);
    }
    @keyframes bulkDiscountSweep {
        0%   { background-position: 100% 0%; }
        100% { background-position: -100% 0%; }
    }

    /* =============================== Hero ==================================
       Single huge gradient % digit + an emotional subline. The big number
       is what the eye lands on first; the eyebrow + subline are context.
    ===================================================================== */
    .bulk-discount-banner .bdb-hero {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 4px;
    }
    .bulk-discount-banner .bdb-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: clamp(10px, 2.6vw, 11px);
        letter-spacing: .22em;
        font-weight: 700;
        text-transform: uppercase;
        color: #fde68a;
        opacity: 0.92;
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-eyebrow {
        color: #ddd6fe;
    }
    .bulk-discount-banner .bdb-eyebrow-icon {
        font-size: 14px;
        line-height: 1;
        animation: bulkDiscountWiggle 5s ease-in-out infinite;
    }
    .bulk-discount-banner .bdb-hero-lead {
        font-size: clamp(13px, 3.4vw, 14px);
        font-weight: 700;
        color: rgba(254,243,199,0.78);
        margin-top: 4px;
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-hero-lead {
        color: rgba(221,214,254,0.82);
    }
    .bulk-discount-banner .bdb-hero-number {
        display: inline-flex;
        align-items: baseline;
        gap: 4px;
        font-size: clamp(46px, 12vw, 64px);
        line-height: 1.0;
        font-weight: 900;
        letter-spacing: -0.02em;
        background: linear-gradient(135deg, #fde047 0%, #fbbf24 45%, #f59e0b 100%);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
        text-shadow: 0 0 24px rgba(251,191,36,0.18);
        font-variant-numeric: tabular-nums;
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-hero-number {
        background: linear-gradient(135deg, #c4b5fd 0%, #a78bfa 45%, #7c3aed 100%);
        -webkit-background-clip: text;
                background-clip: text;
        text-shadow: 0 0 24px rgba(167,139,250,0.22);
    }
    .bulk-discount-banner .bdb-hero-number .bdb-hero-pct {
        font-size: 0.55em;
        font-weight: 800;
        letter-spacing: 0;
    }
    .bulk-discount-banner .bdb-hero-sub {
        font-size: clamp(12.5px, 3.4vw, 14px);
        line-height: 1.5;
        color: rgba(254,243,199,0.82);
        max-width: 38ch;
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-hero-sub {
        color: rgba(221,214,254,0.85);
    }
    @keyframes bulkDiscountWiggle {
        0%, 92%, 100% { transform: rotate(0deg); }
        94%           { transform: rotate(-10deg); }
        96%           { transform: rotate(8deg); }
        98%           { transform: rotate(-3deg); }
    }

    /* ========================= Family band =================================
       Two labels sitting over their owning nodes. In RTL the family
       segment (first node, percent 20) is on the right; the church
       segment (nodes 2–4) on the left.
    ===================================================================== */
    .bulk-discount-banner .bdb-families {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 3fr 1fr;   /* RTL: church on left = 3 cells, family on right = 1 cell */
        gap: 6px;
        align-items: center;
        margin-bottom: -2px;
    }
    .bulk-discount-banner .bdb-family-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: clamp(11px, 2.8vw, 12px);
        font-weight: 800;
        letter-spacing: 0.02em;
        padding: 4px 10px;
        border-radius: 999px;
        border: 1px solid transparent;
        line-height: 1.2;
        white-space: nowrap;
    }
    .bulk-discount-banner .bdb-family-label[data-family="family"] {
        color: #fde68a;
        border-color: rgba(251,191,36,0.35);
        background: linear-gradient(135deg, rgba(251,191,36,0.18), rgba(251,191,36,0.06));
        justify-self: end;             /* hug the right edge (RTL start) */
    }
    .bulk-discount-banner .bdb-family-label[data-family="church"] {
        color: #ddd6fe;
        border-color: rgba(167,139,250,0.32);
        background: linear-gradient(135deg, rgba(167,139,250,0.20), rgba(167,139,250,0.06));
        justify-self: start;           /* hug the left edge */
    }
    .bulk-discount-banner .bdb-family-icon { font-size: 13px; line-height: 1; }

    /* ============================== Rail ===================================
       4 connected nodes. Each node = badge + threshold + percent. Nodes
       are joined by tiny chevron dots; the active node lifts + glows
       and carries the live tier color. The progression direction
       follows the document's text direction (RTL: right → left).
    ===================================================================== */
    .bulk-discount-banner .bdb-rail-wrap {
        position: relative;
        z-index: 1;
    }
    .bulk-discount-banner .bdb-rail {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: clamp(4px, 1.6vw, 8px);
        align-items: stretch;
        position: relative;
    }
    .bulk-discount-banner .bdb-rail::before {
        content: "";
        position: absolute;
        inset-inline: 8%;
        top: 50%;
        height: 2px;
        transform: translateY(-50%);
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(167,139,250,0.42), rgba(34,211,238,0.28), rgba(251,191,36,0.36));
        opacity: .58;
        pointer-events: none;
    }
    .bulk-discount-banner .bdb-node {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        padding: clamp(8px, 2.6vw, 12px) clamp(4px, 1.6vw, 8px);
        min-height: 92px;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,0.10);
        background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
        color: rgba(254,243,199,0.74);
        text-align: center;
        transition: transform .25s var(--prism-ease, ease-out),
                    box-shadow .25s var(--prism-ease, ease-out),
                    border-color .25s var(--prism-ease, ease-out),
                    background .25s var(--prism-ease, ease-out);
    }
    .bulk-discount-banner .bdb-node[data-tier-family="family"] {
        border-color: rgba(251,191,36,0.28);
        background: linear-gradient(135deg, rgba(251,191,36,0.06), rgba(251,191,36,0.02));
        color: rgba(254,243,199,0.82);
    }
    .bulk-discount-banner .bdb-node[data-tier-family="church"] {
        border-color: rgba(167,139,250,0.24);
        background: linear-gradient(135deg, rgba(167,139,250,0.06), rgba(167,139,250,0.02));
        color: rgba(221,214,254,0.82);
    }
    .bulk-discount-banner .bdb-node-badge {
        font-size: clamp(18px, 5vw, 22px);
        line-height: 1;
    }
    .bulk-discount-banner .bdb-node-min {
        font-size: clamp(10.5px, 2.8vw, 11.5px);
        font-weight: 700;
        opacity: 0.85;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.02em;
    }
    .bulk-discount-banner .bdb-node-pct {
        font-size: clamp(14px, 3.8vw, 16px);
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        background: linear-gradient(135deg, #fde047, #fbbf24);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
    }
    .bulk-discount-banner .bdb-node[data-tier-family="church"] .bdb-node-pct {
        background: linear-gradient(135deg, #c4b5fd, #a78bfa);
        -webkit-background-clip: text;
                background-clip: text;
    }
    .bulk-discount-banner .bdb-node-unlock {
        margin-top: 2px;
        font-size: clamp(9px, 2.4vw, 10px);
        font-weight: 700;
        line-height: 1.25;
        color: rgba(254,243,199,0.64);
        max-width: 13ch;
    }
    .bulk-discount-banner .bdb-node[data-tier-family="church"] .bdb-node-unlock {
        color: rgba(221,214,254,0.66);
    }

    /* Active node — lifted, brighter border, family-tinted glow */
    .bulk-discount-banner .bdb-node[data-is-active] {
        transform: translateY(-3px);
        background: linear-gradient(135deg, rgba(251,191,36,0.26), rgba(251,191,36,0.10));
        border-color: rgba(251,191,36,0.75);
        color: #fef9c3;
        box-shadow:
            0 10px 26px -10px rgba(251,191,36,0.60),
            inset 0 1px 0 rgba(255,255,255,0.18);
    }
    .bulk-discount-banner .bdb-node[data-tier-family="church"][data-is-active] {
        background: linear-gradient(135deg, rgba(167,139,250,0.30), rgba(167,139,250,0.12));
        border-color: rgba(167,139,250,0.75);
        color: #ede9fe;
        box-shadow:
            0 10px 26px -10px rgba(167,139,250,0.60),
            inset 0 1px 0 rgba(255,255,255,0.18);
    }

    /* Active node gets a thin pulse ring */
    .bulk-discount-banner .bdb-node[data-is-active]::before {
        content: "";
        position: absolute;
        inset: -2px;
        border-radius: 16px;
        border: 1.5px solid currentColor;
        opacity: 0.18;
        animation: bulkDiscountActivePulse 2.4s ease-out infinite;
        pointer-events: none;
    }
    @keyframes bulkDiscountActivePulse {
        0%   { transform: scale(1);   opacity: 0.32; }
        70%  { transform: scale(1.06); opacity: 0;    }
        100% { transform: scale(1.06); opacity: 0;    }
    }

    /* "+N tickets" pip — sits on the next-tier node when JS sets
       [data-bulk-discount-banner][data-tickets-to-next] to a positive
       number. The pip is rendered via `::before` on the next node;
       we mark the next node with `[data-is-next]`. */
    .bulk-discount-banner .bdb-node[data-is-next] {
        border-style: dashed;
    }
    .bulk-discount-banner .bdb-node-pip {
        position: absolute;
        top: -10px;
        inset-inline-start: 50%;
        transform: translateX(-50%);
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        color: #fff;
        background: linear-gradient(135deg, #f97316, #ef4444);
        box-shadow: 0 4px 10px -2px rgba(239,68,68,0.45);
        white-space: nowrap;
        line-height: 1.3;
        letter-spacing: 0.01em;
        opacity: 0;
        transform: translate(-50%, 4px);
        transition: opacity .25s var(--prism-ease, ease-out), transform .25s var(--prism-ease, ease-out);
    }
    .bulk-discount-banner .bdb-node[data-is-next] .bdb-node-pip {
        opacity: 1;
        transform: translate(-50%, 0);
    }

    /* Ribbon flags — "الأكثر طلباً" on the 30% tier and "أعلى خصم"
       on the top tier. Subtle, sit just below each node. */
    .bulk-discount-banner .bdb-node-flag {
        position: absolute;
        bottom: -9px;
        inset-inline-start: 50%;
        transform: translateX(-50%);
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        white-space: nowrap;
        line-height: 1.3;
        color: rgba(254,243,199,0.92);
        background: rgba(15,23,42,0.78);
        border: 1px solid rgba(251,191,36,0.45);
        box-shadow: 0 2px 6px -1px rgba(0,0,0,0.4);
    }
    .bulk-discount-banner .bdb-node[data-tier-family="church"] .bdb-node-flag {
        color: #ede9fe;
        border-color: rgba(167,139,250,0.50);
    }
    .bulk-discount-banner .bdb-node-flag[data-flag="best"] {
        background: linear-gradient(135deg, rgba(124,58,237,0.85), rgba(99,102,241,0.85));
        border-color: rgba(167,139,250,0.85);
        color: #fff;
    }

    /* =========================== Progress rail =============================
       Thin bar below the node row, fills toward the user's current
       position on the ladder (set via `--bdb-progress: 0..1`).
    ===================================================================== */
    .bulk-discount-banner .bdb-progress {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: clamp(4px, 1.6vw, 8px);
    }
    .bulk-discount-banner .bdb-progress-track {
        position: relative;
        flex: 1;
        height: 6px;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        overflow: hidden;
    }
    .bulk-discount-banner .bdb-progress-fill {
        position: absolute;
        inset-block: 0;
        inset-inline-start: 0;
        width: calc(var(--bdb-progress, 0) * 100%);
        background: linear-gradient(90deg, #fbbf24, #f59e0b);
        border-radius: 999px;
        transition: width .35s var(--prism-ease, ease-out);
        box-shadow: 0 0 14px rgba(251,191,36,0.55);
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-progress-fill {
        background: linear-gradient(90deg, #a78bfa, #7c3aed);
        box-shadow: 0 0 14px rgba(167,139,250,0.55);
    }
    .bulk-discount-banner .bdb-progress-msg {
        font-size: clamp(10.5px, 2.8vw, 11.5px);
        font-weight: 700;
        color: rgba(254,243,199,0.82);
        white-space: nowrap;
        letter-spacing: 0.01em;
    }
    .bulk-discount-banner[data-active-family="church"] .bdb-progress-msg {
        color: rgba(221,214,254,0.82);
    }
    /* Hide the "+N" copy while it's the empty "+0" placeholder
       (e.g. when the user is already at the top tier). */
    .bulk-discount-banner[data-tickets-to-next="0"] .bdb-progress-msg-next,
    .bulk-discount-banner:not([data-tickets-to-next]) .bdb-progress-msg-next {
        display: none;
    }
    .bulk-discount-banner[data-tickets-to-next="0"] .bdb-progress-msg-top {
        display: inline;
    }
    .bulk-discount-banner .bdb-progress-msg-top {
        display: none;
    }

    /* ============================ Compact variant ============================
       Single-line pill — used inside the seat picker and the final form
       where a full hero+rail would steal too much vertical space.
    ============================================================================ */
    .bulk-discount-banner.is-compact {
        flex-direction: row;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
    }
    .bulk-discount-banner.is-compact .bdb-hero,
    .bulk-discount-banner.is-compact .bdb-families,
    .bulk-discount-banner.is-compact .bdb-rail-wrap,
    .bulk-discount-banner.is-compact .bdb-progress {
        display: none;
    }
    .bulk-discount-banner.is-compact .bdb-compact-row {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
    }
    .bulk-discount-banner .bdb-compact-row { display: none; }
    .bulk-discount-banner.is-compact .bdb-compact-icon {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(251,191,36,0.32), rgba(251,191,36,0.10));
        border: 1px solid rgba(251,191,36,0.55);
        font-size: 16px;
        line-height: 1;
    }
    .bulk-discount-banner.is-compact[data-active-family="church"] .bdb-compact-icon {
        background: linear-gradient(135deg, rgba(167,139,250,0.34), rgba(167,139,250,0.10));
        border-color: rgba(167,139,250,0.60);
    }
    .bulk-discount-banner.is-compact .bdb-compact-text {
        font-size: 12.5px;
        font-weight: 800;
        line-height: 1.35;
        color: #fef9c3;
    }
    .bulk-discount-banner.is-compact[data-active-family="church"] .bdb-compact-text {
        color: #ede9fe;
    }
    .bulk-discount-banner.is-compact .bdb-compact-text b {
        font-weight: 900;
        background: linear-gradient(135deg, #fde047, #fbbf24);
        -webkit-background-clip: text;
                background-clip: text;
        color: transparent;
        padding: 0 2px;
    }
    .bulk-discount-banner.is-compact[data-active-family="church"] .bdb-compact-text b {
        background: linear-gradient(135deg, #c4b5fd, #a78bfa);
        -webkit-background-clip: text;
                background-clip: text;
    }

    /* ============================== Subtle variant ============================
       Drops the moving sweep + orb so the card stays calm next to other
       glass surfaces (e.g. seat picker, where multiple shimmer surfaces
       would compete).
    ============================================================================ */
    .bulk-discount-banner.is-subtle::before,
    .bulk-discount-banner.is-subtle::after { display: none; }

    /* ============================== Reduced motion =========================== */
    @media (prefers-reduced-motion: reduce) {
        .bulk-discount-banner,
        .bulk-discount-banner::before,
        .bulk-discount-banner .bdb-eyebrow-icon,
        .bulk-discount-banner .bdb-node,
        .bulk-discount-banner .bdb-node[data-is-active]::before,
        .bulk-discount-banner .bdb-progress-fill {
            animation: none !important;
            transition: none !important;
        }
        .bulk-discount-banner .bdb-node[data-is-active] { transform: none; }
        .bulk-discount-banner .bdb-node-pip { transform: translateX(-50%); }
    }

    /* ============================== Light theme ============================== */
    :root[data-pt-theme="light"] .bulk-discount-banner {
        background:
            radial-gradient(120% 80% at 0% 0%,   rgba(8,145,178,0.10) 0%, rgba(8,145,178,0)  55%),
            radial-gradient(120% 80% at 100% 100%, rgba(245,158,11,0.18) 0%, rgba(245,158,11,0) 55%),
            linear-gradient(180deg, rgba(255,251,235,0.97), rgba(254,243,199,0.85));
        border: 1px solid rgba(180,83,9,0.32);
        color: #78350f;
        box-shadow:
            0 14px 30px -16px rgba(180,83,9,0.22),
            0 2px 6px -2px rgba(180,83,9,0.10),
            inset 0 1px 0 rgba(255,255,255,0.90);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] {
        background:
            radial-gradient(120% 80% at 0% 0%,   rgba(124,58,237,0.10) 0%, rgba(124,58,237,0) 55%),
            radial-gradient(120% 80% at 100% 100%, rgba(167,139,250,0.20) 0%, rgba(167,139,250,0) 55%),
            linear-gradient(180deg, rgba(250,245,255,0.97), rgba(237,233,254,0.86));
        border-color: rgba(124,58,237,0.32);
        color: #4c1d95;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-eyebrow         { color: #b45309; }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-eyebrow { color: #6d28d9; }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-hero-lead        { color: rgba(120,53,15,0.78); }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-hero-lead { color: rgba(76,29,149,0.85); }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-hero-sub         { color: rgba(120,53,15,0.85); }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-hero-sub { color: rgba(76,29,149,0.85); }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-hero-number {
        background: linear-gradient(135deg, #b45309, #d97706, #f59e0b);
        -webkit-background-clip: text;
                background-clip: text;
        text-shadow: none;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-hero-number {
        background: linear-gradient(135deg, #6d28d9, #7c3aed, #a78bfa);
        -webkit-background-clip: text;
                background-clip: text;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-family-label[data-family="family"] {
        color: #92400e;
        border-color: rgba(180,83,9,0.40);
        background: linear-gradient(135deg, rgba(254,243,199,0.95), rgba(253,224,71,0.40));
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-family-label[data-family="church"] {
        color: #5b21b6;
        border-color: rgba(124,58,237,0.40);
        background: linear-gradient(135deg, rgba(237,233,254,0.95), rgba(196,181,253,0.45));
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node {
        background: linear-gradient(135deg, rgba(255,255,255,0.70), rgba(255,255,255,0.45));
        color: rgba(120,53,15,0.78);
        border-color: rgba(180,83,9,0.18);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node[data-tier-family="church"] {
        color: rgba(76,29,149,0.80);
        border-color: rgba(124,58,237,0.18);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node[data-is-active] {
        background: linear-gradient(135deg, rgba(254,243,199,0.95), rgba(253,224,71,0.55));
        color: #78350f;
        border-color: rgba(180,83,9,0.55);
        box-shadow: 0 8px 22px -10px rgba(180,83,9,0.40), inset 0 1px 0 rgba(255,255,255,0.80);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node[data-tier-family="church"][data-is-active] {
        background: linear-gradient(135deg, rgba(237,233,254,0.95), rgba(196,181,253,0.55));
        color: #4c1d95;
        border-color: rgba(124,58,237,0.55);
        box-shadow: 0 8px 22px -10px rgba(124,58,237,0.40), inset 0 1px 0 rgba(255,255,255,0.80);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node-pct {
        background: linear-gradient(135deg, #b45309, #d97706);
        -webkit-background-clip: text;
                background-clip: text;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node[data-tier-family="church"] .bdb-node-pct {
        background: linear-gradient(135deg, #6d28d9, #7c3aed);
        -webkit-background-clip: text;
                background-clip: text;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node-unlock {
        color: rgba(120,53,15,0.66);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node[data-tier-family="church"] .bdb-node-unlock {
        color: rgba(76,29,149,0.68);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node-flag {
        color: #78350f;
        background: rgba(255,251,235,0.95);
        border-color: rgba(180,83,9,0.55);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node[data-tier-family="church"] .bdb-node-flag {
        color: #4c1d95;
        border-color: rgba(124,58,237,0.55);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-node-flag[data-flag="best"] {
        background: linear-gradient(135deg, #7c3aed, #6366f1);
        color: #fff;
        border-color: rgba(124,58,237,0.75);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-progress-track {
        background: rgba(180,83,9,0.12);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-progress-track {
        background: rgba(124,58,237,0.12);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner .bdb-progress-msg { color: rgba(120,53,15,0.85); }
    :root[data-pt-theme="light"] .bulk-discount-banner[data-active-family="church"] .bdb-progress-msg { color: rgba(76,29,149,0.85); }

    /* Compact variant — light theme tweaks */
    :root[data-pt-theme="light"] .bulk-discount-banner.is-compact .bdb-compact-icon {
        background: linear-gradient(135deg, rgba(245,158,11,0.30), rgba(245,158,11,0.10));
        border-color: rgba(180,83,9,0.40);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner.is-compact[data-active-family="church"] .bdb-compact-icon {
        background: linear-gradient(135deg, rgba(167,139,250,0.30), rgba(167,139,250,0.10));
        border-color: rgba(124,58,237,0.40);
    }
    :root[data-pt-theme="light"] .bulk-discount-banner.is-compact .bdb-compact-text { color: #78350f; }
    :root[data-pt-theme="light"] .bulk-discount-banner.is-compact[data-active-family="church"] .bdb-compact-text { color: #4c1d95; }
    :root[data-pt-theme="light"] .bulk-discount-banner.is-compact .bdb-compact-text b {
        background: linear-gradient(135deg, #b45309, #d97706);
        -webkit-background-clip: text;
                background-clip: text;
    }
    :root[data-pt-theme="light"] .bulk-discount-banner.is-compact[data-active-family="church"] .bdb-compact-text b {
        background: linear-gradient(135deg, #6d28d9, #7c3aed);
        -webkit-background-clip: text;
                background-clip: text;
    }
</style>

<div class="bulk-discount-banner {{ $compactView ? 'is-compact' : '' }} {{ $variantView === 'subtle' ? 'is-subtle' : '' }}"
     role="note"
     data-bulk-discount-banner
     data-min-tickets="{{ $minTickets }}"
     data-discount-percent="{{ $maxDiscount }}"
     data-active-family="none"
     style="--bdb-progress: 0;"
     aria-label="عرض الخصومات الجماعية المتدرجة">

    @if($compactView)
        {{-- Compact single-line pill (seat picker, final form) --}}
        <div class="bdb-compact-row">
            <span class="bdb-compact-icon" aria-hidden="true">🎉</span>
            <span class="bdb-compact-text">
                <span data-i18n="bulk_discount_compact_a">وفّر حتى</span>
                <b dir="ltr">{{ $maxDiscount }}%</b>
                <span data-i18n="bulk_discount_compact_b">— احجز أكتر، الخصم يكبر معاك</span>
            </span>
        </div>
    @else
        {{-- Hero block --}}
        <div class="bdb-hero">
            <span class="bdb-eyebrow">
                <span class="bdb-eyebrow-icon" aria-hidden="true">🎉</span>
                <span data-i18n="bulk_discount_eyebrow">عرض خاص · خصومات متدرجة</span>
            </span>
            <span class="bdb-hero-lead" data-i18n="bulk_discount_hero_lead">وفّر حتى</span>
            <span class="bdb-hero-number" dir="ltr">
                {{ $maxDiscount }}<span class="bdb-hero-pct" aria-hidden="true">%</span>
            </span>
            <span class="bdb-hero-sub" data-i18n="bulk_discount_hero_sub">احجز أكتر — الخصم يكبر معاك مع كل تذكرة</span>
        </div>

        {{-- Family band (labels above the rail) --}}
        <div class="bdb-families" aria-hidden="true">
            <span class="bdb-family-label" data-family="church">
                <span class="bdb-family-icon">⛪</span>
                <span data-i18n="bulk_discount_family_church">خصومات الكنائس</span>
            </span>
            <span class="bdb-family-label" data-family="family">
                <span class="bdb-family-icon">🎁</span>
                <span data-i18n="bulk_discount_family_family">خصومات العيلة</span>
            </span>
        </div>

        {{-- Rail --}}
        <div class="bdb-rail-wrap">
            <div class="bdb-rail" role="list" aria-label="مستويات الخصم">
                @foreach($tiers as $tier)
                    @php
                        $pct = (int) $tier['percent'];
                        $flag = $pct === $popularPct ? 'popular' : ($pct === $bestPct ? 'best' : null);
                    @endphp
                    <div class="bdb-node"
                         role="listitem"
                         data-tier-chip="{{ $pct }}"
                         data-tier-family="{{ $tier['family'] }}"
                         title="من {{ (int) $tier['min'] }} تذاكر فأكثر — خصم {{ $pct }}%">
                        <span class="bdb-node-badge" aria-hidden="true">{{ $tier['badge'] }}</span>
                        <span class="bdb-node-min" dir="ltr">{{ (int) $tier['min'] }}+</span>
                        <span class="bdb-node-pct" dir="ltr">-{{ $pct }}%</span>
                        <span class="bdb-node-unlock">
                            <span data-i18n="bulk_discount_unlock_prefix">من</span>
                            <span dir="ltr">{{ (int) $tier['min'] }}+</span>
                            <span data-i18n="bulk_discount_unlock_suffix">تذاكر</span>
                        </span>

                        <span class="bdb-node-pip" aria-hidden="true">
                            <span data-i18n="bulk_discount_pip_prefix">+</span><span data-bdb-pip-count>0</span>
                            <span data-i18n="bulk_discount_pip_suffix">تذكرة</span>
                        </span>

                        @if($flag === 'popular')
                            <span class="bdb-node-flag" data-flag="popular" data-i18n="bulk_discount_flag_popular">الأكثر طلباً</span>
                        @elseif($flag === 'best')
                            <span class="bdb-node-flag" data-flag="best" data-i18n="bulk_discount_flag_best">أعلى خصم</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Progress rail --}}
        <div class="bdb-progress"
             role="progressbar"
             aria-valuemin="0"
             aria-valuemax="{{ $maxDiscount }}"
             aria-valuenow="0">
            <div class="bdb-progress-track">
                <div class="bdb-progress-fill"></div>
            </div>
            <span class="bdb-progress-msg">
                <span class="bdb-progress-msg-next">
                    <span data-i18n="bulk_discount_progress_next_prefix">+</span><span data-bdb-progress-count>0</span>
                    <span data-i18n="bulk_discount_progress_next_suffix">لخصم أكبر</span>
                </span>
                <span class="bdb-progress-msg-top" data-i18n="bulk_discount_progress_top">وصلت لأعلى مستوى 👑</span>
            </span>
        </div>
    @endif
</div>
