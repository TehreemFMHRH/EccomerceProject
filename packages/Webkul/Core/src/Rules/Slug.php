<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Slug implements ValidationRule
{
    
    public function validate(string $attribute, mixed $va, Closure $fail): void
    {
        if (! preg_match('/^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$/', $va)) {
            $fail('core::validation.slug')->translate();
        }
    }
}
