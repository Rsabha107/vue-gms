<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InvitationStatus;

class InvitationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'not_invited',
                'label' => 'Not Invited',
                'description' => 'Guest has not been invited yet',
                'color' => '#6b7280', // Gray
            ],
            [
                'name' => 'invited',
                'label' => 'Invited',
                'description' => 'Invitation has been sent to guest',
                'color' => '#3b82f6', // Blue
            ],
            [
                'name' => 'pending',
                'label' => 'Pending',
                'description' => 'Awaiting guest response',
                'color' => '#f59e0b', // Amber
            ],
            [
                'name' => 'new',
                'label' => 'New',
                'description' => 'New request, not yet processed',
                'color' => '#f59e0b', // Amber
            ],
            [
                'name' => 'requested',
                'label' => 'Requested',
                'description' => 'Guest has requested service via portal',
                'color' => '#8b5cf6', // Purple
            ],
            [
                'name' => 'accepted',
                'label' => 'Accepted',
                'description' => 'Guest has accepted the invitation',
                'color' => '#10b981', // Green
            ],
            [
                'name' => 'confirmed',
                'label' => 'Confirmed',
                'description' => 'Attendance confirmed',
                'color' => '#059669', // Dark Green
            ],
            [
                'name' => 'change',
                'label' => 'Change Request',
                'description' => 'Request for changes to existing booking',
                'color' => '#3b82f6', // Blue
            ],
            [
                'name' => 'cancelled',
                'label' => 'Cancelled',
                'description' => 'Booking has been cancelled',
                'color' => '#6b7280', // Gray
            ],
            [
                'name' => 'declined',
                'label' => 'Declined',
                'description' => 'Guest has declined the invitation',
                'color' => '#ef4444', // Red
            ],
        ];

        foreach ($statuses as $status) {
            InvitationStatus::updateOrCreate(
                ['name' => $status['name']],
                $status
            );
        }
    }
}
