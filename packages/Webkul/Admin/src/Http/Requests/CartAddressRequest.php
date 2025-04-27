<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;

class CartAddressRequest extends FormRequest
{
    
    protected $rules = [];

    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        if ($this->has('billing')) {
            $this->mergeAddressRules('billing');
        }

        if (! $this->input('billing.use_for_shipping')) {
            $this->mergeAddressRules('shipping');
        }

        return $this->rules;
    }

    
    private function mergeAddressRules(string $addressType)
    {
        $this->mergeWithRules([
            "{$addressType}.company_name" => ['nullable'],
            "{$addressType}.first_name"   => ['required'],
            "{$addressType}.last_name"    => ['required'],
            "{$addressType}.email"        => ['required'],
            "{$addressType}.address"      => ['required', 'array', 'min:1'],
            "{$addressType}.city"         => ['required'],
            "{$addressType}.country"      => ['required'],
            "{$addressType}.state"        => ['required'],
            "{$addressType}.postcode"     => ['required', new PostCode],
            "{$addressType}.phone"        => ['required', new PhoneNumber],
        ]);
    }

    
    private function mergeWithRules($rules): void
    {
        $this->rules = array_merge($this->rules, $rules);
    }
}
