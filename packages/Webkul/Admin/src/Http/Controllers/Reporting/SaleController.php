<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

class SaleController extends Controller
{
    
    protected $typeFunctions = [
        'total-sales'         => 'getTotalSalesStats',
        'average-sales'       => 'getAverageSalesStats',
        'total-orders'        => 'getTotalOrdersStats',
        'purchase-funnel'     => 'getPurchaseFunnelStats',
        'abandoned-carts'     => 'getAbandonedCartsStats',
        'refunds'             => 'getRefundsStats',
        'tax-collected'       => 'getTaxCollectedStats',
        'shipping-collected'  => 'getShippingCollectedStats',
        'top-payment-methods' => 'getTopPaymentMethods',
    ];

    
    public function index()
    {
        return view('admin::reporting.sales.index')->with([
            'startDate' => $this->reportingHelper->getStartDate(),
            'endDate'   => $this->reportingHelper->getEndDate(),
        ]);
    }

    
    public function view()
    {
        return view('admin::reporting.view')->with([
            'entity'    => 'sales',
            'startDate' => $this->reportingHelper->getStartDate(),
            'endDate'   => $this->reportingHelper->getEndDate(),
        ]);
    }
}
