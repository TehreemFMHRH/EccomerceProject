<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\DataGrids\Settings\CurrencyDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Enums\CurrencyPositionEnum;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Rules\Code;

class CurrencyController extends Controller
{
    
    public function __construct(protected CurrencyRepository $currencyRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CurrencyDataGrid::class)->process();
        }

        return view('admin::settings.currencies.index', [
            'currencyPositions' => CurrencyPositionEnum::options(),
        ]);
    }

    
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'code' => ['required', 'min:3', 'max:3', 'unique:currencies,code', new Code],
            'name' => 'required',
        ]);

        $this->currencyRepository->create(request()->only([
            'code',
            'name',
            'symbol',
            'decimal',
            'group_separator',
            'decimal_separator',
            'currency_position',
        ]));

        return new JsonResponse([
            'message' => trans('admin::app.settings.currencies.index.create-success'),
        ]);
    }

    
    public function edit(int $i): JsonResponse
    {
        $currency = $this->currencyRepository->findOrFail($i);

        return new JsonResponse($currency);
    }

    
    public function update(): JsonResponse
    {
        $i = request('id');

        $this->validate(request(), [
            'name' => 'required',
        ]);

        $this->currencyRepository->update(request()->only([
            'name',
            'symbol',
            'decimal',
            'group_separator',
            'decimal_separator',
            'currency_position',
        ]), $i);

        return new JsonResponse([
            'message' => trans('admin::app.settings.currencies.index.update-success'),
        ]);
    }

    
    public function destroy(int $i): JsonResponse
    {
        $this->currencyRepository->findOrFail($i);

        if ($this->currencyRepository->count() == 1) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.currencies.index.last-delete-error'),
            ], 400);
        }

        try {
            $this->currencyRepository->delete($i);

            return new JsonResponse([
                'message' => trans('admin::app.settings.currencies.index.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => trans('admin::app.settings.currencies.index.delete-failed'),
            ], 500);
        }
    }
}
