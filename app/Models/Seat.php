<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seat extends Model
{
    public const AVAILABLE = 'available';
    public const RESERVED  = 'reserved';
    public const ASSIGNED  = 'assigned';
    public const TICKET    = 'ticket';

    protected $fillable = [
        'game_match_id', 'seating_template_id', 'code', 'block_code',
        'row_label', 'col', 'status', 'guest_id', 'res_label', 'hidden',
    ];

    protected $casts = ['hidden' => 'boolean'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'game_match_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SeatingTemplate::class, 'seating_template_id');
    }

    /** Serialize to the flat shape the Vue seat map consumes. */
    public function toMapArray(): array
    {
        return [
            'id'       => $this->code,        // the front-end keys seats by `id` = code
            'block'    => $this->block_code,
            'rowLabel' => $this->row_label,
            'col'      => $this->col,
            'status'   => $this->status,
            'guestId'  => $this->guest_id,
            'resLabel' => $this->res_label,
            'hidden'   => (bool) $this->hidden,
        ];
    }

    /**
     * {total, available, reserved, assigned, ticket, hidden}.
     * Hidden seats are excluded from `total` and the status buckets.
     */
    public static function statsFor(Collection $seats): array
    {
        $c = ['total' => 0, 'available' => 0, 'reserved' => 0, 'assigned' => 0, 'ticket' => 0, 'hidden' => 0];
        foreach ($seats as $s) {
            if ($s->hidden) {
                $c['hidden']++;
                continue;
            }
            $c['total']++;
            $c[$s->status]++;
        }
        return $c;
    }
}
