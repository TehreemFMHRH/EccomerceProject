<?php

namespace Webkul\CartRule\Listeners;

use Webkul\CartRule\Helpers\CartRule;

class Cart
{
    
    public function __construct(protected CartRule $cartRuleHelper) {}

    
    public function applyCartRules($cart)
    {
        $this->cartRuleHelper->collect($cart);
    }
}
