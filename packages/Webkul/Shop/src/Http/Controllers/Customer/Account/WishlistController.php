<?php

namespace Webkul\Shop\Http\Controllers\Customer\Account;

use Webkul\Shop\Http\Controllers\Controller;

class WishlistController extends Controller
{
    
    public function index()
    {
        if (! core()->getConfigData('customer.settings.wishlist.wishlist_option')) {
            abort(404);
        }

        return view('shop::customers.account.wishlist.index');
    }
}
