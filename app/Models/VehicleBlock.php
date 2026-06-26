<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleBlock extends Model
{
    protected $fillable = [
        'event_id',
        'provider',
        'vehicle_type',
        'vehicle_class',
        'daily_rate',
        'currency',
        'start_date',
        'end_date',
        'fleet_size',
        'assigned',
        'cutoff_date',
        'notes',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'cutoff_date' => 'date',
        'daily_rate'  => 'decimal:2',
        'fleet_size'  => 'integer',
        'assigned'    => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getRemainingAttribute(): int
    {
        return $this->fleet_size - $this->assigned;
    }

    public function getVehicleDaysAttribute(): int
    {
        return $this->fleet_size * $this->days;
    }

    public function getContractValueAttribute(): float
    {
        return $this->vehicle_days * (float) $this->daily_rate;
    }
}
