<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'reference_number',
        'name',
        'firstName',
        'lastName',
        'title',
        'guestType',
        'qid',
        'tier',
        'group_id',
        'nationality',
        'status_id',
        'email',
        'phone',
        'host',
        'hotel',
        'dietaryNotes',
        'notes',
        'facilities',
        'facilityOverrides',
        'flightPreferences',
        'accommodationPreferences',
        'transportationPreferences',
        'companionList',
        'companions',
    ];

    protected $casts = [
        'facilities' => 'array',
        'facilityOverrides' => 'array',
        'companionList' => 'array',
        'companions' => 'integer',
    ];

    protected $attributes = [
        'guestType' => 'local',
        'status_id' => 'invited',
    ];

    /**
     * Get the event for this guest
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the status for this guest
     */
    public function status()
    {
        return $this->belongsTo(GuestStatus::class, 'status_id');
    }

    /**
     * Get the tier information for this guest
     */
    public function tierInfo()
    {
        return $this->belongsTo(ServiceLevel::class, 'tier', 'id');
    }

    /**
     * Get the group for this guest
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    /**
     * Get all invitations for this guest
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'guest_id', 'id');
    }

    /**
     * Get all flight requests for this guest
     */
    public function flightRequests()
    {
        return $this->hasMany(FlightRequest::class);
    }

    /**
     * Get the final list of facilities for this guest (tier baseline + overrides)
     * 
     * @return array
     */
    public function getFinalFacilitiesAttribute(): array
    {
        // Get tier baseline facilities
        $tier = $this->tierInfo;
        $tierFacilities = $tier ? ($tier->facilities ?? []) : [];
        
        // Get overrides
        $overrides = $this->facilityOverrides ?? ['added' => [], 'removed' => []];
        $added = $overrides['added'] ?? [];
        $removed = $overrides['removed'] ?? [];
        
        // Filter out removed and add custom ones
        $baseFacilities = array_filter($tierFacilities, fn($f) => !in_array($f, $removed));
        
        return array_values(array_unique([...$baseFacilities, ...$added]));
    }
    
    /**
     * Check if guest has a specific facility (considering overrides)
     * 
     * @param string $facility
     * @return bool
     */
    public function hasFacility(string $facility): bool
    {
        return in_array($facility, $this->final_facilities);
    }
    
    /**
     * Get only custom added facilities
     * 
     * @return array
     */
    public function getCustomFacilitiesAttribute(): array
    {
        $overrides = $this->facilityOverrides ?? ['added' => [], 'removed' => []];
        return $overrides['added'] ?? [];
    }
    
    /**
     * Get only removed facilities
     * 
     * @return array
     */
    public function getRemovedFacilitiesAttribute(): array
    {
        $overrides = $this->facilityOverrides ?? ['added' => [], 'removed' => []];
        return $overrides['removed'] ?? [];
    }
    
    /**
     * Check if guest has any facility customizations
     * 
     * @return bool
     */
    public function hasCustomFacilities(): bool
    {
        $overrides = $this->facilityOverrides ?? ['added' => [], 'removed' => []];
        return !empty($overrides['added']) || !empty($overrides['removed']);
    }
    
    /**
     * Scope: Guests with a specific facility
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $facility
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFacility($query, string $facility)
    {
        return $query->whereHas('tierInfo', function ($q) use ($facility) {
            $q->whereJsonContains('facilities', $facility);
        })->where(function ($q) use ($facility) {
            // Not removed
            $q->whereJsonDoesntContain('facilityOverrides->removed', $facility)
              ->orWhereNull('facilityOverrides');
        })->orWhereJsonContains('facilityOverrides->added', $facility);
    }
    
    /**
     * Scope: Guests with custom facilities
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCustomFacilities($query)
    {
        return $query->where(function ($q) {
            $q->whereJsonLength('facilityOverrides->added', '>', 0)
              ->orWhereJsonLength('facilityOverrides->removed', '>', 0);
        });
    }

    /**
     * Get all accommodation requests for this guest
     */
    public function accommodationRequests()
    {
        return $this->hasMany(AccommodationRequest::class);
    }

    /**
     * Scope for confirmed guests
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status_id', 'confirmed');
    }

    /**
     * Scope for pending guests
     */
    public function scopePending($query)
    {
        return $query->where('status_id', 'pending');
    }

    /**
     * Scope for invited guests
     */
    public function scopeInvited($query)
    {
        return $query->where('status_id', 'invited');
    }

    /**
     * Scope for declined guests
     */
    public function scopeDeclined($query)
    {
        return $query->where('status_id', 'declined');
    }

    /**
     * Scope for local guests
     */
    public function scopeLocal($query)
    {
        return $query->where('guestType', 'local');
    }

    /**
     * Scope for international guests
     */
    public function scopeInternational($query)
    {
        return $query->where('guestType', 'international');
    }

    /**
     * Scope for searching guests
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%")
              ->orWhere('id', 'like', "%{$search}%");
        });
    }
}
