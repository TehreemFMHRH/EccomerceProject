<?php

namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Contracts\CustomerAddress;

class CustomerAddressRepository extends Repository
{
    
    public function model(): string
    {
        return CustomerAddress::class;
    }

    
    public function create(array $dat)
    {
        if (! empty($dat['default_address'])) {
            $this->model->where('customer_id', $dat['customer_id'])
                ->where('default_address', 1)
                ->update(['default_address' => 0]);
        }

        return $this->model->create($dat);
    }
}
