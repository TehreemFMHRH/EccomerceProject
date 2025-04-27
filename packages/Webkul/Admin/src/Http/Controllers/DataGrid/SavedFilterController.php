<?php

namespace Webkul\Admin\Http\Controllers\DataGrid;

use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DataGrid\Repositories\SavedFilterRepository;

class SavedFilterController extends Controller
{
    
    public function __construct(protected SavedFilterRepository $savedFilterRepository) {}

    
    public function store()
    {
        $userId = auth()->guard('admin')->user()->id;

        $this->validate(request(), [
            'name' => 'required|unique:datagrid_saved_filters,name,NULL,id,src,'.request('src').',user_id,'.$userId,
        ]);

        Event::dispatch('datagrid.saved_filter.create.before');

        $savedFilter = $this->savedFilterRepository->create([
            'user_id' => $userId,
            'name'    => request('name'),
            'src'     => request('src'),
            'applied' => request('applied'),
        ]);

        Event::dispatch('datagrid.saved_filter.create.after', $savedFilter);

        return response()->json([
            'data'    => $savedFilter,
            'message' => trans('admin::app.components.datagrid.toolbar.filter.saved-success'),
        ]);
    }

    
    public function get()
    {
        $savedFilters = $this->savedFilterRepository->findWhere([
            'src'     => request()->get('src'),
            'user_id' => auth()->guard('admin')->user()->id,
        ]);

        return response()->json(['data' => $savedFilters]);
    }

    
    public function update(int $i)
    {
        $userId = auth()->guard('admin')->user()->id;

        $this->validate(request(), [
            'name' => 'required|unique:datagrid_saved_filters,name,'.$i.',id,src,'.request('src').',user_id,'.$userId,
        ]);

        $savedFilter = $this->savedFilterRepository->findOneWhere([
            'id'      => $i,
            'user_id' => auth()->guard('admin')->user()->id,
        ]);

        if (! $savedFilter) {
            return response()->json([], 404);
        }

        Event::dispatch('datagrid.saved_filter.update.before', $i);

        $updatedFilter = $this->savedFilterRepository->update(request()->only([
            'name',
            'src',
            'applied',
        ]), $i);

        Event::dispatch('datagrid.saved_filter.update.after', $updatedFilter);

        return response()->json([
            'data'    => $updatedFilter,
            'message' => trans('admin::app.components.datagrid.toolbar.filter.updated-success'),
        ]);
    }

    
    public function destroy(int $i)
    {
        Event::dispatch('datagrid.saved_filter.delete.before', $i);

        $success = $this->savedFilterRepository->deleteWhere([
            'id'      => $i,
            'user_id' => auth()->guard('admin')->user()->id,
        ]);

        Event::dispatch('datagrid.saved_filter.delete.after', $i);

        if (! $success) {
            return response()->json([
                'message' => trans('admin::app.components.datagrid.toolbar.filter.delete-error'),
            ]);
        }

        return response()->json([
            'message' => trans('admin::app.components.datagrid.toolbar.filter.delete-success'),
        ]);
    }
}
