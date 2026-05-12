<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShowTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'show_id',
        'date',
        'time',
        'ticket_price',
        'total_tickets',
        'available_tickets',
        'is_sold_out',
    ];

    protected $casts = [
        'date' => 'date',
        'is_sold_out' => 'boolean',
    ];

    // العلاقة مع العرض نفسه
    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    /**
     * Canonical "when does this showtime start, as a UTC timestamp".
     *
     * The DB stores the local date and local time as two separate columns
     * (`date` cast as a Carbon date, `time` as a raw string from a HTML5
     * `<input type="time">`). Different code paths used to glue those
     * pieces together inline — usually as
     * `Carbon::parse($s->date->format('Y-m-d') . ' ' . $s->time)` — but
     * each call site handled edge cases slightly differently:
     *   - the `time` column comes back as `'19:00'` from a Postgres
     *     timestamp-without-tz column on Railway, but as `'19:00:00'`
     *     from the historical seeder, and Carbon parses both happily —
     *     unless someone passes the raw value into JS;
     *   - some call sites forgot to pin the parse to `Africa/Cairo`, so
     *     the resulting Carbon picked up whatever `app.timezone` was at
     *     boot (or the server's, on stale opcache);
     *   - missing/null values blew up the view with an Exception before
     *     the try/catch could fire.
     *
     * Centralising the computation here means the thank-you countdown,
     * the show-detail ETA chips, and any future surface (calendar
     * exports, scheduled push reminders, …) all read the *same* UTC
     * Carbon. Returns `null` on any unparseable input so callers can
     * skip rendering the countdown block instead of crashing.
     */
    public function getStartsAtUtcAttribute(): ?\Carbon\Carbon
    {
        if (! $this->date || ! $this->time) {
            return null;
        }

        $dateStr = $this->date instanceof \DateTimeInterface
            ? $this->date->format('Y-m-d')
            : (string) $this->date;

        // Normalise the time string: strip microsecond / timezone tails
        // some drivers append (`19:00:00.000000`, `19:00:00+03`), and
        // pad short forms so Carbon's strict parsers stay happy.
        $rawTime = trim((string) $this->time);
        $rawTime = preg_replace('/[.+\-].*$/', '', $rawTime) ?: $rawTime;
        if (preg_match('/^\d{1,2}:\d{2}$/', $rawTime)) {
            $rawTime .= ':00';
        }

        try {
            return \Carbon\Carbon::parse(
                $dateStr.' '.$rawTime,
                config('app.timezone', 'Africa/Cairo')
            )->utc();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ShowTime::startsAtUtc parse failed', [
                'show_time_id' => $this->id,
                'date'         => $dateStr,
                'time'         => $rawTime,
                'error'        => $e->getMessage(),
            ]);
            return null;
        }
    }

    // 👈 دي اللي ناقصاك: كل الحجوزات المرتبطة بالميعاد ده
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'show_time_id');
    }

    public function bookedSeats()
    {
        return $this->hasMany(BookingSeat::class, 'show_time_id');
    }

    public function seatBlocks()
    {
        return $this->hasMany(SeatBlock::class, 'show_time_id');
    }

    /**
     * IDs of seats that are unavailable for this show time — either booked
     * (pending/approved) or admin-blocked.
     */
    public function unavailableSeatIds(): array
    {
        $bookedActive = $this->bookedSeats()
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['approved', 'pending']);
            })
            ->pluck('seat_id');

        $blocked = $this->seatBlocks()->pluck('seat_id');

        return $bookedActive->merge($blocked)->unique()->values()->all();
    }

    /**
     * Number of seats admin-blocked for this show time. Blocked seats are
     * operationally unavailable (kept out of the customer seat picker and
     * subtracted from remaining inventory) but are NOT paid tickets — they
     * never contribute to revenue or ticket-sale totals.
     *
     * Always returns 0 for non-seatmap shows ("Other" theater type) since
     * there is no seat layout to block against.
     */
    public function blockedSeatsCount(): int
    {
        $this->loadMissing('show');
        if (! $this->show || ! $this->show->usesSeatMap()) {
            return 0;
        }

        return (int) $this->seatBlocks()->count();
    }

    /**
     * Customer-facing remaining-ticket count.
     *
     * Subtracts both customer bookings (pending + approved) and, for
     * seatmap-backed shows, admin-blocked seats — so the storefront never
     * advertises capacity that the seat picker would refuse to actually
     * sell. For "Other" / manual-capacity shows this falls back to the
     * existing `total_tickets - reserved` calculation, since those shows
     * have no seat layout to block against.
     */
    public function effectiveRemainingTickets(): int
    {
        $reserved = (int) $this->bookings()
            ->whereIn('status', ['approved', 'pending'])
            ->sum('tickets_count');

        return max(0, (int) $this->total_tickets - $reserved - $this->blockedSeatsCount());
    }
}
