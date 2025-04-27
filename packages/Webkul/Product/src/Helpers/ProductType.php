<?php

namespace Webkul\Product\Helpers;

use Webkul\Product\Type\AbstractType;

class ProductType
{
    
    public static function hasVariants(string $typeKey): bool
    {
        
        $type = app(config('product_types.'.$typeKey.'.class'));

        return $type->hasVariants();
    }

    
    public static function getAllTypesHavingVariants(): array
    {
        $havingVariants = [];

        foreach (config('product_types') as $type) {
            if (self::hasVariants($type['key'])) {
                array_push($havingVariants, $type['key']);
            }
        }

        return $havingVariants;
    }
}
