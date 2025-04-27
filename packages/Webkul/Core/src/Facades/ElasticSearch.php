<?php

namespace Webkul\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Core\ElasticSearch as BaseElasticSearch;

class ElasticSearch extends Facade
{
    
    protected static function getFacadeAccessor()
    {
        return BaseElasticSearch::class;
    }
}
