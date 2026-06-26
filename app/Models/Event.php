<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subtitle',
        'location',
        'venue',
        'date_start',
        'date_end',
        'logo',
        'active_flag',
        'portal_enabled',
        'portal_auth_mode',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'active_flag' => 'boolean',
        'portal_enabled' => 'boolean',
    ];

    /**
     * Get the venues for the event
     */
    public function venues()
    {
        return $this->belongsToMany(\App\Models\Venue::class, 'event_venue');
    }

    /**
     * Get all guests for this event
     */
    public function guests()
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * Get all flight requests for this event
     */
    public function flightRequests()
    {
        return $this->hasMany(FlightRequest::class);
    }

    /**
     * Get all accommodation requests for this event
     */
    public function accommodationRequests()
    {
        return $this->hasMany(AccommodationRequest::class);
    }

    /**
     * Scope to get only active events
     */
    public function scopeActive($query)
    {
        return $query->where('active_flag', true);
    }

    /**
     * Get formatted date range
     */
    public function getFormattedDatesAttribute(): string
    {
        $sameYear = $this->date_start->year === $this->date_end->year;
        $startFmt = $sameYear ? 'M j' : 'M j, Y';
        return $this->date_start->format($startFmt) . ' – ' . $this->date_end->format('M j, Y');
    }
}
