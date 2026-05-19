{{--
    Bulk-discount client helper.

    Exposes `window.BulkDiscount` with the same calculate() shape as
    `App\Support\BookingPricing::calculate()`. Pages that need a live
    pricing summary call `BulkDiscount.calculate(unitPrice, count)`
    and `BulkDiscount.render(rootEl, unitPrice, count)` to update a
    `_price_breakdown` partial in-place.

    The server is still the source of truth — this helper only
    drives the live summary.
--}}
@php
    $bd = $bulkDiscount ?? ['min_tickets' => 5, 'discount_percent' => 20];
@endphp
<script>
(function () {
    if (window.BulkDiscount) return; // already loaded on this page

    var CFG = {
        minTickets:      {{ (int) $bd['min_tickets'] }},
        discountPercent: {{ (int) $bd['discount_percent'] }},
    };

    function fmt(n) {
        try { return Number(n || 0).toLocaleString('en-US'); }
        catch (e) { return String(n || 0); }
    }

    function tt(key, fallback, vars) {
        var s;
        if (window.PT_T) {
            s = window.PT_T(key, vars);
            if (s !== key) return s;
        }
        s = fallback != null ? String(fallback) : key;
        if (vars && typeof s === 'string') {
            s = s.replace(/\{(\w+)\}/g, function (m, k) {
                return vars[k] !== undefined ? vars[k] : m;
            });
        }
        return s;
    }

    function calculate(unitPrice, count) {
        unitPrice = Math.max(0, parseInt(unitPrice || 0, 10) || 0);
        count     = Math.max(0, parseInt(count     || 0, 10) || 0);

        var original  = unitPrice * count;
        var qualifies = count >= CFG.minTickets;
        var percent   = qualifies ? CFG.discountPercent : 0;
        var discount  = qualifies ? Math.floor((original * percent) / 100) : 0;
        var total     = Math.max(0, original - discount);

        return {
            unitPrice:        unitPrice,
            ticketsCount:     count,
            originalPrice:    original,
            discountPercent:  percent,
            discountAmount:   discount,
            totalPrice:       total,
            qualifies:        qualifies,
            ticketsToUnlock:  qualifies ? 0 : Math.max(0, CFG.minTickets - count),
        };
    }

    /**
     * Renders pricing into a `_price_breakdown` partial.
     * `root` is the element with `[data-price-breakdown]`.
     */
    function render(root, unitPrice, count) {
        if (!root) return null;
        var p = calculate(unitPrice, count);

        var line = root.querySelector('[data-price-line]');
        if (line) {
            line.textContent = '(' + fmt(p.unitPrice) + ' × ' + p.ticketsCount + ')';
        }

        var origEl   = root.querySelector('[data-price-original]');
        var strikeEl = root.querySelector('[data-price-original-strike]');
        if (origEl)   origEl.textContent   = fmt(p.originalPrice);
        if (strikeEl) strikeEl.textContent = fmt(p.originalPrice);

        var discPctEl = root.querySelector('[data-price-discount-pct]');
        var discAmtEl = root.querySelector('[data-price-discount-amt]');
        if (discPctEl) discPctEl.textContent = '(-' + p.discountPercent + '%)';
        if (discAmtEl) discAmtEl.textContent = fmt(p.discountAmount);

        var finalEl = root.querySelector('[data-price-final]');
        if (finalEl) finalEl.textContent = fmt(p.totalPrice);

        root.setAttribute('data-has-discount', p.qualifies ? '1' : '0');

        var progressMsg = root.querySelector('[data-price-progress-msg]');
        if (progressMsg) {
            if (p.qualifies) {
                progressMsg.textContent = tt(
                    'price_progress_qualifies',
                    'تم تطبيق خصم {pct}% على إجمالي حجزك 🎉',
                    { pct: p.discountPercent }
                );
            } else if (p.ticketsCount === 0) {
                progressMsg.textContent = tt(
                    'price_progress_zero',
                    'احجز {n} تذاكر أو أكثر للحصول على خصم {pct}%',
                    { n: CFG.minTickets, pct: CFG.discountPercent }
                );
            } else {
                progressMsg.textContent = tt(
                    'price_progress_partial',
                    'أضف {n} تذاكر إضافية للحصول على خصم {pct}%',
                    { n: p.ticketsToUnlock, pct: CFG.discountPercent }
                );
            }
        }

        return p;
    }

    window.BulkDiscount = {
        CFG:       CFG,
        calculate: calculate,
        render:    render,
        format:    fmt,
    };
})();
</script>
