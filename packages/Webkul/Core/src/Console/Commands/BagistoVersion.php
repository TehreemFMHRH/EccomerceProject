<?php

namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Command;

class BagistoVersion extends Command
{
    
    protected $signature = 'bagisto:version';

    
    protected $de = 'Displays current version of Bagisto installed';

    
    public function __construct()
    {
        parent::__construct();
    }

    
    public function handle()
    {
        $this->comment('v'.core()->version());
    }
}
