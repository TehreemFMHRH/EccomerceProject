<?php

namespace Webkul\DataGrid\ColumnTypes;

use Webkul\DataGrid\Column;
use Webkul\DataGrid\Enums\DateRangeOptionEnum;
use Webkul\DataGrid\Enums\FilterTypeEnum;
use Webkul\DataGrid\Exceptions\InvalidColumnException;
use Webkul\DataGrid\Exceptions\InvalidColumnExpressionException;

class Date extends Column
{
    
    public function setFilterableType(?string $filterableType): void
    {
        if (
            $filterableType
            && ($filterableType !== FilterTypeEnum::DATE_RANGE->value)
        ) {
            throw new InvalidColumnException('Date filters will only work with `date_range` type. Either remove the `filterable_type` or set it to `date_range`.');
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
                    $scopeQueryBuilder->whereBetween($this->columnName, [
                        $va[0] ? (str_contains($va[0], ' ') ? $va[0] : $va[0].' 00:00:01') : '',
                        $va[1] ? (str_contains($va[1], ' ') ? $va[1] : $va[1].' 23:59:59') : '',
                    ]);
                }
            } else {
                throw new InvalidColumnExpressionException('Only string and array are allowed for date column type.');
            }
        });
    }
}
