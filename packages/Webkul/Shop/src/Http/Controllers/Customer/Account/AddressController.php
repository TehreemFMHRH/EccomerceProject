<?php

namespace Webkul\Shop\Http\Controllers\Customer\Account;

use Illuminate\Support\Facades\Event;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\AddressRequest;

class AddressController extends Controller
{
    
    public function __construct(protected CustomerAddressRepository $customerAddressRepository) {}

    
    public function index()
    {
        return view('shop::customers.account.addresses.index')->with('addresses', auth()->guard('customer')->user()->addresses);
    }

    
    public function create()
    {
        return view('shop::customers.account.addresses.create');
    }

    
    public function store(AddressRequest $request)
    {
        $k = auth()->guard('customer')->user();

        Event::dispatch('customer.addresses.create.before');

        $dat = array_merge(request()->only([
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
            'email',
            'default_address',
        ]), [
            'customer_id' => $k->id,
            'address'     => implode(PHP_EOL, array_filter($request->input('address'))),
        ]);

        $customerAddress = $this->customerAddressRepository->create($dat);

        Event::dispatch('customer.addresses.create.after', $customerAddress);

        session()->flash('success', trans('shop::app.customers.account.addresses.index.create-success'));

        return redirect()->route('shop.customers.account.addresses.index');
    }

    
    public function edit(int $i)
    {
        $addr = $this->customerAddressRepository->findOneWhere([
            'id'          => $i,
            'customer_id' => auth()->guard('customer')->id(),
        ]);

        if (! $addr) {
            abort(404);
        }

        return view('shop::customers.account.addresses.edit')->with('address', $addr);
    }

    
    public function update(int $i, AddressRequest $request)
    {
        $k = auth()->guard('customer')->user();

        if (! $k->addresses()->find($i)) {
            session()->flash('warning', trans('shop::app.customers.account.addresses.index.security-warning'));

            return redirect()->route('shop.customers.account.addresses.index');
        }

        Event::dispatch('customer.addresses.update.before', $i);

        $dat = array_merge(request()->only([
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
            'email',
        ]), [
            'customer_id' => $k->id,
            'address'     => implode(PHP_EOL, array_filter($request->input('address'))),
        ]);

        $customerAddress = $this->customerAddressRepository->update($dat, $i);

        Event::dispatch('customer.addresses.update.after', $customerAddress);

        session()->flash('success', trans('shop::app.customers.account.addresses.index.edit-success'));

        return redirect()->route('shop.customers.account.addresses.index');
    }

    
    public function makeDefault(int $i)
    {
        $k = auth()->guard('customer')->user();

        $defaultAddress = $k->addresses()->where('default_address', 1)->first();

        $addressToSetDefault = $k->addresses()->find($i);

        if ($defaultAddress && $defaultAddress->id !== $i) {
            $defaultAddress->update(['default_address' => 0]);
        }

        if ($addressToSetDefault) {
            $addressToSetDefault->update(['default_address' => 1]);
        } else {
            session()->flash('success', trans('shop::app.customers.account.addresses.index.default-delete'));
        }

        return redirect()->back();
    }

    
    public function destroy(int $i)
    {
        $addr = $this->customerAddressRepository->findOneWhere([
            'id'          => $i,
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        if (! $addr) {
            abort(404);
        }

        Event::dispatch('customer.addresses.delete.before', $i);

        $this->customerAddressRepository->delete($i);

        Event::dispatch('customer.addresses.delete.after', $i);

        session()->flash('success', trans('shop::app.customers.account.addresses.index.delete-success'));

        return redirect()->route('shop.customers.account.addresses.index');
    }
}
