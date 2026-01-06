<?php

namespace App\Enums;

enum ExchangeRateType: string
{
    case PARALLEL = 'parallel';
    case SUNAT = 'sunat';

    public function label(): string
    {
        return match($this) {
            self::PARALLEL => 'Paralelo',
            self::SUNAT => 'Sunat',
        };
    }
}
