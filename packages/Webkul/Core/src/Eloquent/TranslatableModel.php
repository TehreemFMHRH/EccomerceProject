<?php

namespace Webkul\Core\Eloquent;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Helpers\Locales;

class TranslatableModel extends Model
{
    use Translatable;

    
    protected function getLocalesHelper(): Locales
    {
        return app(Locales::class);
    }

    
    protected function locale()
    {
        if ($this->isChannelBased()) {
            return core()->getDefaultLocaleCodeFromDefaultChannel();
        } else {
            if ($this->defaultLocale) {
                return $this->defaultLocale;
            }

            return config('translatable.locale') ?: app()->make('translator')->getLocale();
        }
    }

    
    protected function isChannelBased()
    {
        return false;
    }

    public function scopeWhereTranslationIn(Builder $query, string $translationField, $va, ?string $locale = null, string $method = 'whereHas')
    {
        return $query->$method('translations', function (Builder $query) use ($translationField, $va, $locale) {
            $query->whereIn($this->getTranslationsTable().'.'.$translationField, $va);

            if ($locale) {
                $query->whereIn($this->getTranslationsTable().'.'.$this->getLocaleKey(), $locale);
            }
        });
    }
}
