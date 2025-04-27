<?php

namespace Webkul\Product\Helpers\Indexers\Price;

class Configurable extends AbstractType
{
    
    public function getIndices()
    {
        return [
            'min_price'         => $this->getMinimalPrice() ?? 0,
            'regular_min_price' => $this->getRegularMinimalPrice() ?? 0,
            'max_price'         => $this->getMaximumPrice() ?? 0,
            'regular_max_price' => $this->getRegularMaximumPrice() ?? 0,
            'product_id'        => $this->product->id,
            'channel_id'        => $this->channel->id,
            'customer_group_id' => $this->customerGroup->id,
        ];
    }

    
    public function getMinimalPrice($qty = null)
    {
        $minPrices = [];

        foreach ($this->product->variants as $variant) {
            if (! $variant->getTypeInstance()->isSaleable()) {
                continue;
            }

            $variantIndexer = $variant->getTypeInstance()
                ->getPriceIndexer()
                ->setChannel($this->channel)
                ->setCustomerGroup($this->customerGroup)
                ->setProduct($variant);

            $minPrices[] = $variantIndexer->getMinimalPrice();
        }

        if (empty($minPrices)) {
            return 0;
        }

        return min($minPrices);
    }

    
    public function getRegularMinimalPrice()
    {
        $minPrices = [];

        foreach ($this->product->variants as $variant) {
            if (! $variant->getTypeInstance()->isSaleable()) {
                continue;
            }

            $minPrices[] = $variant->price;
        }

        if (empty($minPrices)) {
            return 0;
        }

        return min($minPrices);
    }

    
    public function getMaximumPrice()
    {
        $maxPrices = [];

        foreach ($this->product->variants as $variant) {
            if (! $variant->getTypeInstance()->isSaleable()) {
                continue;
            }

            $variantIndexer = $variant->getTypeInstance()
                ->getPriceIndexer()
                ->setChannel($this->channel)
                ->setCustomerGroup($this->customerGroup)
                ->setProduct($variant);

            $maxPrices[] = $variantIndexer->getMinimalPrice();
        }

        if (empty($maxPrices)) {
            return 0;
        }

        return max($maxPrices);
    }

    
    public function getRegularMaximumPrice()
    {
        $maxPrices = [];

        foreach ($this->product->variants as $variant) {
            if (! $variant->getTypeInstance()->isSaleable()) {
                continue;
            }

            $maxPrices[] = $variant->price;
        }

        if (empty($maxPrices)) {
            return 0;
        }

        return max($maxPrices);
    }
}
