<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Webkul\Admin\DataGrids\Sales\OrderShipmentDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class ShipmentController extends Controller
{
    
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemRepository $orderItemRepository,
        protected ShipmentRepository $shipmentRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderShipmentDataGrid::class)->process();
        }

        return view('admin::sales.shipments.index');
    }

    
    public function create(int $orderId)
    {
        $o = $this->orderRepository->findOrFail($orderId);

        if (! $o->channel || ! $o->canShip()) {
            session()->flash('error', trans('admin::app.sales.shipments.create.creation-error'));

            return redirect()->back();
        }

        return view('admin::sales.shipments.create', compact('order'));
    }

    
    public function store(int $orderId)
    {
        $o = $this->orderRepository->findOrFail($orderId);

        if (! $o->canShip()) {
            session()->flash('error', trans('admin::app.sales.shipments.create.order-error'));

            return redirect()->back();
        }

        $this->validate(request(), [
            'shipment.source'    => 'required',
            'shipment.items.*.*' => 'required|numeric|min:0',
        ]);

        $dat = request()->only(['shipment', 'carrier_name']);

        if (! $this->isInventoryValidate($dat)) {
            session()->flash('error', trans('admin::app.sales.shipments.create.quantity-invalid'));

            return redirect()->back();
        }

        $this->shipmentRepository->create(array_merge($dat, [
            'order_id' => $orderId,
        ]));

        session()->flash('success', trans('admin::app.sales.shipments.create.success'));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    
    public function isInventoryValidate(&$dat)
    {
        if (! isset($dat['shipment']['items'])) {
            return;
        }

        $valid = false;

        $inventorySourceId = $dat['shipment']['source'];

        foreach ($dat['shipment']['items'] as $itemId => $inventorySource) {
            $qty = $inventorySource[$inventorySourceId];

            if ((int) $qty) {
                $orderItem = $this->orderItemRepository->find($itemId);

                if ($orderItem->qty_to_ship < $qty) {
                    return false;
                }

                if ($orderItem->getTypeInstance()->isComposite()) {
                    foreach ($orderItem->children as $child) {
                        if (! $child->qty_ordered) {
                            continue;
                        }

                        $finalQty = ($child->qty_ordered / $orderItem->qty_ordered) * $qty;

                        $availableQty = $child->product->inventories()
                            ->where('inventory_source_id', $inventorySourceId)
                            ->sum('qty');

                        if (
                            $child->qty_to_ship < $finalQty
                            || $availableQty < $finalQty
                        ) {
                            return false;
                        }
                    }
                } else {
                    $availableQty = $orderItem->product->inventories()
                        ->where('inventory_source_id', $inventorySourceId)
                        ->sum('qty');

                    if (
                        $orderItem->qty_to_ship < $qty
                        || $availableQty < $qty
                    ) {
                        return false;
                    }
                }

                $valid = true;
            } else {
                unset($dat['shipment']['items'][$itemId]);
            }
        }

        return $valid;
    }

    
    public function view(int $i)
    {
        $shipment = $this->shipmentRepository->findOrFail($i);

        return view('admin::sales.shipments.view', compact('shipment'));
    }
}
