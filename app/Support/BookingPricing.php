<?php

namespace App\Support;

/**
 * Centralised pricing rules for ticket bookings.
 *
 * Tiered bulk-discount offer:
 *
 *   1–4   tickets  → no discount
 *   5–9   tickets  → 20% OFF   ·  family tier ("خصومات العيلة")
 *   10–30 tickets  → 30% OFF   ·  church tier ("خصومات الكنائس")
 *   31–49 tickets  → 40% OFF   ·  church tier
 *   50+   tickets  → 50% OFF   ·  church tier
 *
 * The same numbers are mirrored to the client via `toJs()` so the
 * live pricing summary on the booking pages computes identical
 * values to the server. The server remains the source of truth —
 * the persisted `bookings.total_price` is always recomputed here in
 * BookingController, never trusted from the client.
 *
 * Backward compatibility:
 *   `toJs()` continues to expose `min_tickets` / `discount_percent`
 *   for any legacy consumer; the new tier table is published under
 *   `tiers`. The persisted `discount_percent` column on a booking
 *   row is the authoritative truth for that row — never recomputed
 *   retroactively — so legacy rows render with what the customer
 *   actually paid even after this upgrade.
 */
class BookingPricing
{
    /** Family tier — the warm "خصومات العيلة" branding (5–9 tickets). */
    public const FAMILY_FAMILY = 'family';

    /** Church / group tier — the premium "خصومات الكنائس" branding (10+). */
    public const FAMILY_CHURCH = 'church';

    /** First-tier minimum, exposed for legacy `bulkDiscount.min_tickets`. */
    public const BULK_MIN_TICKETS = 5;

    /** First-tier percent, exposed for legacy `bulkDiscount.discount_percent`. */
    public const BULK_DISCOUNT_PERCENT = 20;

    /**
     * Tier table — listed ascending by `min`. The active tier for a
     * given ticket count is the LAST row whose `min <= count`.
     *
     * Keep this list ascending; resolveTierForCount() and the JS
     * mirror in `_bulk_discount_js.blade.php` rely on it.
     *
     * Each row carries:
     *   min         — minimum tickets that activate the tier
     *   percent     — whole-number discount %
     *   family      — visual family (warm vs premium) for theming
     *   label_key   — i18n key for the branded family name
     *   phrase_key  — i18n key for the celebratory phrase
     *   badge       — single-glyph badge used in admin chips
     */
    public const TIERS = [
        ['min' => 5,  'percent' => 20, 'family' => self::FAMILY_FAMILY, 'label_key' => 'discount_family_label', 'phrase_key' => 'discount_family_phrase', 'badge' => '🎁'],
        ['min' => 10, 'percent' => 30, 'family' => self::FAMILY_CHURCH, 'label_key' => 'discount_church_label', 'phrase_key' => 'discount_church_phrase', 'badge' => '⛪'],
        ['min' => 31, 'percent' => 40, 'family' => self::FAMILY_CHURCH, 'label_key' => 'discount_church_label', 'phrase_key' => 'discount_church_phrase', 'badge' => '💎'],
        ['min' => 50, 'percent' => 50, 'family' => self::FAMILY_CHURCH, 'label_key' => 'discount_church_label', 'phrase_key' => 'discount_church_top_phrase', 'badge' => '👑'],
    ];

    /**
     * Compute pricing for a given unit price and ticket count.
     *
     * @return array{
     *   unit_price:int,
     *   tickets_count:int,
     *   original_price:int,
     *   discount_percent:int,
     *   discount_amount:int,
     *   total_price:int,
     *   qualifies:bool,
     *   tickets_to_unlock:int,
     *   next_discount_percent:?int,
     *   current_tier:?array,
     *   next_tier:?array,
     *   current_tier_label:?string,
     * }
     */
    public static function calculate(int $unitPrice, int $ticketsCount): array
    {
        $unitPrice = max(0, $unitPrice);
        $ticketsCount = max(0, $ticketsCount);

        $original = $unitPrice * $ticketsCount;

        $currentTier = self::resolveTierForCount($ticketsCount);
        $nextTier = self::resolveNextTierForCount($ticketsCount);

        $qualifies = $currentTier !== null;
        $percent = $currentTier['percent'] ?? 0;

        // Use integer math to avoid float rounding drift on the
        // stored total. Egyptian Pound bookings are always whole
        // pounds in this app, so we floor the discount to the
        // nearest pound (i.e. keep the customer-friendly side).
        $discount = $qualifies ? (int) floor(($original * $percent) / 100) : 0;
        $total = max(0, $original - $discount);

        // tickets_to_unlock points at the NEXT tier (not "this" tier).
        // When already at the top tier, both this and next_discount_percent
        // collapse to "no more tiers above" (0 / null).
        $toUnlock = $nextTier !== null
            ? max(0, $nextTier['min'] - $ticketsCount)
            : 0;

        return [
            'unit_price' => $unitPrice,
            'tickets_count' => $ticketsCount,
            'original_price' => $original,
            'discount_percent' => $percent,
            'discount_amount' => $discount,
            'total_price' => $total,
            'qualifies' => $qualifies,
            'tickets_to_unlock' => $toUnlock,
            'next_discount_percent' => $nextTier['percent'] ?? null,
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'current_tier_label' => $currentTier['label_key'] ?? null,
        ];
    }

    /**
     * Return the active tier for a given ticket count, or null if no
     * tier qualifies (1–4 tickets, or 0).
     */
    public static function resolveTierForCount(int $ticketsCount): ?array
    {
        $match = null;
        foreach (self::TIERS as $tier) {
            if ($ticketsCount >= $tier['min']) {
                $match = $tier;
            }
        }

        return $match;
    }

    /**
     * Return the FIRST tier whose `min` is strictly greater than the
     * given ticket count — i.e. the next discount the customer could
     * unlock by adding more tickets. Null when already at the top.
     */
    public static function resolveNextTierForCount(int $ticketsCount): ?array
    {
        foreach (self::TIERS as $tier) {
            if ($ticketsCount < $tier['min']) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Classify a STORED `discount_percent` (e.g. on an existing
     * booking row) against the tier table. Used by the admin /
     * thank-you views to render the right family badge for legacy
     * rows — never re-derive the tier from `tickets_count` because
     * the historical row might have been booked under a different
     * set of rules. The persisted percent is the truth.
     */
    public static function resolveTierForPercent(int $percent): ?array
    {
        if ($percent <= 0) {
            return null;
        }

        foreach (self::TIERS as $tier) {
            if ($tier['percent'] === $percent) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Pricing config exposed to the client. The pricing summary on
     * the booking pages uses these values to mirror the server-side
     * math. Keep this in sync with `calculate()`.
     *
     * Legacy keys `min_tickets` + `discount_percent` are preserved
     * for any older consumer; new code reads the `tiers` array.
     *
     * @return array{
     *   min_tickets:int,
     *   discount_percent:int,
     *   tiers: array<int, array{min:int,percent:int,family:string,label_key:string,phrase_key:string,badge:string}>,
     * }
     */
    public static function toJs(): array
    {
        return [
            'min_tickets' => self::BULK_MIN_TICKETS,
            'discount_percent' => self::BULK_DISCOUNT_PERCENT,
            'tiers' => self::TIERS,
        ];
    }
}
