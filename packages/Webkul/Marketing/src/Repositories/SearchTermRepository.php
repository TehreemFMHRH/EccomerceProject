<?php

namespace Webkul\Marketing\Repositories;

use Webkul\Core\Eloquent\Repository;

class SearchTermRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Marketing\Contracts\SearchTerm';
    }
}
