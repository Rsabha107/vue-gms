<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'vip-invitation',
                'type' => 'invitation',
                'name' => 'VIP Invitation',
                'subject' => "You're Invited – {{ event_name }}",
                'body' => "Dear {{ guest_title }} {{ guest_name }},\n\nOn behalf of the Organising Committee, we extend a cordial invitation to {{ event_name }}.\n\nEvent Details:\nDate: {{ event_date }}\nVenue: {{ event_location }}\n\nAs a {{ tier_name }} guest, you will enjoy exclusive access to premium hospitality areas and services tailored to ensure your comfort throughout the event.\n\nPlease confirm your attendance at your earliest convenience by visiting your personalized RSVP portal.\n\nWe look forward to welcoming you.\n\nWarm regards,\nThe Protocol Team",
                'is_default' => true,
                'enabled' => true,
            ],
            [
                'key' => 'default-confirmation',
                'type' => 'confirmation',
                'name' => 'Attendance Confirmed',
                'subject' => "Your {{ event_name }} Attendance is Confirmed",
                'body' => "Dear {{ guest_name }},\n\nWe are pleased to confirm your attendance at {{ event_name }}.\n\nYour protocol officer has finalized your itinerary details. You will receive further information about your services shortly.",
                'is_default' => true,
                'enabled' => true,
            ],
            [
                'key' => 'default-portal-access',
                'type' => 'portal_access',
                'name' => 'Portal Access Link',
                'subject' => "Your {{ event_name }} Guest Portal Access",
                'body' => "Dear {{ guest_name }},\n\nYour personal guest portal is ready. Use the link below to access your itinerary, service requests, and match tickets.\n\nThis link is valid for 72 hours. Please do not share it with others.",
                'is_default' => true,
                'enabled' => true,
            ],
            [
                'key' => 'default-flight-confirmed',
                'type' => 'flight_confirmed',
                'name' => 'Flight Confirmed',
                'subject' => "{{ event_name }} — Flight Booking Confirmed",
                'body' => "Dear {{ guest_name }},\n\nYour flight booking has been confirmed. Please find your itinerary details below.",
                'is_default' => true,
                'enabled' => true,
            ],
            [
                'key' => 'default-accommodation-confirmed',
                'type' => 'accommodation_confirmed',
                'name' => 'Accommodation Confirmed',
                'subject' => "{{ event_name }} — Accommodation Confirmed",
                'body' => "Dear {{ guest_name }},\n\nYour accommodation has been confirmed. Please find your hotel details below.",
                'is_default' => true,
                'enabled' => true,
            ],
            [
                'key' => 'default-transport-confirmed',
                'type' => 'transport_confirmed',
                'name' => 'Transport Confirmed',
                'subject' => "{{ event_name }} — Transport Confirmed",
                'body' => "Dear {{ guest_name }},\n\nYour ground transport has been confirmed. Please find the details below.",
                'is_default' => true,
                'enabled' => true,
            ],
            [
                'key' => 'diamond-welcome',
                'type' => 'invitation',
                'name' => 'Diamond Circle Welcome',
                'subject' => "Welcome to the Diamond Circle",
                'body' => "Dear {{ guest_title }} {{ guest_name }},\n\nAs a Diamond Circle guest, you will enjoy our highest level of service and exclusive access to the Royal Lounge.\n\nYour Diamond Circle benefits include:\n• Priority seating in premium locations\n• Access to the Royal Lounge with gourmet dining\n• Dedicated concierge service\n• Complimentary ground transportation\n• VIP parking access\n\nWe are honored to host you at {{ event_name }}.\n\nWarm regards,\nThe VIP Protocol Team",
                'is_default' => false,
                'enabled' => true,
            ],
            [
                'key' => 'travel-logistics',
                'type' => 'invitation',
                'name' => 'Travel & Logistics',
                'subject' => "Your Travel Arrangements – {{ event_name }}",
                'body' => "Dear {{ guest_title }} {{ guest_name }},\n\nPlease find below your personalized travel briefing for {{ event_name }}.\n\nFlight Details:\n{{ service_details }}\n\nFor any changes or special requirements, please contact us at least 48 hours in advance.\n\nSafe travels,\nThe Logistics Team",
                'is_default' => false,
                'enabled' => true,
            ],
            [
                'key' => 'rsvp-reminder',
                'type' => 'invitation',
                'name' => 'RSVP Reminder',
                'subject' => "RSVP Reminder – {{ event_name }}",
                'body' => "Dear {{ guest_title }} {{ guest_name }},\n\nWe kindly remind you that the RSVP deadline for {{ event_name }} is approaching.\n\nPlease confirm your attendance to ensure we can finalize all arrangements for your visit.\n\nWe hope to welcome you.\n\nBest regards,\nThe Protocol Team",
                'is_default' => false,
                'enabled' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
