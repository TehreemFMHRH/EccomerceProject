<?php

namespace Webkul\Product\Helpers\Indexers;

use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Repositories\ProductInventoryIndexRepository;
use Webkul\Product\Models\Product;

class Inventory extends AbstractIndexer
{
    
    private $batchSize;

    
    protected $product;

    
    protected $channel;

    
    protected $channels;

    
    public function __construct(
        protected ChannelRepository $channelRepository,

        protected ProductInventoryIndexRepository $productInventoryIndexRepository
    ) {
        $this->batchSize = self::BATCH_SIZE;
    }

    
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    
    public function reindexFull()
    {
        while (true) {
            $paginator = Product
                ->with([
                    'inventories',
                    'ordered_inventories',
                    'inventory_indices',
                ])
                ->whereIn('type', ['simple', 'virtual'])
                ->cursorPaginate($this->batchSize);

            $this->reindexBatch($paginator->items());

            if (! $cursor = $paginator->nextCursor()) {
                break;
            }

            request()->query->add(['cursor' => $cursor->encode()]);
        }

        request()->query->remove('cursor');
    }

    
    public function reindexBatch($products)
    {
        $newIndices = [];

        foreach ($products as $product) {
            $this->setProduct($product);

            foreach ($this->getChannels() as $channel) {
                $this->setChannel($channel);

                $channelIndex = $product->inventory_indices
                    ->where('channel_id', $channel->id)
                    ->where('product_id', $product->id)
                    ->first();

                $newIndex = $this->getIndices();

                if ($channelIndex) {
                    $oldIndex = collect($channelIndex->toArray())
                        ->except('id', 'created_at', 'updated_at')
                        ->toArray();

                    $isIndexChanged = $this->isIndexChanged(
                        $oldIndex,
                        $newIndex
                    );

                    if ($isIndexChanged) {
                        $this->productInventoryIndexRepository->update($newIndex, $channelIndex->id);
                    }
                } else {
                    $newIndices[] = $newIndex;
                }
            }
        }

        $this->productInventoryIndexRepository->insert($newIndices);
    }

    
    public function isIndexChanged($oldIndex, $newIndex)
    {
        return (bool) count(array_diff_assoc($oldIndex, $newIndex));
    }

    
    public function getIndices()
    {
        return [
            'qty'        => $this->getQuantity(),
            'product_id' => $this->product->id,
            'channel_id' => $this->channel->id,
        ];
    }

    
    public function getQuantity()
    {
        $channelInventorySourceIds = $this->channel->inventory_sources->where('status', 1)->pluck('id');

        $qty = 0;

        foreach ($this->product->inventories as $inventory) {
            if (is_numeric($channelInventorySourceIds->search($inventory->inventory_source_id))) {
                $qty += $inventory->qty;
            }
        }

        $orderedInventory = $this->product->ordered_inventories
            ->where('channel_id', $this->channel->id)->first();

        if ($orderedInventory) {
            $qty -= $orderedInventory->qty;
        }

        return $qty;
    }

    
    public function getChannels()
    {
        if ($this->channels) {
            return $this->channels;
        }

        return $this->channels = $this->channelRepository->all();
    }
}
