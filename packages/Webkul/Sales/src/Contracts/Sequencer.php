<?php

namespace Webkul\Sales\Contracts;

interface Sequencer
{
    
    public function generate(): string;
}
