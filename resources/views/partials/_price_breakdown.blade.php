{{--
    Dynamic price-breakdown card.

    Shows the original total, discount line, and final total. JS hooks
    on the booking pages keep the values in sync as the ticket count
    or seat selection changes. When the count is below the bulk-
    discount threshold, the discount + final lines collapse so the
    card stays compact ("X جنيه × N ticket = Y جنيه").

    Vars:
      $bulkDiscount = ['min_tickets' => 5, 'discount_percent' => 20]

    The actual numbers are written by the page's JS to:
      [data-price-original]      → original total
      [data-price-discount-pct]  → discount %  ("-20%" string)
      [data-price-discount-amt]  → discount amount
      [data-price-final]         → final total
      [data-price-row="discount"] / [data-price-row="final"]
                                 → toggled hidden when no discount
      [data-price-progress]      → progress hint container
      [data-price-progress-msg]  → "اضف N تذاكر ..." copy
--}}
@php
    $bd          = $bulkDiscount ?? ['min_tickets' => 5, 'discount_percent' => 20];
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
    .price-breakdown[data-has-discount="1"] .pb-progress { display: none; }
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
    :root[data-pt-theme="light"] .price-breakdown .pb-row { color: rgba(15,23,42,0.66); }
    :root[data-pt-theme="light"] .price-breakdown .pb-row.pb-row-discount { color: #047857; }
    :root[data-pt-theme="light"] .price-breakdown .pb-row.pb-row-final { color: #0f172a; }
    :root[data-pt-theme="light"] .price-breakdown .pb-row.pb-row-final .pb-val { color: #b45309; }
    :root[data-pt-theme="light"] .price-breakdown .pb-progress {
        background: linear-gradient(135deg, rgba(8,145,178,0.10), rgba(8,145,178,0.04));
        border-color: rgba(8,145,178,0.40);
        color: #075985;
    }
</style>

<div class="price-breakdown"
     data-price-breakdown
     data-has-discount="0"
     data-min-tickets="{{ $minTickets }}"
     data-discount-percent="{{ $discountPct }}"
     aria-live="polite">

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
        <span data-price-progress-msg>أضف تذكرة إضافية للحصول على خصم {{ $discountPct }}%</span>
    </div>
</div>
