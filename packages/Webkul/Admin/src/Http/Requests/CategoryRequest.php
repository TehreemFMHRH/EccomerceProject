<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Admin\Validations\ProductCategoryUniqueSlug;

class CategoryRequest extends FormRequest
{
    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        $locale = core()->getRequestedLocaleCode();

        $rules = [
            'position'      => 'required|integer',
            'logo_path'     => 'array',
            'logo_path.*'   => 'mimes:bmp,jpeg,jpg,png,webp',
            'banner_path'   => 'array',
            'banner_path.*' => 'mimes:bmp,jpeg,jpg,png,webp',
            'attributes'    => 'required|array',
            'attributes.*'  => 'required',
        ];

        if ($i = $this->id) {
            $rules[$locale.'.slug'] = ['required', new ProductCategoryUniqueSlug('category_translations', $i)];
            $rules[$locale.'.name'] = ['required'];
            $rules[$locale.'.description'] = 'required_if:display_mode,==,description_only,products_and_description';

            return $rules;
        }

        $rules['slug'] = ['required', new ProductCategoryUniqueSlug];
        $rules['name'] = 'required';
        $rules['description'] = 'required_if:display_mode,==,description_only,products_and_description';

        return $rules;
    }
}
