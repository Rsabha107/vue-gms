<?php

use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Portal Routes
|--------------------------------------------------------------------------
|
| Public routes for guest self-service portal. Uses signed URL authentication.
|
*/

Route::prefix('portal')->name('portal.')->group(function () {
    
    // Portal dashboard (requires valid signed URL)
    Route::get('/dashboard/{guest}', [PortalDashboardController::class, 'show'])
        ->name('dashboard');
    
    // Service request submissions (guest validated via route model binding)
    Route::post('/services/flights/{guest}', [PortalServiceController::class, 'storeFlight'])
        ->name('services.flights.store');
    
    Route::post('/services/accommodation/{guest}', [PortalServiceController::class, 'storeAccommodation'])
        ->name('services.accommodation.store');
    
    Route::post('/services/transport/{guest}', [PortalServiceController::class, 'storeTransport'])
        ->name('services.transport.store');
    
    // Future: Magic link authentication
    // Route::get('/magic/{token}', [PortalAuthController::class, 'magicLink'])->name('magic');
});
