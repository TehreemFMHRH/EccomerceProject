<?php

namespace Webkul\Core\Repositories;

use Webkul\Core\Eloquent\Repository;

class SubscribersListRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Core\Contracts\SubscribersList';
    }

    
    public function destroy($i)
    {
        return $this->model->destroy($i);
    }
}
