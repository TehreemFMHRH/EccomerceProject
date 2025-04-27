<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CatalogRuleRequest extends FormRequest
{
    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        $rules = [
            'name'            => 'required',
            'channels'        => 'required|array|min:1',
            'customer_groups' => 'required|array|min:1',
            'starts_from'     => 'nullable|date',
            'ends_till'       => 'nullable|date|after_or_equal:starts_from',
            'action_type'     => 'required',
            'discount_amount' => 'required|numeric',
        ];

        if (request()->has('action_type') && request()->action_type == 'by_percent') {
            $rules = array_merge($rules, [
                'discount_amount' => 'required|numeric|min:0|max:100',
            ]);
        }

        return $rules;
    }
}
