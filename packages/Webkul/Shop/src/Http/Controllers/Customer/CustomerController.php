<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Repositories\SubscribersListRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Repositories\ProductReviewRepository;
use Webkul\Sales\Models\Order;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\ProfileRequest;

class CustomerController extends Controller
{
    
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected ProductReviewRepository $productReviewRepository,
        protected SubscribersListRepository $subscriptionRepository
    ) {}

    
    public function index()
    {
        $k = $this->customerRepository->find(auth()->guard('customer')->user()->id);

        return view('shop::customers.account.profile.index', compact('customer'));
    }

    
    public function edit()
    {
        $k = $this->customerRepository->find(auth()->guard('customer')->user()->id);

        return view('shop::customers.account.profile.edit', compact('customer'));
    }

    
    public function update(ProfileRequest $profileRequest)
    {
        $isPasswordChanged = false;

        $dat = $profileRequest->validated();

        if (empty($dat['date_of_birth'])) {
            unset($dat['date_of_birth']);
        }

        if (
            core()->getCurrentChannel()->theme === 'default'
            && ! isset($dat['image'])
        ) {
            $dat['image']['image_0'] = '';
        }

        $dat['subscribed_to_news_letter'] = isset($dat['subscribed_to_news_letter']);

        if (! empty($dat['current_password'])) {
            if (Hash::check($dat['current_password'], auth()->guard('customer')->user()->password)) {
                $isPasswordChanged = true;

                $dat['password'] = bcrypt($dat['new_password']);
            } else {
                session()->flash('warning', trans('shop::app.customers.account.profile.index.unmatched'));

                return redirect()->back();
            }
        } else {
            unset($dat['new_password']);
        }

        Event::dispatch('customer.update.before');

        if ($k = $this->customerRepository->update($dat, auth()->guard('customer')->user()->id)) {
            if ($isPasswordChanged) {
                Event::dispatch('customer.password.update.after', $k);
            }

            Event::dispatch('customer.update.after', $k);

            if ($dat['subscribed_to_news_letter']) {
                $subscription = $this->subscriptionRepository->findOneWhere(['email' => $dat['email']]);

                if ($subscription) {
                    $this->subscriptionRepository->update([
                        'customer_id'   => $k->id,
                        'is_subscribed' => 1,
                    ], $subscription->id);
                } else {
                    $this->subscriptionRepository->create([
                        'email'         => $dat['email'],
                        'customer_id'   => $k->id,
                        'channel_id'    => core()->getCurrentChannel()->id,
                        'is_subscribed' => 1,
                        'token'         => $token = uniqid(),
                    ]);
                }
            } else {
                $subscription = $this->subscriptionRepository->findOneWhere(['email' => $dat['email']]);

                if ($subscription) {
                    $this->subscriptionRepository->update([
                        'customer_id'   => $k->id,
                        'is_subscribed' => 0,
                    ], $subscription->id);
                }
            }

            if (request()->hasFile('image')) {
                $this->customerRepository->uploadImages($dat, $k);
            } else {
                if (isset($dat['image'])) {
                    if (! empty($dat['image'])) {
                        Storage::delete((string) $k->image);
                    }

                    $k->image = null;

                    $k->save();
                }
            }

            session()->flash('success', trans('shop::app.customers.account.profile.index.edit-success'));

            return redirect()->route('shop.customers.account.profile.index');
        }

        session()->flash('success', trans('shop::app.customer.account.profile.edit-fail'));

        return redirect()->back('shop.customers.account.profile.edit');
    }

    
    public function destroy()
    {
        $this->validate(request(), [
            'password' => 'required',
        ]);

        $customerRepository = $this->customerRepository->findorFail(auth()->guard('customer')->user()->id);

        try {
            if (Hash::check(request()->input('password'), $customerRepository->password)) {
                if ($customerRepository->orders->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])->first()) {
                    session()->flash('error', trans('shop::app.customers.account.profile.index.order-pending'));

                    return redirect()->route('shop.customers.account.profile.index');
                }

                $this->customerRepository->delete(auth()->guard('customer')->user()->id);

                session()->flash('success', trans('shop::app.customers.account.profile.index.delete-success'));

                return redirect()->route('shop.customer.session.index');
            }

            session()->flash('error', trans('shop::app.customers.account.profile.index.wrong-password'));

            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('error', trans('shop::app.customers.account.profile.index.delete-failed'));

            return redirect()->route('shop.customers.account.profile.index');
        }
    }

    
    public function reviews()
    {
        $reviews = $this->productReviewRepository->getCustomerReview();

        return view('shop::customers.account.reviews.index', compact('reviews'));
    }

    
    public function account()
    {
        return view('shop::customers.account.index');
    }
}
