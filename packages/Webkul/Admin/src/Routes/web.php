<?php

use Illuminate\Support\Facades\Route;


require 'auth-routes.php';

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    
    require 'sales-routes.php';

    
    require 'catalog-routes.php';

    
    require 'customers-routes.php';

    
    require 'marketing-routes.php';

    
    require 'cms-routes.php';

    
    require 'reporting-routes.php';

    
    require 'settings-routes.php';

    
    require 'configuration-routes.php';

    
    require 'notification-routes.php';

    
    require 'rest-routes.php';
});
