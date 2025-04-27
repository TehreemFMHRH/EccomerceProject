<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Repositories\ProductCustomizableOptionRepository;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Generators\OrderSequencer;
use Webkul\Sales\Models\Order;

class OrderRepository extends Repository
{

    public function __construct(
        protected OrderItemRepository $orderItemRepository,
        protected ProductCustomizableOptionRepository $productCustomizableOptionRepository,
        protected DownloadableLinkPurchasedRepository $downloadableLinkPurchasedRepository,
        Container $container
    ) {
        parent::__construct($container);
    }


    public function model(): string
    {
        return OrderContract::class;
    }


    public function createOrderIfNotThenRetry(array $dat)
    {
        DB::beginTransaction();

        try {
            Event::dispatch('checkout.order.save.before', [$dat]);

            $dat['status'] = Order::STATUS_PENDING;

            $o = $this->model->create(array_merge($dat, ['increment_id' => $this->generateIncrementId()]));

            $o->payment()->create($dat['payment']);

            if (isset($dat['shipping_address'])) {
                $o->addresses()->create($dat['shipping_address']);
            }

            $o->addresses()->create($dat['billing_address']);

            foreach ($dat['items'] as $item) {
                Event::dispatch('checkout.order.orderitem.save.before', $item);

                $orderItem = $this->orderItemRepository->create(array_merge($item, ['order_id' => $o->id]));

                if (! empty($item['children'])) {
                    foreach ($item['children'] as $child) {
                        $this->orderItemRepository->create(array_merge($child, ['order_id' => $o->id, 'parent_id' => $orderItem->id]));
                    }
                }

                $this->orderItemRepository->manageInventory($orderItem);

                $this->orderItemRepository->manageCustomizableOptions($orderItem);

                $this->downloadableLinkPurchasedRepository->saveLinks($orderItem, 'available');

                Event::dispatch('checkout.order.orderitem.save.after', $orderItem);
            }

            Event::dispatch('checkout.order.save.after', $o);
        } catch (\Exception $e) {
            /* rolling back first */
            DB::rollBack();

            /* storing log for errors */
            Log::error(
                'OrderRepository:createOrderIfNotThenRetry: '.$e->getMessage(),
                ['data' => $dat]
            );

            /* recalling */
            return $this->createOrderIfNotThenRetry($dat);
        } finally {
            /* commit in each case */
            DB::commit();
        }

        return $o;
    }


    public function create(array $dat)
    {
        return $this->createOrderIfNotThenRetry($dat);
    }


    public function cancel($orderOrId)
    {
        /* order */
        $o = $this->resolveOrderInstance($orderOrId);

        /* check wether order can be cancelled or not */
        if (! $o->canCancel()) {
            return false;
        }

        Event::dispatch('sales.order.cancel.before', $o);

        foreach ($o->items as $item) {
            if (! $item->qty_to_cancel) {
                continue;
            }

            $orderItems = [];

            if ($item->getTypeInstance()->isComposite()) {
                foreach ($item->children as $child) {
                    $orderItems[] = $child;
                }
            } else {
                $orderItems[] = $item;
            }

            foreach ($orderItems as $orderItem) {
                $this->orderItemRepository->returnQtyToProductInventory($orderItem);

                if ($orderItem->qty_ordered) {
                    $orderItem->qty_canceled += $orderItem->qty_to_cancel;
                    $orderItem->save();

                    if (
                        $orderItem->parent
                        && $orderItem->parent->qty_ordered
                    ) {
                        $orderItem->parent->qty_canceled += $orderItem->parent->qty_to_cancel;
                        $orderItem->parent->save();
                    }
                } else {
                    $orderItem->parent->qty_canceled += $orderItem->parent->qty_to_cancel;
                    $orderItem->parent->save();
                }
            }

            $this->downloadableLinkPurchasedRepository->updateStatus($item, 'expired');
        }

        $this->updateOrderStatus($o);

        Event::dispatch('sales.order.cancel.after', $o);

        return true;
    }


    public function generateIncrementId()
    {
        return app(OrderSequencer::class)->resolveGeneratorClass();
    }


    public function isInCompletedState($o)
    {
        $totalQtyOrdered = $totalQtyInvoiced = $totalQtyShipped = $totalQtyRefunded = $totalQtyCanceled = 0;

        foreach ($o->items()->get() as $item) {
            $totalQtyOrdered += $item->qty_ordered;
            $totalQtyInvoiced += $item->qty_invoiced;

            if (! $item->isStockable()) {
                $totalQtyShipped += $item->qty_invoiced;
            } else {
                $totalQtyShipped += $item->qty_shipped;
            }

            $totalQtyRefunded += $item->qty_refunded;
            $totalQtyCanceled += $item->qty_canceled;
        }


        if ($totalQtyOrdered === $totalQtyCanceled) {
            return false;
        }


        if ($totalQtyOrdered === $totalQtyRefunded + $totalQtyCanceled) {
            return false;
        }


        if (
            $totalQtyOrdered === $totalQtyInvoiced + $totalQtyCanceled
        ) {
            if ($totalQtyInvoiced === $totalQtyShipped) {
                return $totalQtyOrdered === $totalQtyShipped + $totalQtyCanceled;
            }

            return $totalQtyOrdered === $totalQtyShipped + $totalQtyRefunded;
        }


        if (
            $o->status === Order::STATUS_COMPLETED
            && $totalQtyOrdered != $totalQtyRefunded
        ) {
            return true;
        }

        return false;
    }


    public function isInCanceledState($o)
    {
        $totalQtyOrdered = $totalQtyCanceled = 0;

        foreach ($o->items()->get() as $item) {
            $totalQtyOrdered += $item->qty_ordered;
            $totalQtyCanceled += $item->qty_canceled;
        }

        return $totalQtyOrdered === $totalQtyCanceled;
    }


    public function isInClosedState($o)
    {
        $totalQtyOrdered = $totalQtyRefunded = $totalQtyCanceled = 0;

        foreach ($o->items()->get() as $item) {
            $totalQtyOrdered += $item->qty_ordered;
            $totalQtyRefunded += $item->qty_refunded;
            $totalQtyCanceled += $item->qty_canceled;
        }

        return $totalQtyOrdered === $totalQtyRefunded + $totalQtyCanceled;
    }


    public function updateOrderStatus($o, $orderState = null)
    {
        Event::dispatch('sales.order.update-status.before', $o);

        if (! empty($orderState)) {
            $st = $orderState;
        } else {
            $st = Order::STATUS_PROCESSING;

            if ($this->isInCompletedState($o)) {
                $st = Order::STATUS_COMPLETED;
            }

            if ($this->isInCanceledState($o)) {
                $st = Order::STATUS_CANCELED;
            } elseif ($this->isInClosedState($o)) {
                $st = Order::STATUS_CLOSED;
            }
        }

        $o->status = $st;

        $o->save();

        Event::dispatch('sales.order.update-status.after', $o);
    }


    public function collectTotals($o)
    {
        // order invoice total
        $o->sub_total_invoiced = $o->base_sub_total_invoiced = 0;
        $o->shipping_invoiced = $o->base_shipping_invoiced = 0;
        $o->tax_amount_invoiced = $o->base_tax_amount_invoiced = 0;
        $o->discount_invoiced = $o->base_discount_invoiced = 0;

        foreach ($o->invoices as $invoice) {
            $o->sub_total_invoiced += $invoice->sub_total;
            $o->base_sub_total_invoiced += $invoice->base_sub_total;

            $o->shipping_invoiced += $invoice->shipping_amount;
            $o->base_shipping_invoiced += $invoice->base_shipping_amount;

            $o->tax_amount_invoiced += $invoice->tax_amount;
            $o->base_tax_amount_invoiced += $invoice->base_tax_amount;

            $o->discount_invoiced += $invoice->discount_amount;
            $o->base_discount_invoiced += $invoice->base_discount_amount;
        }

        $o->grand_total_invoiced = $o->sub_total_invoiced + $o->shipping_invoiced + $o->tax_amount_invoiced - $o->discount_invoiced;
        $o->base_grand_total_invoiced = $o->base_sub_total_invoiced + $o->base_shipping_invoiced + $o->base_tax_amount_invoiced - $o->base_discount_invoiced;

        // order refund total
        $o->sub_total_refunded = $o->base_sub_total_refunded = 0;
        $o->shipping_refunded = $o->base_shipping_refunded = 0;
        $o->tax_amount_refunded = $o->base_tax_amount_refunded = 0;
        $o->discount_refunded = $o->base_discount_refunded = 0;
        $o->grand_total_refunded = $o->base_grand_total_refunded = 0;

        foreach ($o->refunds as $refund) {
            $o->sub_total_refunded += $refund->sub_total;
            $o->base_sub_total_refunded += $refund->base_sub_total;

            $o->shipping_refunded += $refund->shipping_amount;
            $o->base_shipping_refunded += $refund->base_shipping_amount;

            $o->tax_amount_refunded += $refund->tax_amount;
            $o->base_tax_amount_refunded += $refund->base_tax_amount;

            $o->discount_refunded += $refund->discount_amount;
            $o->base_discount_refunded += $refund->base_discount_amount;

            $o->grand_total_refunded += $refund->adjustment_refund - $refund->adjustment_fee;
            $o->base_grand_total_refunded += $refund->base_adjustment_refund - $refund->base_adjustment_fee;
        }

        $o->grand_total_refunded += $o->sub_total_refunded + $o->shipping_refunded + $o->tax_amount_refunded - $o->discount_refunded;
        $o->base_grand_total_refunded += $o->base_sub_total_refunded + $o->base_shipping_refunded + $o->base_tax_amount_refunded - $o->base_discount_refunded;

        $o->save();

        return $o;
    }


    protected function resolveOrderInstance($orderOrId)
    {
        return $orderOrId instanceof Order
            ? $orderOrId
            : $this->findOrFail($orderOrId);
    }
}
