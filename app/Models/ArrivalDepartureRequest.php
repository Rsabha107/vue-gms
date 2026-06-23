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
    ];

    protected $casts = [
        'datetime' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
