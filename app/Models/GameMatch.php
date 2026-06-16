<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    /** Table name, because "matches" collides + the model is GameMatch. */
    protected $table = 'game_matches';

    protected $fillable = [
        'event_id', 'venue_id', 'seating_template_id', 'stage', 'label',
        'team_a_name', 'team_a_flag', 'team_b_name', 'team_b_flag',
        'date', 'day', 'time', 'capacity', 'sold', 'featured', 'tbd',
    ];

    protected $casts = [
        'date' => 'date',
        'featured' => 'boolean',
        'tbd' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** The single template this match draws its seats from (nullable until picked). */
    public function seatingTemplate(): BelongsTo
    {
        return $this->belongsTo(SeatingTemplate::class);
    }

    /** This match's own seat instance (one row per template seat). */
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    /** Invitations that OFFER this match (with per-match RSVP on the pivot). */
    public function invitations(): BelongsToMany
    {
        return $this->belongsToMany(Invitation::class, 'invitation_matches')
            ->withPivot('response')
            ->withTimestamps();
    }

    public function getTitleAttribute(): string
    {
        if ($this->team_a_name && $this->team_b_name) {
            return "{$this->team_a_name} v {$this->team_b_name}";
        }
        return $this->label ?: 'Match';
    }

    /** {total, available, reserved, assigned, ticket, hidden} — hidden excluded from total. */
    public function seatStats(): array
    {
        return Seat::statsFor($this->seats()->get());
    }
}
