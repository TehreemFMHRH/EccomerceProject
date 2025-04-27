<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Support\Facades\Event;
use Webkul\Checkout\Facades\Cart;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\Sales\Repositories\OrderRepository;

class OnepageController extends Controller
{
    
    public function index()
    {
        if (! core()->getConfigData('sales.checkout.shopping_cart.cart_page')) {
            abort(404);
        }

        Event::dispatch('checkout.load.index');

        
        if (
            ! auth()->guard('customer')->check()
            && ! core()->getConfigData('sales.checkout.shopping_cart.allow_guest_checkout')
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        
        if (auth()->guard('customer')->user()?->is_suspended) {
            session()->flash('warning', trans('shop::app.checkout.cart.suspended-account-message'));

            return redirect()->route('shop.checkout.cart.index');
        }

        
        if (Cart::hasError()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        
        if (
            ! auth()->guard('customer')->check()
            && (
                $cart->hasDownloadableItems()
                || ! $cart->hasGuestCheckoutItems()
            )
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        return view('shop::checkout.onepage.index', compact('cart'));
    }

    
    public function success(OrderRepository $orderRepository)
    {
        if (! $o = $orderRepository->find(session('order_id'))) {
            return redirect()->route('shop.checkout.cart.index');
        }

        if (
            core()->getConfigData('general.magic_ai.settings.enabled')
            && core()->getConfigData('general.magic_ai.checkout_message.enabled')
            && ! empty(core()->getConfigData('general.magic_ai.checkout_message.prompt'))
        ) {

            try {
                $model = core()->getConfigData('general.magic_ai.checkout_message.model');

                $resp = MagicAI::setModel($model)
                    ->setTemperature(0)
                    ->setPrompt($this->getCheckoutPrompt($o))
                    ->ask();

                $o->checkout_message = $resp;
            } catch (\Exception $e) {
            }
        }

        return view('shop::checkout.success', compact('order'));
    }

    
    public function getCheckoutPrompt($o)
    {
        $prompt = core()->getConfigData('general.magic_ai.checkout_message.prompt');

        $products = '';

        foreach ($o->items as $item) {
            $products .= "Name: $item->name\n";
            $products .= "Qty: $item->qty_ordered\n";
            $products .= 'Price: '.core()->formatPrice($item->total)."\n\n";
        }

        $prompt .= "\n\nProduct Details:\n $products";

        $prompt .= "Customer Details:\n $o->customer_full_name \n\n";

        $prompt .= "Current Locale:\n ".core()->getCurrentLocale()->name."\n\n";

        $prompt .= "Store Name:\n".core()->getCurrentChannel()->name;

        return $prompt;
    }
}
