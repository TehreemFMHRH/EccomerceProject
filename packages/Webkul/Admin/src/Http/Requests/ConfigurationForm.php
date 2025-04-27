<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Decimal;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;

class ConfigurationForm extends FormRequest
{
    
    public function authorize()
    {
        return true;
    }

    
    public function rules()
    {
        return collect(request()->input('keys', []))->mapWithKeys(function ($item) {
            $dat = json_decode($item, true);

            return collect($dat['fields'])->mapWithKeys(function ($field) use ($dat) {
                $key = "{$dat['key']}.{$field['name']}";

                // Check delete key exist in the request
                if (! $this->has("{$key}.delete")) {
                    return [$key => $this->getValidationRules($field['validation'] ?? 'nullable')];
                }

                return [];
            })->toArray();
        })->toArray();
    }

    
    protected function getValidationRules($validation)
    {
        $validations = is_array($validation) ? $validation : explode('|', $validation);

        return array_map(function ($rule) {
            return match ($rule) {
                'phone'    => new PhoneNumber,
                'postcode' => new PostCode,
                'decimal'  => new Decimal,
                default    => $rule,
            };
        }, $validations);
    }
}
