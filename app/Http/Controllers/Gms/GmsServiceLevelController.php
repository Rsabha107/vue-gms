<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Models\ServiceLevel;
use App\Services\Gms\GmsMockData;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GmsServiceLevelController extends Controller
{
    public function index()
    {
        // Fetch service levels from database with guest count
        $tiers = ServiceLevel::withCount('guests')
            ->orderBy('rank')
            ->get();

        return Inertia::render('Gms/ServiceLevels/Index', [
            'tiers'  => $tiers,
            'guests' => GmsMockData::getGuests(),
            'event'  => GmsMockData::getEvent(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:60',
            'color'       => 'required|string|max:9',
            'description' => 'nullable|string|max:255',
            'modules'     => 'nullable|array',
            'facilities'  => 'nullable|array',
        ]);

        // Generate unique ID (T1, T2, T3, etc.)
        $count = ServiceLevel::count();
        $id = 'T' . ($count + 1);
        
        // Ensure uniqueness
        while (ServiceLevel::find($id)) {
            $count++;
            $id = 'T' . ($count + 1);
        }
        
        // Generate background color (lighter version of primary color)
        $bg = $this->lightenColor($request->color, 0.9);
        
        // Get next rank
        $maxRank = ServiceLevel::max('rank') ?? 0;
        
        ServiceLevel::create([
            'id' => $id,
            'name' => $request->name,
            'color' => $request->color,
            'bg' => $bg,
            'rank' => $maxRank + 1,
            'facilities' => $request->facilities ?? [],
        ]);

        return back()->with('success', 'Service level created.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name'        => 'required|string|max:60',
            'color'       => 'required|string|max:9',
            'description' => 'nullable|string|max:255',
            'modules'     => 'nullable|array',
            'facilities'  => 'nullable|array',
        ]);

        $serviceLevel = ServiceLevel::findOrFail($id);
        
        // Generate background color (lighter version of primary color)
        $bg = $this->lightenColor($request->color, 0.9);
        
        $serviceLevel->update([
            'name' => $request->name,
            'color' => $request->color,
            'bg' => $bg,
            'facilities' => $request->facilities ?? [],
        ]);

        return back()->with('success', 'Service level updated.');
    }

    public function destroy(string $id)
    {
        $serviceLevel = ServiceLevel::findOrFail($id);
        
        // Check if any guests are assigned to this service level
        $guestCount = $serviceLevel->guests()->count();
        
        if ($guestCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete service level. {$guestCount} guest(s) are currently assigned to it."
            ]);
        }
        
        $serviceLevel->delete();
        
        return back()->with('success', 'Service level deleted.');
    }
    
    /**
     * Generate a lighter background color from the primary color
     */
    private function lightenColor(string $color, float $percent = 0.9): string
    {
        // Remove # if present
        $color = ltrim($color, '#');
        
        // Convert to RGB
        if (strlen($color) === 3) {
            $r = hexdec(substr($color, 0, 1) . substr($color, 0, 1));
            $g = hexdec(substr($color, 1, 1) . substr($color, 1, 1));
            $b = hexdec(substr($color, 2, 1) . substr($color, 2, 1));
        } else {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
        }
        
        // Lighten by mixing with white
        $r = round($r + (255 - $r) * $percent);
        $g = round($g + (255 - $g) * $percent);
        $b = round($b + (255 - $b) * $percent);
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
