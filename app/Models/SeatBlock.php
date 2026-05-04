<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatBlock extends Model
{
    protected $fillable = [
        'show_time_id',
        'seat_id',
    ];

    public function showTime(): BelongsTo
    {
        return $this->belongsTo(ShowTime::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }
}
