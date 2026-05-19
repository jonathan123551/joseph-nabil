<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
    'show_time_id',
    'full_name',
    'phone',
    'tickets_count',
    'total_price',

    // الخصم (bulk-discount offer — see App\Support\BookingPricing)
    'original_price',
    'discount_percent',
    'discount_amount',

    // الدفع
    'payment_method',
    'payment_status',
    'transfer_screenshot_path',
    'paid_at',

    // الحجز
    'status',              // ✅ ده سبب المشكلة
    'reference_code',
    'qr_code_path',        // ✅ ده سبب المشكلة
    'admin_notes',         // ✅ للرفض

    // الإدارة
    'approved_by_admin_id',
    'approved_at',

    // واتساب
    'whatsapp_sent',
    'whatsapp_sent_at',

    'transfer_screenshot_public_id',
    'qr_code_public_id',
];


    protected $casts = [
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'integer',
    ];

    /**
     * True when the bulk-discount offer was applied to this booking.
     * Safe against legacy rows (pre-migration) which carry NULL
     * original_price / 0 discount.
     */
    public function hasDiscount(): bool
    {
        return (int) $this->discount_percent > 0
            && (float) $this->discount_amount > 0;
    }

    public function showTime()
    {
        return $this->belongsTo(ShowTime::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function seats()
    {
        return $this->hasMany(BookingSeat::class);
    }
}
