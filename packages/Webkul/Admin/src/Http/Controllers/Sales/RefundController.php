<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Webkul\Admin\DataGrids\Sales\OrderRefundDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Sales\Exceptions\InvalidRefundQuantityException;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\RefundRepository;

class RefundController extends Controller
{
    
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemRepository $orderItemRepository,
        protected RefundRepository $refundRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderRefundDataGrid::class)->process();
        }

        return view('admin::sales.refunds.index');
    }

    
    public function create(int $orderId)
    {
        $o = $this->orderRepository->findOrFail($orderId);

        return view('admin::sales.refunds.create', compact('order'));
    }

    
    public function store(int $orderId)
    {
        $o = $this->orderRepository->findOrFail($orderId);

        if (! $o->canRefund()) {
            session()->flash('error', trans('admin::app.sales.refunds.create.creation-error'));

            return redirect()->back();
        }

        $this->validate(request(), [
            'refund.items'   => 'array',
            'refund.items.*' => 'required|numeric|min:0',
        ]);

        $dat = request()->all();

        if (! isset($dat['refund']['shipping'])) {
            $dat['refund']['shipping'] = 0;
        }

        try {
            $totals = $this->refundRepository->getOrderItemsRefundSummary($dat['refund'], $orderId);

            if (! $totals) {
                throw new InvalidRefundQuantityException(trans('admin::app.sales.refunds.create.invalid-qty'));
            }
        } catch (InvalidRefundQuantityException $invalidRefundQuantityException) {
            session()->flash('error', $invalidRefundQuantityException->getMessage());

            return redirect()->back();
        }

        $maxRefundAmount = $totals['grand_total']['price'] - $o->refunds()->sum('base_adjustment_refund');

        $refundAmount = $totals['grand_total']['price'] - $totals['shipping']['price'] + $dat['refund']['shipping'] + $dat['refund']['adjustment_refund'] - $dat['refund']['adjustment_fee'];

        if (! $refundAmount) {
            session()->flash('error', trans('admin::app.sales.refunds.create.invalid-refund-amount-error'));

            return redirect()->back();
        }

        if ($refundAmount > $maxRefundAmount) {
            session()->flash('error', trans('admin::app.sales.refunds.create.refund-limit-error', [
                'amount' => core()->formatBasePrice($maxRefundAmount),
            ]));

            return redirect()->back();
        }

        $this->refundRepository->create(array_merge($dat, ['order_id' => $orderId]));

        session()->flash('success', trans('admin::app.sales.refunds.create.create-success'));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    
    public function updateTotals(int $orderId)
    {
        try {
            $dat = $this->refundRepository->getOrderItemsRefundSummary(request()->input(), $orderId);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json($dat);
    }

    
    public function view($i)
    {
        $refund = $this->refundRepository->findOrFail($i);

        return view('admin::sales.refunds.view', compact('refund'));
    }
}
