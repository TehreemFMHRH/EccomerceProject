<?php

namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Contracts\CompareItem;

class CompareItemRepository extends Repository
{
    
    public function model(): string
    {
        return CompareItem::class;
    }
}
