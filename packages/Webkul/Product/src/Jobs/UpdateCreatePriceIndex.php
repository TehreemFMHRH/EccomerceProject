<?php

namespace Webkul\Product\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Models\Product;

class UpdateCreatePriceIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    public function __construct(protected $productIds)
    {
        $this->productIds = $productIds;
    }

    
    public function handle()
    {
        if (! count($this->productIds)) {
            return;
        }

        $ids = implode(',', $this->productIds);

        $products = app(Product::class)
            ->whereIn('id', $this->productIds)
            ->orderByRaw("FIELD(id, $ids)")
            ->get();

        app(PriceIndexer::class)->reindexRows($products);
    }
}
