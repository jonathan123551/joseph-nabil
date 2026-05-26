<?php

namespace Tests\Unit;

use App\Support\BookingPricing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the tiered bulk-discount pricing helper.
 *
 * Locks in the tier table exactly as advertised to the customer:
 *
 *   1–4   tickets  → no discount
 *   5–9   tickets  → 20% OFF   ·  family   ("خصومات العيلة")
 *   10–29 tickets  → 30% OFF   ·  church   ("خصومات الكنائس")
 *   30–49 tickets  → 40% OFF   ·  church
 *   50+   tickets  → 50% OFF   ·  church
 *
 * Also covers the 7 / 22 / 55 reference examples from the spec and
 * the edge cases (0 tickets, top-tier saturation, negative inputs,
 * legacy-percent classification).
 */
class BookingPricingTest extends TestCase
{
    public static function tierBoundariesProvider(): array
    {
        // [unitPrice, count, expectedPercent, expectedDiscount, expectedTotal, expectedToUnlock, expectedNextPercent]
        return [
            'zero tickets' => [100, 0, 0, 0, 0, 5, 20],
            'one ticket' => [100, 1, 0, 0, 100, 4, 20],
            'four — just shy' => [100, 4, 0, 0, 400, 1, 20],
            'five — first tier' => [100, 5, 20, 100, 400, 5, 30],
            'seven (spec)' => [100, 7, 20, 140, 560, 3, 30],
            'nine' => [100, 9, 20, 180, 720, 1, 30],
            'ten — second tier' => [100, 10, 30, 300, 700, 21, 40],
            'twenty-two (spec)' => [100, 22, 30, 660, 1540, 9, 40],
            'twenty-nine' => [100, 29, 30, 870, 2030, 2, 40],
            'thirty — just shy of premium church tier' => [100, 30, 30, 900, 2100, 1, 40],
            'thirty-one — premium church tier' => [100, 31, 40, 1240, 1860, 19, 50],
            'forty-nine' => [100, 49, 40, 1960, 2940, 1, 50],
            'fifty — top tier' => [100, 50, 50, 2500, 2500, 0, null],
            'fifty-five (spec)' => [100, 55, 50, 2750, 2750, 0, null],
            'one thousand' => [100, 1000, 50, 50000, 50000, 0, null],
        ];
    }

    #[Test]
    #[DataProvider('tierBoundariesProvider')]
    public function tier_table_matches_specification(
        int $unitPrice,
        int $count,
        int $expectedPercent,
        int $expectedDiscount,
        int $expectedTotal,
        int $expectedToUnlock,
        ?int $expectedNextPercent,
    ): void {
        $result = BookingPricing::calculate($unitPrice, $count);

        $this->assertSame($expectedPercent, $result['discount_percent'], 'discount_percent');
        $this->assertSame($expectedDiscount, $result['discount_amount'], 'discount_amount');
        $this->assertSame($expectedTotal, $result['total_price'], 'total_price');
        $this->assertSame($expectedToUnlock, $result['tickets_to_unlock'], 'tickets_to_unlock');
        $this->assertSame($expectedNextPercent, $result['next_discount_percent'], 'next_discount_percent');
        $this->assertSame($expectedPercent > 0, $result['qualifies'], 'qualifies');
    }

    #[Test]
    public function negative_inputs_are_clamped_to_zero(): void
    {
        $result = BookingPricing::calculate(-100, -5);

        $this->assertSame(0, $result['unit_price']);
        $this->assertSame(0, $result['tickets_count']);
        $this->assertSame(0, $result['original_price']);
        $this->assertSame(0, $result['discount_amount']);
        $this->assertSame(0, $result['total_price']);
        $this->assertFalse($result['qualifies']);
    }

    #[Test]
    public function discount_is_floored_to_keep_the_customer_friendly_side(): void
    {
        // 7 tickets * 33 EGP = 231 original; 20 % = 46.2 — floor to 46.
        $result = BookingPricing::calculate(33, 7);

        $this->assertSame(231, $result['original_price']);
        $this->assertSame(46, $result['discount_amount']);
        $this->assertSame(185, $result['total_price']);
    }

    #[Test]
    public function current_tier_carries_branding_for_family_bracket(): void
    {
        $result = BookingPricing::calculate(100, 7);

        $this->assertNotNull($result['current_tier']);
        $this->assertSame(BookingPricing::FAMILY_FAMILY, $result['current_tier']['family']);
        $this->assertSame('discount_family_label', $result['current_tier_label']);
    }

    #[Test]
    public function current_tier_carries_branding_for_church_bracket(): void
    {
        foreach ([15, 35, 75] as $count) {
            $result = BookingPricing::calculate(100, $count);

            $this->assertNotNull($result['current_tier'], "tier present for {$count}");
            $this->assertSame(
                BookingPricing::FAMILY_CHURCH,
                $result['current_tier']['family'],
                "church family for {$count}",
            );
            $this->assertSame('discount_church_label', $result['current_tier_label']);
        }
    }

    #[Test]
    public function next_tier_is_null_at_top(): void
    {
        $this->assertNull(BookingPricing::calculate(100, 50)['next_tier']);
        $this->assertNull(BookingPricing::calculate(100, 200)['next_tier']);
    }

    #[Test]
    public function resolve_tier_for_percent_classifies_legacy_rows(): void
    {
        $this->assertNull(BookingPricing::resolveTierForPercent(0));
        $this->assertSame(BookingPricing::FAMILY_FAMILY, BookingPricing::resolveTierForPercent(20)['family']);
        $this->assertSame(BookingPricing::FAMILY_CHURCH, BookingPricing::resolveTierForPercent(30)['family']);
        $this->assertSame(BookingPricing::FAMILY_CHURCH, BookingPricing::resolveTierForPercent(40)['family']);
        $this->assertSame(BookingPricing::FAMILY_CHURCH, BookingPricing::resolveTierForPercent(50)['family']);
        $this->assertNull(BookingPricing::resolveTierForPercent(99));
    }

    #[Test]
    public function to_js_keeps_legacy_keys_and_publishes_tiers(): void
    {
        $payload = BookingPricing::toJs();

        $this->assertArrayHasKey('min_tickets', $payload);
        $this->assertArrayHasKey('discount_percent', $payload);
        $this->assertSame(5, $payload['min_tickets']);
        $this->assertSame(20, $payload['discount_percent']);

        $this->assertArrayHasKey('tiers', $payload);
        $this->assertCount(4, $payload['tiers']);
        $this->assertSame([5, 10, 31, 50], array_column($payload['tiers'], 'min'));
        $this->assertSame([20, 30, 40, 50], array_column($payload['tiers'], 'percent'));
    }
}
