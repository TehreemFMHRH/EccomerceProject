<?php

namespace Webkul\CatalogRule\Console\Commands;

use Illuminate\Console\Command;
use Webkul\CatalogRule\Helpers\CatalogRuleIndex;

class PriceRuleIndex extends Command
{
    
    protected $signature = 'product:price-rule:index';

    
    protected $de = 'Automatically updates catalog rule price index information (eg. rule_price)';

    
    public function __construct(protected CatalogRuleIndex $catalogRuleIndexHelper)
    {
        parent::__construct();
    }

    
    public function handle()
    {
        $this->catalogRuleIndexHelper->reIndexComplete();
    }
}
