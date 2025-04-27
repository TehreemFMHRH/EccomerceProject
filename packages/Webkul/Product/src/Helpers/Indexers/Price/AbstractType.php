<?php

namespace Webkul\Product\Helpers\Indexers\Price;

use Illuminate\Support\Carbon;
use Webkul\CatalogRule\Repositories\CatalogRuleProductPriceRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;

abstract class AbstractType
{
    
    protected $product;

    
    protected $channel;

    
    protected $customerGroup;

    
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected ProductCustomerGroupPriceRepository $productCustomerGroupPriceRepository,
        protected CatalogRuleProductPriceRepository $catalogRuleProductPriceRepository
    ) {}

    
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    
    public function setCustomerGroup($customerGroup)
    {
        $this->customerGroup = $customerGroup;

        return $this;
    }

    
    public function getIndices()
    {
        return [
            'min_price'         => ($minPrice = $this->getMinimalPrice()) ?? 0,
            'regular_min_price' => $this->product->price ?? 0,
            'max_price'         => $minPrice ?? 0,
            'regular_max_price' => $this->product->price ?? 0,
            'product_id'        => $this->product->id,
            'channel_id'        => $this->channel->id,
            'customer_group_id' => $this->customerGroup->id,
        ];
    }

    
    public function getMinimalPrice($qty = null)
    {
        $customerGroupPrice = $this->getCustomerGroupPrice($qty ?? 1);

        $rulePrice = $this->getCatalogRulePrice();

        if (
            empty($this->product->special_price)
            && empty($rulePrice)
            && $customerGroupPrice == $this->product->price
        ) {
            return $this->product->price;
        }

        if (! (float) $this->product->special_price) {
            if ($rulePrice) {
                $discountedPrice = min($rulePrice->price, $this->product->price);
            } else {
                $discountedPrice = $this->product->price;
            }
        } else {
            if ($rulePrice) {
                if (
                    core()->isChannelDateInInterval(
                        $this->product->special_price_from,
                        $this->product->special_price_to
                    )
                ) {
                    $discountedPrice = min($rulePrice->price, $this->product->special_price);
                } else {
                    $discountedPrice = $rulePrice->price;
                }
            } else {
                if (
                    core()->isChannelDateInInterval(
                        $this->product->special_price_from,
                        $this->product->special_price_to
                    )
                ) {
                    $discountedPrice = $this->product->special_price;
                } else {
                    $discountedPrice = $this->product->price;
                }
            }
        }

        return min($discountedPrice, $customerGroupPrice);
    }

    
    public function getCustomerGroupPrice($qty)
    {
        $customerGroupPrices = $this->productCustomerGroupPriceRepository
            ->prices($this->product, $this->customerGroup->id);

        if ($customerGroupPrices->isEmpty()) {
            return $this->product->price;
        }

        $lastQty = 1;

        $lastPrice = $this->product->price;

        foreach ($customerGroupPrices as $customerGroupPrice) {
            if (
                $customerGroupPrice->qty > $qty
                || $customerGroupPrice->qty < $lastQty
            ) {
                continue;
            }

            if ($customerGroupPrice->value_type == 'discount') {
                if (
                    $customerGroupPrice->value >= 0
                    && $customerGroupPrice->value <= 100
                ) {
                    $lastPrice = $this->product->price - ($this->product->price * $customerGroupPrice->value) / 100;

                    $lastQty = $customerGroupPrice->qty;
                }
            } else {
                if (
                    $customerGroupPrice->value >= 0
                    && $customerGroupPrice->value < $lastPrice
                ) {
                    $lastPrice = $customerGroupPrice->value;

                    $lastQty = $customerGroupPrice->qty;
                }
            }
        }

        return $lastPrice;
    }

    
    public function getCatalogRulePrice()
    {
        return $this->product->catalog_rule_prices
            ->where('customer_group_id', $this->customerGroup->id)
            ->where('channel_id', $this->channel->id)
            ->where('rule_date', Carbon::now()->format('Y-m-d'))
            ->first();
    }
}
