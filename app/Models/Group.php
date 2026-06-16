<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'label',
    ];

    public function guests()
    {
        return $this->hasMany(Guest::class, 'group_id', 'id');
    }
}
