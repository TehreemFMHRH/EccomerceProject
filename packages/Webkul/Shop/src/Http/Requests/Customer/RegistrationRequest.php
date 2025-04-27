<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Customer\Facades\Captcha;

class RegistrationRequest extends FormRequest
{
    
    private $rules = [
        'first_name' => 'string|required',
        'last_name'  => 'string|required',
        'email'      => 'email|required|unique:customers,email',
        'password'   => 'confirmed|min:6|required',
    ];

    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        return Captcha::getValidations($this->rules);
    }

    
    public function messages()
    {
        return Captcha::getValidationMessages();
    }
}
