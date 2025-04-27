<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Shipping\Exceptions\CarrierCodeException;

abstract class AbstractShipping
{
    
    protected $code;

    
    protected $method;

    abstract public function calculate();

    
    public function isAvailable()
    {
        return $this->getConfigData('active');
    }

    
    public function getCode()
    {
        if (empty($this->code)) {
            throw new CarrierCodeException('Carrier code should be initialized.');
        }

        return $this->code;
    }

    
    public function getMethod()
    {
        if (empty($this->method)) {
            $code = $this->getCode();

            return $code.'_'.$code;
        }

        return $this->method;
    }

    
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    
    public function getDescription()
    {
        return $this->getConfigData('description');
    }

    
    public function getConfigData($field)
    {
        return core()->getConfigData('sales.carriers.'.$this->getCode().'.'.$field);
    }
}
