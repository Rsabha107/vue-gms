<?php

namespace App\Services\Gms;

use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PortalTokenService
{
    /**
     * Generate a signed portal URL for a guest
     * 
     * @param Guest $guest
     * @param int $hoursValid Number of hours the token is valid (default 72)
     * @return string The signed URL
     */
    public static function generateSignedUrl(Guest $guest, int $hoursValid = 72): string
    {
        // Generate a unique token for tracking
        $token = Str::random(64);
        
        // Create signed URL that expires after specified hours
        return URL::temporarySignedRoute(
            'portal.dashboard',
            now()->addHours($hoursValid),
            [
                'guest' => $guest->id,
                'token' => $token,
            ]
        );
    }

    /**
     * Update invitation portal tracking when URL is generated
     * 
     * @param Invitation $invitation
     * @param string $token
     * @param int $hoursValid
     * @return void
     */
    public static function trackPortalSent(Invitation $invitation, string $token, int $hoursValid = 72): void
    {
        $invitation->update([
            'portal_token' => $token,
            'portal_token_expires_at' => now()->addHours($hoursValid),
            'portal_sent_at' => now(),
        ]);
    }

    /**
     * Record portal access
     * 
     * @param Invitation $invitation
     * @return void
     */
    public static function trackPortalAccess(Invitation $invitation): void
    {
        $updates = [
            'portal_last_accessed_at' => now(),
        ];
        
        // Track first access
        if (!$invitation->portal_accessed_at) {
            $updates['portal_accessed_at'] = now();
        }
        
        $invitation->update($updates);
    }

    /**
     * Check if portal token is valid
     * 
     * @param Invitation $invitation
     * @return bool
     */
    public static function isTokenValid(Invitation $invitation): bool
    {
        if (!$invitation->portal_token || !$invitation->portal_token_expires_at) {
            return false;
        }
        
        return now()->isBefore($invitation->portal_token_expires_at);
    }

    /**
     * Generate magic link (one-time use token sent via email)
     * 
     * @param Guest $guest
     * @return string
     */
    public static function generateMagicLink(Guest $guest): string
    {
        $token = Str::random(64);
        
        // Store token in invitation
        $invitation = $guest->invitations()->where('event_id', session('current_event_id'))->first();
        if ($invitation) {
            $invitation->update([
                'portal_token' => $token,
                'portal_token_expires_at' => now()->addMinutes(15), // Short expiry for magic links
            ]);
        }
        
        return route('portal.magic', ['token' => $token]);
    }
}
