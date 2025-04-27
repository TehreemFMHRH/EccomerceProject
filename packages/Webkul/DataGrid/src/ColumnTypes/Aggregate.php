<?php

namespace Webkul\DataGrid\ColumnTypes;

use Webkul\DataGrid\Column;
use Webkul\DataGrid\Enums\FilterTypeEnum;
use Webkul\DataGrid\Exceptions\InvalidColumnExpressionException;

class Aggregate extends Column
{
    
    public function processFilter($queryBuilder, $requestedValues)
    {
        if ($this->filterableType === FilterTypeEnum::DROPDOWN->value) {
            return $queryBuilder->having(function ($scopeQueryBuilder) use ($requestedValues) {
                if (is_string($requestedValues)) {
                    $scopeQueryBuilder->orHaving($this->columnName, $requestedValues);
                } elseif (is_array($requestedValues)) {
                    foreach ($requestedValues as $va) {
                        $scopeQueryBuilder->orHaving($this->columnName, $va);
                    }
                } else {
                    throw new InvalidColumnExpressionException('Only string and array are allowed for text column type.');
                }
            });
        }

        return $queryBuilder->having(function ($scopeQueryBuilder) use ($requestedValues) {
            if (is_string($requestedValues)) {
                $scopeQueryBuilder->orHaving($this->columnName, 'LIKE', '%'.$requestedValues.'%');
            } elseif (is_array($requestedValues)) {
                foreach ($requestedValues as $va) {
                    $scopeQueryBuilder->orHaving($this->columnName, 'LIKE', '%'.$va.'%');
                }
            } else {
                throw new InvalidColumnExpressionException('Only string and array are allowed for text column type.');
            }
        });
    }
}
