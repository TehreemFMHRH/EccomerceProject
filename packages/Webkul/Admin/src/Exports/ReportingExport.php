<?php

namespace Webkul\Admin\Exports;

use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromCollection;

class ReportingExport implements FromCollection
{
    
    public function __construct(protected $records = []) {}

    
    public function collection()
    {
        $rows[] = Arr::pluck($this->records['columns'], 'label');

        foreach ($this->records['records'] as $key => $record) {
            $dat = [];

            foreach ($this->records['columns'] as $column) {
                $dat[$column['label']] = $record[$column['key']];
            }

            $rows[] = (object) $dat;
        }

        return collect($rows);
    }
}
