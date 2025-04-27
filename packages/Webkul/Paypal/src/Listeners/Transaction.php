<?php

namespace Webkul\Paypal\Listeners;

use Webkul\Paypal\Payment\SmartButton;
use Webkul\Sales\Repositories\OrderTransactionRepository;

class Transaction
{

    public function __construct(
        protected OrderTransactionRepository $orderTransactionRepository
    ) {}


    public function saveTransaction($invoice)
    {
        $dat = request()->all();

        if ($invoice->order->payment->method == 'paypal_smart_button') {
            if (isset($dat['orderData']['orderID'])) {
                //$transactionDetails = $this->smartButton->getOrder($dat['orderData']['orderID']);

                $transactionDetails = json_decode(json_encode($transactionDetails), true);

                if ($transactionDetails['statusCode'] == 200) {
                    $this->orderTransactionRepository->create([
                        'transaction_id' => $transactionDetails['result']['id'],
                        'status'         => $transactionDetails['result']['status'],
                        'type'           => $transactionDetails['result']['intent'],
                        'amount'         => $transactionDetails['result']['purchase_units'][0]['amount']['value'],
                        'payment_method' => $invoice->order->payment->method,
                        'order_id'       => $invoice->order->id,
                        'invoice_id'     => $invoice->id,
                        'data'           => json_encode(
                            array_merge(
                                $transactionDetails['result']['purchase_units'],
                                $transactionDetails['result']['payer']
                            )
                        ),
                    ]);
                }
            }
        } elseif ($invoice->order->payment->method == 'paypal_standard') {
            $this->orderTransactionRepository->create([
                'transaction_id' => $dat['txn_id'],
                'status'         => $dat['payment_status'],
                'type'           => $dat['payment_type'],
                'payment_method' => $invoice->order->payment->method,
                'order_id'       => $invoice->order->id,
                'invoice_id'     => $invoice->id,
                'data'           => json_encode($dat),
            ]);
        }
    }
}
