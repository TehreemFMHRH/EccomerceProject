<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Customer\Facades\Captcha;

class ForgotPasswordRequest extends FormRequest
{
    
    private $rules = [
        'email'    => 'required|email',
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
