<?php

namespace Webkul\Product\Helpers\Indexers;

abstract class AbstractIndexer
{
    
    protected const BATCH_SIZE = 100;

    
    protected const SPECIAL_PRICE_FROM_ATTRIBUTE_ID = 14;

    
    protected const SPECIAL_PRICE_TO_ATTRIBUTE_ID = 15;

    abstract public function reindexBatch(array $products);

    
    public function reindexFull() {}

    
    public function reindexSelective()
    {
        return $this->reindexFull();
    }

    
    public function reindexRows($products)
    {
        $currentBatch = [];

        $i = 0;

        foreach ($products as $product) {
            $currentBatch[] = $product;

            if (++$i === self::BATCH_SIZE) {
                $this->reindexBatch($currentBatch);

                $i = 0;

                $currentBatch = [];
            }
        }

        if (! empty($currentBatch)) {
            $this->reindexBatch($currentBatch);
        }
    }

    
    public function reindexRow($product)
    {
        $this->reindexBatch([$product]);
    }
}
