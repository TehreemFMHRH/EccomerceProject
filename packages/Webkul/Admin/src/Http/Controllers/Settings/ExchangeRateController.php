<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Settings\ExchangeRatesDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\ExchangeRateRepository;

class ExchangeRateController extends Controller
{
    
    public function __construct(
        protected ExchangeRateRepository $exchangeRateRepository,
        protected CurrencyRepository $currencyRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ExchangeRatesDataGrid::class)->process();
        }

        $currencies = $this->currencyRepository->with('exchange_rate')->all();

        return view('admin::settings.exchange-rates.index', compact('currencies'));
    }

    
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'target_currency' => ['required', 'unique:currency_exchange_rates,target_currency'],
            'rate'            => 'required|numeric',
        ]);

        Event::dispatch('core.exchange_rate.create.before');

        $exchangeRate = $this->exchangeRateRepository->create(request()->only([
            'target_currency',
            'rate',
        ]));

        Event::dispatch('core.exchange_rate.create.after', $exchangeRate);

        return new JsonResponse([
            'message' => trans('admin::app.settings.exchange-rates.index.create-success'),
        ]);
    }

    
    public function edit(int $i): JsonResponse
    {
        $currencies = $this->currencyRepository->all();

        $exchangeRate = $this->exchangeRateRepository->findOrFail($i);

        return new JsonResponse([
            'data' => [
                'currencies'   => $currencies,
                'exchangeRate' => $exchangeRate,
            ],
        ]);
    }

    
    public function update(): JsonResponse
    {
        $this->validate(request(), [
            'target_currency' => ['required', 'unique:currency_exchange_rates,target_currency,'.request()->id],
            'rate'            => 'required|numeric',
        ]);

        Event::dispatch('core.exchange_rate.update.before', request()->id);

        $exchangeRate = $this->exchangeRateRepository->update(request()->only([
            'target_currency',
            'rate',
        ]), request()->id);

        Event::dispatch('core.exchange_rate.update.after', $exchangeRate);

        return new JsonResponse([
            'message' => trans('admin::app.settings.exchange-rates.index.update-success'),
        ]);
    }

    
    public function updateRates()
    {
        try {
            app(config('services.exchange_api.'.config('services.exchange_api.default').'.class'))->updateRates();

            session()->flash('success', trans('admin::app.settings.exchange-rates.index.update-success'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('admin.settings.exchange_rates.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        try {
            $this->exchangeRateRepository->findOrFail($i);

            Event::dispatch('core.exchange_rate.delete.before', $i);

            $this->exchangeRateRepository->delete($i);

            Event::dispatch('core.exchange_rate.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.settings.exchange-rates.index.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.exchange-rates.index.delete-error'),
        ], 500);
    }
}
