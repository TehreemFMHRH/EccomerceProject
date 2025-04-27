<?php

namespace Webkul\DataGrid;

use Webkul\DataGrid\Enums\ColumnTypeEnum;
use Webkul\DataGrid\Exceptions\InvalidColumnException;

class Column
{
    
    protected string $index;

    
    protected string $label;

    
    protected string $type;

    
    protected bool $searchable = false;

    
    protected bool $filterable = false;

    
    protected ?string $filterableType = null;

    
    protected array $filterableOptions = [];

    
    protected bool $allowMultipleValues = true;

    
    protected bool $sortable = false;

    
    protected bool $exportable = true;

    
    protected bool $visibility = true;

    
    protected mixed $closure = null;

    
    protected $columnName;

    
    public function __construct(array $column)
    {
        $this->init($column);
    }

    
    public function init(array $column): void
    {
        $this->setIndex($column['index']);

        $this->setLabel($column['label']);

        $this->setType($column['type']);

        $this->setSearchable($column['searchable'] ?? $this->searchable);

        $this->setFilterable($column['filterable'] ?? $this->filterable);

        $this->setFilterableType($column['filterable_type'] ?? $this->filterableType);

        $this->setFilterableOptions($column['filterable_options'] ?? $this->filterableOptions);

        $this->setAllowMultipleValues($column['allow_multiple_values'] ?? $this->allowMultipleValues);

        $this->setSortable($column['sortable'] ?? $this->sortable);

        $this->setExportable($column['exportable'] ?? $this->exportable);

        $this->setVisibility($column['visibility'] ?? $this->visibility);

        $this->setClosure($column['closure'] ?? $this->closure);

        $this->setColumnName($this->index);
    }

    
    public function setIndex(string $index): void
    {
        $this->index = $index;
    }

    
    public function getIndex(): string
    {
        return $this->index;
    }

    
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    
    public function getLabel(): string
    {
        return $this->label;
    }

    
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    
    public function getType(): string
    {
        return $this->type;
    }

    
    public function setSearchable(bool $searchable): void
    {
        $this->searchable = $searchable;
    }

    
    public function getSearchable(): bool
    {
        return $this->searchable;
    }

    
    public function setFilterable(bool $filterable): void
    {
        $this->filterable = $filterable;
    }

    
    public function getFilterable(): bool
    {
        return $this->filterable;
    }

    
    public function setFilterableType(?string $filterableType): void
    {
        $this->filterableType = $filterableType;
    }

    
    public function getFilterableType(): ?string
    {
        return $this->filterableType;
    }

    
    public function setFilterableOptions(mixed $filterableOptions): void
    {
        if ($filterableOptions instanceof \Closure) {
            $filterableOptions = $filterableOptions();
        }

        $this->filterableOptions = $filterableOptions;
    }

    
    public function getFilterableOptions(): array
    {
        return $this->filterableOptions;
    }

    
    public function setAllowMultipleValues(bool $allowMultipleValues): void
    {
        $this->allowMultipleValues = $allowMultipleValues;
    }

    
    public function getAllowMultipleValues(): bool
    {
        return $this->allowMultipleValues;
    }

    
    public function setSortable(?bool $sortable = null): void
    {
        $this->sortable = $sortable;
    }

    
    public function getSortable(): bool
    {
        return $this->sortable;
    }

    
    public function setExportable(bool $exportable): void
    {
        $this->exportable = $exportable;
    }

    
    public function getExportable(): bool
    {
        return $this->exportable;
    }

    
    public function setVisibility(bool $visibility): void
    {
        $this->visibility = $visibility;
    }

    
    public function getVisibility(): bool
    {
        return $this->visibility;
    }

    
    public function setClosure(mixed $closure): void
    {
        $this->closure = $closure;
    }

    
    public function getClosure(): mixed
    {
        return $this->closure;
    }

    
    public function setColumnName(mixed $columnName): void
    {
        $this->columnName = $columnName;
    }

    
    public function getColumnName(): mixed
    {
        return $this->columnName;
    }

    
    public function toArray(): array
    {
        return [
            'index'                 => $this->index,
            'label'                 => $this->label,
            'type'                  => $this->type,
            'searchable'            => $this->searchable,
            'filterable'            => $this->filterable,
            'filterable_type'       => $this->filterableType,
            'filterable_options'    => $this->filterableOptions,
            'allow_multiple_values' => $this->allowMultipleValues,
            'sortable'              => $this->sortable,
            'exportable'            => $this->exportable,
            'visibility'            => $this->visibility,
        ];
    }

    
    public static function validate(array $column): void
    {
        if (empty($column['index'])) {
            throw new InvalidColumnException('The `index` key is required. Ensure that the `index` key is present in all calls to the `addColumn` method.');
        }

        if (empty($column['label'])) {
            throw new InvalidColumnException('The `label` key is required. Ensure that the `label` key is present in all calls to the `addColumn` method.');
        }

        if (empty($column['type'])) {
            throw new InvalidColumnException('The `type` key is required. Ensure that the `type` key is present in all calls to the `addColumn` method.');
        }
    }

    
    public static function resolveType(array $column): self
    {
        self::validate($column);

        $columnTypeClass = ColumnTypeEnum::getClassName($column['type']);

        return new $columnTypeClass($column);
    }
}
