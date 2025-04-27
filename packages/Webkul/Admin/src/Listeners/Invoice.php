<?php

namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\InvoicedNotification;
use Webkul\Sales\Repositories\OrderTransactionRepository;

class Invoice extends Base
{
    
    public function __construct(
        protected OrderTransactionRepository $orderTransactionRepository,
    ) {}

    
    public function afterCreated($invoice)
    {
        $this->sendMail($invoice);

        if ($invoice->can_create_transaction) {
            $this->createTransaction($invoice);
        }
    }

    
    public function sendMail($invoice)
    {
        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.new_invoice_mail_to_admin')) {
                return;
            }

            $this->prepareMail($invoice, new InvoicedNotification($invoice));
        } catch (\Exception $e) {
            report($e);
        }
    }

    
    public function createTransaction($invoice)
    {
        $transactionId = md5(uniqid());

        $transactionData = [
            'transaction_id' => $transactionId,
            'status'         => $invoice->state,
            'type'           => $invoice->order->payment->method,
            'payment_method' => $invoice->order->payment->method,
            'order_id'       => $invoice->order->id,
            'invoice_id'     => $invoice->id,
            'amount'         => $invoice->grand_total,
        ];

        $this->orderTransactionRepository->create($transactionData);
    }
}
