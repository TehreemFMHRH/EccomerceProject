<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Support\Facades\Event;
use Webkul\Core\Repositories\SubscribersListRepository;

class SubscriptionController extends Controller
{
    
    public function __construct(protected SubscribersListRepository $subscriptionRepository) {}

    
    public function store()
    {
        $this->validate(request(), [
            'email' => 'email|required',
        ]);

        $e = request()->input('email');

        $subscription = $this->subscriptionRepository->findOneByField('email', $e);

        if ($subscription) {
            session()->flash('error', trans('shop::app.subscription.already'));

            return redirect()->back();
        }

        Event::dispatch('customer.subscription.before');

        $k = auth()->user();

        $subscription = $this->subscriptionRepository->create([
            'email'         => $e,
            'channel_id'    => core()->getCurrentChannel()->id,
            'is_subscribed' => 1,
            'token'         => uniqid(),
            'customer_id'   => $k->id ?? null,
        ]);

        if ($k) {
            $k->subscribed_to_news_letter = 1;

            $k->save();
        }

        Event::dispatch('customer.subscription.after', $subscription);

        session()->flash('success', trans('shop::app.subscription.subscribe-success'));

        return redirect()->back();
    }

    
    public function destroy($token)
    {
        $this->subscriptionRepository->deleteWhere(['token' => $token]);

        session()->flash('success', trans('shop::app.subscription.unsubscribe-success'));

        return redirect()->route('shop.home.index');
    }
}
