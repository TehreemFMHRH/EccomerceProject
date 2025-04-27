@component('shop::emails.layout')
    <div style="margin-bottom: 34px;">
        <span style="font-size: 22px;font-weight: 600;color: #121A26">
            @lang('shop::app.emails.orders.created.title')
        </span> <br>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('shop::app.emails.dear', ['customer_name' => $o->customer_full_name]),👋
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            {!! __('shop::app.emails.orders.created.greeting', [
                'order_id' =>
                    '<a href="' .
                    route('shop.customers.account.orders.view', $o->id) .
                    '" style="color: #2969FF;">#' .
                    $o->increment_id .
                    '</a>',
                'created_at' => core()->formatDate($o->created_at, 'Y-m-d H:i:s'),
            ]) !!}
        </p>
    </div>

    <div style="font-size: 20px;font-weight: 600;color: #121A26">
        @lang('shop::app.emails.orders.created.summary')
    </div>

    <div style="display: flex;flex-direction: row;margin-top: 20px;justify-content: space-between;margin-bottom: 40px;">
        @if ($o->shipping_address)
            <div style="line-height: 25px;">
                <div style="font-size: 16px;font-weight: 600;color: #121A26;">
                    @lang('shop::app.emails.orders.shipping-address')
                </div>

                <div style="font-size: 16px;font-weight: 400;color: #384860;margin-bottom: 40px;">
                    {{ $o->shipping_address->company_name ?? '' }}<br />

                    {{ $o->shipping_address->name }}<br />

                    {{ $o->shipping_address->address }}<br />

                    {{ $o->shipping_address->postcode . ' ' . $o->shipping_address->city }}<br />

                    {{ $o->shipping_address->state }}<br />

                    ---<br />

                    @lang('shop::app.emails.orders.contact') : {{ $o->billing_address->phone }}
                </div>

                <div style="font-size: 16px;font-weight: 600;color: #121A26;">
                    @lang('shop::app.emails.orders.shipping')
                </div>

                <div style="font-size: 16px;font-weight: 400;color: #384860;">
                    {{ $o->shipping_title }}
                </div>
            </div>
        @endif

        @if ($o->billing_address)
            <div style="line-height: 25px;">
                <div style="font-size: 16px;font-weight: 600;color: #121A26;">
                    @lang('shop::app.emails.orders.billing-address')
                </div>

                <div style="font-size: 16px;font-weight: 400;color: #384860;margin-bottom: 40px;">
                    {{ $o->billing_address->company_name ?? '' }}<br />

                    {{ $o->billing_address->name }}<br />

                    {{ $o->billing_address->address }}<br />

                    {{ $o->billing_address->postcode . ' ' . $o->billing_address->city }}<br />

                    {{ $o->billing_address->state }}<br />

                    ---<br />

                    @lang('shop::app.emails.orders.contact') : {{ $o->billing_address->phone }}
                </div>

                <div style="font-size: 16px;font-weight: 600;color: #121A26;">
                    @lang('shop::app.emails.orders.payment')
                </div>

                <div style="font-size: 16px;font-weight: 400;color: #384860;">
                    {{ core()->getConfigData('sales.payment_methods.' . $o->payment->method . '.title') }}
                </div>

                @php $additionalDetails = \Webkul\Payment\Payment::getAdditionalDetails($o->payment->method); @endphp

                @if (!empty($additionalDetails))
                    <div style="font-size: 16px; color: #384860;">
                        <div>{{ $additionalDetails['title'] }}</div>

                        <div>{{ $additionalDetails['value'] }}</div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div style="padding-bottom: 40px;border-bottom: 1px solid #CBD5E1;">
        <table style="overflow-x: auto; border-collapse: collapse;
        border-spacing: 0;width: 100%">
            <thead>
                <tr style="color: #121A26;border-top: 1px solid #CBD5E1;border-bottom: 1px solid #CBD5E1;">
                    @foreach (['sku', 'name', 'price', 'qty'] as $item)
                        <th style="text-align: left;padding: 15px">
                            @lang('shop::app.emails.orders.' . $item)
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody style="font-size: 16px;font-weight: 400;color: #384860;">
                @foreach ($o->items as $item)
                    <tr style="vertical-align: text-top;">
                        <td style="text-align: left;padding: 15px">
                            {{ $item->getTypeInstance()->getOrderedItem($item)->sku }}
                        </td>

                        <td style="text-align: left;padding: 15px">
                            {{ $item->name }}

                            @if (isset($item->additional['attributes']))
                                <div>
                                    @foreach ($item->additional['attributes'] as $attribute)
                                        @if (!isset($attribute['attribute_type']) || $attribute['attribute_type'] !== 'file')
                                            <b>{{ $attribute['attribute_name'] }} :
                                            </b>{{ $attribute['option_label'] }}<br>
                                        @else
                                            {{ $attribute['attribute_name'] }} :

                                            <a href="{{ Storage::url($attribute['option_label']) }}"
                                                class="text-blue-600 hover:underline"
                                                download="{{ File::basename($attribute['option_label']) }}">
                                                {{ File::basename($attribute['option_label']) }}
                                            </a>

                                            <br>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        <td style="display: flex;flex-direction: column;text-align: left;padding: 15px">
                            @if (core()->getConfigData('sales.taxes.sales.display_prices') == 'including_tax')
                                {{ core()->formatPrice($item->price_incl_tax, $o->order_currency_code) }}
                            @elseif (core()->getConfigData('sales.taxes.sales.display_prices') == 'both')
                                {{ core()->formatPrice($item->price_incl_tax, $o->order_currency_code) }}

                                <span style="font-size: 12px; white-space: nowrap">
                                    @lang('shop::app.emails.orders.excl-tax')

                                    <span style="font-weight: 600">
                                        {{ core()->formatPrice($item->price, $o->order_currency_code) }}
                                    </span>
                                </span>
                            @else
                                {{ core()->formatPrice($item->price, $o->order_currency_code) }}
                            @endif
                        </td>

                        <td style="text-align: left;padding: 15px">
                            {{ $item->qty_ordered }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div
        style="display: grid;justify-content: end;font-size: 16px;color: #384860;line-height: 30px;padding-top: 20px;padding-bottom: 20px;">
        @if (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'including_tax')
            <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                <span>
                    @lang('shop::app.emails.orders.subtotal')
                </span>

                <span style="text-align: right;">
                    {{ core()->formatPrice($o->sub_total, $o->order_currency_code_incl_tax) }}
                </span>
            </div>
        @elseif (core()->getConfigData('sales.taxes.sales.display_subtotal') == 'both')
            <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                <span>
                    @lang('shop::app.emails.orders.subtotal-excl-tax')
                </span>

                <span style="text-align: right;">
                    {{ core()->formatPrice($o->sub_total, $o->order_currency_code) }}
                </span>
            </div>

            <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                <span>
                    @lang('shop::app.emails.orders.subtotal-incl-tax')
                </span>

                <span style="text-align: right;">
                    {{ core()->formatPrice($o->sub_total, $o->order_currency_code_incl_tax) }}
                </span>
            </div>
        @else
            <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                <span>
                    @lang('shop::app.emails.orders.subtotal')
                </span>

                <span style="text-align: right;">
                    {{ core()->formatPrice($o->sub_total, $o->order_currency_code) }}
                </span>
            </div>
        @endif

        @if ($o->shipping_address)
            @if (core()->getConfigData('sales.taxes.sales.display_shipping_amount') == 'including_tax')
                <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <span>
                        @lang('shop::app.emails.orders.shipping-handling')
                    </span>

                    <span style="text-align: right;">
                        {{ core()->formatPrice($o->shipping_amount_incl_tax, $o->order_currency_code) }}
                    </span>
                </div>
            @elseif (core()->getConfigData('sales.taxes.sales.display_shipping_amount') == 'both')
                <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <span>
                        @lang('shop::app.emails.orders.shipping-handling-excl-tax')
                    </span>

                    <span style="text-align: right;">
                        {{ core()->formatPrice($o->shipping_amount, $o->order_currency_code) }}
                    </span>
                </div>

                <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <span>
                        @lang('shop::app.emails.orders.shipping-handling-incl-tax')
                    </span>

                    <span style="text-align: right;">
                        {{ core()->formatPrice($o->shipping_amount_incl_tax, $o->order_currency_code) }}
                    </span>
                </div>
            @else
                <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <span>
                        @lang('shop::app.emails.orders.shipping-handling')
                    </span>

                    <span style="text-align: right;">
                        {{ core()->formatPrice($o->shipping_amount, $o->order_currency_code) }}
                    </span>
                </div>
            @endif
        @endif

        <div style="display: grid;gap: 100px;grid-template-columns: repeat(2, minmax(0, 1fr));">
            <span>
                @lang('shop::app.emails.orders.tax')
            </span>

            <span style="text-align: right;">
                {{ core()->formatPrice($o->tax_amount, $o->order_currency_code) }}
            </span>
        </div>

        @if ($o->discount_amount > 0)
            <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));">
                <span>
                    @lang('shop::app.emails.orders.discount')
                </span>

                <span style="text-align: right;">
                    {{ core()->formatPrice($o->discount_amount, $o->order_currency_code) }}
                </span>
            </div>
        @endif

        <div style="display: grid;gap: 20px;grid-template-columns: repeat(2, minmax(0, 1fr));font-weight: bold">
            <span>
                @lang('shop::app.emails.orders.grand-total')
            </span>

            <span style="text-align: right;">
                {{ core()->formatPrice($o->grand_total, $o->order_currency_code) }}
            </span>
        </div>
    </div>
@endcomponent
