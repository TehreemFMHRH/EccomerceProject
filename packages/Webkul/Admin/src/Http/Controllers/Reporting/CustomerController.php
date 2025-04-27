<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

class CustomerController extends Controller
{
    
    protected $typeFunctions = [
        'total-customers'             => 'getTotalCustomersStats',
        'customers-traffic'           => 'getCustomersTrafficStats',
        'customers-with-most-sales'   => 'getCustomersWithMostSales',
        'customers-with-most-orders'  => 'getCustomersWithMostOrders',
        'customers-with-most-reviews' => 'getCustomersWithMostReviews',
        'top-customer-groups'         => 'getTopCustomerGroups',
    ];

    
    public function index()
    {
        return view('admin::reporting.customers.index')->with([
            'startDate' => $this->reportingHelper->getStartDate(),
            'endDate'   => $this->reportingHelper->getEndDate(),
        ]);
    }

    
    public function view()
    {
        return view('admin::reporting.view')->with([
            'entity'    => 'customers',
            'startDate' => $this->reportingHelper->getStartDate(),
            'endDate'   => $this->reportingHelper->getEndDate(),
        ]);
    }
}
