<?php

namespace Webkul\Inventory\Repositories;

use Webkul\Core\Eloquent\Repository;

class InventorySourceRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Inventory\Contracts\InventorySource';
    }
}
