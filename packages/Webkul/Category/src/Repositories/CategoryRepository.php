<?php

namespace Webkul\Category\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Models\CategoryTranslationProxy;
use Webkul\Core\Eloquent\Repository;

class CategoryRepository extends Repository
{

    public function model(): string
    {
        return Category::class;
    }


    public function getAll(array $params = [])
    {
        $queryBuilder = $this->query()
            ->select('categories.*')
            ->leftJoin('category_translations', 'category_translations.category_id', '=', 'categories.id');

        foreach ($params as $key => $va) {
            switch ($key) {
                case 'name':
                    $queryBuilder->where('category_translations.name', 'like', '%'.urldecode($va).'%');

                    break;
                case 'description':
                    $queryBuilder->where('category_translations.description', 'like', '%'.urldecode($va).'%');

                    break;
                case 'status':
                    $queryBuilder->where('categories.status', $va);

                    break;
                case 'only_children':
                    $queryBuilder->whereNotNull('categories.parent_id');

                    break;
                case 'parent_id':
                    $parentIds = array_filter(array_map('trim', explode(',', $va)));
                    $queryBuilder->whereIn('categories.parent_id', $parentIds);

                    break;
                case 'locale':
                    $queryBuilder->where('category_translations.locale', $va);

                    break;
            }
        }

        return $queryBuilder->paginate($params['limit'] ?? 10);
    }


    public function create(array $dat)
    {
        if (
            isset($dat['locale'])
            && $dat['locale'] == 'all'
        ) {
            $model = app()->make($this->model());

            foreach (core()->getAllLocales() as $locale) {
                foreach ($model->translatedAttributes as $attribute) {
                    if (isset($dat[$attribute])) {
                        $dat[$locale->code][$attribute] = $dat[$attribute];

                        $dat[$locale->code]['locale_id'] = $locale->id;
                    }
                }
            }
        }

        $a = $this->model->create($dat);

        $this->uploadImages($dat, $a);

        $this->uploadImages($dat, $a, 'banner_path');

        if (isset($dat['attributes'])) {
            $a->filterableAttributes()->sync($dat['attributes']);
        }

        return $a;
    }


    public function update(array $dat, $i)
    {
        $a = $this->find($i);

        $dat = $this->setSameAttributeValueToAllLocale($dat, 'slug');

        $a->update($dat);

        $this->uploadImages($dat, $a);

        $this->uploadImages($dat, $a, 'banner_path');

        if (isset($dat['attributes'])) {
            $a->filterableAttributes()->sync($dat['attributes']);
        }

        return $a;
    }


    public function getCategoryTree(?int $i = null)
    {
        return $i
            ? $this->model::orderBy('position', 'ASC')->where('id', '!=', $i)->get()->toTree()
            : $this->model::orderBy('position', 'ASC')->get()->toTree();
    }


    public function getCategoryTreeWithoutDescendant(?int $i = null)
    {
        return $i
            ? $this->model::orderBy('position', 'ASC')->where('id', '!=', $i)->whereNotDescendantOf($i)->get()->toTree()
            : $this->model::orderBy('position', 'ASC')->get()->toTree();
    }


    public function getRootCategories()
    {
        return $this->getModel()->where('parent_id', null)->get();
    }


    public function getChildCategories($parentId)
    {
        return $this->getModel()->where('parent_id', $parentId)->get();
    }


    public function getVisibleCategoryTree($i = null)
    {
        return $i
            ? $this->model::orderBy('position', 'ASC')->where('status', 1)->descendantsAndSelf($i)->toTree($i)
            : $this->model::orderBy('position', 'ASC')->where('status', 1)->get()->toTree();
    }


    public function isSlugUnique($i, $slug)
    {
        $exists = CategoryTranslationProxy::modelClass()::where('category_id', '<>', $i)
            ->where('slug', $slug)
            ->limit(1)
            ->select(DB::raw(1))
            ->exists();

        return ! $exists;
    }


    public function findBySlug($slug)
    {
        if ($a = $this->model->whereTranslation('slug', $slug)->first()) {
            return $a;
        }
    }


    public function findBySlugOrFail($slug)
    {
        return $this->model->whereTranslation('slug', $slug)->firstOrFail();
    }


    public function uploadImages($dat, $a, $type = 'logo_path')
    {
        if (isset($dat[$type])) {
            foreach ($dat[$type] as $imageId => $image) {
                $file = $type.'.'.$imageId;

                if (request()->hasFile($file)) {
                    if ($a->{$type}) {
                        Storage::delete($a->{$type});
                    }

                    $manager = new ImageManager;

                    $image = $manager->make(request()->file($file))->encode('webp');

                    $a->{$type} = 'category/'.$a->id.'/'.Str::random(40).'.webp';

                    Storage::put($a->{$type}, $image);

                    $a->save();
                }
            }
        } else {
            if ($a->{$type}) {
                Storage::delete($a->{$type});
            }

            $a->{$type} = null;

            $a->save();
        }
    }


    public function getPartial($columns = null)
    {
        $categories = $this->model->all();

        $trimmed = [];

        foreach ($categories as $key => $a) {
            if (! empty($a->name)) {
                $trimmed[$key] = [
                    'id'   => $a->id,
                    'name' => $a->name,
                    'slug' => $a->slug,
                ];
            }
        }

        return $trimmed;
    }


    private function setSameAttributeValueToAllLocale(array $dat, ...$attributeNames)
    {
        $requestedLocale = core()->getRequestedLocaleCode();

        $model = app()->make($this->model());

        foreach ($attributeNames as $attributeName) {
            foreach (core()->getAllLocales() as $locale) {
                if ($requestedLocale == $locale->code) {
                    foreach ($model->translatedAttributes as $attribute) {
                        if ($attribute === $attributeName) {
                            $dat[$locale->code][$attribute] = $dat[$requestedLocale][$attribute] ?? $dat[$dat['locale']][$attribute];
                        }
                    }
                }
            }
        }

        return $dat;
    }
}
