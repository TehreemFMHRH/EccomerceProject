<x-shop::layouts :has-header="true" :has-feature="false" :has-footer="true">
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.checkout.success.thanks')
    </x-slot>

    <!-- Page content -->
    <div class="container mt-8 px-[60px] max-lg:px-8">
        <div class="grid place-items-center gap-y-5 max-md:gap-y-2.5">
            {{ view_render_event('bagisto.shop.checkout.success.image.before', ['order' => $o]) }}

            <img class="max-md:h-[100px] max-md:w-[100px]" src="{{ bagisto_asset('images/thank-you.png') }}"
                alt="@lang('shop::app.checkout.success.thanks')" title="@lang('shop::app.checkout.success.thanks')">

            {{ view_render_event('bagisto.shop.checkout.success.image.after', ['order' => $o]) }}

            <p class="text-xl max-md:text-sm">
                @if (auth()->guard('customer')->user())
                    @lang('shop::app.checkout.success.order-id-info', [
                        'order_id' => '<a class="text-blue-700" href="' . route('shop.customers.account.orders.view', $o->id) . '">' . $o->increment_id . '</a>',
                    ])
                @else
                    @lang('shop::app.checkout.success.order-id-info', ['order_id' => $o->increment_id])
                @endif
            </p>

            <p class="font-medium md:text-2xl">
                @lang('shop::app.checkout.success.thanks')
            </p>

            <p class="text-xl text-zinc-500 max-md:text-center max-md:text-xs">
                @if (!empty($o->checkout_message))
                    {!! nl2br($o->checkout_message) !!}
                @else
                    @lang('shop::app.checkout.success.info')
                @endif
            </p>

            {{ view_render_event('bagisto.shop.checkout.success.continue-shopping.before', ['order' => $o]) }}

            <a href="{{ route('shop.home.index') }}">
                <div
                    class="w-max cursor-pointer rounded-2xl bg-navyBlue px-11 py-3 text-center text-base font-medium text-white max-md:rounded-lg max-md:px-6 max-md:py-1.5">
                    @lang('shop::app.checkout.cart.index.continue-shopping')
                </div>
            </a>

            {{ view_render_event('bagisto.shop.checkout.success.continue-shopping.after', ['order' => $o]) }}
        </div>
    </div>
</x-shop::layouts>
