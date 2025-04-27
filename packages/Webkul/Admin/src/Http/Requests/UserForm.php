<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserForm extends FormRequest
{
    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        return [
            'name'                  => 'required',
            'email'                 => 'required|email|unique:admins,email,'.$this->id,
            'password'              => 'nullable|min:6|confirmed',
            'password_confirmation' => 'nullable|required_with:password|same:password',
            'status'                => 'sometimes',
            'role_id'               => 'required',
            'image'                 => 'array',
            'image.*'               => 'mimes:jpeg,jpg,png,gif|max:10000',
        ];
    }
}
