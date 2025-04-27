<?php

namespace Webkul\Sales\Repositories;

use Webkul\Core\Eloquent\Repository;


class OrderAddressRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Sales\Contracts\OrderAddress';
    }
}
