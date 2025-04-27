<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Settings\InventorySourcesDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\InventorySourceRequest;
use Webkul\Inventory\Repositories\InventorySourceRepository;

class InventorySourceController extends Controller
{
    
    public function __construct(protected InventorySourceRepository $inventorySourceRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(InventorySourcesDataGrid::class)->process();
        }

        return view('admin::settings.inventory-sources.index');
    }

    
    public function create()
    {
        return view('admin::settings.inventory-sources.create');
    }

    
    public function store(InventorySourceRequest $inventorySourceRequest)
    {
        Event::dispatch('inventory.inventory_source.create.before');

        $dat = request()->only([
            'code',
            'name',
            'description',
            'latitude',
            'longitude',
            'priority',
            'contact_name',
            'contact_email',
            'contact_number',
            'contact_fax',
            'country',
            'state',
            'city',
            'street',
            'postcode',
            'status',
        ]);

        $inventorySource = $this->inventorySourceRepository->create($dat);

        Event::dispatch('inventory.inventory_source.create.after', $inventorySource);

        session()->flash('success', trans('admin::app.settings.inventory-sources.create-success'));

        return redirect()->route('admin.settings.inventory_sources.index');
    }

    
    public function edit(int $i)
    {
        $inventorySource = $this->inventorySourceRepository->findOrFail($i);

        return view('admin::settings.inventory-sources.edit', compact('inventorySource'));
    }

    
    public function update(InventorySourceRequest $inventorySourceRequest, int $i)
    {
        Event::dispatch('inventory.inventory_source.update.before', $i);

        if (! $inventorySourceRequest->status) {
            $inventorySourceRequest['status'] = 0;
        }

        $dat = $inventorySourceRequest->only([
            'code',
            'name',
            'description',
            'latitude',
            'longitude',
            'priority',
            'contact_name',
            'contact_email',
            'contact_number',
            'contact_fax',
            'country',
            'state',
            'city',
            'street',
            'postcode',
            'status',
        ]);

        $inventorySource = $this->inventorySourceRepository->update($dat, $i);

        Event::dispatch('inventory.inventory_source.update.after', $inventorySource);

        session()->flash('success', trans('admin::app.settings.inventory-sources.update-success'));

        return redirect()->route('admin.settings.inventory_sources.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        $this->inventorySourceRepository->findOrFail($i);

        if ($this->inventorySourceRepository->count() == 1) {
            return response()->json(['message' => trans('admin::app.settings.inventory-sources.last-delete-error')], 400);
        }

        try {
            Event::dispatch('inventory.inventory_source.delete.before', $i);

            $this->inventorySourceRepository->delete($i);

            Event::dispatch('inventory.inventory_source.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.settings.inventory-sources.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.inventory-sources.delete-failed', ['name' => 'admin::app.settings.inventory_sources.index.title']),
        ], 500);
    }
}
