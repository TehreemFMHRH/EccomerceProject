<?php

namespace Webkul\Shop\Http\Controllers;

use Webkul\CMS\Repositories\PageRepository;
use Webkul\Marketing\Repositories\URLRewriteRepository;

class PageController extends Controller
{
    
    public function __construct(
        protected PageRepository $pageRepository,
        protected URLRewriteRepository $urlRewriteRepository
    ) {}

    
    public function view($urlKey)
    {
        $page = $this->pageRepository->findByUrlKey($urlKey);

        if (! $page) {
            $urlRewrite = $this->urlRewriteRepository->findOneWhere([
                'entity_type'  => 'cms_page',
                'request_path' => $urlKey,
                'locale'       => app()->getLocale(),
            ]);

            if ($urlRewrite) {
                return redirect()->to($urlRewrite->target_path, $urlRewrite->redirect_type);
            }

            abort_if(! $page && ! $urlRewrite, 404);
        }

        return view('shop::cms.page')->with('page', $page);
    }
}
