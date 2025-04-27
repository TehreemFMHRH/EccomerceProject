<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Shop\Http\Requests\Customer\AddressRequest;
use Webkul\Shop\Http\Resources\AddressResource;

class AddressController extends APIController
{
    
    public function __construct(protected CustomerAddressRepository $customerAddressRepository) {}

    
    public function index(): JsonResource
    {
        $k = auth()->guard('customer')->user();

        return AddressResource::collection($k->addresses);
    }

    
    public function store(AddressRequest $request): JsonResource
    {
        $k = auth()->guard('customer')->user();

        Event::dispatch('customer.addresses.create.before');

        $dat = array_merge($request->only([
            'company_name',
            'first_name',
            'last_name',
            'vat_id',
            'address',
            'country',
            'state',
            'city',
            'postcode',
            'phone',
            'default_address',
            'email',
        ]), [
            'customer_id' => $k->id,
            'address'     => implode(PHP_EOL, array_filter($request->input('address'))),
        ]);

        $customerAddress = $this->customerAddressRepository->create($dat);

        Event::dispatch('customer.addresses.create.after', $customerAddress);

        return new JsonResource([
            'data'    => new AddressResource($customerAddress),
            'message' => trans('shop::app.customers.account.addresses.index.create-success'),
        ]);
    }

    
    public function update(AddressRequest $request): JsonResource
    {
        $k = auth()->guard('customer')->user();

        Event::dispatch('customer.addresses.update.before');

        $customerAddress = $this->customerAddressRepository->update(array_merge($request->only([
            'company_name',
            'first_name',
            'last_name',
            'vat_id',
            'address',
            'country',
            'state',
            'city',
            'postcode',
            'phone',
            'default_address',
            'email',
        ]), [
            'customer_id' => $k->id,
            'address'     => implode(PHP_EOL, array_filter(request()->input('address'))),
        ]), request('id'));

        Event::dispatch('customer.addresses.update.after', $customerAddress);

        return new JsonResource([
            'data'    => new AddressResource($customerAddress),
            'message' => trans('shop::app.customers.account.addresses.index.update-success'),
        ]);
    }
}
