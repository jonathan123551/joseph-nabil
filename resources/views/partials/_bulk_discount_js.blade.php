{{--
    Bulk-discount client helper.

    Exposes `window.BulkDiscount` with the same calculate() shape as
    `App\Support\BookingPricing::calculate()` — same tier table, same
    floor() math, same branding metadata. Pages that need a live
    pricing summary call `BulkDiscount.calculate(unitPrice, count)`
    and `BulkDiscount.render(rootEl, unitPrice, count)` to update a
    `_price_breakdown` partial in-place.

    The server is still the source of truth — this helper only
    drives the live summary.
--}}
@php
    $bd = $bulkDiscount ?? \App\Support\BookingPricing::toJs();
@endphp
<script>
(function () {
    if (window.BulkDiscount) return; // already loaded on this page

    // Tier table — mirror of PHP `BookingPricing::TIERS`. Order
    // matters (ascending by `min`). resolveTier() walks this list
    // to find the highest tier whose `min <= count`.
    var TIERS = @json($bd['tiers']);

    var CFG = {
        // Legacy keys kept for backward compat with any older
        // consumer reading min_tickets / discount_percent.
        minTickets:      {{ (int) ($bd['min_tickets'] ?? 5) }},
        discountPercent: {{ (int) ($bd['discount_percent'] ?? 20) }},
        tiers:           TIERS,
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

    function resolveTier(count) {
        var match = null;
        for (var i = 0; i < TIERS.length; i++) {
            if (count >= TIERS[i].min) match = TIERS[i];
        }
        return match;
    }

    function resolveNextTier(count) {
        for (var i = 0; i < TIERS.length; i++) {
            if (count < TIERS[i].min) return TIERS[i];
        }
        return null;
    }

    function calculate(unitPrice, count) {
        unitPrice = Math.max(0, parseInt(unitPrice || 0, 10) || 0);
        count     = Math.max(0, parseInt(count     || 0, 10) || 0);

        var original = unitPrice * count;

        var current  = resolveTier(count);
        var next     = resolveNextTier(count);

        var qualifies = current !== null;
        var percent   = current ? current.percent : 0;
        var discount  = qualifies ? Math.floor((original * percent) / 100) : 0;
        var total     = Math.max(0, original - discount);

        var toUnlock = next ? Math.max(0, next.min - count) : 0;

        return {
            unitPrice:           unitPrice,
            ticketsCount:        count,
            originalPrice:       original,
            discountPercent:     percent,
            discountAmount:      discount,
            totalPrice:          total,
            qualifies:           qualifies,
            ticketsToUnlock:     toUnlock,
            nextDiscountPercent: next ? next.percent : null,
            currentTier:         current,
            nextTier:            next,
            currentTierFamily:   current ? current.family : 'none',
            currentTierLabel:    current ? current.label_key : null,
        };
    }

    // Resolve the branded family label ("خصومات العيلة" / "خصومات الكنائس")
    // by walking the i18n table if available, falling back to the
    // hardcoded Arabic phrasing otherwise.
    function familyLabel(tier) {
        if (!tier) return '';
        var fallback = tier.family === 'family'
            ? 'خصومات العيلة'
            : 'خصومات الكنائس';
        return tt(tier.label_key, fallback);
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
        // data-tier-family drives the warm-vs-premium card theming
        // (see `.price-breakdown[data-tier-family="church"]` rules).
        root.setAttribute('data-tier-family', p.currentTierFamily);
        root.setAttribute('data-tier-percent', String(p.discountPercent));

        var tierLabelEl = root.querySelector('[data-price-tier-label]');
        if (tierLabelEl) {
            tierLabelEl.textContent = familyLabel(p.currentTier);
        }
        var tierBadgeEl = root.querySelector('[data-price-tier-badge]');
        if (tierBadgeEl) {
            tierBadgeEl.textContent = p.currentTier ? p.currentTier.badge : '';
        }

        var progressMsg = root.querySelector('[data-price-progress-msg]');
        if (progressMsg) {
            var nextLabelFallback = p.nextTier && p.nextTier.family === 'church'
                ? 'خصومات الكنائس'
                : 'خصومات العيلة';
            var nextLabel = p.nextTier ? tt(p.nextTier.label_key, nextLabelFallback) : '';
            var currentLabel = familyLabel(p.currentTier);

            if (p.qualifies && p.nextTier) {
                // Mid-tier: tell the user how close they are to the
                // next bracket, branded with both the current and
                // next family names.
                progressMsg.textContent = tt(
                    'price_progress_next_tier',
                    'تم تطبيق {currentLabel} ({currentPct}%) — احجز {n} تذكرة إضافية للوصول إلى {nextLabel} {nextPct}%',
                    {
                        currentLabel: currentLabel,
                        currentPct:   p.discountPercent,
                        n:            p.ticketsToUnlock,
                        nextLabel:    nextLabel,
                        nextPct:      p.nextDiscountPercent,
                    }
                );
            } else if (p.qualifies) {
                // Top tier — celebrate.
                progressMsg.textContent = tt(
                    'price_progress_top_tier',
                    '👑 تم تطبيق أعلى مستوى من {currentLabel} — خصم {pct}%',
                    {
                        currentLabel: currentLabel,
                        pct:          p.discountPercent,
                    }
                );
            } else if (p.ticketsCount === 0) {
                progressMsg.textContent = tt(
                    'price_progress_zero',
                    'احجز {n} تذاكر للحصول على {nextLabel} — خصم {pct}%',
                    {
                        n:         p.ticketsToUnlock,
                        nextLabel: nextLabel,
                        pct:       p.nextDiscountPercent,
                    }
                );
            } else {
                progressMsg.textContent = tt(
                    'price_progress_partial',
                    'اقتربت من {nextLabel} — احجز {n} تذاكر إضافية لخصم {pct}%',
                    {
                        n:         p.ticketsToUnlock,
                        nextLabel: nextLabel,
                        pct:       p.nextDiscountPercent,
                    }
                );
            }
        }

        // Always sync any tier-ladder banners on the same page so
        // the active chip stays in lockstep with the breakdown.
        syncBanners(p.ticketsCount);

        return p;
    }

    /**
     * Normalised progress along the whole tier ladder (0..1) for the
     * given count. Each tier occupies an equal segment; within a
     * segment we interpolate linearly between the current and next
     * tier's `min` thresholds.
     */
    function railProgress(count) {
        if (!TIERS.length) return 0;
        var step = 1 / TIERS.length;                // 0.25 for 4 tiers
        var center = function (i) { return (i + 0.5) * step; };

        // Find current tier index, or -1 if below the first tier.
        var curIdx = -1;
        for (var i = 0; i < TIERS.length; i++) {
            if (count >= TIERS[i].min) curIdx = i;
        }
        if (count <= 0) return 0;
        if (curIdx === -1) {
            // 0 < count < first tier — fill toward first node center.
            var first = TIERS[0].min || 1;
            return Math.max(0, Math.min(1, (count / first) * center(0)));
        }
        if (curIdx >= TIERS.length - 1) return 1;
        var curMin  = TIERS[curIdx].min;
        var nextMin = TIERS[curIdx + 1].min;
        var span    = Math.max(1, nextMin - curMin);
        var ratio   = Math.max(0, Math.min(1, (count - curMin) / span));
        return center(curIdx) + ratio * (center(curIdx + 1) - center(curIdx));
    }

    /**
     * Sync a tier-ladder banner (`_bulk_discount_banner`) so the
     * currently-active tier chip lights up, the next-tier node
     * carries the "+N tickets" pip, and the progress bar fills
     * proportionally. Called by the booking pages whenever the
     * seat / ticket count changes.
     */
    function syncBanners(count) {
        count = Math.max(0, parseInt(count || 0, 10) || 0);
        var nodes = document.querySelectorAll('[data-bulk-discount-banner]');
        if (!nodes.length) return;
        var p = calculate(0, count);

        var activePct = p.discountPercent;
        var nextPct   = p.nextTier ? p.nextTier.percent : null;
        var toNext    = p.ticketsToUnlock || 0;
        var progress  = railProgress(count);

        nodes.forEach(function (banner) {
            banner.setAttribute('data-active-tier',  String(activePct));
            banner.setAttribute('data-active-family', p.currentTierFamily);
            banner.setAttribute('data-tickets-to-next', String(toNext));
            banner.style.setProperty('--bdb-progress', String(progress));

            // Active + next-tier flags on each node.
            var chipNodes = banner.querySelectorAll('[data-tier-chip]');
            chipNodes.forEach(function (chip) {
                var pct = parseInt(chip.getAttribute('data-tier-chip') || '0', 10);
                chip.toggleAttribute('data-is-active', pct === activePct && activePct > 0);
                chip.toggleAttribute('data-is-next',   nextPct !== null && pct === nextPct && toNext > 0);

                // Pip count is also written into the next-tier node
                // so screen readers / non-CSS clients see it.
                var pipCount = chip.querySelector('[data-bdb-pip-count]');
                if (pipCount) {
                    pipCount.textContent = (nextPct !== null && pct === nextPct) ? String(toNext) : '';
                }
            });

            // Progress message "+N to next" — single shared counter.
            var progressCount = banner.querySelector('[data-bdb-progress-count]');
            if (progressCount) progressCount.textContent = String(toNext);

            // ARIA: progressbar value now = current discount percent.
            var progressEl = banner.querySelector('[role="progressbar"]');
            if (progressEl) progressEl.setAttribute('aria-valuenow', String(activePct));
        });
    }

    window.BulkDiscount = {
        CFG:           CFG,
        TIERS:         TIERS,
        calculate:     calculate,
        render:        render,
        syncBanners:   syncBanners,
        format:        fmt,
        resolveTier:   resolveTier,
        familyLabel:   familyLabel,
    };
})();
</script>
