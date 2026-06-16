<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatingBlock extends Model
{
    protected $fillable = ['seating_template_id', 'code', 'label', 'tier', 'position'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SeatingTemplate::class, 'seating_template_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(SeatingRow::class)->orderBy('position');
    }
}
