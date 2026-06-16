<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlightRequest extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'code',
        'status',
        'ref',
        'pax',
        'requested_at',
        'note',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function legs(): HasMany
    {
        return $this->hasMany(FlightLeg::class)->orderBy('sort');
    }

    /**
     * Calculate stats for the flight requests queue.
     * Returns {total, new, confirmed, change, cancelled}.
     */
    public static function statsFor($rows): array
    {
        $by = collect($rows)->countBy('status');
        return [
            'total'     => count($rows),
            'new'       => $by['new'] ?? 0,
            'confirmed' => $by['confirmed'] ?? 0,
            'change'    => $by['change'] ?? 0,
            'cancelled' => $by['cancelled'] ?? 0,
        ];
    }
}
