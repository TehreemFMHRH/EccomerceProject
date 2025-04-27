<?php

namespace Webkul\CartRule\Repositories;

use Webkul\Core\Eloquent\Repository;

class CartRuleCouponUsageRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\CartRule\Contracts\CartRuleCouponUsage';
    }
}
