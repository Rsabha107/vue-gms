# Guest Facility Reporting - Usage Guide

This guide shows how to use the facility reporting helpers for guest data analysis.

---

## Quick Access Methods (Guest Model)

### Get Final Facilities for a Guest
```php
$guest = Guest::find('G001');

// Get merged facilities (tier + overrides)
$facilities = $guest->final_facilities;
// Returns: ['VIP Lounge', 'Chauffeur', 'Spa Package']

// Check if guest has specific facility
if ($guest->hasFacility('VIP Lounge')) {
    // Guest has VIP lounge access
}

// Get only custom added facilities
$customFacilities = $guest->custom_facilities;
// Returns: ['Spa Package']

// Get only removed facilities
$removedFacilities = $guest->removed_facilities;
// Returns: ['Presidential Suite']

// Check if guest has any customizations
if ($guest->hasCustomFacilities()) {
    // Guest has custom facilities beyond their tier
}
```

---

## Query Scopes (Database Queries)

### Find Guests with Specific Facility
```php
// All guests who need VIP lounge
$guestsNeedingLounge = Guest::withFacility('VIP Royal Lounge')->get();

// Count for capacity planning
$loungeCount = Guest::withFacility('VIP Royal Lounge')
    ->where('event_id', $eventId)
    ->count();
```

### Find Guests with Custom Facilities
```php
// Guests who have customized their facilities
$customizedGuests = Guest::withCustomFacilities()->get();

// For billing review
$guestsWithExtras = Guest::withCustomFacilities()
    ->where('event_id', $eventId)
    ->get();
```

---

## Reporting Service (Advanced Analytics)

### 1. Facility Usage Summary
```php
use App\Services\Gms\GuestFacilityReport;

// Get usage across all facilities
$usage = GuestFacilityReport::getFacilityUsageSummary($eventId);

/*
Returns:
[
    [
        'facility' => 'VIP Royal Lounge',
        'count' => 45,
        'from_tier' => 40,
        'custom_added' => 5
    ],
    [
        'facility' => 'Chauffeur Escort',
        'count' => 38,
        'from_tier' => 35,
        'custom_added' => 3
    ],
    ...
]
*/
```

### 2. Guests with Customizations
```php
// List all guests who customized facilities
$customized = GuestFacilityReport::getGuestsWithCustomFacilities($eventId);

/*
Returns:
[
    [
        'id' => 'G001',
        'name' => 'Sheikh Ahmed',
        'tier' => 'T1',
        'added_facilities' => ['Private Jet', 'Spa'],
        'removed_facilities' => ['Fine Dining']
    ],
    ...
]
*/
```

### 3. Most Removed Facilities (Tier Optimization)
```php
// Which facilities are guests removing from each tier?
$removals = GuestFacilityReport::getMostRemovedFacilitiesByTier($eventId);

/*
Returns:
[
    'T1' => [
        'Fine Dining' => 12,  // 12 T1 guests removed this
        'Spa Access' => 5
    ],
    'T2' => [
        'Business Lounge' => 8
    ]
]

Use case: If 60% of T2 guests remove "Business Lounge", 
consider removing it from T2 package
*/
```

### 4. Capacity Planning for Specific Facility
```php
// How many guests need VIP lounge access?
$requirement = GuestFacilityReport::getFacilityRequirement('VIP Royal Lounge', $eventId);

/*
Returns:
[
    'facility' => 'VIP Royal Lounge',
    'total_guests' => 150,
    'requires_facility' => 45,
    'percentage' => 30.0,
    'by_tier' => [
        'T1' => 20,
        'T2' => 18,
        'T3' => 7   // Custom additions
    ]
]
*/
```

### 5. Billing Report (Custom Facilities)
```php
// Guests with services beyond their tier package
$billing = GuestFacilityReport::getCustomFacilitiesBillingReport($eventId);

/*
Returns:
[
    [
        'guest_id' => 'G001',
        'guest_name' => 'Sheikh Ahmed',
        'tier' => 'T2',
        'tier_name' => 'Platinum',
        'extra_facilities' => ['Private Jet', 'Spa Package'],
        'extra_count' => 2
    ],
    ...
]
*/
```

### 6. Tier Package Effectiveness
```php
// Are tier packages working? Which facilities are underutilized?
$effectiveness = GuestFacilityReport::getTierPackageEffectiveness($eventId);

/*
Returns:
[
    'T1' => [
        'tier' => 'T1',
        'tier_name' => 'Platinum',
        'total_guests' => 50,
        'facility_utilization' => [
            'VIP Royal Lounge' => [
                'guests_using' => 48,
                'percentage' => 96.0  // High utilization ✓
            ],
            'Fine Dining' => [
                'guests_using' => 20,
                'percentage' => 40.0  // Low utilization - consider removing?
            ]
        ],
        'guests_with_customizations' => 12
    ]
]

Use case: Optimize tier packages based on actual usage
*/
```

---

## Controller Example

```php
// app/Http/Controllers/Gms/GmsReportsController.php

use App\Services\Gms\GuestFacilityReport;

public function facilitySummary()
{
    $eventId = session('gms_current_event_id');
    
    return Inertia::render('Gms/Reports/FacilitySummary', [
        'usage' => GuestFacilityReport::getFacilityUsageSummary($eventId),
        'customizations' => GuestFacilityReport::getGuestsWithCustomFacilities($eventId),
        'event' => GmsMockData::getEvent(),
    ]);
}

public function capacityPlanning()
{
    $eventId = session('gms_current_event_id');
    
    // Get requirements for all key facilities
    $facilities = ['VIP Royal Lounge', 'Chauffeur Escort', 'Private Driver'];
    $requirements = [];
    
    foreach ($facilities as $facility) {
        $requirements[] = GuestFacilityReport::getFacilityRequirement($facility, $eventId);
    }
    
    return Inertia::render('Gms/Reports/CapacityPlanning', [
        'requirements' => $requirements,
        'event' => GmsMockData::getEvent(),
    ]);
}
```

---

## Common Report Queries

### Daily Lounge Capacity Check
```php
$loungeCapacity = 50;
$guestsNeedingLounge = Guest::withFacility('VIP Royal Lounge')
    ->where('event_id', $eventId)
    ->count();

if ($guestsNeedingLounge > $loungeCapacity) {
    // Alert: Need larger lounge or multiple sessions
}
```

### Custom Services Cost Estimation
```php
$guestsWithExtras = Guest::withCustomFacilities()
    ->where('event_id', $eventId)
    ->with('tierInfo')
    ->get();

$totalExtraServices = $guestsWithExtras->sum(function($guest) {
    return count($guest->custom_facilities);
});
```

### Tier Distribution with Customizations
```php
$tierStats = Guest::selectRaw('
        tier,
        COUNT(*) as total,
        SUM(CASE WHEN JSON_LENGTH(facilityOverrides->"$.added") > 0 
                  OR JSON_LENGTH(facilityOverrides->"$.removed") > 0 
            THEN 1 ELSE 0 END) as with_custom
    ')
    ->where('event_id', $eventId)
    ->groupBy('tier')
    ->get();
```

---

## Best Practices

1. **Always filter by event** when running reports for specific events
2. **Use scopes** (`withFacility`, `withCustomFacilities`) for efficient queries
3. **Access via attributes** (`$guest->final_facilities`) for single-guest operations
4. **Use reporting service** for complex analytics and dashboards
5. **Cache heavy reports** if running frequently

---

## Frontend Usage (Inertia/Vue)

```javascript
// In a Vue component
const guest = props.guest

// Display final facilities
const facilities = computed(() => {
    const tierFacilities = tierFor(guest.tier)?.facilities ?? []
    const overrides = guest.facilityOverrides ?? { added: [], removed: [] }
    
    return [
        ...tierFacilities.filter(f => !overrides.removed.includes(f)),
        ...overrides.added
    ]
})

// Check if has customizations
const hasCustom = computed(() => {
    const overrides = guest.facilityOverrides ?? { added: [], removed: [] }
    return overrides.added.length > 0 || overrides.removed.length > 0
})
```

---

## Performance Tips

- Load `tierInfo` relationship when querying multiple guests: `->with('tierInfo')`
- Use scopes for database-level filtering instead of collection filtering
- Cache reporting service results for dashboards
- Index `facilityOverrides` JSON column if doing frequent queries

---

## Summary

**Simple queries:** Use model attributes (`$guest->final_facilities`)  
**Database queries:** Use scopes (`Guest::withFacility()`)  
**Complex reports:** Use `GuestFacilityReport` service  
**Frontend:** Use computed properties to merge tier + overrides
