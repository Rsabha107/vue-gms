<?php

namespace App\Services\Gms;

use App\Models\Guest;
use App\Models\ServiceLevel;
use Illuminate\Support\Collection;

class GuestFacilityReport
{
    /**
     * Get facility usage summary across all guests
     * 
     * @param int|null $eventId
     * @return array
     */
    public static function getFacilityUsageSummary(?int $eventId = null): array
    {
        $guests = Guest::query()
            ->with('tierInfo')
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->get();
        
        $usage = [];
        
        foreach ($guests as $guest) {
            foreach ($guest->final_facilities as $facility) {
                if (!isset($usage[$facility])) {
                    $usage[$facility] = [
                        'facility' => $facility,
                        'count' => 0,
                        'from_tier' => 0,
                        'custom_added' => 0,
                    ];
                }
                
                $usage[$facility]['count']++;
                
                // Check if from tier or custom
                $tierFacilities = $guest->tierInfo?->facilities ?? [];
                if (in_array($facility, $tierFacilities)) {
                    $usage[$facility]['from_tier']++;
                } else {
                    $usage[$facility]['custom_added']++;
                }
            }
        }
        
        // Sort by usage count
        uasort($usage, fn($a, $b) => $b['count'] <=> $a['count']);
        
        return array_values($usage);
    }
    
    /**
     * Get guests who have customized their facilities
     * 
     * @param int|null $eventId
     * @return Collection
     */
    public static function getGuestsWithCustomFacilities(?int $eventId = null): Collection
    {
        return Guest::query()
            ->with('tierInfo')
            ->withCustomFacilities()
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->get()
            ->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'tier' => $guest->tier,
                    'added_facilities' => $guest->custom_facilities,
                    'removed_facilities' => $guest->removed_facilities,
                ];
            });
    }
    
    /**
     * Get most frequently removed facilities by tier
     * (Helps identify if tier packages need adjustment)
     * 
     * @param int|null $eventId
     * @return array
     */
    public static function getMostRemovedFacilitiesByTier(?int $eventId = null): array
    {
        $guests = Guest::query()
            ->with('tierInfo')
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->whereJsonLength('facilityOverrides->removed', '>', 0)
            ->get();
        
        $tierRemovals = [];
        
        foreach ($guests as $guest) {
            $tier = $guest->tier;
            if (!isset($tierRemovals[$tier])) {
                $tierRemovals[$tier] = [];
            }
            
            foreach ($guest->removed_facilities as $facility) {
                if (!isset($tierRemovals[$tier][$facility])) {
                    $tierRemovals[$tier][$facility] = 0;
                }
                $tierRemovals[$tier][$facility]++;
            }
        }
        
        return $tierRemovals;
    }
    
    /**
     * Get facility requirement forecast for capacity planning
     * 
     * @param string $facility
     * @param int|null $eventId
     * @return array
     */
    public static function getFacilityRequirement(string $facility, ?int $eventId = null): array
    {
        $guests = Guest::query()
            ->with('tierInfo')
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->get();
        
        $requiresIt = $guests->filter(fn($g) => $g->hasFacility($facility));
        
        return [
            'facility' => $facility,
            'total_guests' => $guests->count(),
            'requires_facility' => $requiresIt->count(),
            'percentage' => $guests->count() > 0 
                ? round(($requiresIt->count() / $guests->count()) * 100, 1) 
                : 0,
            'by_tier' => $requiresIt->groupBy('tier')->map->count()->toArray(),
        ];
    }
    
    /**
     * Get billing report for custom facilities
     * (Guests with services beyond their tier package)
     * 
     * @param int|null $eventId
     * @return Collection
     */
    public static function getCustomFacilitiesBillingReport(?int $eventId = null): Collection
    {
        return Guest::query()
            ->with('tierInfo')
            ->whereJsonLength('facilityOverrides->added', '>', 0)
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->get()
            ->map(function ($guest) {
                return [
                    'guest_id' => $guest->id,
                    'guest_name' => $guest->name,
                    'tier' => $guest->tier,
                    'tier_name' => $guest->tierInfo?->name,
                    'extra_facilities' => $guest->custom_facilities,
                    'extra_count' => count($guest->custom_facilities),
                ];
            });
    }
    
    /**
     * Compare actual facility usage vs tier package definitions
     * (Helps optimize tier packages)
     * 
     * @param int|null $eventId
     * @return array
     */
    public static function getTierPackageEffectiveness(?int $eventId = null): array
    {
        $tiers = ServiceLevel::all();
        $guests = Guest::query()
            ->with('tierInfo')
            ->when($eventId, fn($q) => $q->where('event_id', $eventId))
            ->get()
            ->groupBy('tier');
        
        $analysis = [];
        
        foreach ($tiers as $tier) {
            $tierGuests = $guests->get($tier->id, collect());
            $tierFacilities = $tier->facilities ?? [];
            
            if ($tierGuests->isEmpty()) continue;
            
            $utilization = [];
            foreach ($tierFacilities as $facility) {
                $using = $tierGuests->filter(fn($g) => $g->hasFacility($facility))->count();
                $utilization[$facility] = [
                    'guests_using' => $using,
                    'percentage' => round(($using / $tierGuests->count()) * 100, 1),
                ];
            }
            
            $analysis[$tier->id] = [
                'tier' => $tier->id,
                'tier_name' => $tier->name,
                'total_guests' => $tierGuests->count(),
                'facility_utilization' => $utilization,
                'guests_with_customizations' => $tierGuests->filter(fn($g) => $g->hasCustomFacilities())->count(),
            ];
        }
        
        return $analysis;
    }
}
