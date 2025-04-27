<?php

namespace Webkul\Core\Helpers\Exchange;

use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\ExchangeRateRepository;

class FixerExchange extends ExchangeRate
{
    
    protected $apiKey;

    
    protected $apiEndPoint;

    
    public function __construct(
        protected CurrencyRepository $currencyRepository,
        protected ExchangeRateRepository $exchangeRateRepository
    ) {
        $this->apiEndPoint = 'http://data.fixer.io/api';

        $this->apiKey = config('services.exchange_api')['fixer']['key'];
    }

    
    public function updateRates()
    {
        $client = new \GuzzleHttp\Client;

        foreach ($this->currencyRepository->all() as $currency) {
            if ($currency->code == config('app.currency')) {
                continue;
            }

            res = $client->request('GET', $this->apiEndPoint.'/'.date('Y-m-d').'?access_key='.$this->apiKey.'&base='.config('app.currency').'&symbols='.$currency->code);

            res = json_decode(res->getBody()->getContents(), true);

            if (
                isset(res['success'])
                && ! res['success']
            ) {
                throw new \Exception(res['error']['info'] ?? res['error']['type'], 1);
            }

            if ($exchangeRate = $currency->exchange_rate) {
                $this->exchangeRateRepository->update([
                    'rate' => res['rates'][$currency->code],
                ], $exchangeRate->id);
            } else {
                $this->exchangeRateRepository->create([
                    'rate'            => res['rates'][$currency->code],
                    'target_currency' => $currency->id,
                ]);
            }
        }
    }
}
