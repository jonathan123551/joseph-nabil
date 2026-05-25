{{--
    Dynamic price-breakdown card — tier-aware.

    Shows the original total, discount line (with the current tier's
    branded label), and final total. JS hooks on the booking pages
    keep the values in sync as the ticket count or seat selection
    changes. When the count is below the bulk-discount threshold,
    the discount + final lines collapse so the card stays compact.

    The `[data-tier-family]` attribute drives the warm-vs-premium
    tint — family (20 %) glows emerald-gold; church (30/40/50 %)
    glows violet for the "خصومات الكنائس" branding.

    Vars:
      $bulkDiscount = ['min_tickets' => 5, 'discount_percent' => 20,
                       'tiers' => [...]]

    JS writes:
      [data-price-original]         → original total
      [data-price-discount-pct]     → discount %  ("-20%" / "-30%" / …)
      [data-price-discount-amt]     → discount amount
      [data-price-final]            → final total
      [data-price-row="discount"] / [data-price-row="final"]
                                    → toggled hidden when no discount
      [data-price-progress]         → progress hint container
      [data-price-progress-msg]     → tier-aware progress copy
      [data-price-tier-label]       → branded family label
      [data-price-tier-badge]       → 🎁 / ⛪ / 💎 / 👑
      [data-tier-family]            → 'family' | 'church' | 'none'
--}}
@php
    use App\Support\BookingPricing;
    $bd = $bulkDiscount ?? BookingPricing::toJs();
    $minTickets  = (int) ($bd['min_tickets'] ?? 5);
    $discountPct = (int) ($bd['discount_percent'] ?? 20);
@endphp

<style>
    .price-breakdown {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 14px 16px;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(15,23,42,0.55), rgba(8,10,20,0.65));
        border: 1px solid var(--prism-border, rgba(255,255,255,0.10));
        color: var(--prism-text, #f1f5fb);
        transition: border-color .25s var(--prism-ease, ease-out),
                    box-shadow .25s var(--prism-ease, ease-out);
    }
    .price-breakdown[data-tier-family="family"] {
        border-color: rgba(251,191,36,0.40);
        box-shadow: 0 12px 28px -18px rgba(251,191,36,0.30), inset 0 1px 0 rgba(255,255,255,0.05);
    }
    .price-breakdown[data-tier-family="church"] {
        border-color: rgba(167,139,250,0.45);
        box-shadow: 0 12px 28px -18px rgba(167,139,250,0.35), inset 0 1px 0 rgba(255,255,255,0.06);
    }
    .price-breakdown .pb-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        font-size: 12.5px;
        color: var(--prism-text-2, rgba(241,245,251,0.78));
    }
    .price-breakdown .pb-row.pb-row-discount {
        color: #6ee7b7;
        font-weight: 700;
    }
    .price-breakdown[data-tier-family="church"] .pb-row.pb-row-discount {
        color: #c4b5fd;
    }
    .price-breakdown .pb-row.pb-row-final {
        margin-top: 4px;
        padding-top: 10px;
        border-top: 1px dashed var(--prism-border, rgba(255,255,255,0.16));
        font-size: 14px;
        font-weight: 800;
    }
    .price-breakdown .pb-row.pb-row-final .pb-val {
        color: var(--prism-gold, #fbbf24);
        font-size: 18px;
        letter-spacing: -0.01em;
    }
    .price-breakdown[data-tier-family="church"] .pb-row.pb-row-final .pb-val {
        color: #c4b5fd;
    }
    .price-breakdown .pb-row .pb-val-strike {
        text-decoration: line-through;
        text-decoration-thickness: 1.5px;
        color: var(--prism-text-3, rgba(241,245,251,0.55));
        font-weight: 600;
        margin-inline-end: 6px;
        font-size: 12px;
    }
    .price-breakdown .pb-val { font-variant-numeric: tabular-nums; }
    .price-breakdown .pb-key small { opacity: 0.78; }

    .price-breakdown[data-has-discount="0"] .pb-row-final .pb-val-strike { display: none; }
    .price-breakdown[data-has-discount="0"] [data-price-row="discount"] { display: none; }
    .price-breakdown[data-has-discount="0"] [data-price-row="tier-label"] { display: none; }

    /* Branded tier-label chip — sits above the discount row. */
    .price-breakdown .pb-tier-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.01em;
        color: #fef3c7;
        background: linear-gradient(135deg, rgba(251,191,36,0.18), rgba(251,191,36,0.06));
        border: 1px solid rgba(251,191,36,0.45);
        box-shadow: 0 0 12px rgba(251,191,36,0.18);
        align-self: flex-start;
    }
    .price-breakdown[data-tier-family="church"] .pb-tier-label {
        color: #ede9fe;
        background: linear-gradient(135deg, rgba(167,139,250,0.20), rgba(167,139,250,0.06));
        border-color: rgba(167,139,250,0.50);
        box-shadow: 0 0 12px rgba(167,139,250,0.20);
    }
    .price-breakdown .pb-tier-label-badge { font-size: 13px; line-height: 1; }
    .price-breakdown .pb-tier-label-pct {
        font-variant-numeric: tabular-nums;
        opacity: 0.86;
    }

    .price-breakdown .pb-progress {
        margin-top: 6px;
        padding: 8px 10px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(34,211,238,0.12), rgba(34,211,238,0.04));
        border: 1px dashed rgba(34,211,238,0.40);
        font-size: 11px;
        color: #bae6fd;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .price-breakdown[data-tier-family="family"] .pb-progress {
        background: linear-gradient(135deg, rgba(251,191,36,0.12), rgba(251,191,36,0.04));
        border-color: rgba(251,191,36,0.40);
        color: #fde68a;
    }
    .price-breakdown[data-tier-family="church"] .pb-progress {
        background: linear-gradient(135deg, rgba(167,139,250,0.14), rgba(167,139,250,0.04));
        border-color: rgba(167,139,250,0.45);
        color: #ddd6fe;
    }
    .price-breakdown .pb-progress-icon { font-size: 14px; }

    @media (prefers-reduced-motion: reduce) {
        .price-breakdown { transition: none; }
    }

    /* Light theme */
    :root[data-pt-theme="light"] .price-breakdown {
        background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(252,250,245,0.86));
        border-color: rgba(15,23,42,0.12);
        color: #0f172a;
        box-shadow: 0 12px 28px -18px rgba(15,23,42,0.18), inset 0 1px 0 rgba(255,255,255,0.85);
    }
    :root[data-pt-theme="light"] .price-breakdown[data-tier-family="family"] {
        border-color: rgba(180,83,9,0.40);
    }
    :root[data-pt-theme="light"] .price-breakdown[data-tier-family="church"] {
        border-color: rgba(124,58,237,0.45);
    }
    :root[data-pt-theme="light"] .price-breakdown .pb-row { color: rgba(15,23,42,0.66); }
    :root[data-pt-theme="light"] .price-breakdown .pb-row.pb-row-discount { color: #047857; }
    :root[data-pt-theme="light"] .price-breakdown[data-tier-family="church"] .pb-row.pb-row-discount { color: #6d28d9; }
    :root[data-pt-theme="light"] .price-breakdown .pb-row.pb-row-final { color: #0f172a; }
    :root[data-pt-theme="light"] .price-breakdown .pb-row.pb-row-final .pb-val { color: #b45309; }
    :root[data-pt-theme="light"] .price-breakdown[data-tier-family="church"] .pb-row.pb-row-final .pb-val { color: #6d28d9; }
    :root[data-pt-theme="light"] .price-breakdown .pb-progress {
        background: linear-gradient(135deg, rgba(8,145,178,0.10), rgba(8,145,178,0.04));
        border-color: rgba(8,145,178,0.40);
        color: #075985;
    }
    :root[data-pt-theme="light"] .price-breakdown[data-tier-family="family"] .pb-progress {
        background: linear-gradient(135deg, rgba(245,158,11,0.12), rgba(245,158,11,0.04));
        border-color: rgba(180,83,9,0.40);
        color: #b45309;
    }
    :root[data-pt-theme="light"] .price-breakdown[data-tier-family="church"] .pb-progress {
        background: linear-gradient(135deg, rgba(167,139,250,0.14), rgba(167,139,250,0.04));
        border-color: rgba(124,58,237,0.45);
        color: #6d28d9;
    }
    :root[data-pt-theme="light"] .price-breakdown .pb-tier-label {
        background: linear-gradient(135deg, rgba(254,243,199,0.95), rgba(253,224,71,0.45));
        border-color: rgba(180,83,9,0.45);
        color: #78350f;
    }
    :root[data-pt-theme="light"] .price-breakdown[data-tier-family="church"] .pb-tier-label {
        background: linear-gradient(135deg, rgba(237,233,254,0.95), rgba(196,181,253,0.45));
        border-color: rgba(124,58,237,0.45);
        color: #4c1d95;
    }
</style>

<div class="price-breakdown"
     data-price-breakdown
     data-has-discount="0"
     data-tier-family="none"
     data-tier-percent="0"
     data-min-tickets="{{ $minTickets }}"
     data-discount-percent="{{ $discountPct }}"
     aria-live="polite">

    {{-- Branded tier label — hidden until a tier kicks in via
         `data-has-discount="1"` on the parent. The label text is
         filled in by JS so it matches the current tier exactly. --}}
    <div data-price-row="tier-label">
        <span class="pb-tier-label">
            <span class="pb-tier-label-badge" data-price-tier-badge aria-hidden="true">🎁</span>
            <span data-price-tier-label>خصومات العيلة</span>
        </span>
    </div>

    <div class="pb-row" data-price-row="original">
        <span class="pb-key">
            <span data-i18n="price_subtotal">المجموع</span>
            <small data-price-line>(0 × 0)</small>
        </span>
        <span class="pb-val">
            <span data-price-original>0</span>
            <span class="opacity-70" data-i18n="shows_egp">جنيه</span>
        </span>
    </div>

    <div class="pb-row pb-row-discount" data-price-row="discount">
        <span class="pb-key">
            <span data-i18n="price_discount">الخصم</span>
            <small data-price-discount-pct>(-{{ $discountPct }}%)</small>
        </span>
        <span class="pb-val">
            −<span data-price-discount-amt>0</span>
            <span class="opacity-70" data-i18n="shows_egp">جنيه</span>
        </span>
    </div>

    <div class="pb-row pb-row-final" data-price-row="final">
        <span class="pb-key" data-i18n="price_final_total">الإجمالي بعد الخصم</span>
        <span class="pb-val">
            <span class="pb-val-strike" data-price-original-strike>0</span>
            <span data-price-final>0</span>
            <span class="opacity-70" data-i18n="shows_egp">جنيه</span>
        </span>
    </div>

    <div class="pb-progress" data-price-progress>
        <span class="pb-progress-icon" aria-hidden="true">🎁</span>
        <span data-price-progress-msg>احجز {{ $minTickets }} تذاكر للحصول على خصومات العيلة — خصم {{ $discountPct }}%</span>
    </div>
</div>
