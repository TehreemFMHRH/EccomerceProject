<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;

class InvoiceItemRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Sales\Contracts\InvoiceItem';
    }

    
    public function updateProductInventory($dat)
    {
        if (! $dat['product']) {
            return;
        }

        if (! $dat['product']->manage_stock) {
            return;
        }

        $orderedInventory = $dat['product']->ordered_inventories()
            ->where('channel_id', $dat['invoice']->order->channel->id)
            ->first();

        if ($orderedInventory) {
            if (($orderedQty = $orderedInventory->qty - $dat['qty']) < 0) {
                $orderedQty = 0;
            }

            $orderedInventory->update(['qty' => $orderedQty]);
        }

        $inventories = $dat['product']->inventories()
            ->where('vendor_id', $dat['vendor_id'])
            ->whereIn('inventory_source_id', $dat['invoice']->order->channel->inventory_sources()->pluck('id'))
            ->orderBy('qty', 'desc')
            ->get();

        foreach ($inventories as $inventory) {
            if ($inventory->qty >= $dat['qty']) {
                $inventory->update(['qty' => $inventory->qty - $dat['qty']]);

                break;
            } else {
                $dat['qty'] -= $inventory->qty;

                $inventory->update(['qty' => 0]);
            }
        }

        Event::dispatch('catalog.product.update.after', $dat['product']);
    }
}
