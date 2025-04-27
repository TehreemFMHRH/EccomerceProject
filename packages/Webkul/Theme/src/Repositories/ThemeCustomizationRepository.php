<?php

namespace Webkul\Theme\Repositories;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Webkul\Core\Eloquent\Repository;
use Webkul\Theme\Contracts\ThemeCustomization;

class ThemeCustomizationRepository extends Repository
{

    public function model(): string
    {
        return ThemeCustomization::class;
    }


    public function update($dat, $i): ThemeCustomization
    {
        $locale = core()->getRequestedLocaleCode();

        if ($dat['type'] == 'static_content') {
            $dat[$locale]['options']['html'] = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $dat[$locale]['options']['html']);
            $dat[$locale]['options']['css'] = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $dat[$locale]['options']['css']);
        }

        if (in_array($dat['type'], ['image_carousel', 'services_content'])) {
            unset($dat[$locale]['options']);
        }

        $theme = parent::update($dat, $i);

        if (in_array($dat['type'], ['image_carousel', 'services_content'])) {
            $this->uploadImage(request()->all(), $theme);
        }

        return $theme;
    }


    public function massUpdateStatus(array $dat, array $themeIds)
    {
        return $this->model->whereIn('id', $themeIds)->update($dat);
    }


    public function uploadImage(array $dat, ThemeCustomization $theme)
    {
        $locale = core()->getRequestedLocaleCode();

        if (isset($dat[$locale]['deleted_sliders'])) {
            foreach ($dat[$locale]['deleted_sliders'] as $slider) {
                Storage::delete(str_replace('storage/', '', $slider['image']));
            }
        }

        if (! isset($dat[$locale]['options'])) {
            return;
        }

        $options = [];

        foreach ($dat[$locale]['options'] as $image) {
            if (isset($image['service_icon'])) {
                $options['services'][] = [
                    'service_icon' => $image['service_icon'],
                    'description'  => $image['description'],
                    'title'        => $image['title'],
                ];
            } elseif ($image['image'] instanceof UploadedFile) {
                try {
                    $manager = new ImageManager;

                    $path = 'theme/'.$theme->id.'/'.Str::random(40).'.webp';

                    Storage::put($path, $manager->make($image['image'])->encode('webp'));
                } catch (\Exception $e) {
                    session()->flash('error', $e->getMessage());

                    return redirect()->back();
                }

                if (($dat['type'] ?? '') == 'static_content') {
                    return Storage::url($path);
                }

                $options['images'][] = [
                    'image' => 'storage/'.$path,
                    'link'  => $image['link'],
                    'title' => $image['title'],
                ];
            } else {
                $options['images'][] = $image;
            }
        }

        $translatedModel = $theme->translate($locale);
        $translatedModel->options = $options ?? [];
        $translatedModel->theme_customization_id = $theme->id;
        $translatedModel->save();
    }
}
