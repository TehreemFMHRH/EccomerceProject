<?php

namespace Webkul\Admin\Http\Controllers\Settings\Tax;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Settings\TaxCategoryDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\TaxCategoryResource;
use Webkul\Tax\Repositories\TaxCategoryRepository;
use Webkul\Tax\Repositories\TaxRateRepository;

class TaxCategoryController extends Controller
{
    
    public function __construct(
        protected TaxCategoryRepository $taxCategoryRepository,
        protected TaxRateRepository $taxRateRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(TaxCategoryDataGrid::class)->process();
        }

        return view('admin::settings.taxes.categories.index')->with('taxRates', $this->taxRateRepository->all());
    }

    
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'code'        => 'required|string|unique:tax_categories,code',
            'name'        => 'required|string',
            'description' => 'required|string',
            'taxrates'    => 'array|required',
        ]);

        Event::dispatch('tax.category.create.before');

        $dat = request()->only([
            'code',
            'name',
            'description',
            'taxrates',
        ]);

        $taxCategory = $this->taxCategoryRepository->create($dat);

        $taxCategory->tax_rates()->sync($dat['taxrates']);

        Event::dispatch('tax.category.create.after', $taxCategory);

        return new JsonResponse([
            'message' => trans('admin::app.settings.taxes.categories.index.create-success'),
        ]);
    }

    
    public function edit(int $i): TaxCategoryResource
    {
        $taxCategory = $this->taxCategoryRepository->findOrFail($i);

        return new TaxCategoryResource($taxCategory);
    }

    
    public function update(): JsonResponse
    {
        $i = request()->id;

        $this->validate(request(), [
            'code'        => 'required|string|unique:tax_categories,code,'.$i,
            'name'        => 'required|string',
            'description' => 'required|string',
            'taxrates'    => 'array|required',
        ]);

        Event::dispatch('tax.category.update.before', $i);

        $dat = request()->only([
            'code',
            'name',
            'description',
            'taxrates',
        ]);

        $taxCategory = $this->taxCategoryRepository->update($dat, $i);

        $taxCategory->tax_rates()->sync($dat['taxrates']);

        Event::dispatch('tax.category.update.after', $taxCategory);

        return new JsonResponse([
            'message' => trans('admin::app.settings.taxes.categories.index.update-success'),
        ]);
    }

    
    public function destroy(int $i): JsonResponse
    {
        try {
            $taxCategory = $this->taxCategoryRepository->findOrFail($i);

            if (! $taxCategory->tax_rates()->count()) {
                Event::dispatch('tax.category.delete.before', $i);

                $taxCategory->delete();

                Event::dispatch('tax.category.delete.after', $i);

                return new JsonResponse([
                    'message' => trans('admin::app.settings.taxes.categories.index.delete-success'),
                ]);
            }

            return new JsonResponse([
                'message' => trans('admin::app.settings.taxes.categories.index.can-not-delete'),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.taxes.categories.index.delete-failed'),
            ], 500);
        }
    }
}
