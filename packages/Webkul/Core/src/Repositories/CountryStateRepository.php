<?php

namespace Webkul\Core\Repositories;

use Webkul\Core\Eloquent\Repository;

class CountryStateRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Core\Contracts\CountryState';
    }
}
