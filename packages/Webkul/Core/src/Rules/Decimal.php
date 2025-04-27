<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Decimal implements ValidationRule
{
    
    public function validate(string $attribute, mixed $va, Closure $fail): void
    {
        if (! preg_match('/^\d*(\.\d{1,4})?$/', $va)) {
            $fail('core::validation.decimal')->translate();
        }
    }
}
