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
            ],
            [
                'name' => 'sent',
                'label' => 'Sent',
                'description' => 'Invitation has been sent to guest',
            ],
            [
                'name' => 'pending',
                'label' => 'Pending',
                'description' => 'Awaiting guest response',
            ],
            [
                'name' => 'accepted',
                'label' => 'Accepted',
                'description' => 'Guest has accepted the invitation',
            ],
            [
                'name' => 'confirmed',
                'label' => 'Confirmed',
                'description' => 'Attendance confirmed',
            ],
            [
                'name' => 'declined',
                'label' => 'Declined',
                'description' => 'Guest has declined the invitation',
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
