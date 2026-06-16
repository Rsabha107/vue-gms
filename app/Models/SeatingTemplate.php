<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatingTemplate extends Model
{
    protected $fillable = ['venue_id', 'name'];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(SeatingBlock::class)->orderBy('position');
    }

    /** Matches currently using this template. */
    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    /**
     * Serialize blocks/rows into the nested shape the Vue components expect:
     * [{ id, label, tier, rows:[{ label, seats, aisles, walkway }] }]
     */
    public function toBlocksArray(): array
    {
        return $this->blocks->map(fn (SeatingBlock $b) => [
            'id'    => $b->code,
            'label' => $b->label,
            'tier'  => $b->tier,
            'rows'  => $b->rows->map(fn (SeatingRow $r) => [
                'label'   => $r->label,
                'seats'   => $r->seats,
                'aisles'  => $r->aisles ?? [],
                'walkway' => (bool) $r->walkway,
            ])->values()->all(),
        ])->values()->all();
    }

    public function seatCount(): int
    {
        return $this->blocks->sum(fn (SeatingBlock $b) => $b->rows->sum('seats'));
    }
}
