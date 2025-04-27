<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CommaSeparatedInteger implements ValidationRule
{
    
    public function validate(string $attribute, mixed $va, Closure $fail): void
    {
        if (! $this->isCommaSeparatedInteger($attribute, $va)) {
            $fail('core::validation.comma-separated-integer')->translate();
        }
    }

    
    public function isCommaSeparatedInteger($attribute, $va)
    {
        $integerValues = explode(',', $va);

        foreach ($integerValues as $integerValue) {
            if (! preg_match('/^[0-9]+$/', $integerValue)) {
                return false;
            }
        }

        return true;
    }
}
