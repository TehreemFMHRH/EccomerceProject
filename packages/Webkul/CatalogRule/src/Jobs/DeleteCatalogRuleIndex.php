<?php

namespace Webkul\CatalogRule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Models\Product;

class DeleteCatalogRuleIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    protected const BATCH_SIZE = 100;

    
    public function __construct(protected $productIds)
    {
        $this->productIds = $productIds;
    }

    
    public function handle()
    {
        
        while (true) {
            $paginator = app(Product::class)
                ->whereIn('id', $this->productIds)
                ->cursorPaginate(self::BATCH_SIZE);

            
            app(PriceIndexer::class)->reindexBatch($paginator->items());

            if (! $cursor = $paginator->nextCursor()) {
                break;
            }

            request()->query->add(['cursor' => $cursor->encode()]);
        }
    }
}
