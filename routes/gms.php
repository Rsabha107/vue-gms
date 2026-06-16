<?php

use App\Http\Controllers\Gms\GmsArrivalDepartureController;
use App\Http\Controllers\Gms\GmsAccommodationController;
use App\Http\Controllers\Gms\GmsDashboardController;
use App\Http\Controllers\Gms\GmsEmailTemplateController;
use App\Http\Controllers\Gms\GmsEventController;
use App\Http\Controllers\Gms\GmsEventsController;
use App\Http\Controllers\Gms\GmsFlightController;
use App\Http\Controllers\Gms\GmsGuestController;
use App\Http\Controllers\Gms\GmsInvitationController;
use App\Http\Controllers\Gms\GmsMatchesController;
use App\Http\Controllers\Gms\GmsSeatingController;
use App\Http\Controllers\Gms\GmsServiceLevelController;
use App\Http\Controllers\Gms\GmsTransportController;
use App\Http\Controllers\Gms\GmsVenuesController;
use Illuminate\Support\Facades\Route;

// Route::prefix('gms')->middleware('auth')->name('gms.')->group(function () {
Route::prefix('gms')->name('gms.')->group(function () {

    Route::get('/', [GmsDashboardController::class, 'index'])->name('dashboard');

    // Event Switching
    Route::post('/events/switch', [GmsEventController::class, 'switch'])->name('events.switch');

    // Guests
    Route::get('/guests',          [GmsGuestController::class, 'index'])->name('guests.index');
    Route::post('/guests',         [GmsGuestController::class, 'store'])->name('guests.store');
    Route::put('/guests/{id}',     [GmsGuestController::class, 'update'])->name('guests.update');
    Route::delete('/guests/{id}',  [GmsGuestController::class, 'destroy'])->name('guests.destroy');

    // Invitations
    Route::get('/invitations',       [GmsInvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations/send', [GmsInvitationController::class, 'send'])->name('invitations.send');

    // Seating
    Route::get('/seating',                                     [GmsSeatingController::class, 'index'])->name('seating.index');
    Route::post('/seating/templates',                          [GmsSeatingController::class, 'storeTemplate'])->name('seating.templates.store');
    Route::put('/seating/templates/{id}',                      [GmsSeatingController::class, 'updateTemplate'])->name('seating.templates.update');
    Route::post('/seating/templates/{id}/duplicate',           [GmsSeatingController::class, 'duplicateTemplate'])->name('seating.templates.duplicate');
    Route::delete('/seating/templates/{id}',                   [GmsSeatingController::class, 'destroyTemplate'])->name('seating.templates.destroy');
    Route::post('/seating/matches/{matchId}/assign-template',  [GmsSeatingController::class, 'assignTemplateToMatch'])->name('seating.matches.assignTemplate');
    Route::post('/seating/{matchId}/seats/{seatId}',          [GmsSeatingController::class, 'assignSeat'])->name('seating.seat.update');

    // Service Levels
    Route::get('/service-levels',         [GmsServiceLevelController::class, 'index'])->name('service-levels.index');
    Route::post('/service-levels',        [GmsServiceLevelController::class, 'store'])->name('service-levels.store');
    Route::put('/service-levels/{id}',    [GmsServiceLevelController::class, 'update'])->name('service-levels.update');
    Route::delete('/service-levels/{id}', [GmsServiceLevelController::class, 'destroy'])->name('service-levels.destroy');

    // Flights
    Route::get('/flights',                  [GmsFlightController::class, 'index'])->name('flights.index');
    Route::post('/flights',                 [GmsFlightController::class, 'store'])->name('flights.store');
    Route::put('/flights/{id}',             [GmsFlightController::class, 'update'])->name('flights.update');
    Route::patch('/flights/{id}/status',    [GmsFlightController::class, 'updateStatus'])->name('flights.status');
    Route::patch('/flights/{id}/legs/{legId}', [GmsFlightController::class, 'updateLeg'])->name('flights.legs.update');
    Route::delete('/flights/{id}',          [GmsFlightController::class, 'destroy'])->name('flights.destroy');

    // Accommodation
    Route::get('/accommodation',                [GmsAccommodationController::class, 'index'])->name('accommodation.index');
    Route::post('/accommodation',              [GmsAccommodationController::class, 'store'])->name('accommodation.store');
    Route::patch('/accommodation/{id}',        [GmsAccommodationController::class, 'update'])->name('accommodation.update');
    Route::patch('/accommodation/{id}/status', [GmsAccommodationController::class, 'updateStatus'])->name('accommodation.status');
    Route::delete('/accommodation/{id}',       [GmsAccommodationController::class, 'destroy'])->name('accommodation.destroy');

    // Transport
    Route::get('/transport',                [GmsTransportController::class, 'index'])->name('transport.index');
    Route::post('/transport',              [GmsTransportController::class, 'store'])->name('transport.store');
    Route::patch('/transport/{id}/status', [GmsTransportController::class, 'updateStatus'])->name('transport.status');
    Route::delete('/transport/{id}',       [GmsTransportController::class, 'destroy'])->name('transport.destroy');

    // Arrival & Departure
    Route::get('/arrival-departure',                [GmsArrivalDepartureController::class, 'index'])->name('ad.index');
    Route::post('/arrival-departure',              [GmsArrivalDepartureController::class, 'store'])->name('ad.store');
    Route::patch('/arrival-departure/{id}/status', [GmsArrivalDepartureController::class, 'updateStatus'])->name('ad.status');
    Route::delete('/arrival-departure/{id}',       [GmsArrivalDepartureController::class, 'destroy'])->name('ad.destroy');

    // Events (Setup)
    Route::get('/events',           [GmsEventsController::class, 'index'])->name('events.index');
    Route::post('/events',          [GmsEventsController::class, 'store'])->name('events.store');
    Route::put('/events/{id}',      [GmsEventsController::class, 'update'])->name('events.update');
    Route::delete('/events/{id}',   [GmsEventsController::class, 'destroy'])->name('events.destroy');

    // Venues (Setup)
    Route::get('/venues',          [GmsVenuesController::class, 'index'])->name('venues.index');
    Route::post('/venues',         [GmsVenuesController::class, 'store'])->name('venues.store');
    Route::put('/venues/{id}',     [GmsVenuesController::class, 'update'])->name('venues.update');
    Route::delete('/venues/{id}',  [GmsVenuesController::class, 'destroy'])->name('venues.destroy');

    // Matches (Setup)
    Route::get('/matches',          [GmsMatchesController::class, 'index'])->name('matches.index');
    Route::post('/matches',         [GmsMatchesController::class, 'store'])->name('matches.store');
    Route::put('/matches/{id}',     [GmsMatchesController::class, 'update'])->name('matches.update');
    Route::delete('/matches/{id}',  [GmsMatchesController::class, 'destroy'])->name('matches.destroy');

    // Email Templates (Setup)
    Route::get('/email-templates',         [GmsEmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::post('/email-templates',        [GmsEmailTemplateController::class, 'store'])->name('email-templates.store');
    Route::put('/email-templates/{id}',    [GmsEmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::delete('/email-templates/{id}', [GmsEmailTemplateController::class, 'destroy'])->name('email-templates.destroy');

    // Settings
    Route::get('/settings', function () {
        return inertia('Gms/Settings/Index', [
            'user'      => auth()->user(),
            'event'     => \App\Services\Gms\GmsMockData::getEvent(),
            'teamUsers' => \App\Models\User::orderBy('name')->get(),
        ]);
    })->name('settings');
});
