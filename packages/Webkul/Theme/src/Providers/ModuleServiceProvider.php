<?php

namespace Webkul\Theme\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    
    protected $models = [
        \Webkul\Theme\Models\ThemeCustomization::class,
        \Webkul\Theme\Models\ThemeCustomizationTranslation::class,
    ];
}
