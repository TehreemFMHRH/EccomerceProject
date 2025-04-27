<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Customer\Facades\Captcha;

class LoginRequest extends FormRequest
{
    
    private $rules = [
        'email'    => 'required|email',
        'password' => 'required|min:6',
    ];

    
    public function authorize()
    {
        return true;
    }

    
    public function rules(): array
    {
        return Captcha::getValidations($this->rules);
    }

    
    public function messages(): array
    {
        return Captcha::getValidationMessages();
    }
}
