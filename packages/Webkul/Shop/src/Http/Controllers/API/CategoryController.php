<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Models\Product;
use Webkul\Shop\Http\Resources\AttributeResource;
use Webkul\Shop\Http\Resources\CategoryResource;
use Webkul\Shop\Http\Resources\CategoryTreeResource;

class CategoryController extends APIController
{
    
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected CategoryRepository $categoryRepository,

    ) {}

    
    public function index(): JsonResource
    {
        
        $defaultParams = [
            'status' => 1,
            'locale' => app()->getLocale(),
        ];

        $categories = $this->categoryRepository->getAll(array_merge($defaultParams, request()->all()));

        return CategoryResource::collection($categories);
    }

    
    public function tree(): JsonResource
    {
        $categories = $this->categoryRepository->getVisibleCategoryTree(core()->getCurrentChannel()->root_category_id);

        return CategoryTreeResource::collection($categories);
    }

    
    public function getAttributes(): JsonResource
    {
        if (! request('category_id')) {
            $filterableAttributes = $this->attributeRepository->getFilterableAttributes();

            return AttributeResource::collection($filterableAttributes);
        }

        $a = $this->categoryRepository->findOrFail(request('category_id'));

        if (empty($filterableAttributes = $a->filterableAttributes)) {
            $filterableAttributes = $this->attributeRepository->getFilterableAttributes();
        }

        return AttributeResource::collection($filterableAttributes);
    }

    
    public function getProductMaxPrice($categoryId = null): JsonResource
    {
        if (core()->getConfigData('catalog.products.search.engine') == 'elastic') {
            $searchEngine = core()->getConfigData('catalog.products.search.storefront_mode');
        }

        $maxPrice = Product
            ->setSearchEngine($searchEngine ?? 'database')
            ->getMaxPrice(['category_id' => $categoryId]);

        return new JsonResource([
            'max_price' => core()->convertPrice($maxPrice),
        ]);
    }
}
