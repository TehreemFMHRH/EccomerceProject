<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Sales\OrderInvoiceDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class InvoiceController extends Controller
{
    use PDFHandler;

    
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderInvoiceDataGrid::class)->process();
        }

        return view('admin::sales.invoices.index');
    }

    
    public function create(int $orderId)
    {
        $o = $this->orderRepository->findOrFail($orderId);

        if ($o->payment->method === 'paypal_standard') {
            abort(404);
        }

        return view('admin::sales.invoices.create', compact('order'));
    }

    
    public function store(int $orderId)
    {
        $o = $this->orderRepository->findOrFail($orderId);

        if (! $o->canInvoice()) {
            session()->flash('error', trans('admin::app.sales.invoices.create.creation-error'));

            return redirect()->back();
        }

        $this->validate(request(), [
            'invoice.items'   => 'required|array',
            'invoice.items.*' => 'required|numeric|min:0',
        ]);

        if (! $this->invoiceRepository->haveProductToInvoice(request()->all())) {
            session()->flash('error', trans('admin::app.sales.invoices.create.product-error'));

            return redirect()->back();
        }

        if (! $this->invoiceRepository->isValidQuantity(request()->all())) {
            session()->flash('error', trans('admin::app.sales.invoices.create.invalid-qty'));

            return redirect()->back();
        }

        $this->invoiceRepository->create(array_merge(request()->all(), [
            'order_id' => $orderId,
        ]));

        session()->flash('success', trans('admin::app.sales.invoices.create.create-success'));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    
    public function view(int $i)
    {
        $invoice = $this->invoiceRepository->findOrFail($i);

        return view('admin::sales.invoices.view', compact('invoice'));
    }

    
    public function sendDuplicateEmail(Request $request, int $i)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $invoice = $this->invoiceRepository->findOrFail($i);

        $invoice->email = request()->input('email');

        Event::dispatch('sales.invoice.send_duplicate_email', $invoice);

        session()->flash('success', trans('admin::app.sales.invoices.view.invoice-sent'));

        return redirect()->route('admin.sales.invoices.view', $invoice->id);
    }

    
    public function printInvoice(int $i)
    {
        $invoice = $this->invoiceRepository->findOrFail($i);

        return $this->downloadPDF(
            view('admin::sales.invoices.pdf', compact('invoice'))->render(),
            'invoice-'.$invoice->created_at->format('d-m-Y')
        );
    }
}
