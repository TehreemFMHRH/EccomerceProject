<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Marketing\Repositories\URLRewriteRepository;

class URLRewrite
{
    
    public function __construct(protected URLRewriteRepository $urlRewriteRepository) {}

    
    public function afterUpdate($urlRewrite)
    {
        ResponseCache::forget('/'.$urlRewrite->request_path);
    }

    
    public function beforeDelete($urlRewriteId)
    {
        $urlRewrite = $this->urlRewriteRepository->find($urlRewriteId);

        ResponseCache::forget('/'.$urlRewrite->request_path);
    }
}
