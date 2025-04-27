<?php

namespace Webkul\Marketing\Listeners;

use Illuminate\Support\Facades\Event;
use Webkul\CMS\Repositories\PageRepository;
use Webkul\Marketing\Repositories\URLRewriteRepository;

class Page
{
    
    const PERMANENT_REDIRECT_CODE = 301;

    
    public function __construct(
        protected PageRepository $pageRepository,
        protected URLRewriteRepository $urlRewriteRepository
    ) {}

    
    public function afterCreate($page)
    {
        
        $urlRewrites = $this->urlRewriteRepository->findWhere([
            'entity_type'  => 'cms_page',
            'request_path' => $page->url_key,
            'locale'       => app()->getLocale(),
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

        $page = $this->pageRepository->find($i);

        $translations = $page->translate($locale);

        
        if (empty($translations['url_key'])) {
            return;
        }

        $currentURLKey = request()->input($locale.'.url_key');

        if ($translations['url_key'] === $currentURLKey) {
            return;
        }

        
        $this->urlRewriteRepository->deleteWhere([
            'entity_type' => 'cms_page',
            'target_path' => $translations['url_key'],
            'locale'      => $locale,
        ]);

        Event::dispatch('marketing.search_seo.url_rewrites.create.before');

        $urlRewrite = $this->urlRewriteRepository->create([
            'entity_type'   => 'cms_page',
            'request_path'  => $translations['url_key'],
            'target_path'   => $currentURLKey,
            'locale'        => $locale,
            'redirect_type' => self::PERMANENT_REDIRECT_CODE,
        ]);

        Event::dispatch('marketing.search_seo.url_rewrites.create.after', $urlRewrite);
    }

    
    public function beforeDelete($i)
    {
        $page = $this->pageRepository->find($i);

        
        $translations = $page->getTranslationsArray();

        foreach ($translations as $locale => $translation) {
            $urlRewrites = $this->urlRewriteRepository->findWhere([
                'entity_type'  => 'cms_page',
                'request_path' => $translation['url_key'],
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
