<?php

namespace Webkul\Customer\Rules;

use Illuminate\Contracts\Validation\Rule;


class VatIdRule implements Rule
{
    
    public function passes($attribute, $va)
    {
        $validator = new VatValidator;

        return empty($va) || $validator->validate($va);
    }

    
    public function message()
    {
        return trans('customer::app.validations.vat-id.invalid-format');
    }
}
