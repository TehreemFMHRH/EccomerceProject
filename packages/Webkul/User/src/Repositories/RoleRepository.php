<?php

namespace Webkul\User\Repositories;

use Webkul\Core\Eloquent\Repository;

class RoleRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\User\Contracts\Role';
    }
}
