<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomBlock extends Model
{
    protected $fillable = [
        'event_id',
        'hotel_id',
        'hotel_code',
        'hotel_name',
        'room_type',
        'rate',
        'currency',
        'check_in',
        'check_out',
        'allotment',
        'picked_up',
        'cutoff_date',
        'notes',
    ];

    protected $casts = [
        'check_in'    => 'date',
        'check_out'   => 'date',
        'cutoff_date' => 'date',
        'rate'        => 'decimal:2',
        'allotment'   => 'integer',
        'picked_up'   => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function getNightsAttribute(): int
    {
        return $this->check_in->diffInDays($this->check_out);
    }

    public function getRemainingAttribute(): int
    {
        return $this->allotment - $this->picked_up;
    }

    public function getRoomNightsAttribute(): int
    {
        return $this->allotment * $this->nights;
    }

    public function getContractValueAttribute(): float
    {
        return $this->room_nights * (float) $this->rate;
    }
}
