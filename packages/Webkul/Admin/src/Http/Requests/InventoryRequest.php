<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
{
    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        return [
            'inventories'   => 'required|array',
            'inventories.*' => 'required|numeric|min:0',
        ];
    }

    
    public function messages()
    {
        return [
            'inventories.*.required' => __('admin::app.catalog.products.validations.quantity-required'),
            'inventories.*.integer'  => __('admin::app.catalog.products.validations.quantity-integer'),
            'inventories.*.min'      => __('admin::app.catalog.products.validations.quantity-min-zero'),
        ];
    }
}
