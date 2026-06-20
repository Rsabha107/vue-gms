<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportStatus extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'label',
        'color',
        'bg_color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
