<?php

namespace Webkul\Core\Helpers\Exchange;

use Illuminate\Database\Eloquent\Model;

abstract class ExchangeRate extends Model
{
    protected $table = 'currency_exchange_rates';

    protected $fillable = ['rate', 'target_currency'];

    abstract public function updateRates();
}
