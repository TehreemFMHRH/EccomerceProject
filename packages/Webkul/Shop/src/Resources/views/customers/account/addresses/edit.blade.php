<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.account.addresses.edit.edit')
        @lang('shop::app.customers.account.addresses.edit.title')
    </x-slot>

    <!-- Breadcrumbs -->
    @if (core()->getConfigData('general.general.breadcrumbs.shop'))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="addresses.edit" :entity="$addr" />
        @endSection
    @endif

    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="mx-4 flex-auto max-md:mx-6 max-sm:mx-4">
        <div class="mb-8 flex items-center max-md:mb-5">
            <!-- Back Button -->
            <a class="grid md:hidden" href="{{ route('shop.customers.account.addresses.index') }}">
                <span class="icon-arrow-left rtl:icon-arrow-right text-2xl"></span>
            </a>

            <h2 class="text-2xl font-medium max-sm:text-base ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                @lang('shop::app.customers.account.addresses.edit.edit')
                @lang('shop::app.customers.account.addresses.edit.title')
            </h2>
        </div>

        {!! view_render_event('bagisto.shop.customers.account.address.edit.before', ['address' => $addr]) !!}

        <!-- Customer Address edit Component-->
        <v-edit-customer-address>
            <!-- Address Shimmer -->
            <x-shop::shimmer.form.control-group :count="10" />
        </v-edit-customer-address>

        {!! view_render_event('bagisto.shop.customers.account.address.edit.after', ['address' => $addr]) !!}
    </div>

    @push('scripts')
        <script
            type="text/x-template"
            id="v-edit-customer-address-template"
        >
            <!-- Edit Address Form -->
            <x-shop::form
                method="PUT"
                :action="route('shop.customers.account.addresses.update',  $addr->id)"
            >
                {!! view_render_event('bagisto.shop.customers.account.address.edit_form_controls.before', ['address' => $addr]) !!}

                <!-- Company Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label>
                        @lang('shop::app.customers.account.addresses.edit.company-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="company_name"
                        :value="old('company_name') ?? $addr->company_name"
                        :label="trans('shop::app.customers.account.addresses.edit.company-name')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.company-name')"
                    />

                    <x-shop::form.control-group.error control-name="company_name" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.company_name.after', ['address' => $addr]) !!}

                <!-- First Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.first-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="first_name"
                        rules="required"
                        :value="old('first_name') ?? $addr->first_name"
                        :label="trans('shop::app.customers.account.addresses.edit.first-name')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.first-name')"
                    />

                    <x-shop::form.control-group.error control-name="first_name" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.first_name.after', ['address' => $addr]) !!}

                <!-- Last Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.last-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="last_name"
                        rules="required"
                        :value="old('last_name') ?? $addr->last_name"
                        :label="trans('shop::app.customers.account.addresses.edit.last-name')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.last-name')"
                    />

                    <x-shop::form.control-group.error control-name="last_name" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.last_name.after', ['address' => $addr]) !!}

                <!-- E-mail -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('Email')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="email"
                        rules="required|email"
                        :value="old('email') ?? $addr->email"
                        :label="trans('Email')"
                        :placeholder="trans('Email')"
                    />

                    <x-shop::form.control-group.error control-name="email" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.email.after', ['address' => $addr]) !!}

                <!-- Vat ID -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label>
                        @lang('shop::app.customers.account.addresses.edit.vat-id')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="vat_id"
                        :value="old('vat_id') ?? $addr->vat_id"
                        :label="trans('shop::app.customers.account.addresses.edit.vat-id')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.vat-id')"
                    />

                    <x-shop::form.control-group.error control-name="vat_id" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.vat_id.after', ['address' => $addr]) !!}

                @php
                    $addresses = explode(PHP_EOL, $addr->address);
                @endphp

                <!-- Street Address -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.street-address')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="address[]"
                        :value="collect(old('address'))->first() ?? $addresses[0]"
                        rules="required|address"
                        :label="trans('shop::app.customers.account.addresses.edit.street-address')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.street-address')"
                    />

                    <x-shop::form.control-group.error control-name="address[]" />
                </x-shop::form.control-group>

                @if (
                    core()->getConfigData('customer.address.information.street_lines')
                    && core()->getConfigData('customer.address.information.street_lines') > 1
                )
                    @for ($i = 1; $i < core()->getConfigData('customer.address.information.street_lines'); $i++)
                        <x-shop::form.control-group.control
                            type="text"
                            name="address[{{ $i }}]"
                            :value="old('address[{{$i}}]', $addresses[$i] ?? '')"
                            rules="address"
                            :label="trans('shop::app.customers.account.addresses.edit.street-address')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.street-address')"
                        />

                        <x-shop::form.control-group.error
                            class="mb-2"
                            name="address[{{ $i }}]"
                        />
                    @endfor
                @endif

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.street-addres.after', ['address' => $addr]) !!}

                <!-- Country Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isCountryRequired() ? 'required' : '' }}">
                        @lang('shop::app.customers.account.addresses.edit.country')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="select"
                        name="country"
                        rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                        v-model="addressData.country"
                        :aria-label="trans('shop::app.customers.account.addresses.edit.country')"
                        :label="trans('shop::app.customers.account.addresses.edit.country')"
                    >
                        @foreach (core()->countries() as $country)
                            <option 
                                {{ $country->code === config('app.default_country') ? 'selected' : '' }}  
                                value="{{ $country->code }}"
                            >
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </x-shop::form.control-group.control>

                    <x-shop::form.control-group.error control-name="country" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.country.after', ['address' => $addr]) !!}

                <!-- State Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isStateRequired() ? 'required' : '' }}">
                        @lang('shop::app.customers.account.addresses.edit.state')
                    </x-shop::form.control-group.label>
                    <template v-if="haveStates()">
                        <x-shop::form.control-group.control
                            type="select"
                            name="state"
                            id="state"
                            rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                            v-model="addressData.state"
                            :label="trans('shop::app.customers.account.addresses.edit.state')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.state')"
                        >
                            <option 
                                v-for='(state, index) in countryStates[addressData.country]'
                                :value="state.code"
                            >
                                @{{ state.default_name }}
                            </option>
                        </x-shop::form.control-group.control>
                    </template>

                    <template v-else>
                        <x-shop::form.control-group.control
                            type="text"
                            name="state"
                            rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                            :value="old('state') ?? $addr->state"
                            :label="trans('shop::app.customers.account.addresses.edit.state')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.state')"
                        />
                    </template>

                    <x-shop::form.control-group.error control-name="state" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.state.after', ['address' => $addr]) !!}

                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.city')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="city"
                        rules="required"
                        :value="old('city') ?? $addr->city"
                        :label="trans('shop::app.customers.account.addresses.edit.city')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.city')"
                    />

                    <x-shop::form.control-group.error control-name="city" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.city.after', ['address' => $addr]) !!}

                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isPostCodeRequired() ? 'required' : '' }}">
                        @lang('shop::app.customers.account.addresses.edit.post-code')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="postcode"
                        rules="{{ core()->isPostCodeRequired() ? 'required' : '' }}|postcode"
                        :value="old('postal-code') ?? $addr->postcode"
                        :label="trans('shop::app.customers.account.addresses.edit.post-code')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.post-code')"
                    />

                    <x-shop::form.control-group.error control-name="postcode" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.postcode.after', ['address' => $addr]) !!}

                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.phone')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="phone"
                        rules="required|phone"
                        :value="old('phone') ?? $addr->phone"
                        :label="trans('shop::app.customers.account.addresses.edit.phone')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.phone')"
                    />

                    <x-shop::form.control-group.error control-name="phone" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.phone.after', ['address' => $addr]) !!}

                <button
                    type="submit"
                    class="primary-button m-0 block rounded-2xl px-11 py-3 text-center text-base max-md:w-full max-md:max-w-full max-md:rounded-lg max-md:py-1.5"
                >
                    @lang('shop::app.customers.account.addresses.edit.update-btn')
                </button>
                
                {!! view_render_event('bagisto.shop.customers.account.address.edit_form_controls.after', ['address' => $addr]) !!}

            </x-shop::form>
        </script>

        <script type="module">
            app.component('v-edit-customer-address', {
                template: '#v-edit-customer-address-template',

                data() {
                    return {
                        addressData: {
                            country: "{{ old('country') ?? $addr->country }}",

                            state: "{{ old('state') ?? $addr->state }}",
                        },

                        countryStates: @json(core()->groupedStatesByCountries()),
                    };
                },

                methods: {
                    haveStates() {
                        return !!this.countryStates[this.addressData.country]?.length;
                    },
                },
            });
        </script>
    @endpush

</x-shop::layouts.account>
