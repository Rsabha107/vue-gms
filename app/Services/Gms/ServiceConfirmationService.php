<?php

namespace App\Services\Gms;

use App\Mail\TemplatedMail;
use App\Models\Guest;

class ServiceConfirmationService
{
    public static function sendFlightConfirmation(Guest $guest, string $eventName, array $flightData): void
    {
        if (!$guest->email) return;

        TemplatedMail::deliver('flight_confirmed', $guest->email, [
            'guest_name'  => $guest->name,
            'guest_title' => $guest->title ?? '',
            'event_name'  => $eventName,
        ], [
            'serviceFields' => [
                'Ref Code'    => $flightData['code'] ?? '',
                'Route'       => $flightData['route'] ?? '',
                'Class'       => $flightData['class'] ?? '',
                'Passengers'  => $flightData['pax'] ?? '',
                'Inbound'     => $flightData['inbound'] ?? '',
                'Outbound'    => $flightData['outbound'] ?? '',
            ],
        ]);
    }

    public static function sendAccommodationConfirmation(Guest $guest, string $eventName, array $data): void
    {
        if (!$guest->email) return;

        TemplatedMail::deliver('accommodation_confirmed', $guest->email, [
            'guest_name'  => $guest->name,
            'guest_title' => $guest->title ?? '',
            'event_name'  => $eventName,
        ], [
            'serviceFields' => [
                'Booking Code' => $data['code'] ?? '',
                'Hotel'        => $data['hotel'] ?? '',
                'Room Type'    => $data['roomType'] ?? '',
                'Check-in'     => $data['checkIn'] ?? '',
                'Check-out'    => $data['checkOut'] ?? '',
                'Nights'       => $data['nights'] ?? '',
            ],
        ]);
    }

    public static function sendTransportConfirmation(Guest $guest, string $eventName, array $data): void
    {
        if (!$guest->email) return;

        TemplatedMail::deliver('transport_confirmed', $guest->email, [
            'guest_name'  => $guest->name,
            'guest_title' => $guest->title ?? '',
            'event_name'  => $eventName,
        ], [
            'serviceFields' => [
                'Booking Code' => $data['code'] ?? '',
                'Type'         => $data['type'] ?? '',
                'Vehicle'      => $data['vehicle'] ?? '',
                'Pickup'       => $data['pickup'] ?? '',
                'Drop-off'     => $data['dropoff'] ?? '',
                'Date & Time'  => $data['datetime'] ?? '',
                'Driver'       => $data['driver'] ?? '',
            ],
        ]);
    }
}
