<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'country',
        'capacity',
        'type',
    ];

    /**
     * Get the events for the venue
     */
    public function events()
    {
        return $this->belongsToMany(Event::class, 'venue_event');
    }
}
