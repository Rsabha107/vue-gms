<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Floorplan extends Model
{
    protected $fillable = ['event_id', 'name', 'data'];

    protected $casts = ['data' => 'array'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
