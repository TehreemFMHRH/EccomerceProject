@php
    $channel = core()->getCurrentChannel();
@endphp

<!-- SEO Meta Content -->
@push('meta')
    <meta name="title" content="{{ $channel->home_seo['meta_title'] ?? '' }}" />

    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}" />

    <meta name="keywords" content="{{ $channel->home_seo['meta_keywords'] ?? '' }}" />
@endPush

<x-shop::layouts>
    <!-- Page Title -->
    <x-slot:title>
        {{ $channel->home_seo['meta_title'] ?? '' }}
    </x-slot>

    <!-- Loop over the theme customization -->
    @foreach ($customizations as $customization)
        @php($dat = $customization->options) @endphp

        <!-- Static content -->
        @switch ($customization->type)
            @case ($customization::IMAGE_CAROUSEL)
                <!-- Image Carousel -->
                <x-shop::carousel :options="$dat" aria-label="{{ trans('shop::app.home.index.image-carousel') }}" />
            @break

            @case ($customization::STATIC_CONTENT)
                <!-- push style -->
                @if (!empty($dat['css']))
                    @push('styles')
                        <style>
                            {{ $dat['css'] }}
                        </style>
                    @endpush
                @endif

                <!-- render html -->
                @if (!empty($dat['html']))
                    {!! $dat['html'] !!}
                @endif
            @break

            @case ($customization::CATEGORY_CAROUSEL)
                <!-- Categories carousel -->
                <x-shop::categories.carousel :title="$dat['title'] ?? ''" :src="route('shop.api.categories.index', $dat['filters'] ?? [])" :navigation-link="route('shop.home.index')"
                    aria-label="{{ trans('shop::app.home.index.categories-carousel') }}" />
            @break

            @case ($customization::PRODUCT_CAROUSEL)
                <!-- Product Carousel -->
                <x-shop::products.carousel :title="$dat['title'] ?? ''" :src="route('shop.api.products.index', $dat['filters'] ?? [])" :navigation-link="route('shop.search.index', $dat['filters'] ?? [])"
                    aria-label="{{ trans('shop::app.home.index.product-carousel') }}" />
            @break
        @endswitch
    @endforeach
</x-shop::layouts>
