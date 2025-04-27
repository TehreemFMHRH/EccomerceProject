<?php

namespace Webkul\Paypal\Helpers;

use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class Ipn
{
    
    protected $post;

    
    protected $o;

    
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    
    public function processIpn($post)
    {
        $this->post = $post;

        if (! $this->postBack()) {
            return;
        }

        try {
            if (
                isset($this->post['txn_type'])
                && $this->post['txn_type'] === 'recurring_payment'
            ) {
                // Handle recurring payment (optional implementation)
                return;
            }

            $this->getOrder();

            $this->processOrder();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    
    protected function getOrder()
    {
        if (empty($this->order)) {
            $this->order = $this->orderRepository->findOneByField(['cart_id' => $this->post['invoice']]);
        }
    }

    
    protected function processOrder()
    {
        if ($this->post['payment_status'] === 'Completed') {
            if ($this->post['mc_gross'] != $this->order->grand_total) {
                return;
            }

            $this->orderRepository->update(['status' => 'processing'], $this->order->id);

            if ($this->order->canInvoice()) {
                $this->invoiceRepository->create($this->prepareInvoiceData());
            }
        }
    }

    
    protected function prepareInvoiceData()
    {
        $invoiceData = ['order_id' => $this->order->id];

        foreach ($this->order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }

    
    protected function postBack()
    {
        $method = $this->post['payment_method'] ?? null;

        $url = match ($method) {
            'paypal_standard' => 'https://ipnpb.paypal.com/cgi-bin/webscr',
            // Future payment methods:
            // 'stripe' => 'https://...',
            // 'razorpay' => 'https://...',
            default => null,
        };

        if (!$url) {
            return false;
        }

        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['cmd' => '_notify-validate'] + $this->post),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
        ]);

        $resp = curl_exec($request);
        $st   = curl_getinfo($request, CURLINFO_HTTP_CODE);

        curl_close($request);

        return $st == 200 && $resp === 'VERIFIED';
    }
}
