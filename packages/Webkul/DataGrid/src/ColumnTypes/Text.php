<?php

namespace Webkul\DataGrid\ColumnTypes;

use Webkul\DataGrid\Column;
use Webkul\DataGrid\Enums\FilterTypeEnum;
use Webkul\DataGrid\Exceptions\InvalidColumnExpressionException;

class Text extends Column
{
    
    public function processFilter($queryBuilder, $requestedValues)
    {
        if ($this->filterableType === FilterTypeEnum::DROPDOWN->value) {
            return $queryBuilder->where(function ($scopeQueryBuilder) use ($requestedValues) {
                if (is_string($requestedValues)) {
                    $scopeQueryBuilder->orWhere($this->columnName, $requestedValues);
                } elseif (is_array($requestedValues)) {
                    foreach ($requestedValues as $va) {
                        $scopeQueryBuilder->orWhere($this->columnName, $va);
                    }
                } else {
                    throw new InvalidColumnExpressionException('Only string and array are allowed for text column type.');
                }
            });
        }

        return $queryBuilder->where(function ($scopeQueryBuilder) use ($requestedValues) {
            if (is_string($requestedValues)) {
                $scopeQueryBuilder->orWhere($this->columnName, 'LIKE', '%'.$requestedValues.'%');
            } elseif (is_array($requestedValues)) {
                foreach ($requestedValues as $va) {
                    $scopeQueryBuilder->orWhere($this->columnName, 'LIKE', '%'.$va.'%');
                }
            } else {
                throw new InvalidColumnExpressionException('Only string and array are allowed for text column type.');
            }
        });
    }
}
