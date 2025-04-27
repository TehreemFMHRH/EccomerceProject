<?php

namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Contracts\Wishlist;

class WishlistRepository extends Repository
{
    
    public function model(): string
    {
        return Wishlist::class;
    }
}
