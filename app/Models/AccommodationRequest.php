<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationRequest extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'code',
        'status_id',
        'hotel_code',
        'hotel_name',
        'room_type',
        'check_in',
        'check_out',
        'nights',
        'notes',
        'initiated_by',
        'source',
        'assigned_officer_id',
        'reminded_at',
        'escalated_at',
        'escalation_reason',
    ];

    protected $casts = [
        'check_in'  => 'date',
        'check_out' => 'date',
        'reminded_at' => 'datetime',
        'escalated_at' => 'datetime',
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
        return $this->belongsTo(AccommodationStatus::class, 'status_id');
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    /**
     * Calculate stats for the accommodation requests queue.
     * Returns {total, new, confirmed, change, cancelled}.
     */
    public static function statsFor($rows): array
    {
        $by = collect($rows)->countBy('status_id');
        return [
            'total'     => count($rows),
            'new'       => $by['new'] ?? 0,
            'confirmed' => $by['confirmed'] ?? 0,
            'change'    => $by['change'] ?? 0,
            'cancelled' => $by['cancelled'] ?? 0,
        ];
    }
}
