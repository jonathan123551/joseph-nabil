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

   /**
    * Theater types whose physical capacity is defined by a real seat map.
    * Add future seatmap-backed theaters here so the auto-capacity logic
    * (admin form lock, automatic total_tickets sync, etc.) picks them up
    * without further code changes.
    */
   public const SEAT_MAP_THEATERS = [
       self::THEATER_ANBA_RUWEIS,
   ];


    public function showTimes()
    {
        return $this->hasMany(ShowTime::class);
    }

    /**
     * True when this show's venue has a real seat layout — in which case
     * the total ticket count is derived from the seat table instead of
     * being entered manually by the admin.
     */
    public function usesSeatMap(): bool
    {
        return in_array($this->theater_type, self::SEAT_MAP_THEATERS, true);
    }

    /**
     * Resolve the Theater row for this show, if any. Currently only
     * Anba Ruweis maps to a stored theater; future seatmap theaters can
     * be wired in here by adding cases.
     */
    public function theater(): ?Theater
    {
        return match ($this->theater_type) {
            self::THEATER_ANBA_RUWEIS => Theater::anbaRuweis(),
            default                   => null,
        };
    }

    /**
     * Per-section seat counts pulled from the live seat layout, e.g.
     *   ['hall' => 420, 'balcony' => 180, 'total' => 600]
     *
     * Returns null when the show is not seatmap-backed (e.g. "Other"
     * custom venues), so callers can fall back to manual entry. Counts
     * are memoized on the model instance to avoid hitting the seats
     * table multiple times during a single render.
     */
    public function seatMapCapacity(): ?array
    {
        if (!$this->usesSeatMap()) {
            return null;
        }

        if (isset($this->seatMapCapacityCache)) {
            return $this->seatMapCapacityCache;
        }

        $theater = $this->theater();
        if (!$theater) {
            return $this->seatMapCapacityCache = null;
        }

        $counts = $theater->seats()
            ->selectRaw('section, COUNT(*) as c')
            ->groupBy('section')
            ->pluck('c', 'section')
            ->all();

        $hall    = (int) ($counts[Theater::SECTION_HALL] ?? 0);
        $balcony = (int) ($counts[Theater::SECTION_BALCONY] ?? 0);

        return $this->seatMapCapacityCache = [
            'hall'    => $hall,
            'balcony' => $balcony,
            'total'   => $hall + $balcony,
        ];
    }

    /** @var array<string,int>|null Memoized seat-map capacity. */
    protected ?array $seatMapCapacityCache = null;
}
