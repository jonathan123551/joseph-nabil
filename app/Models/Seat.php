<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    protected $fillable = [
        'theater_id',
        'section',
        'row_letter',
        'seat_number',
        'group_side',
        'display_order',
    ];

    protected $casts = [
        'seat_number'   => 'integer',
        'display_order' => 'integer',
    ];

    public function theater(): BelongsTo
    {
        return $this->belongsTo(Theater::class);
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function seatBlocks(): HasMany
    {
        return $this->hasMany(SeatBlock::class);
    }

    public function label(): string
    {
        return $this->row_letter . $this->seat_number;
    }
}
