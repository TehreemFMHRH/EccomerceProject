<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;

class ShipmentItemRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Sales\Contracts\ShipmentItem';
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
            ->where('channel_id', $dat['shipment']->order->channel->id)
            ->first();

        if ($orderedInventory) {
            if (($orderedQty = $orderedInventory->qty - $dat['qty']) < 0) {
                $orderedQty = 0;
            }

            $orderedInventory->update(['qty' => $orderedQty]);
        }

        $inventory = $dat['product']->inventories()
            ->where('vendor_id', $dat['vendor_id'])
            ->where('inventory_source_id', $dat['shipment']->inventory_source_id)
            ->first();

        if (! $inventory) {
            return;
        }

        if (($qty = $inventory->qty - $dat['qty']) < 0) {
            $qty = 0;
        }

        $inventory->update(['qty' => $qty]);

        Event::dispatch('catalog.product.update.after', $dat['product']);
    }
}
