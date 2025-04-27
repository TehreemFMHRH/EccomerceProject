<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\CMS\Repositories\PageRepository;

class Page
{
    
    public function __construct(protected PageRepository $pageRepository) {}

    
    public function afterUpdate($page)
    {
        ResponseCache::forget('/page/'.$page->url_key);
    }

    
    public function beforeDelete($pageId)
    {
        $page = $this->pageRepository->find($pageId);

        ResponseCache::forget('/page/'.$page->url_key);
    }
}
