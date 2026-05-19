<?php

namespace App\Support;

/**
 * Centralised pricing rules for ticket bookings.
 *
 * Bulk-discount offer:
 *   1–4 tickets  → normal pricing
 *   5+  tickets  → 20% off the total
 *
 * The same numbers are mirrored to the client via `toJs()` so the
 * pricing summary on the booking pages computes identical values
 * to the server. The server remains the source of truth — the
 * persisted `bookings.total_price` is always computed here in
 * BookingController, never trusted from the client.
 */
class BookingPricing
{
    /** Minimum ticket count that activates the bulk discount. */
    public const BULK_MIN_TICKETS = 5;

    /** Discount percentage applied when the bulk threshold is reached. */
    public const BULK_DISCOUNT_PERCENT = 20;

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
     * }
     */
    public static function calculate(int $unitPrice, int $ticketsCount): array
    {
        $unitPrice    = max(0, $unitPrice);
        $ticketsCount = max(0, $ticketsCount);

        $original = $unitPrice * $ticketsCount;
        $qualifies = $ticketsCount >= self::BULK_MIN_TICKETS;

        $percent  = $qualifies ? self::BULK_DISCOUNT_PERCENT : 0;
        // Use integer math to avoid float rounding drift on the
        // stored total. Egyptian Pound bookings are always whole
        // pounds in this app, so we floor the discount to the
        // nearest pound (i.e. keep the customer-friendly side).
        $discount = $qualifies ? (int) floor(($original * $percent) / 100) : 0;
        $total    = max(0, $original - $discount);

        $toUnlock = $qualifies
            ? 0
            : max(0, self::BULK_MIN_TICKETS - $ticketsCount);

        return [
            'unit_price'       => $unitPrice,
            'tickets_count'    => $ticketsCount,
            'original_price'   => $original,
            'discount_percent' => $percent,
            'discount_amount'  => $discount,
            'total_price'      => $total,
            'qualifies'        => $qualifies,
            'tickets_to_unlock'=> $toUnlock,
        ];
    }

    /**
     * Pricing config exposed to the client. The pricing summary on
     * the booking pages uses these values to mirror the server-side
     * math. Keep this in sync with `calculate()`.
     *
     * @return array{min_tickets:int,discount_percent:int}
     */
    public static function toJs(): array
    {
        return [
            'min_tickets'      => self::BULK_MIN_TICKETS,
            'discount_percent' => self::BULK_DISCOUNT_PERCENT,
        ];
    }
}
