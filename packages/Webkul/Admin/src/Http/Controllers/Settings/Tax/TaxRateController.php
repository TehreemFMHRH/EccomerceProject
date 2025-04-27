<?php

namespace Webkul\Admin\Http\Controllers\Settings\Tax;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Settings\TaxRateDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\TaxRateRequest;
use Webkul\Tax\Repositories\TaxRateRepository;

class TaxRateController extends Controller
{
    
    public function __construct(protected TaxRateRepository $taxRateRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(TaxRateDataGrid::class)->process();
        }

        return view('admin::settings.taxes.rates.index');
    }

    
    public function create()
    {
        if (request()->ajax()) {
            return datagrid(TaxRateDataGrid::class)->process();
        }

        return view('admin::settings.taxes.rates.create');
    }

    
    public function store(TaxRateRequest $request)
    {
        Event::dispatch('tax.rate.create.before');

        $taxRate = $this->taxRateRepository->create($request->only([
            'identifier',
            'country',
            'state',
            'tax_rate',
            'zip_code',
            'is_zip',
            'zip_from',
            'zip_to',
        ]));

        Event::dispatch('tax.rate.create.after', $taxRate);

        session()->flash('success', trans('admin::app.settings.taxes.rates.create-success'));

        return redirect()->route('admin.settings.taxes.rates.index');
    }

    
    public function edit(int $i)
    {
        $taxRate = $this->taxRateRepository->findOrFail($i);

        return view('admin::settings.taxes.rates.edit')->with('taxRate', $taxRate);
    }

    
    public function update(TaxRateRequest $request, int $i)
    {
        Event::dispatch('tax.rate.update.before', $i);

        $taxRate = $this->taxRateRepository->update($request->only([
            'identifier',
            'country',
            'state',
            'tax_rate',
            'zip_code',
            'is_zip',
            'zip_from',
            'zip_to',
        ]), $i);

        Event::dispatch('tax.rate.update.after', $taxRate);

        session()->flash('success', trans('admin::app.settings.taxes.rates.update-success'));

        return redirect()->route('admin.settings.taxes.rates.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        try {
            Event::dispatch('tax.rate.delete.before', $i);

            $this->taxRateRepository->delete($i);

            Event::dispatch('tax.rate.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.settings.taxes.rates.delete-success'),
            ]);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.taxes.rates.delete-failed'),
        ], 500);
    }
}
