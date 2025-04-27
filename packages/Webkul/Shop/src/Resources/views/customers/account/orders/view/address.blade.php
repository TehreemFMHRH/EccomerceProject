<div class="flex flex-col max-md:hidden">
    <p class="font-semibold leading-6 text-gray-800">
        {{ $addr->company_name ?? '' }}
    </p>

    <p class="font-semibold leading-6 text-gray-800">
        {{ $addr->name }}
    </p>

    <p class="!leading-6 text-gray-600">
        {{ $addr->address }}<br>

        {{ $addr->city }}<br>

        {{ $addr->state }}<br>

        {{ core()->country_name($addr->country) }} @if ($addr->postcode)
            ({{ $addr->postcode }})
        @endif
        <br>

        {{ __('shop::app.customers.account.orders.view.contact') }} : {{ $addr->phone }}
    </p>
</div>

<!-- For Mobile View -->
<div class="text-gray-800 md:hidden">
    <p class="font-semibold">
        {{ $addr->company_name ?? '' }}
    </p>

    <p class="text-xs">
        {{ $addr->name }}

        {{ $addr->address }}

        {{ $addr->city }}

        {{ $addr->state }}

        {{ core()->country_name($addr->country) }} @if ($addr->postcode)
            ({{ $addr->postcode }})
        @endif <br>

        <span class="no-underline">
            {{ __('shop::app.customers.account.orders.view.contact') }} : {{ $addr->phone }}
        </span>
    </p>
</div>
