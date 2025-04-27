<?php

namespace Webkul\Admin\Http\Controllers\DataGrid;

use Illuminate\Support\Facades\Crypt;
use Webkul\Admin\Http\Controllers\Controller;

class DataGridController extends Controller
{
    
    public function lookUp()
    {
        
        $params = $this->validate(request(), [
            'datagrid_id' => ['required'],
            'column'      => ['required'],
            'search'      => ['required', 'min:2'],
        ]);

        
        $datagrid = app(Crypt::decryptString($params['datagrid_id']));
        $datagrid->prepareColumns();

        
        $column = collect($datagrid->getColumns())->where('index', $params['column'])->firstOrFail();

        
        return app($column->options['params']['repository'])
            ->select([$column->options['params']['column']['label'].' as label', $column->options['params']['column']['value'].' as value'])
            ->where($column->options['params']['column']['label'], 'LIKE', '%'.$params['search'].'%')
            ->get();
    }
}
