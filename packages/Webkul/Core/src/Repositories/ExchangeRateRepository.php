<?php

namespace Webkul\Core\Repositories;

use Webkul\Core\Eloquent\Repository;

class ExchangeRateRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Core\Contracts\CurrencyExchangeRate';
    }
}
