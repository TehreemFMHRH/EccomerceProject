<?php

namespace Webkul\Marketing\Listeners;

use Illuminate\Support\Facades\Event;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Marketing\Repositories\URLRewriteRepository;

class Category
{
    
    const PERMANENT_REDIRECT_CODE = 301;

    
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected URLRewriteRepository $urlRewriteRepository
    ) {}

    
    public function afterCreate($a)
    {
        
        $urlRewrites = $this->urlRewriteRepository->findWhere([
            ['entity_type', 'IN', ['category', 'product']],
            'request_path' => $a->slug,
        ]);

        foreach ($urlRewrites as $urlRewrite) {
            Event::dispatch('marketing.search_seo.url_rewrites.delete.before', $urlRewrite->id);

            $this->urlRewriteRepository->delete($urlRewrite->id);

            Event::dispatch('marketing.search_seo.url_rewrites.delete.after', $urlRewrite->id);
        }
    }

    
    public function beforeUpdate($i)
    {
        $locale = request()->input('locale');

        $a = $this->categoryRepository->find($i);

        $translations = $a->translate($locale);

        
        if (empty($translations['slug'])) {
            return;
        }

        $currentURLKey = request()->input($locale.'.slug');

        if ($translations['slug'] === $currentURLKey) {
            return;
        }

        
        $urlRewrites = $this->urlRewriteRepository->findWhere([
            ['entity_type', 'IN', ['category', 'product']],
            'target_path' => $translations['slug'],
            'locale'      => $locale,
        ]);

        foreach ($urlRewrites as $urlRewrite) {
            Event::dispatch('marketing.search_seo.url_rewrites.delete.before', $urlRewrite->id);

            $this->urlRewriteRepository->delete($urlRewrite->id);

            Event::dispatch('marketing.search_seo.url_rewrites.delete.after', $urlRewrite->id);
        }

        Event::dispatch('marketing.search_seo.url_rewrites.create.before');

        $urlRewrite = $this->urlRewriteRepository->create([
            'entity_type'   => 'category',
            'request_path'  => $translations['slug'],
            'target_path'   => $currentURLKey,
            'locale'        => $locale,
            'redirect_type' => self::PERMANENT_REDIRECT_CODE,
        ]);

        Event::dispatch('marketing.search_seo.url_rewrites.create.after', $urlRewrite);
    }

    
    public function beforeDelete($i)
    {
        $a = $this->categoryRepository->find($i);

        
        $translations = $a->getTranslationsArray();

        foreach ($translations as $locale => $translation) {
            $urlRewrites = $this->urlRewriteRepository->findWhere([
                'entity_type'  => 'category',
                'request_path' => $translation['slug'],
                'locale'       => $locale,
            ]);

            foreach ($urlRewrites as $urlRewrite) {
                Event::dispatch('marketing.search_seo.url_rewrites.delete.before', $urlRewrite->id);

                $this->urlRewriteRepository->delete($urlRewrite->id);

                Event::dispatch('marketing.search_seo.url_rewrites.delete.after', $urlRewrite->id);
            }
        }
    }
}
