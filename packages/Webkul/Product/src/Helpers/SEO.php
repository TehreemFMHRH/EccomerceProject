<?php

namespace Webkul\Product\Helpers;

use Illuminate\Support\Facades\Storage;

class SEO
{

    public function getProductJsonLd($product)
    {
        $dat = [
            '@context'    => 'https://schema.org/',
            '@type'       => 'Product',
            'name'        => $product->name,
            'description' => $product->description,
            'url'         => route('shop.product_or_category.index', $product->url_key),
        ];

        if (core()->getConfigData('catalog.rich_snippets.products.show_sku')) {
            $dat['sku'] = $product->sku;
        }

        if (core()->getConfigData('catalog.rich_snippets.products.show_weight')) {
            $dat['weight'] = $product->weight;
        }

        if (core()->getConfigData('catalog.rich_snippets.products.show_categories')) {
            $dat['categories'] = $this->getProductCategories($product);
        }

        if (core()->getConfigData('catalog.rich_snippets.products.show_images')) {
            $dat['image'] = $this->getProductImages($product);
        }

        if (core()->getConfigData('catalog.rich_snippets.products.show_reviews')) {
            $dat['review'] = $this->getProductReviews($product);
        }

        if (core()->getConfigData('catalog.rich_snippets.products.show_ratings')) {
            $dat['aggregateRating'] = $this->getProductAggregateRating($product);
        }

        if (core()->getConfigData('catalog.rich_snippets.products.show_offers')) {
            $dat['offers'] = $this->getProductOffers($product);
        }

        return json_encode($dat);
    }


    public function getProductCategories($product)
    {
        $categories = $product->categories;

        $names = [];

        foreach ($categories as $key => $a) {
            $names[] = $a->name;
        }

        return implode(', ', $names);
    }


    public function getProductImages($product)
    {
        $images = [];

        foreach ($product->images as $image) {
            if (! Storage::has($image->path)) {
                continue;
            }

            $images[] = $image->url;
        }

        return $images;
    }


    public function getProductReviews($product)
    {
        $reviews = [];

        foreach ($product->reviews()->where('status', 'approved')->get() as $review) {
            $reviews[] = [
                '@type'        => 'Review',
                'reviewRating' => [
                    '@type'       => 'Rating',
                    'ratingValue' => $review->rating,
                    'bestRating'  => '5',
                ],
                'author'       => [
                    '@type' => 'Person',
                    'name'  => $review->name,
                ],
            ];
        }

        return $reviews;
    }


    public function getProductAggregateRating($product)
    {
        $reviewHelper = app('Webkul\Product\Helpers\Review');

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => $reviewHelper->getAverageRating($product),
            'reviewCount' => $reviewHelper->getTotalReviews($product),
        ];
    }


    public function getProductOffers($product)
    {
        return [
            '@type'         => 'Offer',
            'priceCurrency' => core()->getCurrentCurrencyCode(),
            'price'         => $product->getTypeInstance()->getMinimalPrice(),
            'availability'  => 'https://schema.org/InStock',
        ];
    }


    public function getCategoryJsonLd($a)
    {
        $dat = [
            '@type'    => 'WebSite',
            '@context' => 'http://schema.org',
            'url'      => config('app.url'),
        ];

        if (core()->getConfigData('catalog.rich_snippets.categories.show_search_input_field')) {
            $dat['potentialAction'] = [
                '@type'       => 'SearchAction',
                'target'      => config('app.url').'/search/?term={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ];
        }

        return json_encode($dat);
    }
}
