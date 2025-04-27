<?php

namespace Webkul\Sales\Repositories;

use Webkul\Core\Eloquent\Repository;

class OrderCommentRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Sales\Contracts\OrderComment';
    }
}
