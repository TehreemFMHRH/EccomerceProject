<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Models\Order;

class ShipmentRepository extends Repository
{

    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemRepository $orderItemRepository,
        protected ShipmentItemRepository $shipmentItemRepository,
        Container $container
    ) {
        parent::__construct($container);
    }


    public function model(): string
    {
        return 'Webkul\Sales\Contracts\Shipment';
    }


    public function create(array $dat, $orderState = null)
    {
        DB::beginTransaction();

        try {
            Event::dispatch('sales.shipment.save.before', $dat);

            $o = $this->orderRepository->find($dat['order_id']);

            $shipment = $this->model->create([
                'order_id'            => $o->id,
                'total_qty'           => 0,
                'total_weight'        => 0,
                'carrier_title'       => $dat['shipment']['carrier_title'],
                'track_number'        => $dat['shipment']['track_number'],
                'customer_id'         => $o->customer_id,
                'customer_type'       => $o->customer_type,
                'order_address_id'    => $o->shipping_address->id,
                'inventory_source_id' => $dat['shipment']['source'],
            ]);

            $totalQty = $totalWeight = 0;

            foreach ($dat['shipment']['items'] as $itemId => $inventorySource) {
                $qty = $inventorySource[$dat['shipment']['source']];

                $orderItem = $this->orderItemRepository->find($itemId);

                if ($qty > $orderItem->qty_to_ship) {
                    $qty = $orderItem->qty_to_ship;
                }

                $totalQty += $qty;

                $totalWeight += $orderItem->weight * $qty;

                $this->shipmentItemRepository->create([
                    'shipment_id'         => $shipment->id,
                    'order_item_id'       => $orderItem->id,
                    'name'                => $orderItem->name,
                    'sku'                 => $orderItem->getTypeInstance()->getOrderedItem($orderItem)->sku,
                    'qty'                 => $qty,
                    'weight'              => $orderItem->weight * $qty,
                    'price'               => $orderItem->price,
                    'price_incl_tax'      => $orderItem->price_incl_tax,
                    'base_price'          => $orderItem->base_price,
                    'base_price_incl_tax' => $orderItem->base_price_incl_tax,
                    'total'               => $orderItem->price * $qty,
                    'base_total'          => $orderItem->base_price * $qty,
                    'product_id'          => $orderItem->product_id,
                    'product_type'        => $orderItem->product_type,
                    'additional'          => $orderItem->additional,
                ]);

                if ($orderItem->getTypeInstance()->isComposite()) {
                    foreach ($orderItem->children as $child) {
                        if (! $child->qty_ordered) {
                            $finalQty = $qty;
                        } else {
                            $finalQty = ($child->qty_ordered / $orderItem->qty_ordered) * $qty;
                        }

                        $this->shipmentItemRepository->updateProductInventory([
                            'shipment'  => $shipment,
                            'product'   => $child->product,
                            'qty'       => $finalQty,
                            'vendor_id' => $dat['vendor_id'] ?? 0,
                        ]);

                        $this->orderItemRepository->update(['qty_shipped' => $child->qty_shipped + $finalQty], $child->id);
                    }
                } else {
                    $this->shipmentItemRepository->updateProductInventory([
                        'shipment'  => $shipment,
                        'product'   => $orderItem->product,
                        'qty'       => $qty,
                        'vendor_id' => $dat['vendor_id'] ?? 0,
                    ]);
                }

                $this->orderItemRepository->update(['qty_shipped' => $orderItem->qty_shipped + $qty], $orderItem->id);
            }

            $shipment->update([
                'total_qty'             => $totalQty,
                'total_weight'          => $totalWeight,
                'inventory_source_name' => $shipment->inventory_source->name,
            ]);

            if (isset($orderState)) {
                $this->orderRepository->updateOrderStatus($o, $orderState);
            } elseif ($o->hasOpenInvoice()) {
                $this->orderRepository->updateOrderStatus($o, Order::STATUS_PENDING_PAYMENT);
            } else {
                $this->orderRepository->updateOrderStatus($o);
            }

            Event::dispatch('sales.shipment.save.after', $shipment);
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }

        DB::commit();

        return $shipment;
    }
}
