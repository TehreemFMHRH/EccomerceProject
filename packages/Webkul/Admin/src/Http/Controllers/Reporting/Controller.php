<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\ReportingExport;
use Webkul\Admin\Helpers\Reporting as ReportingHelper;
use Webkul\Admin\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    
    protected $typeFunctions = [];

    
    public function __construct(protected ReportingHelper $reportingHelper) {}

    
    public function stats()
    {
        $stats = $this->reportingHelper->{$this->typeFunctions[request()->query('type')]}();

        return response()->json([
            'statistics' => $stats,
            'date_range' => $this->reportingHelper->getDateRange(),
        ]);
    }

    
    public function viewStats()
    {
        $stats = $this->reportingHelper->{$this->typeFunctions[request()->query('type')]}('table');

        return response()->json([
            'statistics' => $stats,
            'date_range' => $this->reportingHelper->getDateRange(),
        ]);
    }

    
    public function export()
    {
        $stats = $this->reportingHelper->{$this->typeFunctions[request()->query('type')]}('table');

        return Excel::download(new ReportingExport($stats), request()->query('type').'.'.request()->query('format'));
    }
}
