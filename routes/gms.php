<?php

use App\Http\Controllers\Gms\GmsArrivalDepartureController;
use App\Http\Controllers\Gms\GmsAccommodationController;
use App\Http\Controllers\Gms\GmsDashboardController;
use App\Http\Controllers\Gms\GmsEmailTemplateController;
use App\Http\Controllers\Gms\GmsEventController;
use App\Http\Controllers\Gms\GmsEventsController;
use App\Http\Controllers\Gms\GmsFlightController;
use App\Http\Controllers\Gms\GmsFloorPlanController;
use App\Http\Controllers\Gms\GmsGroupsController;
use App\Http\Controllers\Gms\GmsGuestController;
use App\Http\Controllers\Gms\GmsInvitationController;
use App\Http\Controllers\Gms\GmsMatchesController;
use App\Http\Controllers\Gms\GmsSeatingController;
use App\Http\Controllers\Gms\GmsServiceLevelController;
use App\Http\Controllers\Gms\GmsSettingsController;
use App\Http\Controllers\Gms\GmsTransportController;
use App\Http\Controllers\Gms\GmsVenuesController;
use Illuminate\Support\Facades\Route;

// Route::prefix('gms')->middleware('auth')->name('gms.')->group(function () {
Route::prefix('gms')->name('gms.')->group(function () {

    Route::get('/', [GmsDashboardController::class, 'index'])->name('dashboard');

    // Event Switching
    Route::post('/events/switch', [GmsEventController::class, 'switch'])->name('events.switch');

    // Guests
    Route::get('/guests',                  [GmsGuestController::class, 'index'])->name('guests.index');
    Route::post('/guests',                 [GmsGuestController::class, 'store'])->name('guests.store');
    Route::post('/guests/import',          [GmsGuestController::class, 'import'])->name('guests.import');
    Route::post('/guests/add-to-event',    [GmsGuestController::class, 'addToEvent'])->name('guests.addToEvent');
    Route::post('/guests/remove-from-event', [GmsGuestController::class, 'removeFromEvent'])->name('guests.removeFromEvent');
    Route::put('/guests/{id}',             [GmsGuestController::class, 'update'])->name('guests.update');
    Route::delete('/guests/{id}',          [GmsGuestController::class, 'destroy'])->name('guests.destroy');

    // Invitations
    Route::get('/invitations',                        [GmsInvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations/send',                  [GmsInvitationController::class, 'send'])->name('invitations.send');
    Route::post('/invitations/add-guests',            [GmsInvitationController::class, 'addGuests'])->name('invitations.addGuests');
    Route::delete('/invitations/remove-guest/{guestId}', [GmsInvitationController::class, 'removeGuest'])->name('invitations.removeGuest');
    Route::post('/invitations/{id}/accept-behalf',    [GmsInvitationController::class, 'acceptOnBehalf'])->name('invitations.acceptOnBehalf');
    Route::post('/invitations/{id}/mark-confirmed',   [GmsInvitationController::class, 'markConfirmed'])->name('invitations.markConfirmed');
    Route::post('/invitations/{id}/mark-declined',    [GmsInvitationController::class, 'markDeclined'])->name('invitations.markDeclined');
    Route::post('/invitations/{id}/reset-pending',    [GmsInvitationController::class, 'resetToPending'])->name('invitations.resetToPending');
    Route::post('/invitations/send-portal/{guestId}', [GmsInvitationController::class, 'sendPortalLink'])->name('invitations.sendPortalLink');

    // Seating
    Route::get('/seating',                                     [GmsSeatingController::class, 'index'])->name('seating.index');
    Route::post('/seating/templates',                          [GmsSeatingController::class, 'storeTemplate'])->name('seating.templates.store');
    Route::put('/seating/templates/{id}',                      [GmsSeatingController::class, 'updateTemplate'])->name('seating.templates.update');
    Route::post('/seating/templates/{id}/duplicate',           [GmsSeatingController::class, 'duplicateTemplate'])->name('seating.templates.duplicate');
    Route::delete('/seating/templates/{id}',                   [GmsSeatingController::class, 'destroyTemplate'])->name('seating.templates.destroy');
    Route::post('/seating/matches/{matchId}/assign-template',  [GmsSeatingController::class, 'assignTemplateToMatch'])->name('seating.matches.assignTemplate');
    Route::post('/seating/{matchId}/clear-all',                [GmsSeatingController::class, 'clearAllAssigned'])->name('seating.clearAllAssigned');
    Route::post('/seating/{matchId}/seats/{seatId}',          [GmsSeatingController::class, 'assignSeat'])->name('seating.seat.update');

    // Service Levels
    Route::get('/service-levels',         [GmsServiceLevelController::class, 'index'])->name('service-levels.index');
    Route::post('/service-levels',        [GmsServiceLevelController::class, 'store'])->name('service-levels.store');
    Route::put('/service-levels/{id}',    [GmsServiceLevelController::class, 'update'])->name('service-levels.update');
    Route::delete('/service-levels/{id}', [GmsServiceLevelController::class, 'destroy'])->name('service-levels.destroy');

    // Floor Plans
    Route::get('/floorplans', [GmsFloorPlanController::class, 'index'])->name('floorplans.index');
    Route::post('/floorplans/{plan}', [GmsFloorPlanController::class, 'save'])->name('floorplans.save');

    // Flights
    Route::get('/flights',                  [GmsFlightController::class, 'index'])->name('flights.index');
    Route::post('/flights',                 [GmsFlightController::class, 'store'])->name('flights.store');
    Route::post('/flights/{guestRequestCode}/book', [GmsFlightController::class, 'bookGuestRequest'])->name('flights.book-guest-request');
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

    // Accommodation guest request booking
    Route::post('/accommodation/{guestRequestCode}/book', [GmsAccommodationController::class, 'bookGuestRequest'])->name('accommodation.book-guest-request');

    // Hotels
    Route::post('/accommodation/hotels',              [GmsAccommodationController::class, 'storeHotel'])->name('accommodation.hotels.store');

    // Room Blocks
    Route::post('/accommodation/blocks',              [GmsAccommodationController::class, 'storeBlock'])->name('accommodation.blocks.store');
    Route::put('/accommodation/blocks/{id}',          [GmsAccommodationController::class, 'updateBlock'])->name('accommodation.blocks.update');
    Route::patch('/accommodation/blocks/{id}/pickup', [GmsAccommodationController::class, 'updateBlockPickup'])->name('accommodation.blocks.pickup');
    Route::delete('/accommodation/blocks/{id}',       [GmsAccommodationController::class, 'destroyBlock'])->name('accommodation.blocks.destroy');

    // Transport
    Route::get('/transport',                [GmsTransportController::class, 'index'])->name('transport.index');
    Route::post('/transport',              [GmsTransportController::class, 'store'])->name('transport.store');
    Route::patch('/transport/{id}/status', [GmsTransportController::class, 'updateStatus'])->name('transport.status');
    Route::delete('/transport/{id}',       [GmsTransportController::class, 'destroy'])->name('transport.destroy');

    // Transport guest request booking
    Route::post('/transport/{guestRequestCode}/book', [GmsTransportController::class, 'bookGuestRequest'])->name('transport.book-guest-request');

    // Vehicle Blocks
    Route::post('/transport/blocks',              [GmsTransportController::class, 'storeBlock'])->name('transport.blocks.store');
    Route::put('/transport/blocks/{id}',          [GmsTransportController::class, 'updateBlock'])->name('transport.blocks.update');
    Route::patch('/transport/blocks/{id}/assign', [GmsTransportController::class, 'updateBlockAssign'])->name('transport.blocks.assign');
    Route::delete('/transport/blocks/{id}',       [GmsTransportController::class, 'destroyBlock'])->name('transport.blocks.destroy');

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

    // Groups (Setup)
    Route::get('/groups',          [GmsGroupsController::class, 'index'])->name('groups.index');
    Route::post('/groups',         [GmsGroupsController::class, 'store'])->name('groups.store');
    Route::put('/groups/{id}',     [GmsGroupsController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{id}',  [GmsGroupsController::class, 'destroy'])->name('groups.destroy');

    // Settings
    Route::get('/settings', [GmsSettingsController::class, 'index'])->name('settings');
    Route::post('/settings/portal', [GmsSettingsController::class, 'updatePortalSettings'])->name('settings.portal');
});
