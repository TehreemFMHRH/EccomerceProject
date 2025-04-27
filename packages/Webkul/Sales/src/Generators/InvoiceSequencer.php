<?php

namespace Webkul\Sales\Generators;

use Webkul\Sales\Models\Invoice;

class InvoiceSequencer extends Sequencer
{
    
    public function __construct()
    {
        $this->setAllConfigs();
    }

    
    public function setAllConfigs()
    {
        $this->prefix = core()->getConfigData('sales.invoice_settings.invoice_number.invoice_number_prefix');

        $this->length = core()->getConfigData('sales.invoice_settings.invoice_number.invoice_number_length');

        $this->suffix = core()->getConfigData('sales.invoice_settings.invoice_number.invoice_number_suffix');

        $this->generatorClass = core()->getConfigData('sales.invoice_settings.invoice_number.invoice_number_generator_class');

        $this->lastId = $this->getLastId();
    }

    
    public function getLastId()
    {
        $lastOrder = Invoice::query()->orderBy('id', 'desc')->limit(1)->first();

        return $lastOrder ? $lastOrder->id : 0;
    }
}
