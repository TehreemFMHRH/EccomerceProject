<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class LocalesDataGrid extends DataGrid
{
    
    public function prepareQueryBuilder()
    {
        return DB::table('locales')
            ->select(
                'id',
                'code',
                'name',
                'direction'
            );
    }

    
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.settings.locales.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.settings.locales.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.settings.locales.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'direction',
            'label'              => trans('admin::app.settings.locales.index.datagrid.direction'),
            'type'               => 'string',
            'searchable'         => true,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('admin::app.settings.locales.index.datagrid.ltr'),
                    'value' => 'ltr',
                ],
                [
                    'label' => trans('admin::app.settings.locales.index.datagrid.rtl'),
                    'value' => 'rtl',
                ],
            ],
            'sortable'   => true,
            'closure'    => function ($va) {
                if ($va->direction == 'ltr') {
                    return trans('admin::app.settings.locales.index.datagrid.ltr');
                }

                return trans('admin::app.settings.locales.index.datagrid.rtl');
            },
        ]);
    }

    
    public function prepareActions()
    {
        if (bouncer()->hasPermission('settings.locales.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.settings.locales.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.locales.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.locales.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.settings.locales.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.settings.locales.delete', $row->id);
                },
            ]);
        }
    }
}
