<?php

namespace Webkul\RentalProduct\Type;

use Webkul\Product\Type\AbstractType;

class RentalProduct extends AbstractType
{
    public function isStockable(): bool
    {
        return true;
    }

    public function isSaleable(): bool
    {
        return true;
    }

    public function showQuantityBox(): bool
    {
        return true;
    }

    public function getTypeInstance()
    {
        return $this;
    }
}
