<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Code implements ValidationRule
{
    
    public function validate(string $attribute, mixed $va, Closure $fail): void
    {
        if (! preg_match('/^[a-zA-Z]+[a-zA-Z0-9_]+$/', $va)) {
            $fail('core::validation.code')->translate();
        }
    }
}
