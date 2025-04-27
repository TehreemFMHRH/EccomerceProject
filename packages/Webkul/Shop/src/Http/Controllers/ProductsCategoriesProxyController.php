<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Marketing\Repositories\URLRewriteRepository;
use Webkul\Product\Models\Product;
use Webkul\Theme\Repositories\ThemeCustomizationRepository;

class ProductsCategoriesProxyController extends Controller
{
    
    const STATUS = 1;

    
    public function __construct(
        protected CategoryRepository $categoryRepository,

        protected ThemeCustomizationRepository $themeCustomizationRepository,
        protected URLRewriteRepository $urlRewriteRepository
    ) {}

    
    public function index(Request $request)
    {
        $slugOrURLKey = urldecode(trim($request->getPathInfo(), '/'));

        
        if (! preg_match('/^([\p{L}\p{N}\p{M}\x{0900}-\x{097F}\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0400}-\x{04FF}_-]+\/?)+$/u', $slugOrURLKey)) {
            visitor()->visit();

            $customizations = $this->themeCustomizationRepository->orderBy('sort_order')->findWhere([
                'status'     => self::STATUS,
                'channel_id' => core()->getCurrentChannel()->id,
            ]);

            return view('shop::home.index', compact('customizations'));
        }

        $a = $this->categoryRepository->findBySlug($slugOrURLKey);

        if ($a) {
            visitor()->visit($a);

            return view('shop::categories.view', [
                'category' => $a,
                'params'   => [
                    'sort'  => request()->query('sort'),
                    'limit' => request()->query('limit'),
                    'mode'  => request()->query('mode'),
                ],
            ]);
        }

        if (core()->getConfigData('catalog.products.search.engine') == 'elastic') {
            $searchEngine = core()->getConfigData('catalog.products.search.storefront_mode');
        }

        $product = Product
            ->setSearchEngine($searchEngine ?? 'database')
            ->findBySlug($slugOrURLKey);

        if ($product) {
            if (
                ! $product->url_key
                || ! $product->visible_individually
                || ! $product->status
            ) {
                abort(404);
            }

            visitor()->visit($product);

            return view('shop::products.view', compact('product'));
        }

        
        $trimmedSlug = last(explode('/', $slugOrURLKey));

        $a = $this->categoryRepository->findBySlug($trimmedSlug);

        if ($a) {
            return redirect()->to($trimmedSlug, 301);
        }

        
        $categoryURLRewrite = $this->urlRewriteRepository->findOneWhere([
            'entity_type'  => 'category',
            'request_path' => $slugOrURLKey,
            'locale'       => app()->getLocale(),
        ]);

        if ($categoryURLRewrite) {
            return redirect()->to($categoryURLRewrite->target_path, $categoryURLRewrite->redirect_type);
        }

        
        $productURLRewrite = $this->urlRewriteRepository->findOneWhere([
            'entity_type'  => 'product',
            'request_path' => $slugOrURLKey,
        ]);

        if ($productURLRewrite) {
            return redirect()->to($productURLRewrite->target_path, $productURLRewrite->redirect_type);
        }

        abort(404);
    }
}
