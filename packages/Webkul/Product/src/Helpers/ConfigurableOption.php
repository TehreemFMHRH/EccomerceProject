<?php

namespace Webkul\Product\Helpers;

use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Facades\ProductVideo;

class ConfigurableOption
{
    
    protected $allowedVariants = [];

    
    protected $superAttributes = [];

    
    public function getAllowedVariants($product)
    {
        if (count($this->allowedVariants)) {
            return $this->allowedVariants;
        }

        $variantCollection = $product->variants()
            ->with([
                'parent',
                'attribute_values',
                'price_indices',
                'inventory_indices',
                'images',
                'videos',
            ])
            ->get();

        foreach ($variantCollection as $variant) {
            if ($variant->isSaleable()) {
                $this->allowedVariants[] = $variant;
            }
        }

        return $this->allowedVariants;
    }

    
    public function getConfigurationConfig($product)
    {
        $options = $this->getOptions($product, $this->getAllowedVariants($product));

        $config = [
            'attributes'     => $this->getAttributesData($product, $options),
            'index'          => $options['index'] ?? [],
            'variant_prices' => $this->getVariantPrices($product),
            'variant_images' => $this->getVariantImages($product),
            'variant_videos' => $this->getVariantVideos($product),
        ];

        return array_merge($config, $product->getTypeInstance()->getProductPrices());
    }

    
    public function getAllowAttributes($product)
    {
        if (isset($this->superAttributes[$product->id])) {
            return $this->superAttributes[$product->id];
        }

        return $this->superAttributes[$product->id] = $product->super_attributes()
            ->with(['translations', 'options', 'options.translations'])
            ->get();
    }

    
    public function getOptions($currentProduct, $allowedProducts)
    {
        $options = [];

        $allowAttributes = $this->getAllowAttributes($currentProduct);

        foreach ($allowedProducts as $product) {
            foreach ($allowAttributes as $productAttribute) {
                $productAttributeId = $productAttribute->id;

                $attributeValue = $product->{$productAttribute->code};

                $options[$productAttributeId][$attributeValue][] = $product->id;

                $options['index'][$product->id][$productAttributeId] = $attributeValue;
            }
        }

        return $options;
    }

    
    public function getAttributesData($product, array $options = [])
    {
        $attributes = [];

        $allowAttributes = $this->getAllowAttributes($product);

        foreach ($allowAttributes as $attribute) {
            $attributes[] = [
                'id'          => $attribute->id,
                'code'        => $attribute->code,
                'label'       => $attribute->name ? $attribute->name : $attribute->admin_name,
                'swatch_type' => $attribute->swatch_type,
                'options'     => $this->getAttributeOptionsData($attribute, $options),
            ];
        }

        return $attributes;
    }

    
    protected function getAttributeOptionsData($attribute, $options)
    {
        $attributeOptionsData = [];

        foreach ($attribute->options->sortBy('sort_order') as $attributeOption) {
            $optionId = $attributeOption->id;

            if (! isset($options[$attribute->id][$optionId])) {
                continue;
            }

            $attributeOptionsData[] = [
                'id'           => $optionId,
                'label'        => $attributeOption->label ? $attributeOption->label : $attributeOption->admin_name,
                'swatch_value' => $attribute->swatch_type == 'image' ? $attributeOption->swatch_value_url : $attributeOption->swatch_value,
                'products'     => $options[$attribute->id][$optionId],
            ];
        }

        return $attributeOptionsData;
    }

    
    protected function getVariantPrices($product)
    {
        $prices = [];

        foreach ($this->getAllowedVariants($product) as $variant) {
            $prices[$variant->id] = $variant->getTypeInstance()->getProductPrices();
        }

        return $prices;
    }

    
    protected function getVariantImages($product)
    {
        $images = [];

        foreach ($this->getAllowedVariants($product) as $variant) {
            $images[$variant->id] = ProductImage::getGalleryImages($variant);
        }

        return $images;
    }

    
    protected function getVariantVideos($product)
    {
        $videos = [];

        foreach ($this->getAllowedVariants($product) as $variant) {
            $videos[$variant->id] = ProductVideo::getVideos($variant);
        }

        return $videos;
    }
}
