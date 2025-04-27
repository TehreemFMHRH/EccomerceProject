<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;
use Webkul\Customer\Rules\VatIdRule;

class AddressRequest extends FormRequest
{
    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        return [
            'company_name' => ['nullable'],
            'first_name'   => ['required'],
            'last_name'    => ['required'],
            'address'      => ['required', 'array', 'min:1'],
            'country'      => core()->isCountryRequired() ? ['required'] : ['nullable'],
            'state'        => core()->isStateRequired() ? ['required'] : ['nullable'],
            'city'         => ['required', 'string'],
            'postcode'     => core()->isPostCodeRequired() ? ['required', new PostCode] : [new PostCode],
            'phone'        => ['required', new PhoneNumber],
            'vat_id'       => [new VatIdRule],
            'email'        => ['required'],
        ];
    }

    
    public function attributes()
    {
        return [
            'address.*' => 'address',
        ];
    }
}
