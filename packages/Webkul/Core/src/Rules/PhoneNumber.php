<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    
    public function validate(string $attribute, mixed $va, Closure $fail): void
    {
        
        if (! preg_match('/^\+?\d+$/', $va)) {
            $fail('core::validation.phone-number')->translate();
        }
    }
}
