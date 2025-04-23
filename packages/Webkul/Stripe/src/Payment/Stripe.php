<?php

namespace Webkul\Stripe\Payment;

use Webkul\Payment\Payment\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Stripe\Stripe as StripeClient;
use Stripe\Checkout\Session as StripeSession;

class Stripe extends Payment
{
    protected $code = 'stripe';

    public function getRedirectUrl()
    {
        try {
            // Load Stripe Secret Key
            StripeClient::setApiKey(core()->getConfigData('sales.payment_methods.stripe.secret_key'));

            // Get the last placed order
            $order = session()->get('order');

            // Create a Stripe Checkout Session
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => $order->order_currency_code,
                        'unit_amount'  => (int) ($order->grand_total * 100), // Stripe expects amount in cents
                        'product_data' => [
                            'name' => 'Order #' . $order->increment_id,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('shop.checkout.onepage.success'),
                'cancel_url'  => route('shop.checkout.cart.index'),
                'metadata' => [
                    'order_id' => $order->id,
                    'increment_id' => $order->increment_id,
                ],
            ]);

            // Save Stripe session ID if needed
            session()->put('stripe_session_id', $session->id);

            // Redirect user to Stripe's Checkout page
            return $session->url;

        } catch (\Exception $e) {
            Log::error('Stripe Error: ' . $e->getMessage());

            return route('shop.checkout.cart.index');
        }
    }

    public function getOrderPlaceRedirectUrl()
    {
        return route('shop.checkout.onepage.success');
    }
}

