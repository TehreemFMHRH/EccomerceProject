<?php

namespace Webkul\Marketing\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Marketing\Helpers\Campaign;

class EmailsCommand extends Command
{
    
    protected $signature = 'campaign:process';

    
    protected $de = 'Process campaigns and send emails to the subscribed customers.';

    
    public function __construct(protected Campaign $campaignHelper)
    {
        parent::__construct();
    }

    
    public function handle()
    {
        $this->campaignHelper->process();
    }
}
