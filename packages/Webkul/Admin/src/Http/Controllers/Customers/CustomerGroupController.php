<?php

namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Customers\GroupDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Rules\Code;
use Webkul\Customer\Repositories\CustomerGroupRepository;

class CustomerGroupController extends Controller
{
    
    public function __construct(protected CustomerGroupRepository $customerGroupRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(GroupDataGrid::class)->process();
        }

        return view('admin::customers.groups.index');
    }

    
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:customer_groups,code', new Code],
            'name' => 'required',
        ]);

        Event::dispatch('customer.customer_group.create.before');

        $dat = array_merge(request()->only([
            'code',
            'name',
        ]), [
            'is_user_defined' => 1,
        ]);

        $customerGroup = $this->customerGroupRepository->create($dat);

        Event::dispatch('customer.customer_group.create.after', $customerGroup);

        return new JsonResponse([
            'message' => trans('admin::app.customers.groups.index.create.success'),
        ]);
    }

    
    public function update(): JsonResponse
    {
        $i = request()->input('id');

        $this->validate(request(), [
            'code' => ['required', 'unique:customer_groups,code,'.$i, new Code],
            'name' => 'required',
        ]);

        Event::dispatch('customer.customer_group.update.before', $i);

        $customerGroup = $this->customerGroupRepository->update(request()->only([
            'code',
            'name',
        ]), $i);

        Event::dispatch('customer.customer_group.update.after', $customerGroup);

        return new JsonResponse([
            'message' => trans('admin::app.customers.groups.index.edit.success'),
        ]);
    }

    
    public function destroy(int $i): JsonResponse
    {
        $customerGroup = $this->customerGroupRepository->findOrFail($i);

        if (! $customerGroup->is_user_defined) {
            return new JsonResponse([
                'message' => trans('admin::app.customers.groups.index.edit.group-default'),
            ], 400);
        }

        if ($customerGroup->customers->count()) {
            return new JsonResponse([
                'message' => trans('admin::app.customers.groups.customer-associate'),
            ], 400);
        }

        try {
            Event::dispatch('customer.customer_group.delete.before', $i);

            $this->customerGroupRepository->delete($i);

            Event::dispatch('customer.customer_group.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.customers.groups.index.edit.delete-success'),
            ]);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.customers.groups.index.edit.delete-failed'),
        ], 500);
    }
}
