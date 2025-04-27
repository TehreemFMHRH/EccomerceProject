<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\DataGrids\Settings\LocalesDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\LocaleRepository;

class LocaleController extends Controller
{
    
    public function __construct(protected LocaleRepository $localeRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(LocalesDataGrid::class)->process();
        }

        return view('admin::settings.locales.index');
    }

    
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'code'        => ['required', 'unique:locales,code', new \Webkul\Core\Rules\Code],
            'name'        => 'required',
            'direction'   => 'required|in:ltr,rtl',
            'logo_path'   => 'array',
            'logo_path.*' => 'image|extensions:jpeg,jpg,png,svg,webp',
        ]);

        $this->localeRepository->create(request()->only([
            'code',
            'name',
            'direction',
            'logo_path',
        ]));

        return new JsonResponse([
            'message' => trans('admin::app.settings.locales.index.create-success'),
        ]);
    }

    
    public function edit(int $i): JsonResponse
    {
        $locale = $this->localeRepository->findOrFail($i);

        return new JsonResponse([
            'data' => $locale,
        ]);
    }

    
    public function update(): JsonResponse
    {
        $this->validate(request(), [
            'name'        => 'required',
            'direction'   => 'required|in:ltr,rtl',
            'logo_path'   => 'array',
            'logo_path.*' => 'image|extensions:jpeg,jpg,png,svg,webp',
        ]);

        $this->localeRepository->update(request()->only([
            'name',
            'direction',
            'logo_path',
        ]), request()->id);

        return new JsonResponse([
            'message' => trans('admin::app.settings.locales.index.update-success'),
        ]);
    }

    
    public function destroy(int $i): JsonResponse
    {
        $locale = $this->localeRepository->findOrFail($i);

        if ($locale->count() == 1) {
            return response()->json([
                'message' => trans('admin::app.settings.locales.index.last-delete-error'),
            ], 400);
        }

        try {
            $locale->delete($i);

            return new JsonResponse([
                'message' => trans('admin::app.settings.locales.index.delete-success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('admin::app.settings.locales.index.delete-failed'),
            ], 500);
        }
    }
}
