<?php

namespace Webkul\Sales\Traits;

trait PaymentTerm
{
    
    public function hasPaymentTerm()
    {
        return (bool) $this->getPaymentTerm();
    }

    
    public function getPaymentTerm()
    {
        return (int) core()->getConfigData('sales.invoice_settings.payment_terms.due_duration');
    }

    
    public function getFormattedPaymentTerm()
    {
        $dueDuration = $this->getPaymentTerm();

        if ($dueDuration > 1) {
            return __('admin::app.configuration.index.sales.invoice-settings.payment-terms.due-duration-days', ['due-duration' => $dueDuration]);
        }

        return $dueDuration
            ? __('admin::app.configuration.index.sales.invoice-settings.payment-terms.due-duration-day', ['due-duration' => $dueDuration])
            : __('admin::app.configuration.index.sales.invoice-settings.payment-terms.due-duration-day', ['due-duration' => 0]);
    }
}
