<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Theme\Repositories\ThemeCustomizationRepository;

class ThemeCustomization
{
    
    public function __construct(protected ThemeCustomizationRepository $themeCustomizationRepository) {}

    
    public function afterCreate($themeCustomization)
    {
        if (in_array($themeCustomization->type, ['footer_links', 'services_content'])) {
            ResponseCache::clear();
        } else {
            ResponseCache::selectCachedItems()
                ->forUrls(config('app.url').'/')
                ->forget();
        }
    }

    
    public function afterUpdate($themeCustomization)
    {
        if (in_array($themeCustomization->type, ['footer_links', 'services_content'])) {
            ResponseCache::clear();
        } else {
            ResponseCache::selectCachedItems()
                ->forUrls(config('app.url').'/')
                ->forget();
        }
    }

    
    public function beforeDelete($themeCustomizationId)
    {
        $themeCustomization = $this->themeCustomizationRepository->find($themeCustomizationId);

        if (in_array($themeCustomization->type, ['footer_links', 'services_content'])) {
            ResponseCache::clear();
        } else {
            ResponseCache::selectCachedItems()
                ->forUrls(config('app.url').'/')
                ->forget();
        }
    }
}
