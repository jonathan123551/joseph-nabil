<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSeat extends Model
{
    protected $fillable = [
        'booking_id',
        'seat_id',
        'show_time_id',
        'section',
        'row_letter',
        'seat_number',
        'price',
    ];

    protected $casts = [
        'seat_number' => 'integer',
        'price'       => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function showTime(): BelongsTo
    {
        return $this->belongsTo(ShowTime::class);
    }
}
