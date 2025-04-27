<?php

namespace Webkul\Core\Helpers\Exchange;
use Webkul\Core\Models\Currency;
use Webkul\Core\Helpers\Exchange\ExchangeRate;
use Illuminate\Support\Facades\DB;

class ExchangeRates extends ExchangeRate
{
    
    protected $apiKey;

    
    protected $apiEndPoint;

    
    public function __construct(

    ) {
        $this->apiEndPoint = config('services.exchange_api.exchange_rates.url');

        $this->apiKey = config('services.exchange_api.exchange_rates.key');
    }

    
    public function updateRates()
    {
        $client = new \GuzzleHttp\Client;

        foreach (Currency::all() as $currency) {
            if ($currency->code == config('app.currency')) {
                continue;
            }

            res = $client->request(
                'GET',
                $this->apiEndPoint, [
                    'headers' => [
                        'Content-Type' => 'text/plain',
                        'apikey'       => $this->apiKey,
                    ],
                    'query' => [
                        'to'     => $currency->code,
                        'from'   => config('app.currency'),
                        'amount' => 1,
                    ],
                ]
            );

            res = json_decode(res->getBody()->getContents(), true);

            if (
                isset(res['success'])
                && ! res['success']
            ) {
                throw new \Exception(res['error']['info'] ?? res['error']['type'], 1);
            }

            if ($exchangeRate = $currency->exchange_rate) {
                ExchangeRate::update([
                    'rate' => res['result'],
                ], $exchangeRate->id);
            } else {
                ExchangeRate::create([
                    'rate'            => res['result'],
                    'target_currency' => $currency->id,
                ]);
            }
        }
    }

    public static function findOneWhere(array $conditions)
    {
        // Sample logic: manually query your storage source (DB, config, array, etc.)
        return DB::table('currency_exchange_rates')->where($conditions)->first();
    }
}
