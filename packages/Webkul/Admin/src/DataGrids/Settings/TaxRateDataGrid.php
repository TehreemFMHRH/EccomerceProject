<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class TaxRateDataGrid extends DataGrid
{
    
    public function prepareQueryBuilder()
    {
        return DB::table('tax_rates')
            ->select(
                'id',
                'identifier',
                'state',
                'country',
                'zip_code',
                'zip_from',
                'zip_to',
                'tax_rate'
            );
    }

    
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.settings.taxes.rates.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'identifier',
            'label'      => trans('admin::app.settings.taxes.rates.index.datagrid.identifier'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'state',
            'label'      => trans('admin::app.settings.taxes.rates.index.datagrid.state'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($va) {
                if (empty($va->state)) {
                    return '*';
                }

                return $va->state;
            },
        ]);

        $this->addColumn([
            'index'      => 'country',
            'label'      => trans('admin::app.settings.taxes.rates.index.datagrid.country'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'zip_code',
            'label'      => trans('admin::app.settings.taxes.rates.index.datagrid.zip-code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'zip_from',
            'label'      => trans('admin::app.settings.taxes.rates.index.datagrid.zip-from'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'zip_to',
            'label'      => trans('admin::app.settings.taxes.rates.index.datagrid.zip-to'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'tax_rate',
            'label'      => trans('admin::app.settings.taxes.rates.index.datagrid.tax-rate'),
            'type'       => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    
    public function prepareActions()
    {
        if (bouncer()->hasPermission('settings.taxes.tax_rates.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.settings.taxes.rates.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.taxes.rates.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.taxes.tax_rates.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.settings.taxes.rates.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.settings.taxes.rates.delete', $row->id);
                },
            ]);
        }
    }
}
