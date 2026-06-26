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
        'completed_at',
        'fulfilled_by_id',
        'fulfills_request_id',
        'initiated_by',
        'source',
        'assigned_officer_id',
        'reminded_at',
        'escalated_at',
        'escalation_reason',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
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
        return $this->belongsTo(InvitationStatus::class, 'status_id');
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(TransportRequest::class, 'fulfilled_by_id');
    }

    public function fulfillsRequest(): BelongsTo
    {
        return $this->belongsTo(TransportRequest::class, 'fulfills_request_id');
    }

    public function scopeGuestRequests($query)
    {
        return $query->where('source', 'portal')->where('initiated_by', 'guest');
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
