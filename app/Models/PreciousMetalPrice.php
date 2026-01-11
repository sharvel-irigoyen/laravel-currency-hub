<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreciousMetalPrice extends Model
{
    protected $fillable = [
        'metal_id',
        // 'currency',
        // 'unit',
        'bid',
        'ask',
        'change_val',
        'change_percent',
        'low',
        'high',
        'market_time',
    ];

    protected $casts = [
        'market_time' => 'datetime',
        'bid' => 'float', // decimal:2 casts to string in some Laravel versions, float needed for math in Resource
        'ask' => 'float',
        'change_val' => 'float',
        'change_percent' => 'float',
        'low' => 'float',
        'high' => 'float',
    ];

    public function metal()
    {
        return $this->belongsTo(PreciousMetal::class);
    }
}
