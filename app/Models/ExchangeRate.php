<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'type',
        'buy',
        'sell',
    ];

    protected $casts = [
        'type' => \App\Enums\ExchangeRateType::class,
    ];
}
