<?php

namespace Webkul\Marketing\Repositories;

use Webkul\Core\Eloquent\Repository;

class EventRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Marketing\Contracts\Event';
    }
}
