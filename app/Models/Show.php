<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
   protected $fillable = [
    'title',
    'description',
    'poster_path',
    'is_active',
    'ticket_template_path',
    'ticket_qr_x',
    'ticket_qr_y',
    'ticket_qr_size',
    'poster_public_id',
    'ticket_template_public_id',
    'theater_type',
    'balcony_price',
    'hall_price',
];

   public const THEATER_ANBA_RUWEIS = 'anba_ruweis';
   public const THEATER_OTHER       = 'other';

   public const THEATER_TYPES = [
       self::THEATER_ANBA_RUWEIS => 'مسرح الأنبا رويس',
       self::THEATER_OTHER       => 'Other',
   ];


    public function showTimes()
    {
        return $this->hasMany(ShowTime::class);
    }
}
