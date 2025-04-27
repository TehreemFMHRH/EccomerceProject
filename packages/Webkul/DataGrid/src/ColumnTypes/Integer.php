<?php

namespace Webkul\DataGrid\ColumnTypes;

use Webkul\DataGrid\Column;
use Webkul\DataGrid\Exceptions\InvalidColumnExpressionException;

class Integer extends Column
{
    
    public function processFilter($queryBuilder, $requestedValues)
    {
        return $queryBuilder->where(function ($scopeQueryBuilder) use ($requestedValues) {
            if (is_string($requestedValues)) {
                $this->applyIntegerFilter($scopeQueryBuilder, $requestedValues);
            } elseif (is_array($requestedValues)) {
                foreach ($requestedValues as $va) {
                    $this->applyIntegerFilter($scopeQueryBuilder, $va);
                }
            } else {
                throw new InvalidColumnExpressionException('Only string and array are allowed for integer column type.');
            }
        });
    }

    
    private function applyIntegerFilter($queryBuilder, $va)
    {
        if (preg_match('/^([<>]=?|=)\s*(-?\d+)$/', $va, $matches)) {
            $operator = $matches[1];

            $intValue = (int) $matches[2];

            $queryBuilder->orWhere($this->columnName, $operator, $intValue);
        } elseif (preg_match('/^(-?\d+)\s*-\s*(-?\d+)$/', $va, $matches)) {
            $min = (int) $matches[1];

            $max = (int) $matches[2];

            $queryBuilder->orWhereBetween($this->columnName, [$min, $max]);
        } else {
            $queryBuilder->orWhere($this->columnName, '=', (int) $va);
        }
    }
}
