<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatingRow extends Model
{
    protected $fillable = ['seating_block_id', 'label', 'seats', 'aisles', 'walkway', 'position'];

    protected $casts = [
        'aisles'  => 'array',
        'walkway' => 'boolean',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(SeatingBlock::class, 'seating_block_id');
    }
}
