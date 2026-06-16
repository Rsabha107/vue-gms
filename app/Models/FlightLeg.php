<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightLeg extends Model
{
    protected $fillable = [
        'flight_request_id',
        'dir',
        'airline',
        'flight_no',
        'from_code',
        'from_city',
        'to_code',
        'to_city',
        'date',
        'dep',
        'arr',
        'cls',
        'dur',
        'sort',
    ];

    public function flightRequest(): BelongsTo
    {
        return $this->belongsTo(FlightRequest::class);
    }
}
