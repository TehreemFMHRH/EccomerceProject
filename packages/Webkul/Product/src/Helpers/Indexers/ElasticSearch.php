<?php

namespace Webkul\Product\Helpers\Indexers;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Facades\ElasticSearch as ElasticSearchClient;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Product\Models\Product;

class ElasticSearch extends AbstractIndexer
{
    
    private $batchSize;

    
    protected $attributes;

    
    protected $channels;

    
    protected $customerGroups;

    
    protected $product;

    
    protected $channel;

    
    protected $locale;

    
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected AttributeRepository $attributeRepository,

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

    
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    
    public function reindexFull()
    {
        while (true) {
            $paginator = Product
                ->select('products.*')
                ->with([
                    'channels',
                    'categories',
                    'inventories',
                    'super_attributes',
                    'variants',
                    'variants.channels',
                    'attribute_family',
                    'attribute_values',
                    'variants.attribute_family',
                    'variants.attribute_values',
                    'price_indices',
                    'variants.price_indices',
                    'inventory_indices',
                    'variants.inventory_indices',
                ])
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
        $refreshIndices = ['body' => []];

        $removeIndices = [];

        foreach ($products as $product) {
            $this->setProduct($product);

            foreach ($this->getChannels() as $channel) {
                $this->setChannel($channel);

                foreach ($channel->locales as $locale) {
                    $this->setLocale($locale);

                    $indexName = $this->getIndexName();

                    if (in_array($channel->id, $product->channels->pluck('id')->toArray())) {
                        $refreshIndices['body'][] = [
                            'index' => [
                                '_index' => $indexName,
                                '_id'    => $product->id,
                            ],
                        ];

                        $refreshIndices['body'][] = $this->getIndices();
                    } else {
                        $removeIndices[$indexName][] = $product->id;
                    }
                }
            }
        }

        if (! empty($refreshIndices['body'])) {
            ElasticsearchClient::bulk($refreshIndices);
        }

        if (! empty($removeIndices)) {
            $this->deleteIndices($removeIndices);
        }
    }

    
    public function deleteIndices($indices)
    {
        foreach ($indices as $indexName => $productIds) {
            foreach ($productIds as $i) {
                $params = [
                    'index' => $indexName,
                    'id'    => $i,
                ];

                try {
                    ElasticsearchClient::delete($params);
                } catch (ClientResponseException $e) {
                }
            }
        }
    }

    
    public function getIndexName()
    {
        return 'products_'.$this->channel->code.'_'.$this->locale->code.'_index';
    }

    
    public function getIndices()
    {
        $properties = array_merge([
            'id'                  => $this->product->id,
            'type'                => $this->product->type,
            'sku'                 => $this->product->sku,
            'attribute_family_id' => $this->product->attribute_family_id,
            'category_ids'        => $this->product->categories->pluck('id')->toArray(),
            'created_at'          => $this->product->created_at,
        ], $this->product->additional ?? []);

        $attributes = $this->getAttributes();

        foreach ($attributes as $attribute) {
            $attributeValue = $this->getAttributeValue($attribute);

            if ($attribute->code == 'price') {
                $properties[$attribute->code] = (float) $attributeValue?->{$attribute->column_name};

                foreach ($this->getCustomerGroups() as $customerGroup) {
                    if (! app()->runningInConsole()) {
                        $this->product->load('price_indices');
                    }

                    $priceIndex = $this->product->price_indices
                        ->where('channel_id', $this->channel->id)
                        ->where('customer_group_id', $customerGroup->id)
                        ->first();

                    if ($priceIndex) {
                        $groupPrice = $priceIndex?->min_price;
                    } else {
                        $groupPrice = $this->product->getTypeInstance()->getMinimalPrice();
                    }

                    $properties[$attribute->code.'_'.$customerGroup->id] = (float) $groupPrice;
                }
            } elseif ($attribute->type == 'boolean') {
                $properties[$attribute->code] = intval($attributeValue?->{$attribute->column_name});
            } else {
                $properties[$attribute->code] = strip_tags($attributeValue?->{$attribute->column_name});
            }
        }

        foreach ($this->product->super_attributes as $attribute) {
            foreach ($this->product->variants as $variant) {
                $properties['ca_'.$attribute->code][] = $variant->{$attribute->code};
            }
        }

        return $properties;
    }

    
    public function getAttributes()
    {
        if ($this->attributes) {
            return $this->attributes;
        }

        $this->attributes = $this->attributeRepository->scopeQuery(function ($query) {
            return $query->where(function ($qb) {
                return $qb->orWhereIn('code', [
                    'name',
                    'status',
                    'visible_individually',
                    'new',
                    'featured',
                    'url_key',
                    'short_description',
                    'description',
                ])
                    ->orWhere('is_filterable', 1);
            });
        })->get();

        return $this->attributes;
    }

    
    public function getAttributeValue($attribute)
    {
        $attributeValues = $this->product->attribute_values
            ->where('attribute_id', $attribute->id);

        if ($attribute->value_per_channel) {
            if ($attribute->value_per_locale) {
                $attributeValues = $attributeValues
                    ->where('channel', $this->channel->code)
                    ->where('locale', $this->locale->code);
            } else {
                $attributeValues = $attributeValues->where('channel', $this->channel->code);
            }
        } else {
            if ($attribute->value_per_locale) {
                $attributeValues = $attributeValues->where('locale', $this->locale->code);
            } else {
                $attributeValues = $attributeValues;
            }
        }

        return $attributeValues->first();
    }

    
    public function getChannels()
    {
        if ($this->channels) {
            return $this->channels;
        }

        return $this->channels = $this->channelRepository->all();
    }

    
    public function getCustomerGroups()
    {
        if ($this->customerGroups) {
            return $this->customerGroups;
        }

        return $this->customerGroups = $this->customerGroupRepository->all();
    }
}
