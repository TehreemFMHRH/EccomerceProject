<?php

namespace Webkul\CatalogRule\Repositories;

use Webkul\Core\Eloquent\Repository;

class CatalogRuleProductPriceRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\CatalogRule\Contracts\CatalogRuleProductPrice';
    }
}
