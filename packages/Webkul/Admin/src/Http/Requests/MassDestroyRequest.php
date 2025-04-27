<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MassDestroyRequest extends FormRequest
{
    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        return [
            'indices'   => ['required', 'array'],
            'indices.*' => ['integer'],
        ];
    }
}
