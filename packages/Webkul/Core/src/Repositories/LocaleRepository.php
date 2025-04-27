<?php

namespace Webkul\Core\Repositories;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Contracts\Locale;
use Webkul\Core\Eloquent\Repository;

class LocaleRepository extends Repository
{
    
    public function model(): string
    {
        return Locale::class;
    }

    
    public function create(array $attributes)
    {
        Event::dispatch('core.locale.create.before');

        $locale = parent::create($attributes);

        $this->uploadImage($attributes, $locale);

        Event::dispatch('core.locale.create.after', $locale);

        return $locale;
    }

    
    public function update(array $attributes, $i)
    {
        Event::dispatch('core.locale.update.before', $i);

        $locale = parent::update($attributes, $i);

        $this->uploadImage($attributes, $locale);

        Event::dispatch('core.locale.update.after', $locale);

        return $locale;
    }

    
    public function delete($i)
    {
        Event::dispatch('core.locale.delete.before', $i);

        $locale = parent::find($i);

        $locale->delete($i);

        Storage::delete((string) $locale->logo_path);

        Event::dispatch('core.locale.delete.after', $i);
    }

    
    public function uploadImage($localeImages, $locale)
    {
        if (! isset($localeImages['logo_path'])) {
            if (! empty($localeImages['logo_path'])) {
                Storage::delete((string) $locale->logo_path);
            }

            $locale->logo_path = null;

            $locale->save();

            return;
        }

        foreach ($localeImages['logo_path'] as $image) {
            if ($image instanceof UploadedFile) {
                $locale->logo_path = $image->storeAs(
                    'locales',
                    $locale->code.'.'.$image->getClientOriginalExtension()
                );

                $locale->save();
            }
        }
    }
}
