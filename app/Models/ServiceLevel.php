<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLevel extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'color',
        'bg',
        'rank',
        'facilities',
    ];

    protected $casts = [
        'facilities' => 'array',
        'rank' => 'integer',
    ];

    public function guests()
    {
        return $this->hasMany(Guest::class, 'tier', 'id');
    }
}
