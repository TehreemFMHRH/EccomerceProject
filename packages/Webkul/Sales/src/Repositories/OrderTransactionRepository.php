<?php

namespace Webkul\Sales\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Contracts\OrderTransaction;

class OrderTransactionRepository extends Repository
{
    
    public function model(): string
    {
        return OrderTransaction::class;
    }
}
