<?php

namespace Webkul\Admin\DataGrids\Marketing\Communications;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class EmailTemplateDataGrid extends DataGrid
{
    
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('marketing_templates')
            ->select(
                'id',
                'name',
                'status'
            );

        $this->addFilter('status', 'status');

        return $queryBuilder;
    }

    
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.marketing.communications.templates.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.marketing.communications.templates.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('admin::app.marketing.communications.templates.index.datagrid.status'),
            'type'               => 'string',
            'searchable'         => true,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('admin::app.marketing.communications.templates.index.datagrid.active'),
                    'value' => 'active',
                ],
                [
                    'label' => trans('admin::app.marketing.communications.templates.index.datagrid.inactive'),
                    'value' => 'inactive',
                ],
                [
                    'label' => trans('admin::app.marketing.communications.templates.index.datagrid.draft'),
                    'value' => 'draft',
                ],
            ],
            'sortable'   => true,
            'closure'    => function ($va) {
                if ($va->status == 'active') {
                    return trans('admin::app.marketing.communications.templates.index.datagrid.active');
                } elseif ($va->status == 'inactive') {
                    return trans('admin::app.marketing.communications.templates.index.datagrid.inactive');
                } elseif ($va->status == 'draft') {
                    return trans('admin::app.marketing.communications.templates.index.datagrid.draft');
                }
            },
        ]);
    }

    
    public function prepareActions()
    {
        if (bouncer()->hasPermission('marketing.communications.email_templates.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.marketing.communications.templates.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.marketing.communications.email_templates.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('marketing.communications.email_templates.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.marketing.communications.templates.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.marketing.communications.email_templates.delete', $row->id);
                },
            ]);
        }
    }
}
