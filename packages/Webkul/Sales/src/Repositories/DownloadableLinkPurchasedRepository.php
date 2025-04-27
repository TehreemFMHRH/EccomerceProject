<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Container\Container;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Repositories\ProductDownloadableLinkRepository;

class DownloadableLinkPurchasedRepository extends Repository
{
    
    public function __construct(
        protected ProductDownloadableLinkRepository $productDownloadableLinkRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    
    public function model(): string
    {
        return 'Webkul\Sales\Contracts\DownloadableLinkPurchased';
    }

    
    public function saveLinks($orderItem)
    {
        if (! $this->isValidDownloadableProduct($orderItem)) {
            return;
        }

        foreach ($orderItem->additional['links'] as $linkId) {
            if (! $productDownloadableLink = $this->productDownloadableLinkRepository->find($linkId)) {
                continue;
            }

            $this->create([
                'name'            => $productDownloadableLink->title,
                'product_name'    => $orderItem->name,
                'url'             => $productDownloadableLink->url,
                'file'            => $productDownloadableLink->file,
                'file_name'       => $productDownloadableLink->file_name,
                'type'            => $productDownloadableLink->type,
                'download_bought' => $productDownloadableLink->downloads * $orderItem->qty_ordered,
                'status'          => 'pending',
                'customer_id'     => $orderItem->order->customer_id,
                'order_id'        => $orderItem->order_id,
                'order_item_id'   => $orderItem->id,
            ]);
        }
    }

    
    private function isValidDownloadableProduct($orderItem): bool
    {
        if (
            stristr($orderItem->type, 'downloadable') !== false
            && isset($orderItem->additional['links'])
        ) {
            return true;
        }

        return false;
    }

    
    public function updateStatus($orderItem, $st)
    {
        $purchasedLinks = $this->findByField('order_item_id', $orderItem->id);

        foreach ($purchasedLinks as $purchasedLink) {
            if ($st == 'expired') {
                if (count($purchasedLink->order_item->invoice_items) > 0) {
                    $totalInvoiceQty = 0;

                    foreach ($purchasedLink->order_item->invoice_items as $invoice_item) {
                        $totalInvoiceQty = $totalInvoiceQty + $invoice_item->qty;
                    }

                    $orderedQty = $purchasedLink->order_item->qty_ordered;
                    $totalInvoiceQty = $totalInvoiceQty * ($purchasedLink->download_bought / $orderedQty);

                    $this->update([
                        'status'            => $purchasedLink->download_used == $totalInvoiceQty ? $st : $purchasedLink->status,
                        'download_canceled' => $purchasedLink->download_bought - $totalInvoiceQty,
                    ], $purchasedLink->id);
                } else {
                    $this->update([
                        'status'            => $st,
                        'download_canceled' => $purchasedLink->download_bought,
                    ], $purchasedLink->id);
                }
            } else {
                $this->update([
                    'status' => $st,
                ], $purchasedLink->id);
            }
        }
    }
}
