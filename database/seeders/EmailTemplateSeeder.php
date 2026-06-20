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
                'name' => 'VIP Invitation',
                'subject' => "You're Invited – {{event_name}}",
                'body' => "Dear {{guest_title}} {{guest_name}},\n\nOn behalf of the Organising Committee, we extend a cordial invitation to {{event_name}}.\n\nEvent Details:\nDate: {{event_date}}\nVenue: {{venue}}\n\nAs a {{tier_name}} guest, you will enjoy exclusive access to premium hospitality areas and services tailored to ensure your comfort throughout the event.\n\nPlease confirm your attendance at your earliest convenience by visiting your personalized RSVP portal.\n\nWe look forward to welcoming you.\n\nWarm regards,\nThe Protocol Team",
                'is_default' => true,
            ],
            [
                'key' => 'confirmation',
                'name' => 'Attendance Confirmation',
                'subject' => "Your Attendance is Confirmed – {{event_name}}",
                'body' => "Dear {{guest_title}} {{guest_name}},\n\nWe are delighted to confirm your attendance at {{event_name}}.\n\nYour confirmation details:\nService Level: {{tier_name}}\nVenue: {{venue}}\nDate: {{event_date}}\n\nFurther details regarding your arrival, accommodation, and match-day arrangements will be shared with you shortly.\n\nShould you have any questions, please do not hesitate to contact our team.\n\nBest regards,\nThe Protocol Team",
                'is_default' => false,
            ],
            [
                'key' => 'diamond-welcome',
                'name' => 'Diamond Circle Welcome',
                'subject' => "Welcome to the Diamond Circle",
                'body' => "Dear {{guest_title}} {{guest_name}},\n\nAs a Diamond Circle guest, you will enjoy our highest level of service and exclusive access to the Royal Lounge.\n\nYour Diamond Circle benefits include:\n• Priority seating in premium locations\n• Access to the Royal Lounge with gourmet dining\n• Dedicated concierge service\n• Complimentary ground transportation\n• VIP parking access\n\nWe are honored to host you at {{event_name}}.\n\nWarm regards,\nThe VIP Protocol Team",
                'is_default' => false,
            ],
            [
                'key' => 'travel-logistics',
                'name' => 'Travel & Logistics',
                'subject' => "Your Travel Arrangements – {{event_name}}",
                'body' => "Dear {{guest_title}} {{guest_name}},\n\nPlease find below your personalized travel briefing for {{event_name}}.\n\nFlight Details:\n{{flight_details}}\n\nAccommodation:\n{{accommodation_details}}\n\nGround Transportation:\n{{transport_details}}\n\nAirport Meet & Greet:\nOur protocol team will meet you at arrivals with a personalized welcome sign.\n\nFor any changes or special requirements, please contact us at least 48 hours in advance.\n\nSafe travels,\nThe Logistics Team",
                'is_default' => false,
            ],
            [
                'key' => 'rsvp-reminder',
                'name' => 'RSVP Reminder',
                'subject' => "RSVP Reminder – {{event_name}}",
                'body' => "Dear {{guest_title}} {{guest_name}},\n\nWe kindly remind you that the RSVP deadline for {{event_name}} is approaching.\n\nPlease confirm your attendance by {{rsvp_deadline}} to ensure we can finalize all arrangements for your visit.\n\nYou can respond by visiting your personalized RSVP portal or by contacting our team directly.\n\nWe hope to welcome you at {{venue}} on {{event_date}}.\n\nBest regards,\nThe Protocol Team",
                'is_default' => false,
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
