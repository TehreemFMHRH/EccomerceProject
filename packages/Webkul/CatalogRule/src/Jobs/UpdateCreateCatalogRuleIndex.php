<?php

namespace Webkul\CatalogRule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\CatalogRule\Contracts\CatalogRule;
use Webkul\CatalogRule\Helpers\CatalogRuleIndex;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Models\Product;

class UpdateCreateCatalogRuleIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    protected const BATCH_SIZE = 100;

    
    public function __construct(protected CatalogRule $catalogRule) {}

    
    public function handle()
    {
        if ($this->catalogRule->status) {
            app(CatalogRuleIndex::class)->reIndexRule($this->catalogRule);

            
            $productIds = $this->catalogRule->catalog_rule_products->pluck('product_id')->unique();
        } else {
            $productIds = $this->catalogRule->catalog_rule_products->pluck('product_id')->unique();

            app(CatalogRuleIndex::class)->cleanProductIndices($productIds);
        }

        while (true) {
            $paginator = app(Product::class)
                ->whereIn('id', $productIds)
                ->cursorPaginate(self::BATCH_SIZE);

            
            app(PriceIndexer::class)->reindexBatch($paginator->items());

            if (! $cursor = $paginator->nextCursor()) {
                break;
            }

            request()->query->add(['cursor' => $cursor->encode()]);
        }
    }
}
