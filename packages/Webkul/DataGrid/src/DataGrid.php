<?php

namespace Webkul\DataGrid;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\DataGrid\Enums\ColumnTypeEnum;
use Webkul\DataGrid\Exports\DataGridExport;

abstract class DataGrid
{
    
    protected $primaryColumn = 'id';

    
    protected $sortColumn;

    
    protected $sortOrder = 'desc';

    
    protected $itemsPerPage = 10;

    
    protected $perPageOptions = [10, 20, 30, 40, 50];

    
    protected $columns = [];

    
    protected $actions = [];

    
    protected $massActions = [];

    
    protected $queryBuilder;

    
    protected LengthAwarePaginator $paginator;

    
    protected bool $exportable = false;

    
    protected string $exportFileName;

    
    protected string $exportFileExtension = 'csv';

    
    abstract public function prepareQueryBuilder();

    
    abstract public function prepareColumns();

    
    public function prepareActions() {}

    
    public function prepareMassActions() {}

    
    public function setPrimaryColumn(string $primaryColumn): void
    {
        $this->primaryColumn = $primaryColumn;
    }

    
    public function getPrimaryColumn(): string
    {
        return $this->primaryColumn;
    }

    
    public function setSortColumn(string $sortColumn): void
    {
        $this->sortColumn = $sortColumn;
    }

    
    public function getSortColumn(): ?string
    {
        return $this->sortColumn;
    }

    
    public function setSortOrder(string $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    
    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    
    public function setItemsPerPage(int $itemsPerPage): void
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    
    public function setPerPageOptions(array $perPageOptions): void
    {
        $this->perPageOptions = $perPageOptions;
    }

    
    public function getPerPageOptions(): array
    {
        return $this->perPageOptions;
    }

    
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    
    public function addColumn(array $column): void
    {
        $this->dispatchEvent('columns.add.before', [$this, $column]);

        $this->columns[] = Column::resolveType($column);

        $this->dispatchEvent('columns.add.after', [$this, $this->columns[count($this->columns) - 1]]);
    }

    
    public function getColumns(): array
    {
        return $this->columns;
    }

    
    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }

    
    public function addAction(array $action): void
    {
        $this->dispatchEvent('actions.add.before', [$this, $action]);

        $this->actions[] = new Action(
            index: $action['index'] ?? '',
            icon: $action['icon'] ?? '',
            title: $action['title'],
            method: $action['method'],
            url: $action['url'],
        );

        $this->dispatchEvent('actions.add.after', [$this, $this->actions[count($this->actions) - 1]]);
    }

    
    public function getActions(): array
    {
        return $this->actions;
    }

    
    public function setMassActions(array $massActions): void
    {
        $this->massActions = $massActions;
    }

    
    public function addMassAction(array $massAction): void
    {
        $this->dispatchEvent('mass_actions.add.before', [$this, $massAction]);

        $this->massActions[] = new MassAction(
            icon: $massAction['icon'] ?? '',
            title: $massAction['title'],
            method: $massAction['method'],
            url: $massAction['url'],
            options: $massAction['options'] ?? [],
        );

        $this->dispatchEvent('mass_actions.add.after', [$this, $this->massActions[count($this->massActions) - 1]]);
    }

    
    public function getMassActions(): array
    {
        return $this->massActions;
    }

    
    public function setQueryBuilder($queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    
    public function getQueryBuilder(): mixed
    {
        return $this->queryBuilder;
    }

    
    public function addFilter(string $datagridColumn, mixed $queryColumn): void
    {
        $this->dispatchEvent('filters.add.before', [$this, $datagridColumn, $queryColumn]);

        foreach ($this->columns as $column) {
            if ($column->getIndex() === $datagridColumn) {
                $column->setColumnName($queryColumn);

                break;
            }
        }

        $this->dispatchEvent('filters.add.after', [$this, $datagridColumn, $queryColumn]);
    }

    
    public function setExportable(bool $exportable): void
    {
        $this->exportable = $exportable;
    }

    
    public function getExportable(): bool
    {
        return $this->exportable;
    }

    
    public function isExportable(): bool
    {
        return $this->getExportable();
    }

    
    public function setExportFileName(string $exportFileName): void
    {
        $this->exportFileName = $exportFileName;
    }

    
    public function getExportFileName(): string
    {
        return $this->exportFileName;
    }

    
    public function setExportFileExtension(string $exportFileExtension = 'csv'): void
    {
        $this->exportFileExtension = $exportFileExtension;
    }

    
    public function getExportFileExtension(): string
    {
        return $this->exportFileExtension;
    }

    
    public function getExporter()
    {
        return new DataGridExport($this);
    }

    
    public function getExportFileNameWithExtension(): string
    {
        return $this->getExportFileName().'.'.$this->getExportFileExtension();
    }

    
    public function downloadExportFile()
    {
        return Excel::download($this->getExporter(), $this->getExportFileNameWithExtension());
    }

    
    public function process()
    {
        $this->prepare();

        if ($this->isExportable()) {
            return $this->downloadExportFile();
        }

        return response()->json($this->formatData());
    }

    
    public function toJson()
    {
        return $this->process();
    }

    
    protected function validatedRequest(): array
    {
        request()->validate([
            'filters'     => ['sometimes', 'required', 'array'],
            'sort'        => ['sometimes', 'required', 'array'],
            'pagination'  => ['sometimes', 'required', 'array'],
            'export'      => ['sometimes', 'required', 'boolean'],
            'format'      => ['sometimes', 'required', 'in:csv,xls,xlsx'],
        ]);

        return request()->only(['filters', 'sort', 'pagination', 'export', 'format']);
    }

    
    protected function processRequestedFilters(array $requestedFilters)
    {
        $this->dispatchEvent('process_request.filters.before', $this);

        foreach ($requestedFilters as $requestedColumn => $requestedValues) {
            if ($requestedColumn === 'all') {
                $this->queryBuilder->where(function ($scopeQueryBuilder) use ($requestedValues) {
                    foreach ($requestedValues as $va) {
                        collect($this->columns)
                            ->filter(fn ($column) => $column->getSearchable() && ! in_array($column->getType(), [
                                ColumnTypeEnum::BOOLEAN->value,
                                ColumnTypeEnum::AGGREGATE->value,
                            ]))
                            ->each(fn ($column) => $scopeQueryBuilder->orWhere($column->getColumnName(), 'LIKE', '%'.$va.'%'));
                    }
                });
            } else {
                collect($this->columns)
                    ->first(fn ($column) => $column->getIndex() === $requestedColumn)
                    ->processFilter($this->queryBuilder, $requestedValues);
            }
        }

        $this->dispatchEvent('process_request.filters.after', $this);
    }

    
    protected function processRequestedSorting($requestedSort)
    {
        $this->dispatchEvent('process_request.sorting.before', $this);

        if (! $this->sortColumn) {
            $this->sortColumn = $this->primaryColumn;
        }

        $this->queryBuilder->orderBy($requestedSort['column'] ?? $this->sortColumn, $requestedSort['order'] ?? $this->sortOrder);

        $this->dispatchEvent('process_request.sorting.after', $this);
    }

    
    protected function processRequestedPagination(array $requestedPagination): void
    {
        $this->dispatchEvent('process_request.paginated.before', $this);

        $this->paginator = $this->queryBuilder->paginate(
            $requestedPagination['per_page'] ?? $this->itemsPerPage,
            ['*'],
            'page',
            $requestedPagination['page'] ?? 1
        );

        $this->dispatchEvent('process_request.paginated.after', $this);
    }

    
    protected function processRequestedExport(string $exportFileExtension = 'csv'): void
    {
        $this->dispatchEvent('process_request.export.before', $this);

        $this->setExportable(true);

        $this->setExportFileName(Str::random(36));

        $this->setExportFileExtension($exportFileExtension);

        $this->dispatchEvent('process_request.export.after', $this);
    }

    
    protected function processRequest(): void
    {
        $this->dispatchEvent('process_request.before', $this);

        
        $requestedParams = $this->validatedRequest();

        $this->processRequestedFilters($requestedParams['filters'] ?? []);

        $this->processRequestedSorting($requestedParams['sort'] ?? []);

        
        isset($requestedParams['export']) && (bool) $requestedParams['export']
            ? $this->processRequestedExport($requestedParams['format'] ?? null)
            : $this->processRequestedPagination($requestedParams['pagination'] ?? []);

        $this->dispatchEvent('process_request.after', $this);
    }

    
    protected function sanitizeRow($row): \stdClass
    {
        
        $tempRow = json_decode(json_encode($row), true);

        foreach ($tempRow as $column => $va) {
            if (! is_string($tempRow[$column])) {
                continue;
            }

            if (is_array($va)) {
                return $this->sanitizeRow($tempRow[$column]);
            } else {
                $row->{$column} = strip_tags($va);
            }
        }

        return $row;
    }

    
    protected function formatColumns(): array
    {
        return collect($this->columns)
            ->map(fn ($column) => $column->toArray())
            ->toArray();
    }

    
    protected function formatActions(): array
    {
        return collect($this->actions)
            ->map(fn ($action) => $action->toArray())
            ->toArray();
    }

    
    protected function formatMassActions(): array
    {
        return collect($this->massActions)
            ->map(fn ($massAction) => $massAction->toArray())
            ->toArray();
    }

    
    protected function formatRecords($records): mixed
    {
        foreach ($records as $record) {
            $record = $this->sanitizeRow($record);

            foreach ($this->columns as $column) {
                if ($closure = $column->getClosure()) {
                    $record->{$column->getIndex()} = $closure($record);
                }
            }

            $record->actions = [];

            foreach ($this->actions as $index => $action) {
                $getUrl = $action->url;

                $record->actions[] = [
                    'index'  => ! empty($action->index) ? $action->index : 'action_'.$index + 1,
                    'icon'   => $action->icon,
                    'title'  => $action->title,
                    'method' => $action->method,
                    'url'    => $getUrl($record),
                ];
            }
        }

        return $records;
    }

    
    protected function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return [
            'id'           => Crypt::encryptString(get_called_class()),
            'columns'      => $this->formatColumns(),
            'actions'      => $this->formatActions(),
            'mass_actions' => $this->formatMassActions(),
            'records'      => $this->formatRecords($paginator['data']),
            'meta'         => [
                'primary_column'   => $this->primaryColumn,
                'from'             => $paginator['from'],
                'to'               => $paginator['to'],
                'total'            => $paginator['total'],
                'per_page_options' => $this->perPageOptions,
                'per_page'         => $paginator['per_page'],
                'current_page'     => $paginator['current_page'],
                'last_page'        => $paginator['last_page'],
            ],
        ];
    }

    
    protected function dispatchEvent(string $eventName, mixed $payload): void
    {
        $reflection = new \ReflectionClass($this);

        $datagridName = Str::snake($reflection->getShortName());

        Event::dispatch("datagrid.{$datagridName}.{$eventName}", $payload);
    }

    
    protected function prepare(): void
    {
        $this->dispatchEvent('prepare.before', $this);

        
        $this->dispatchEvent('columns.prepare.before', $this);

        $this->prepareColumns();

        $this->dispatchEvent('columns.prepare.after', $this);

        
        $this->dispatchEvent('actions.prepare.before', $this);

        $this->prepareActions();

        $this->dispatchEvent('actions.prepare.after', $this);

        
        $this->dispatchEvent('mass_actions.prepare.before', $this);

        $this->prepareMassActions();

        $this->dispatchEvent('mass_actions.prepare.after', $this);

        
        $this->dispatchEvent('query_builder.prepare.before', $this);

        $this->setQueryBuilder($this->prepareQueryBuilder());

        $this->dispatchEvent('query_builder.prepare.after', $this);

        
        $this->processRequest();

        $this->dispatchEvent('prepare.after', $this);
    }
}
