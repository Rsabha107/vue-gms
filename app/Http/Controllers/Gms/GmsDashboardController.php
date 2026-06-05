<?php

namespace App\Http\Controllers\Gms;

use App\Http\Controllers\Controller;
use App\Services\Gms\GmsMockData;
use Inertia\Inertia;

class GmsDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Gms/Dashboard', [
            'event'   => GmsMockData::getEvent(),
            'stats'   => GmsMockData::getDashboardStats(),
            'matches' => array_slice(GmsMockData::getMatches(), 0, 4),
            'guests'  => array_slice(GmsMockData::getGuests(), 0, 8),
            'tiers'   => GmsMockData::getTiers(),
        ]);
    }
}
