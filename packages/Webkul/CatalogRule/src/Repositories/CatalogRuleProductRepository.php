<?php

namespace Webkul\CatalogRule\Repositories;

use Webkul\Core\Eloquent\Repository;

class CatalogRuleProductRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\CatalogRule\Contracts\CatalogRuleProduct';
    }
}
