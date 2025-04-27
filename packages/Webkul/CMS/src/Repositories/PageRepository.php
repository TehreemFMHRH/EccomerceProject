<?php

namespace Webkul\CMS\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\CMS\Models\PageTranslationProxy;
use Webkul\Core\Eloquent\Repository;

class PageRepository extends Repository
{

    public function model(): string
    {
        return 'Webkul\CMS\Contracts\Page';
    }


    public function create(array $dat)
    {
        $model = $this->getModel();

        foreach (core()->getAllLocales() as $locale) {
            foreach ($model->translatedAttributes as $attribute) {
                if (isset($dat[$attribute])) {
                    $dat[$locale->code][$attribute] = $dat[$attribute];
                }
            }

            $dat[$locale->code]['html_content'] = str_replace('=&gt;', '=>', $dat[$locale->code]['html_content']);
        }

        $page = parent::create($dat);

        $page->channels()->sync($dat['channels']);

        return $page;
    }


    public function update(array $dat, $i)
    {
        $page = $this->find($i);

        $locale = $dat['locale'] ?? app()->getLocale();

        $dat[$locale]['html_content'] = str_replace('=&gt;', '=>', $dat[$locale]['html_content']);

        $page = parent::update($dat, $i);

        $page->channels()->sync($dat['channels']);

        return $page;
    }


    public function isUrlKeyUnique($i, $urlKey)
    {
        $exists = PageTranslationProxy::modelClass()::where('cms_page_id', '<>', $i)
            ->where('url_key', $urlKey)
            ->limit(1)
            ->select(\DB::raw(1))
            ->exists();

        return ! $exists;
    }


    public function findByUrlKey($urlKey)
    {
        return $this->model->whereTranslation('url_key', $urlKey)->first();
    }


    public function findByUrlKeyOrFail($urlKey)
    {
        $page = $this->model->whereTranslation('url_key', $urlKey)->first();

        if ($page) {
            return $page;
        }

        throw (new ModelNotFoundException)->setModel(
            get_class($this->model), $urlKey
        );
    }
}
