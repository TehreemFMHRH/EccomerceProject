<div class="flex flex-col">
    <p class="font-semibold leading-6 text-gray-800 dark:text-white">
        {{ $addr->company_name ?? '' }}
    </p>

    <p class="font-semibold leading-6 text-gray-800 dark:text-white">
        {{ $addr->name }}
    </p>

    <p class="!leading-6 text-gray-600 dark:text-gray-300">
        {{ $addr->address }}<br>

        {{ $addr->city }}<br>

        {{ $addr->state }}<br>

        {{ core()->country_name($addr->country) }} @if ($addr->postcode)
            ({{ $addr->postcode }})
        @endif
        <br>

        {{ __('admin::app.sales.orders.view.contact') }} : {{ $addr->phone }}
    </p>
</div>
