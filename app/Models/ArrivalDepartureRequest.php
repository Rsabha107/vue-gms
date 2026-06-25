<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArrivalDepartureRequest extends Model
{
    protected $fillable = [
        'guest_id',
        'status',
        'type',
        'flight_no',
        'terminal',
        'datetime',
        'lounge',
        'greeter',
        'notes',
        'initiated_by',
        'source',
        'assigned_officer_id',
        'reminded_at',
        'escalated_at',
        'escalation_reason',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'reminded_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }
}
