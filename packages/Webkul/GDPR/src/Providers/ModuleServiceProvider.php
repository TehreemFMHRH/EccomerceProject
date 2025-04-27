<?php

namespace Webkul\GDPR\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    
    protected $models = [
        \Webkul\GDPR\Models\GDPRDataRequest::class,
    ];
}
