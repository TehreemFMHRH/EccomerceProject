<?php

namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;

class CustomerNoteRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Customer\Contracts\CustomerNote';
    }
}
