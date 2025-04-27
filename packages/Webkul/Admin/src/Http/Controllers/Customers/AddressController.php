<?php

namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\AddressRequest;
use Webkul\Admin\Http\Resources\AddressResource;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Customer\Models\Customer;

class AddressController extends Controller
{
    
    public function index(int $i)
    {
        $k = Customer::find($i);

        return view('admin::customers.addresses.index', compact('customer'));
    }

    
    public function create(int $i)
    {
        $k = Customer::find($i);

        return view('admin::customers.addresses.create', compact('customer'));
    }

    
    public function store(int $i, AddressRequest $request): JsonResponse
    {
        $dat = array_merge($request->only([
            'customer_id',
            'company_name',
            'vat_id',
            'first_name',
            'last_name',
            'address',
            'city',
            'country',
            'state',
            'postcode',
            'phone',
            'email',
            'default_address',
        ]), [
            'address' => implode(PHP_EOL, array_filter(request()->input('address'))),
        ]);

        Event::dispatch('customer.addresses.create.before');

        $addr = CustomerAddress::create(array_merge($dat, [
            'customer_id' => $i,
        ]));

        Event::dispatch('customer.addresses.create.after', $addr);

        return new JsonResponse([
            'message' => trans('admin::app.customers.customers.view.address.create-success'),
            'data'    => new AddressResource($addr),
        ]);
    }

    
    public function edit(int $i)
    {
        $addr = CustomerAddress::find($i);

        return view('admin::customers.addresses.edit', compact('address'));
    }

    
    public function update(int $i, AddressRequest $request): JsonResponse
    {
        $dat = array_merge($request->only([
            'customer_id',
            'company_name',
            'vat_id',
            'first_name',
            'last_name',
            'address',
            'city',
            'country',
            'state',
            'postcode',
            'phone',
            'email',
            'default_address',
        ]), [
            'address' => implode(PHP_EOL, array_filter(request()->input('address'))),
        ]);

        Event::dispatch('customer.addresses.update.before', $i);

        $addr = CustomerAddress::update($dat, [$i]);

        Event::dispatch('customer.addresses.update.after', $addr);

        return new JsonResponse([
            'message' => trans('admin::app.customers.customers.view.address.update-success'),
            'data'    => new AddressResource($addr),
        ]);
    }

    
    public function makeDefault($i)
    {
        if ($default = CustomerAddress::findOneWhere(['customer_id' => $i, 'default_address' => 1])) {
            $default->update(['default_address' => 0]);
        }

        $addr = CustomerAddress::findOneWhere([
            'id'              => request('set_as_default'),
            'customer_id'     => $i,
        ]);

        $addr->update(['default_address' => 1]);

        return new JsonResponse([
            'message' => trans('admin::app.customers.customers.view.address.set-default-success'),
            'data'    => $addr,
        ]);
    }

    
    public function destroy(int $i)
    {
        Event::dispatch('customer.addresses.delete.before', $i);

        CustomerAddress::delete($i);

        Event::dispatch('customer.addresses.delete.after', $i);

        return new JsonResponse([
            'message' => trans('admin::app.customers.customers.view.address.address-delete-success'),
        ]);
    }
}
