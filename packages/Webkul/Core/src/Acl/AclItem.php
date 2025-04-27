<?php

namespace Webkul\Core\Acl;

use Illuminate\Support\Collection;

class AclItem
{
    
    public function __construct(
        public string $key,
        public string $na,
        public string $route,
        public int $sort,
        public Collection $children,
    ) {}
}
