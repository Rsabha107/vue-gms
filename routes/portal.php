<?php

use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalServiceController;
use App\Http\Controllers\Portal\PortalTotpController;
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
    
    // TOTP verification (signed URL required)
    Route::get('/totp/{guest}', [PortalTotpController::class, 'show'])
        ->name('totp.show');
    Route::post('/totp/{guest}/verify', [PortalTotpController::class, 'verify'])
        ->name('totp.verify');

    // Portal dashboard (requires valid signed URL)
    Route::get('/dashboard/{guest}', [PortalDashboardController::class, 'show'])
        ->name('dashboard');
    
    // Service request submissions (guest validated via route model binding)
    Route::post('/services/flights/{guest}', [PortalServiceController::class, 'storeFlight'])
        ->name('services.flights.store');
    Route::put('/services/flights/{guest}/{id}', [PortalServiceController::class, 'updateFlight'])
        ->name('services.flights.update');
    Route::delete('/services/flights/{guest}/{id}', [PortalServiceController::class, 'destroyFlight'])
        ->name('services.flights.destroy');

    Route::post('/services/accommodation/{guest}', [PortalServiceController::class, 'storeAccommodation'])
        ->name('services.accommodation.store');
    Route::put('/services/accommodation/{guest}/{id}', [PortalServiceController::class, 'updateAccommodation'])
        ->name('services.accommodation.update');
    Route::delete('/services/accommodation/{guest}/{id}', [PortalServiceController::class, 'destroyAccommodation'])
        ->name('services.accommodation.destroy');

    Route::post('/services/transport/{guest}', [PortalServiceController::class, 'storeTransport'])
        ->name('services.transport.store');
    Route::put('/services/transport/{guest}/{id}', [PortalServiceController::class, 'updateTransport'])
        ->name('services.transport.update');
    Route::delete('/services/transport/{guest}/{id}', [PortalServiceController::class, 'destroyTransport'])
        ->name('services.transport.destroy');

    Route::post('/services/{type}/{guest}/{id}/remarks', [PortalServiceController::class, 'submitRemarks'])
        ->name('services.remarks');

    Route::post('/companions/{guest}', [PortalServiceController::class, 'saveCompanions'])
        ->name('companions.save');
    
    // Future: Magic link authentication
    // Route::get('/magic/{token}', [PortalAuthController::class, 'magicLink'])->name('magic');
});
