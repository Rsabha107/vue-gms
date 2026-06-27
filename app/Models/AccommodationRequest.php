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
        'hotel_id',
        'hotel_code',
        'hotel_name',
        'room_type',
        'check_in',
        'check_out',
        'nights',
        'notes',
        'guest_remarks',
        'checked_in_at',
        'checked_out_at',
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
        'check_in'  => 'date',
        'check_out' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
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

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(AccommodationRequest::class, 'fulfilled_by_id');
    }

    public function fulfillsRequest(): BelongsTo
    {
        return $this->belongsTo(AccommodationRequest::class, 'fulfills_request_id');
    }

    public function scopeGuestRequests($query)
    {
        return $query->where('source', 'portal')->where('initiated_by', 'guest');
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
