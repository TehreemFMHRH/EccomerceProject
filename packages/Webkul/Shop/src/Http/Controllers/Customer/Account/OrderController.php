<?php

namespace Webkul\Shop\Http\Controllers\Customer\Account;

use Webkul\Checkout\Facades\Cart;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\DataGrids\OrderDataGrid;
use Webkul\Shop\Http\Controllers\Controller;

class OrderController extends Controller
{
    use PDFHandler;

    
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderDataGrid::class)->process();
        }

        return view('shop::customers.account.orders.index');
    }

    
    public function view($i)
    {
        $o = $this->orderRepository->findOneWhere([
            'customer_id' => auth()->guard('customer')->id(),
            'id'          => $i,
        ]);

        abort_if(! $o, 404);

        return view('shop::customers.account.orders.view', compact('order'));
    }

    
    public function reorder(int $i)
    {
        $o = $this->orderRepository->findOrFail($i);

        foreach ($o->items as $item) {
            try {
                Cart::addProduct($item->product, $item->additional);
            } catch (\Exception $e) {
                // do nothing
            }
        }

        return redirect()->route('shop.checkout.cart.index');
    }

    
    public function printInvoice($i)
    {
        $invoice = $this->invoiceRepository->where('id', $i)
            ->whereHas('order', function ($query) {
                $query->where('customer_id', auth()->guard('customer')->id());
            })
            ->firstOrFail();

        return $this->downloadPDF(
            view('shop::customers.account.orders.pdf', compact('invoice'))->render(),
            'invoice-'.$invoice->created_at->format('d-m-Y')
        );
    }

    
    public function cancel($i)
    {
        $k = auth()->guard('customer')->user();

        /* find by order id in customer's order */
        $o = $k->orders()->find($i);

        /* if order id not found then process should be aborted with 404 page */
        if (! $o) {
            abort(404);
        }

        $result = $this->orderRepository->cancel($o);

        if ($result) {
            session()->flash('success', trans('shop::app.customers.account.orders.view.cancel-success', ['name' => trans('admin::app.customers.account.orders.order')]));
        } else {
            session()->flash('error', trans('shop::app.customers.account.orders.view.cancel-error', ['name' => trans('admin::app.customers.account.orders.order')]));
        }

        return redirect()->back();
    }
}
