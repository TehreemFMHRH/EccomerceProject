<?php

namespace Webkul\Payment\Listeners;

use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;


class GenerateInvoice
{
    
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    
    public function handle($o)
    {
        if (
            $o->payment->method == 'cashondelivery'
            && core()->getConfigData('sales.payment_methods.cashondelivery.generate_invoice')
        ) {
            $this->invoiceRepository->create(
                $this->prepareInvoiceData($o),
                core()->getConfigData('sales.payment_methods.cashondelivery.invoice_status'),
                core()->getConfigData('sales.payment_methods.cashondelivery.order_status')
            );
        }

        if (
            $o->payment->method == 'moneytransfer'
            && core()->getConfigData('sales.payment_methods.moneytransfer.generate_invoice')
        ) {
            $this->invoiceRepository->create(
                $this->prepareInvoiceData($o),
                core()->getConfigData('sales.payment_methods.moneytransfer.invoice_status'),
                core()->getConfigData('sales.payment_methods.moneytransfer.order_status')
            );
        }
    }

    
    protected function prepareInvoiceData($o)
    {
        $invoiceData = ['order_id' => $o->id];

        foreach ($o->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }
}
