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

        $blocked = 0;
        $this->loadMissing('show');
        if ($this->show && $this->show->usesSeatMap()) {
            $blocked = (int) $this->seatBlocks()->count();
        }

        return max(0, (int) $this->total_tickets - $reserved - $blocked);
    }
}
