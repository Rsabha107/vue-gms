<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'area',
        'stars',
        'active',
    ];

    protected $casts = [
        'stars'  => 'integer',
        'active' => 'boolean',
    ];

    public function roomBlocks(): HasMany
    {
        return $this->hasMany(RoomBlock::class);
    }

    public function accommodationRequests(): HasMany
    {
        return $this->hasMany(AccommodationRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
