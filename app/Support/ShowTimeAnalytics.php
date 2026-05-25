<?php

namespace App\Support;

use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Theater;
use Illuminate\Support\Collection;

/**
 * Pre-computes the analytics displayed on the admin "Show Times / Revenue"
 * dashboard for a single showtime.
 *
 * All numbers are derived from already-loaded relations on the ShowTime
 * model — the caller is responsible for eager-loading
 * `bookings.seats` (and `seatBlocks` for seatmap shows) before calling
 * `compute()`. This keeps the dashboard view free of N+1 queries even
 * when a show has dozens of showtimes.
 *
 * Money columns are kept as integers (EGP, no fractional currency) so
 * the view can pass them straight into `number_format()` / Blade without
 * casting.
 *
 * The class deliberately handles BOTH theater types:
 *   - Anba Ruweis (seatmap, hall + balcony pricing) — produces a
 *     per-section breakdown.
 *   - "Other" theaters (manual capacity, single ticket_price) — the
 *     section breakdown is null and the view skips that block.
 */
class ShowTimeAnalytics
{
    /**
     * Compute the full analytics payload for one showtime.
     *
     * @return array{
     *   capacity: int,
     *   blocked: int,
     *   remaining: int,
     *   approved_tickets: int,
     *   pending_tickets: int,
     *   rejected_tickets: int,
     *   sold_tickets: int,
     *   occupancy_percent: float,
     *   sold_percent: float,
     *   pending_percent: float,
     *   blocked_percent: float,
     *   remaining_percent: float,
     *   approved_bookings: int,
     *   pending_bookings: int,
     *   rejected_bookings: int,
     *   approved_revenue: int,
     *   pending_revenue: int,
     *   total_revenue: int,
     *   total_discount: int,
     *   discounted_bookings: int,
     *   tier_breakdown: array<int, array{bookings:int, tickets:int, discount_amount:int, revenue:int}>,
     *   average_booking_value: int,
     *   conversion_percent: float,
     *   uses_section_pricing: bool,
     *   hall: ?array,
     *   balcony: ?array,
     *   hall_price: int,
     *   balcony_price: int,
     *   ticket_price: int,
     * }
     */
    public static function compute(ShowTime $showTime): array
    {
        $show = $showTime->show;
        $usesSectionPricing = $show
            && $show->theater_type === Show::THEATER_ANBA_RUWEIS;

        $bookings = $showTime->bookings ?? collect();

        // Booking buckets keyed by status. Using filter() over the
        // eager-loaded collection is the cheap path — Laravel's loaded
        // collection ops are in-memory, no extra queries.
        $approved = $bookings->where('status', 'approved');
        $pending = $bookings->where('status', 'pending');
        $rejected = $bookings->where('status', 'rejected');

        $capacity = (int) $showTime->total_tickets;
        $blocked = self::resolveBlocked($showTime, $show);

        $approvedTickets = (int) $approved->sum('tickets_count');
        $pendingTickets = (int) $pending->sum('tickets_count');
        $rejectedTickets = (int) $rejected->sum('tickets_count');
        $soldTickets = $approvedTickets + $pendingTickets;

        // Remaining mirrors ShowTime::effectiveRemainingTickets() but
        // works off the already-loaded collection so this method stays
        // query-free in the hot loop.
        $remaining = max(0, $capacity - $soldTickets - $blocked);

        $approvedRevenue = (int) round((float) $approved->sum('total_price'));
        $pendingRevenue = (int) round((float) $pending->sum('total_price'));
        $totalRevenue = $approvedRevenue + $pendingRevenue;

        $discountedApproved = $approved->filter(
            fn ($b) => (float) $b->discount_amount > 0
        );
        $totalDiscount = (int) round((float) $discountedApproved->sum('discount_amount'));
        $discountedBookings = $discountedApproved->count();

        // Per-tier breakdown — keyed by the BookingPricing tier percent
        // (20, 30, 40, 50). Reads each booking's STORED discount_percent
        // so legacy rows keep matching what the customer actually paid
        // (see BookingPricing::resolveTierForPercent).
        $tierBreakdown = self::computeTierBreakdown($approved);

        $averageBookingValue = $approved->isNotEmpty()
            ? (int) round($approvedRevenue / $approved->count())
            : 0;

        // Conversion = approved share of "decided" bookings (approved
        // + rejected). Pending is intentionally excluded — those are
        // still in the funnel. Returns 0 when no decisions yet.
        $decided = $approved->count() + $rejected->count();
        $conversionPercent = $decided > 0
            ? round(($approved->count() / $decided) * 100, 1)
            : 0.0;

        $hall = null;
        $balcony = null;
        if ($usesSectionPricing) {
            $hall = self::sectionAnalytics($bookings, Theater::SECTION_HALL);
            $balcony = self::sectionAnalytics($bookings, Theater::SECTION_BALCONY);
        }

        return [
            'capacity' => $capacity,
            'blocked' => $blocked,
            'remaining' => $remaining,
            'approved_tickets' => $approvedTickets,
            'pending_tickets' => $pendingTickets,
            'rejected_tickets' => $rejectedTickets,
            'sold_tickets' => $soldTickets,
            'occupancy_percent' => self::percent($soldTickets, $capacity),
            'sold_percent' => self::percent($approvedTickets, $capacity),
            'pending_percent' => self::percent($pendingTickets, $capacity),
            'blocked_percent' => self::percent($blocked, $capacity),
            'remaining_percent' => self::percent($remaining, $capacity),
            'approved_bookings' => $approved->count(),
            'pending_bookings' => $pending->count(),
            'rejected_bookings' => $rejected->count(),
            'approved_revenue' => $approvedRevenue,
            'pending_revenue' => $pendingRevenue,
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'discounted_bookings' => $discountedBookings,
            'tier_breakdown' => $tierBreakdown,
            'average_booking_value' => $averageBookingValue,
            'conversion_percent' => $conversionPercent,
            'uses_section_pricing' => $usesSectionPricing,
            'hall' => $hall,
            'balcony' => $balcony,
            'hall_price' => (int) ($show->hall_price ?? 0),
            'balcony_price' => (int) ($show->balcony_price ?? 0),
            'ticket_price' => (int) $showTime->ticket_price,
        ];
    }

    /**
     * Break the approved-bookings collection out by discount tier
     * (20 / 30 / 40 / 50 %). The "0 %" rows (no discount) are
     * intentionally omitted from the breakdown — admins already see
     * the non-discounted bookings in the main `approved_revenue` /
     * `approved_bookings` KPIs.
     *
     * @return array<int, array{
     *   bookings:int,
     *   tickets:int,
     *   discount_amount:int,
     *   revenue:int,
     *   percent:int,
     *   family:string,
     *   label:string,
     *   badge:string,
     * }>
     */
    private static function computeTierBreakdown($approved): array
    {
        $out = [];

        foreach (BookingPricing::TIERS as $tier) {
            $percent = (int) $tier['percent'];
            $rows = $approved->filter(
                fn ($b) => (int) ($b->discount_percent ?? 0) === $percent
            );

            if ($rows->isEmpty()) {
                continue;
            }

            $isChurch = $tier['family'] === BookingPricing::FAMILY_CHURCH;
            $out[$percent] = [
                'bookings' => $rows->count(),
                'tickets' => (int) $rows->sum('tickets_count'),
                'discount_amount' => (int) round((float) $rows->sum('discount_amount')),
                'revenue' => (int) round((float) $rows->sum('total_price')),
                'percent' => $percent,
                'family' => $tier['family'],
                'label' => $isChurch ? 'خصومات الكنائس' : 'خصومات العيلة',
                'badge' => $tier['badge'],
            ];
        }

        return $out;
    }

    /**
     * Aggregate analytics for one section across all of a showtime's
     * bookings. Anba bookings are constrained to a single section per
     * booking (see BookingController::storeSeatBased), so the discount
     * attribution by section is exact — no proportional allocation.
     *
     * Returns:
     *   tickets_sold       — approved + pending ticket count
     *   tickets_approved   — approved-only
     *   tickets_pending    — pending-only
     *   list_revenue       — sum(booking_seats.price) for approved
     *                        seats in this section (pre-discount list)
     *   final_revenue      — sum(bookings.total_price) for approved
     *                        bookings in this section (post-discount)
     *   discount_amount    — list_revenue − final_revenue, floored at 0
     */
    private static function sectionAnalytics($bookings, string $section): array
    {
        // Anba: each booking's seats are all in one section. Tag the
        // booking with its section using the first booking_seat.
        $sectionBookings = $bookings->filter(function ($booking) use ($section) {
            $seats = $booking->seats ?? collect();
            if ($seats->isEmpty()) {
                return false;
            }

            return $seats->first()->section === $section;
        });

        $approved = $sectionBookings->where('status', 'approved');
        $pending = $sectionBookings->where('status', 'pending');

        $ticketsApproved = (int) $approved->sum('tickets_count');
        $ticketsPending = (int) $pending->sum('tickets_count');
        $ticketsSold = $ticketsApproved + $ticketsPending;

        // list_revenue: per-seat catalog price summed across approved
        // bookings — what the show would have grossed at list before
        // any bulk-discount was applied.
        $listRevenue = (int) round((float) $approved
            ->flatMap(fn ($b) => $b->seats ?? collect())
            ->where('section', $section)
            ->sum('price'));

        $finalRevenue = (int) round((float) $approved->sum('total_price'));

        $discountAmount = max(0, $listRevenue - $finalRevenue);

        return [
            'section' => $section,
            'tickets_sold' => $ticketsSold,
            'tickets_approved' => $ticketsApproved,
            'tickets_pending' => $ticketsPending,
            'list_revenue' => $listRevenue,
            'final_revenue' => $finalRevenue,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Resolve the blocked-seat count from an already-loaded collection
     * when available — falls back to the model accessor otherwise (for
     * code paths that compute analytics without eager-loading seatBlocks).
     */
    private static function resolveBlocked(ShowTime $showTime, ?Show $show): int
    {
        if (! $show || ! $show->usesSeatMap()) {
            return 0;
        }

        if ($showTime->relationLoaded('seatBlocks')) {
            return $showTime->seatBlocks->count();
        }

        return $showTime->blockedSeatsCount();
    }

    /**
     * Safe percentage helper — returns 0 when the denominator is 0
     * (instead of a NaN that would render as "NaN%" in the view).
     * Capped at 100 in case a stale row temporarily holds more sold
     * tickets than capacity.
     */
    private static function percent(int $part, int $whole): float
    {
        if ($whole <= 0) {
            return 0.0;
        }

        return round(min(100, ($part / $whole) * 100), 1);
    }

    /**
     * Aggregate top-level KPIs across every showtime on the dashboard.
     * Used by the page-header KPI strip so admins can scan one number
     * each for tickets, occupancy, revenue, and savings.
     *
     * @param  iterable<array>  $perShowtime  output of compute() per row
     */
    public static function totals(iterable $perShowtime): array
    {
        $capacity = 0;
        $sold = 0;
        $approvedRevenue = 0;
        $pendingRevenue = 0;
        $totalRevenue = 0;
        $totalDiscount = 0;
        $approvedBookings = 0;
        $pendingBookings = 0;
        $count = 0;
        // Per-tier rollup across every showtime — same shape as the
        // per-showtime `tier_breakdown` so the dashboard view can
        // iterate either source identically.
        $tierBreakdown = [];

        foreach ($perShowtime as $row) {
            $capacity += (int) ($row['capacity'] ?? 0);
            $sold += (int) ($row['sold_tickets'] ?? 0);
            $approvedRevenue += (int) ($row['approved_revenue'] ?? 0);
            $pendingRevenue += (int) ($row['pending_revenue'] ?? 0);
            $totalRevenue += (int) ($row['total_revenue'] ?? 0);
            $totalDiscount += (int) ($row['total_discount'] ?? 0);
            $approvedBookings += (int) ($row['approved_bookings'] ?? 0);
            $pendingBookings += (int) ($row['pending_bookings'] ?? 0);
            $count++;

            foreach (($row['tier_breakdown'] ?? []) as $percent => $tier) {
                if (! isset($tierBreakdown[$percent])) {
                    $tierBreakdown[$percent] = [
                        'percent' => (int) $tier['percent'],
                        'family' => $tier['family'],
                        'label' => $tier['label'],
                        'badge' => $tier['badge'],
                        'bookings' => 0,
                        'tickets' => 0,
                        'discount_amount' => 0,
                        'revenue' => 0,
                    ];
                }
                $tierBreakdown[$percent]['bookings'] += (int) ($tier['bookings'] ?? 0);
                $tierBreakdown[$percent]['tickets'] += (int) ($tier['tickets'] ?? 0);
                $tierBreakdown[$percent]['discount_amount'] += (int) ($tier['discount_amount'] ?? 0);
                $tierBreakdown[$percent]['revenue'] += (int) ($tier['revenue'] ?? 0);
            }
        }

        // Keep the breakdown ordered ascending by percent so the UI
        // renders 🎁 20 % → ⛪ 30 % → 💎 40 % → 👑 50 % left-to-right.
        ksort($tierBreakdown);

        return [
            'count' => $count,
            'capacity' => $capacity,
            'sold' => $sold,
            'occupancy_percent' => self::percent($sold, $capacity),
            'approved_revenue' => $approvedRevenue,
            'pending_revenue' => $pendingRevenue,
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'approved_bookings' => $approvedBookings,
            'pending_bookings' => $pendingBookings,
            'tier_breakdown' => $tierBreakdown,
        ];
    }
}
