<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'booking_id',
        // Per-ticket identity (PR #70): each ticket points to one
        // BookingSeat so the scanner can resolve "this QR -> this seat".
        // Nullable for manual / "Other" venue bookings.
        'booking_seat_id',
        'name',
        'phone',
        'ticket_code',
        'qr_image_path',
        'is_scanned',
        'scanned_at',
        'scanned_by_admin_id',
        'whatsapp_sent',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Booking::class);
    }

    /**
     * The specific seat this ticket represents (if any). Null on
     * tickets created through the manual / "Other" venue flow.
     */
    public function bookingSeat(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BookingSeat::class, 'booking_seat_id');
    }
}
