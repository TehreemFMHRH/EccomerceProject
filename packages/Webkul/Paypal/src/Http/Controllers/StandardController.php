<?php

namespace Webkul\Paypal\Http\Controllers;

use Webkul\Checkout\Facades\Cart;
use Webkul\Paypal\Helpers\Ipn;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class StandardController extends Controller
{

    public function __construct(
        protected OrderRepository $orderRepository,
        protected Ipn $ipnHelper
    ) {}


    public function redirect()
    {
        return view('paypal::standard-redirect');
    }


    public function cancel()
    {
        session()->flash('error', trans('shop::app.checkout.cart.paypal-payment-cancelled'));

        return redirect()->route('shop.checkout.cart.index');
    }


    public function success()
    {
        $cart = Cart::getCart();

        $dat = (new OrderResource($cart))->jsonSerialize();

        $o = $this->orderRepository->create($dat);

        Cart::deActivateCart();

        session()->flash('order_id', $o->id);

        return redirect()->route('shop.checkout.onepage.success');
    }


    public function ipn()
    {
        $this->ipnHelper->processIpn(request()->all());
    }
}
