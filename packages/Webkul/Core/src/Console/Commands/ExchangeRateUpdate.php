<?php

namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Command;

class ExchangeRateUpdate extends Command
{
    
    protected $signature = 'exchange-rate:update';

    
    protected $de = 'Automatically updates currency exchange rates ';

    
    public function handle()
    {
        try {
            app(config('services.exchange_api.'.config('services.exchange_api.default').'.class'))->updateRates();
        } catch (\Exception $e) {

        }
    }
}
