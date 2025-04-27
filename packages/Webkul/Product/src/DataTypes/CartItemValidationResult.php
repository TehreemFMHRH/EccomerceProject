<?php

namespace Webkul\Product\DataTypes;

class CartItemValidationResult
{
    
    private $cartIsInvalid = false;

    
    private $itemIsInactive = false;

    
    public function isCartInvalid(): bool
    {
        return $this->cartIsInvalid;
    }

    
    public function isItemInactive(): bool
    {
        return $this->itemIsInactive;
    }

    
    public function itemIsInactive(): void
    {
        $this->itemIsInactive = true;
    }

    
    public function cartIsInvalid(): void
    {
        $this->cartIsInvalid = true;
    }
}
