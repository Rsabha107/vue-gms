<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportRequest extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'code',
        'status_id',
        'type',
        'vehicle',
        'pickup_location',
        'dropoff_location',
        'datetime',
        'driver',
        'notes',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TransportStatus::class, 'status_id');
    }

    /**
     * Calculate stats for the transport requests queue.
     * Returns {total, pending, confirmed, cancelled}.
     */
    public static function statsFor($rows): array
    {
        $by = collect($rows)->countBy('status_id');
        return [
            'total' => count($rows),
            'pending' => $by->get('pending', 0),
            'confirmed' => $by->get('confirmed', 0),
            'cancelled' => $by->get('cancelled', 0),
        ];
    }
}
