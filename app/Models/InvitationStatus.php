<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationStatus extends Model
{
    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    /**
     * Get invitations with this status.
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class, 'status_id');
    }
}
