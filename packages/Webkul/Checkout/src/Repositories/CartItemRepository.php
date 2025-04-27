<?php

namespace Webkul\Checkout\Repositories;

use Webkul\Core\Eloquent\Repository;

class CartItemRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Checkout\Contracts\CartItem';
    }

    
    public function getProduct($cartItemId)
    {
        return $this->model->find($cartItemId)->product->id;
    }
}
