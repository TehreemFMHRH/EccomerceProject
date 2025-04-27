<?php

namespace Webkul\Tax;

class Tax
{
    
    private const TAX_RATE_PRECISION = 4;

    
    private const TAX_AMOUNT_PRECISION = 2;

    
    public function isInclusiveTaxProductPrices(): bool
    {
        return core()->getConfigData('sales.taxes.calculation.product_prices') == 'including_tax';
    }

    
    public function isInclusiveTaxShippingPrices(): bool
    {
        return core()->getConfigData('sales.taxes.calculation.shipping_prices') == 'including_tax';
    }

    
    public function getTaxRatesWithAmount(object $that, bool $asBase = false): array
    {
        $taxes = [];

        foreach ($that->items as $item) {
            $taxRate = $item->applied_tax_rate.' ('.(string) round((float) $item->tax_percent, self::TAX_RATE_PRECISION).'%)';

            if (! array_key_exists($taxRate, $taxes)) {
                $taxes[$taxRate] = 0;
            }

            $taxes[$taxRate] += $asBase ? $item->base_tax_amount : $item->tax_amount;
        }

        if (
            $that->selected_shipping_rate
            && $that->selected_shipping_rate->tax_amount > 0
        ) {
            $taxRate = $that->selected_shipping_rate->applied_tax_rate.' ('.(string) round((float) $that->selected_shipping_rate->tax_percent, self::TAX_RATE_PRECISION).'%)';

            if (! array_key_exists($taxRate, $taxes)) {
                $taxes[$taxRate] = 0;
            }

            $taxes[$taxRate] += $asBase ? $that->selected_shipping_rate->base_tax_amount : $that->selected_shipping_rate->tax_amount;
        }

        
        foreach ($taxes as $taxRate => $taxAmount) {
            $taxes[$taxRate] = round($taxAmount, self::TAX_AMOUNT_PRECISION);
        }

        return $taxes;
    }

    
    public function getShippingOriginAddress(): object
    {
        return new class
        {
            public $country;

            public $state;

            public $postcode;

            public function __construct()
            {
                $this->country = core()->getConfigData('sales.shipping.origin.country') != ''
                    ? core()->getConfigData('sales.shipping.origin.country')
                    : strtoupper(config('app.default_country'));

                $this->state = core()->getConfigData('sales.shipping.origin.state');

                $this->postcode = core()->getConfigData('sales.shipping.origin.postcode');
            }
        };
    }

    
    public function getDefaultAddress(): object
    {
        return new class
        {
            public $country;

            public $state;

            public $postcode;

            public function __construct()
            {
                $this->country = core()->getConfigData('sales.taxes.default_destination_calculation.country') != ''
                    ? core()->getConfigData('sales.taxes.default_destination_calculation.country')
                    : strtoupper(config('app.default_country'));

                $this->state = core()->getConfigData('sales.taxes.default_destination_calculation.state');

                $this->postcode = core()->getConfigData('sales.taxes.default_destination_calculation.post_code');
            }
        };
    }

    
    public function isTaxApplicableInCurrentAddress($taxCategory, $addr, $operation): void
    {
        if (! $addr?->country) {
            return;
        }

        $taxRates = $taxCategory->tax_rates()->where([
            'country' => $addr->country,
        ])->orderBy('tax_rate', 'desc')->get();

        if (! $taxRates->count()) {
            return;
        }

        // dump($addr);
        foreach ($taxRates as $rate) {
            if (
                ! in_array(trim($rate->state), ['*', ''])
                && $rate->state != $addr->state
            ) {
                continue;
            }

            $haveTaxRate = false;

            if (! $rate->is_zip) {
                if (
                    empty($rate->zip_code)
                    || in_array($rate->zip_code, ['*', $addr->postcode])
                ) {
                    $haveTaxRate = true;
                }
            } else {
                if (
                    $addr->postcode >= $rate->zip_from
                    && $addr->postcode <= $rate->zip_to
                ) {
                    $haveTaxRate = true;
                }
            }

            if ($haveTaxRate) {
                $operation($rate);

                break;
            }
        }
    }
}
