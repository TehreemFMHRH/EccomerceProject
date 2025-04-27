<?php

namespace Webkul\Rule\Helpers;

use Webkul\Checkout\Contracts\Cart as CheckoutContract;
use Webkul\Checkout\Facades\Cart;

class Validator
{
    
    public function validate($rule, $entity)
    {
        if (! $rule->conditions) {
            return true;
        }

        $validConditionCount = $totalConditionCount = 0;

        foreach ($rule->conditions as $condition) {
            if (
                ! $condition['attribute']
                || empty($condition['value'])
            ) {
                continue;
            }

            if (
                $entity instanceof CheckoutContract
                && strpos($condition['attribute'], 'cart|') === false
            ) {
                continue;
            }

            $totalConditionCount++;

            if ($rule->condition_type == '1') {
                if (! $this->validateObject($condition, $entity)) {
                    return false;
                } else {
                    $validConditionCount++;
                }
            } elseif ($rule->condition_type == '2') {
                if ($this->validateObject($condition, $entity)) {
                    return true;
                }
            }
        }

        return $validConditionCount == $totalConditionCount;
    }

    
    public function getAttributeValue($condition, $entity)
    {
        $chunks = explode('|', $condition['attribute']);

        $attributeNameChunks = explode('::', $chunks[1]);

        $attributeCode = $attributeNameChunks[count($attributeNameChunks) - 1];

        switch (current($chunks)) {
            case 'cart':
                $cart = $entity instanceof CheckoutContract ? $entity : $entity->cart;

                if (in_array($attributeCode, ['postcode', 'state', 'country'])) {
                    if (! $cart->shipping_address) {
                        return;
                    }

                    return $cart->shipping_address->{$attributeCode};
                } elseif ($attributeCode == 'shipping_method') {
                    if (! $cart->shipping_method) {
                        return;
                    }

                    $shippingChunks = explode('_', $cart->shipping_method);

                    return current($shippingChunks);
                } elseif ($attributeCode == 'payment_method') {
                    if (! $cart->payment) {
                        return;
                    }

                    return $cart->payment->method;
                } else {
                    return $cart->{$attributeCode};
                }

            case 'cart_item':
                return $entity->{$attributeCode};

            case 'product':
                if ($attributeCode == 'category_ids') {
                    $va = $entity->product
                        ? $entity->product->categories()->pluck('id')->toArray()
                        : $entity->categories()->pluck('id')->toArray();

                    return $va;
                } else {
                    $va = $entity->product
                        ? $entity->product->{$attributeCode}
                        : $entity->{$attributeCode};

                    if (! in_array($condition['attribute_type'], ['multiselect', 'checkbox'])) {
                        return $va;
                    }

                    return $va ? explode(',', $va) : [];
                }
        }
    }

    
    private function validateObject($condition, $entity)
    {
        $validated = false;

        foreach ($this->getAllItems($this->getAttributeScope($condition), $entity) as $item) {
            $attributeValue = $this->getAttributeValue($condition, $item);

            if ($validated = $this->validateAttribute($condition, $attributeValue)) {
                break;
            }
        }

        return $validated;
    }

    
    private function getAllItems($attributeScope, $item)
    {
        if ($attributeScope === 'parent') {
            return [$item];
        } elseif ($attributeScope === 'children') {
            return $item->children ?: [$item];
        } else {
            $items = $item->children ?: [];

            $items[] = $item;
        }

        return $items;
    }

    
    private function getAttributeScope($condition)
    {
        $chunks = explode('|', $condition['attribute']);

        $attributeNameChunks = explode('::', $chunks[1]);

        return count($attributeNameChunks) == 2 ? $attributeNameChunks[0] : null;
    }

    
    public function validateAttribute($condition, $attributeValue)
    {
        switch ($condition['operator']) {
            case '==': case '!=':
                if (is_array($condition['value'])) {
                    if (! is_array($attributeValue)) {
                        return false;
                    }

                    $result = ! empty(array_intersect($condition['value'], $attributeValue));
                } else {
                    if (is_array($attributeValue)) {
                        $result = count($attributeValue) == 1 && array_shift($attributeValue) == $condition['value'];
                    } else {
                        $result = $attributeValue == $condition['value'];
                    }
                }

                break;

            case '<=': case '>':
                if (! is_scalar($attributeValue)) {
                    return false;
                }

                $result = $attributeValue <= $condition['value'];

                break;

            case '>=': case '<':
                if (! is_scalar($attributeValue)) {
                    return false;
                }

                $result = $attributeValue >= $condition['value'];

                break;

            case '{}': case '!{}':
                if (
                    is_scalar($attributeValue)
                    && is_array($condition['value'])
                ) {
                    foreach ($condition['value'] as $item) {
                        if (stripos($attributeValue, $item) !== false) {
                            $result = true;

                            break;
                        }
                    }
                } elseif (is_array($condition['value'])) {
                    if (! is_array($attributeValue)) {
                        return false;
                    }

                    $result = ! empty(array_intersect($condition['value'], $attributeValue));
                } else {
                    if (is_array($attributeValue)) {
                        $result = self::validateArrayValues($attributeValue, $condition['value']);
                    } else {
                        $result = strpos($attributeValue, $condition['value']) !== false;
                    }
                }

                break;
        }

        if (in_array($condition['operator'], ['!=', '>', '<', '!{}'])) {
            $result = ! $result;
        }

        return $result;
    }

    
    private static function validateArrayValues(array $attributeValue, string $conditionValue): bool
    {
        if (in_array($conditionValue, $attributeValue, true) === true) {
            return true;
        }

        foreach ($attributeValue as $subValue) {
            if (! is_array($subValue)) {
                continue;
            }

            if (self::validateArrayValues($subValue, $conditionValue) === true) {
                return true;
            }
        }

        return false;
    }
}
