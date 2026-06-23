<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = [
        'guest_id',
        'event_id',
        'status_id',
        'subject',
        'body',
        'rsvp_token',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->rsvp_token)) {
                $invitation->rsvp_token = Str::random(64);
            }
        });
    }

    /**
     * Get the guest for this invitation
     */
    public function guest()
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'id');
    }

    /**
     * Get the event for this invitation
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    /**
     * Get the status for this invitation
     */
    public function status()
    {
        return $this->belongsTo(InvitationStatus::class, 'status_id', 'id');
    }

    /**
     * Get the matches offered in this invitation
     */
    public function matches()
    {
        return $this->belongsToMany(GameMatch::class, 'invitation_matches', 'invitation_id', 'game_match_id')
            ->withPivot('response')
            ->withTimestamps();
    }

    /**
     * Scope for sent invitations
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for confirmed invitations
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope for declined invitations
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
     * Check if invitation has been responded to
     */
    public function hasResponded(): bool
    {
        return !is_null($this->responded_at);
    }

    /**
     * Check if invitation has been sent
     */
    public function isSent(): bool
    {
        return !is_null($this->sent_at);
    }

    /**
     * Update invitation status based on match responses
     * Status logic:
     * - 'sent': No responses yet (all null)
     * - 'accepted': All matches accepted (all 'yes') - guest action
     * - 'partial': Some accepted, some declined (mix of 'yes' and 'no')
    /**
     * Update invitation status based on match responses:
     * - 'sent': No responses yet
     * - 'accepted': All matches accepted (all 'yes')
     * - 'declined': All matches declined (all 'no')
     * - 'confirmed': Admin-only override (not set by this method)
     */
    public function updateStatusFromResponses(): void
    {
        $responses = $this->matches()->pluck('response')->filter();
        
        $statusName = 'sent';
        if ($responses->isEmpty()) {
            $statusName = 'sent';
        } elseif ($responses->every(fn($r) => $r === 'yes')) {
            $statusName = 'accepted';
        } elseif ($responses->every(fn($r) => $r === 'no')) {
            $statusName = 'declined';
        } else {
            $statusName = 'pending';
        }
        
        $status = InvitationStatus::where('name', $statusName)->first();
        if ($status) {
            $this->status_id = $status->id;
            $this->save();
        }
    }
}
