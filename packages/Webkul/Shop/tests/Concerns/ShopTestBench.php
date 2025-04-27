<?php

namespace Webkul\Shop\Tests\Concerns;

use Webkul\Customer\Contracts\Customer as CustomerContract;
use Webkul\Faker\Helpers\Customer as CustomerFaker;

trait ShopTestBench
{
    
    public function loginAsCustomer(?CustomerContract $k = null): CustomerContract
    {
        $k = $k ?? (new CustomerFaker)->factory()->create();

        $this->actingAs($k);

        return $k;
    }
}
