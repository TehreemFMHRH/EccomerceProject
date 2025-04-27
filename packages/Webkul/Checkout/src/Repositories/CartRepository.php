<?php

namespace Webkul\Checkout\Repositories;

use Webkul\Core\Eloquent\Repository;

class CartRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Checkout\Contracts\Cart';
    }
}
