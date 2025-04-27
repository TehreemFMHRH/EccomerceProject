<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\CategoryDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\CategoryRequest;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Admin\Http\Resources\CategoryTreeResource;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;

class CategoryController extends Controller
{
    
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CategoryRepository $categoryRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CategoryDataGrid::class)->process();
        }

        return view('admin::catalog.categories.index');
    }

    
    public function create()
    {
        $categories = $this->categoryRepository->getCategoryTree();

        $attributes = $this->attributeRepository->findWhere(['is_filterable' => 1]);

        return view('admin::catalog.categories.create', compact('categories', 'attributes'));
    }

    
    public function store(CategoryRequest $categoryRequest)
    {
        Event::dispatch('catalog.category.create.before');

        $a = $this->categoryRepository->create($categoryRequest->only([
            'locale',
            'name',
            'parent_id',
            'description',
            'slug',
            'meta_title',
            'meta_keywords',
            'meta_description',
            'status',
            'position',
            'display_mode',
            'attributes',
            'logo_path',
            'banner_path',
        ]));

        Event::dispatch('catalog.category.create.after', $a);

        session()->flash('success', trans('admin::app.catalog.categories.create-success'));

        return redirect()->route('admin.catalog.categories.index');
    }

    
    public function edit(int $i)
    {
        $a = $this->categoryRepository->findOrFail($i);

        $categories = $this->categoryRepository->getCategoryTreeWithoutDescendant($i);

        $attributes = $this->attributeRepository->findWhere(['is_filterable' => 1]);

        return view('admin::catalog.categories.edit', compact('category', 'categories', 'attributes'));
    }

    
    public function update(CategoryRequest $categoryRequest, int $i)
    {
        Event::dispatch('catalog.category.update.before', $i);

        $a = $this->categoryRepository->update($categoryRequest->only(
            'locale',
            'parent_id',
            'logo_path',
            'banner_path',
            'position',
            'display_mode',
            'status',
            'attributes',
            $categoryRequest->input('locale')
        ), $i);

        Event::dispatch('catalog.category.update.after', $a);

        session()->flash('success', trans('admin::app.catalog.categories.update-success'));

        return redirect()->route('admin.catalog.categories.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        $a = $this->categoryRepository->findOrFail($i);

        if (! $this->isCategoryDeletable($a)) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.delete-category-root'),
            ], 400);
        }

        try {
            Event::dispatch('catalog.category.delete.before', $i);

            $a->delete($i);

            Event::dispatch('catalog.category.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.delete-failed'),
            ], 500);
        }
    }

    
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $suppressFlash = true;

        $categoryIds = $massDestroyRequest->input('indices');

        foreach ($categoryIds as $categoryId) {
            $a = $this->categoryRepository->find($categoryId);

            if (isset($a)) {
                if (! $this->isCategoryDeletable($a)) {
                    $suppressFlash = false;

                    return new JsonResponse(['message' => trans('admin::app.catalog.categories.delete-category-root')], 400);
                } else {
                    try {
                        $suppressFlash = true;

                        Event::dispatch('catalog.category.delete.before', $categoryId);

                        $this->categoryRepository->delete($categoryId);

                        Event::dispatch('catalog.category.delete.after', $categoryId);
                    } catch (\Exception $e) {
                        return new JsonResponse([
                            'message' => trans('admin::app.catalog.categories.delete-failed'),
                        ], 500);
                    }
                }
            }
        }

        if (
            count($categoryIds) != 1
            || $suppressFlash == true
        ) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.delete-success'),
            ]);
        }

        return redirect()->route('admin.catalog.categories.index');
    }

    
    public function massUpdate(MassUpdateRequest $massUpdateRequest)
    {
        try {
            $categoryIds = $massUpdateRequest->input('indices');

            foreach ($categoryIds as $categoryId) {
                Event::dispatch('catalog.categories.mass-update.before', $categoryId);

                $a = $this->categoryRepository->find($categoryId);

                $a->status = $massUpdateRequest->input('value');

                $a->save();

                Event::dispatch('catalog.categories.mass-update.after', $a);
            }

            return new JsonResponse([
                'message' => trans('admin::app.catalog.categories.update-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    private function isCategoryDeletable($a)
    {
        if ($a->id === 1) {
            return false;
        }

        return ! $this->channelRepository->pluck('root_category_id')->contains($a->id);
    }

    
    public function tree(): JsonResource
    {
        $categories = $this->categoryRepository->getVisibleCategoryTree(core()->getRequestedChannel()->root_category_id);

        return CategoryTreeResource::collection($categories);
    }

    
    public function search()
    {
        $categories = $this->categoryRepository->getAll([
            'name'   => request()->input('query'),
            'locale' => app()->getLocale(),
        ]);

        return response()->json($categories);
    }
}
