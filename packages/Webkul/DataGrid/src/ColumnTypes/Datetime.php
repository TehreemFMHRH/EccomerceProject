<?php

namespace Webkul\DataGrid\ColumnTypes;

use Webkul\DataGrid\Column;
use Webkul\DataGrid\Enums\DateRangeOptionEnum;
use Webkul\DataGrid\Enums\FilterTypeEnum;
use Webkul\DataGrid\Exceptions\InvalidColumnException;
use Webkul\DataGrid\Exceptions\InvalidColumnExpressionException;

class Datetime extends Column
{
    
    public function setFilterableType(?string $filterableType): void
    {
        if (
            $filterableType
            && ($filterableType !== FilterTypeEnum::DATETIME_RANGE->value)
        ) {
            throw new InvalidColumnException('Datetime filters will only work with `datetime_range` type. Either remove the `filterable_type` or set it to `datetime_range`.');
        }

        parent::setFilterableType($filterableType);
    }

    
    public function setFilterableOptions(mixed $filterableOptions): void
    {
        if (empty($filterableOptions)) {
            $filterableOptions = DateRangeOptionEnum::options();
        }

        parent::setFilterableOptions($filterableOptions);
    }

    
    public function processFilter($queryBuilder, $requestedDates)
    {
        return $queryBuilder->where(function ($scopeQueryBuilder) use ($requestedDates) {
            if (is_string($requestedDates)) {
                $rangeOption = collect($this->filterableOptions)->firstWhere('name', $requestedDates);

                $requestedDates = ! $rangeOption
                    ? [[$requestedDates, $requestedDates]]
                    : [[$rangeOption['from'], $rangeOption['to']]];
            } elseif (is_array($requestedDates)) {
                foreach ($requestedDates as $va) {
                    $scopeQueryBuilder->whereBetween($this->columnName, [$va[0] ?? '', $va[1] ?? '']);
                }
            } else {
                throw new InvalidColumnExpressionException('Only string and array are allowed for datetime column type.');
            }
        });
    }
}
