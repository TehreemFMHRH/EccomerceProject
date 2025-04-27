<?php

namespace Webkul\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Customer\Facades\Captcha;

class ContactRequest extends FormRequest
{
    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        return Captcha::getValidations([
            'name'    => 'string|required',
            'email'   => 'string|required',
            'contact' => new PhoneNumber,
            'message' => 'required',
        ]);
    }

    
    public function messages()
    {
        return Captcha::getValidationMessages();
    }
}
