<?php

namespace Webkul\Admin\Http\Controllers;

use Webkul\Admin\Helpers\Dashboard;

class DashboardController extends Controller
{
    
    protected $typeFunctions = [
        'over-all'                 => 'getOverAllStats',
        'today'                    => 'getTodayStats',
        'stock-threshold-products' => 'getStockThresholdProducts',
        'total-sales'              => 'getSalesStats',
        'total-visitors'           => 'getVisitorStats',
        'top-selling-products'     => 'getTopSellingProducts',
        'top-customers'            => 'getTopCustomers',
    ];

    
    public function __construct(protected Dashboard $dashboardHelper) {}

    
    public function index()
    {
        return view('admin::dashboard.index')->with([
            'startDate' => $this->dashboardHelper->getStartDate(),
            'endDate'   => $this->dashboardHelper->getEndDate(),
        ]);
    }

    
    public function stats()
    {
        $stats = $this->dashboardHelper->{$this->typeFunctions[request()->query('type')]}();

        return response()->json([
            'statistics' => $stats,
            'date_range' => $this->dashboardHelper->getDateRange(),
        ]);
    }
}
