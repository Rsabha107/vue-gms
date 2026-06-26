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

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function legs(): HasMany
    {
        return $this->hasMany(FlightLeg::class)->orderBy('sort');
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(FlightRequest::class, 'fulfilled_by_id');
    }

    public function fulfillsRequest(): BelongsTo
    {
        return $this->belongsTo(FlightRequest::class, 'fulfills_request_id');
    }

    public function scopeGuestRequests($query)
    {
        return $query->where('source', 'portal')->where('initiated_by', 'guest');
    }

    public function scopePendingGuestRequests($query)
    {
        return $query->guestRequests()->whereNull('fulfilled_by_id');
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
