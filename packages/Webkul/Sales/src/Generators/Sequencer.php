<?php

namespace Webkul\Sales\Generators;

use Webkul\Sales\Contracts\Sequencer as SequencerContract;

class Sequencer implements SequencerContract
{
    
    public $length;

    
    public $prefix;

    
    public $suffix;

    
    public $generatorClass;

    
    public $lastId = 0;

    
    public function setLength($configKey)
    {
        $this->length = core()->getConfigData($configKey);
    }

    
    public function setPrefix($configKey)
    {
        $this->prefix = core()->getConfigData($configKey);
    }

    
    public function setSuffix($configKey)
    {
        $this->suffix = core()->getConfigData($configKey);
    }

    
    public function setGeneratorClass($configKey)
    {
        $this->generatorClass = core()->getConfigData($configKey);
    }

    
    public function resolveGeneratorClass()
    {
        if (
            $this->generatorClass !== ''
            && class_exists($this->generatorClass)
            && in_array(SequencerContract::class, class_implements($this->generatorClass), true)
        ) {
            return app($this->generatorClass)->generate();
        }

        return $this->generate();
    }

    
    public function generate(): string
    {
        return $this->prefix.sprintf(
            "%0{$this->length}d",
            ($this->lastId + 1)
        ).($this->suffix);
    }
}
