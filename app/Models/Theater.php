<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theater extends Model
{
    public const SLUG_ANBA_RUWEIS = 'anba-ruweis';

    public const SECTION_BALCONY = 'balcony';
    public const SECTION_HALL    = 'hall';

    public const SECTION_LABELS = [
        self::SECTION_BALCONY => 'بلكون',
        self::SECTION_HALL    => 'صالة',
    ];

    protected $fillable = [
        'name',
        'slug',
    ];

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public static function anbaRuweis(): ?self
    {
        return static::where('slug', self::SLUG_ANBA_RUWEIS)->first();
    }
}
