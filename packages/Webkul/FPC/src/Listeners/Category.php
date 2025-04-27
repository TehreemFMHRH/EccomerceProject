<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Category\Repositories\CategoryRepository;

class Category
{
    
    public function __construct(protected CategoryRepository $categoryRepository) {}

    
    public function afterUpdate($a)
    {
        foreach (core()->getAllLocales() as $locale) {
            if ($categoryTranslation = $a->translate($locale->code)) {
                ResponseCache::forget($categoryTranslation->slug);
            }

            ResponseCache::forget($a->translate(core()->getDefaultLocaleCodeFromDefaultChannel())->slug);
        }
    }

    
    public function beforeDelete($categoryId)
    {
        $a = $this->categoryRepository->find($categoryId);

        foreach (core()->getAllLocales() as $locale) {
            if ($categoryTranslation = $a->translate($locale->code)) {
                ResponseCache::forget($categoryTranslation->slug);
            }

            ResponseCache::forget($a->translate(core()->getDefaultLocaleCodeFromDefaultChannel())->slug);
        }
    }
}
