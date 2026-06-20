<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    protected $table = 'nationalities';
    
    protected $fillable = [
        'alpha_2_code',
        'alpha_3_code',
        'en_short_name',
        'title',
        'num_code',
    ];
    
    public $timestamps = true;
    
    /**
     * Get nationalities formatted for frontend dropdown
     */
    public static function getForDropdown()
    {
        return static::orderBy('en_short_name')
            ->get()
            ->map(fn($n) => [
                'value' => $n->alpha_2_code,
                'label' => $n->en_short_name,
            ])
            ->toArray();
    }
}
