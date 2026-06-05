<?php

use App\Http\Controllers\Gms\GmsArrivalDepartureController;
use App\Http\Controllers\Gms\GmsAccommodationController;
use App\Http\Controllers\Gms\GmsDashboardController;
use App\Http\Controllers\Gms\GmsEventController;
use App\Http\Controllers\Gms\GmsEventsController;
use App\Http\Controllers\Gms\GmsFlightController;
use App\Http\Controllers\Gms\GmsGuestController;
use App\Http\Controllers\Gms\GmsInvitationController;
use App\Http\Controllers\Gms\GmsSeatingController;
use App\Http\Controllers\Gms\GmsServiceLevelController;
use App\Http\Controllers\Gms\GmsTransportController;
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
    Route::delete('/flights/{id}',          [GmsFlightController::class, 'destroy'])->name('flights.destroy');

    // Accommodation
    Route::get('/accommodation',                [GmsAccommodationController::class, 'index'])->name('accommodation.index');
    Route::post('/accommodation',              [GmsAccommodationController::class, 'store'])->name('accommodation.store');
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

    // Settings
    Route::get('/settings', function () {
        return inertia('Gms/Settings/Index', [
            'user'  => auth()->user(),
            'event' => \App\Services\Gms\GmsMockData::getEvent(),
        ]);
    })->name('settings');
});
