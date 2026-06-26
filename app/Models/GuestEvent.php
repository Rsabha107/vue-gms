<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GuestEvent extends Pivot
{
    protected $table = 'guest_event';

    protected $fillable = [
        'guest_id',
        'event_id',
        'status_id',
        'added_at',
        'invited_at',
        'companions',
        'passport_no',
        'personal_photo',
        'passport_front',
        'totp_secret',
        'attendance_mode',
        'preference_overrides',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'invited_at' => 'datetime',
        'companions' => 'array',
        'preference_overrides' => 'array',
    ];

    /**
     * Get the invitation status for this guest-event relationship
     */
    public function invitationStatus()
    {
        return $this->belongsTo(InvitationStatus::class, 'status_id');
    }
}
