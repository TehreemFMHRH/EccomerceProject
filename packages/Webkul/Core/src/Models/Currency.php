<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Core\Contracts\Currency as CurrencyContract;
use Webkul\Core\Database\Factories\CurrencyFactory;

class Currency extends Model implements CurrencyContract
{
    use HasFactory;

    
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal',
        'group_separator',
        'decimal_separator',
        'currency_position',
    ];

    
    public function setCodeAttribute($code): void
    {
        $this->attributes['code'] = strtoupper($code);
    }

    
    public function exchange_rate(): HasOne
    {
        return $this->hasOne(CurrencyExchangeRateProxy::modelClass(), 'target_currency');
    }

    
    protected static function newFactory(): Factory
    {
        return CurrencyFactory::new();
    }
}
