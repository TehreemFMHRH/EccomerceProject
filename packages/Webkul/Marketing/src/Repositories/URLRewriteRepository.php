<?php

namespace Webkul\Marketing\Repositories;

use Webkul\Core\Eloquent\Repository;

class URLRewriteRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Marketing\Contracts\URLRewrite';
    }
}
