<?php

namespace Webkul\Product\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Shetabit\Visitor\Traits\Visitable;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Repositories\ElasticSearchRepository;
use Webkul\BookingProduct\Models\BookingProductProxy;
use Webkul\CatalogRule\Models\CatalogRuleProductPriceProxy;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Inventory\Models\InventorySourceProxy;
use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Product\Database\Factories\ProductFactory;
use Webkul\Product\Type\AbstractType;
use Illuminate\Support\Facades\DB;
class Product extends Model implements ProductContract
{
    use HasFactory, Visitable;


    protected $fillable = [
        'type',
        'attribute_family_id',
        'sku',
        'parent_id',
    ];


    protected $casts = [
        'additional' => 'array',
    ];


    protected $typeInstance;


    public function product_flats(): HasMany
    {
        return $this->hasMany(ProductFlatProxy::modelClass(), 'product_id');
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }


    public function attribute_family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamilyProxy::modelClass());
    }


    public function super_attributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeProxy::modelClass(), 'product_super_attributes');
    }


    public function attribute_values(): HasMany
    {
        return $this->hasMany(ProductAttributeValueProxy::modelClass());
    }


    public function customer_group_prices(): HasMany
    {
        return $this->hasMany(ProductCustomerGroupPriceProxy::modelClass());
    }


    public function catalog_rule_prices(): HasMany
    {
        return $this->hasMany(CatalogRuleProductPriceProxy::modelClass());
    }


    public function price_indices(): HasMany
    {
        return $this->hasMany(ProductPriceIndexProxy::modelClass());
    }


    public function inventory_indices(): HasMany
    {
        return $this->hasMany(ProductInventoryIndexProxy::modelClass());
    }


    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CategoryProxy::modelClass(), 'product_categories');
    }


    public function images(): HasMany
    {
        return $this->hasMany(ProductImageProxy::modelClass(), 'product_id')
            ->orderBy('position');
    }


    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideoProxy::modelClass(), 'product_id')
            ->orderBy('position');
    }


    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReviewProxy::modelClass());
    }


    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }


    public function inventory_sources(): BelongsToMany
    {
        return $this->belongsToMany(InventorySourceProxy::modelClass(), 'product_inventories')
            ->withPivot('id', 'qty');
    }


    public function inventory_source_qty($inventorySourceId)
    {
        return $this->inventories()
            ->where('inventory_source_id', $inventorySourceId)
            ->sum('qty');
    }


    public function inventories(): HasMany
    {
        return $this->hasMany(ProductInventoryProxy::modelClass(), 'product_id');
    }


    public function ordered_inventories(): HasMany
    {
        return $this->hasMany(ProductOrderedInventoryProxy::modelClass(), 'product_id');
    }


    public function customizable_options(): HasMany
    {
        return $this->hasMany(ProductCustomizableOptionProxy::modelClass())
            ->orderBy('sort_order');
    }


    public function variants(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }


    public function grouped_products(): HasMany
    {
        return $this->hasMany(ProductGroupedProductProxy::modelClass());
    }


    public function booking_products(): HasMany
    {
        return $this->hasMany(BookingProductProxy::modelClass());
    }


    public function downloadable_samples(): HasMany
    {
        return $this->hasMany(ProductDownloadableSampleProxy::modelClass());
    }


    public function downloadable_links(): HasMany
    {
        return $this->hasMany(ProductDownloadableLinkProxy::modelClass());
    }


    public function bundle_options(): HasMany
    {
        return $this->hasMany(ProductBundleOptionProxy::modelClass());
    }


    public function related_products(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'product_relations', 'parent_id', 'child_id');
    }


    public function up_sells(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'product_up_sells', 'parent_id', 'child_id');
    }


    public function cross_sells(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'product_cross_sells', 'parent_id', 'child_id');
    }


    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(ChannelProxy::modelClass(), 'product_channels', 'product_id', 'channel_id');
    }


    public function isSaleable(): bool
    {
        return $this->getTypeInstance()
            ->isSaleable();
    }


    public function isStockable(): bool
    {
        return $this->getTypeInstance()
            ->isStockable();
    }


    public function totalQuantity(): int
    {
        return $this->getTypeInstance()
            ->totalQuantity();
    }


    public function haveSufficientQuantity(int $qty): bool
    {
        return $this->getTypeInstance()
            ->haveSufficientQuantity($qty);
    }


    public function getTypeInstance(): AbstractType
    {
        if ($this->typeInstance) {
            return $this->typeInstance;
        }

        $this->typeInstance = app(config('product_types.'.$this->type.'.class'));

        if (! $this->typeInstance instanceof AbstractType) {
            throw new Exception("Please ensure the product type '{$this->type}' is configured in your application.");
        }

        $this->typeInstance->setProduct($this);

        return $this->typeInstance;
    }


    public function getBaseImageUrlAttribute()
    {
        $image = $this->images->first();

        return $image->url ?? null;
    }


    public function getAttribute($key)
    {
        if (! method_exists(static::class, $key)
            && ! in_array($key, [
                'pivot',
                'parent_id',
                'attribute_family_id',
            ])
            && ! isset($this->attributes[$key])
        ) {
            if (isset($this->id)) {
                $attribute = $this->checkInLoadedFamilyAttributes()->where('code', $key)->first();

                $this->attributes[$key] = $this->getCustomAttributeValue($attribute);

                return $this->getAttributeValue($key);
            }
        }

        return parent::getAttribute($key);
    }


    public function getEditableAttributes($group = null, $skipSuperAttribute = true): Collection
    {
        return $this->getTypeInstance()
            ->getEditableAttributes($group, $skipSuperAttribute);
    }


    public function getCustomAttributeValue($attribute)
    {
        if (! $attribute) {
            return;
        }

        $locale = core()->getRequestedLocaleCodeInRequestedChannel();

        $channel = core()->getRequestedChannelCode();

        if (empty($this->attribute_values->count())) {
            $this->load('attribute_values');
        }

        if ($attribute->value_per_channel) {
            if ($attribute->value_per_locale) {
                $attributeValue = $this->attribute_values
                    ->where('channel', $channel)
                    ->where('locale', $locale)
                    ->where('attribute_id', $attribute->id)
                    ->first();

                if (empty($attributeValue[$attribute->column_name])) {
                    $attributeValue = $this->attribute_values
                        ->where('channel', core()->getDefaultChannelCode())
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel())
                        ->where('attribute_id', $attribute->id)
                        ->first();
                }
            } else {
                $attributeValue = $this->attribute_values
                    ->where('channel', $channel)
                    ->where('attribute_id', $attribute->id)
                    ->first();
            }
        } else {
            if ($attribute->value_per_locale) {
                $attributeValue = $this->attribute_values
                    ->where('locale', $locale)
                    ->where('attribute_id', $attribute->id)
                    ->first();

                if (empty($attributeValue[$attribute->column_name])) {
                    $attributeValue = $this->attribute_values
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel())
                        ->where('attribute_id', $attribute->id)
                        ->first();
                }
            } else {
                $attributeValue = $this->attribute_values
                    ->where('attribute_id', $attribute->id)
                    ->first();
            }
        }

        return $attributeValue[$attribute->column_name] ?? $attribute->default_value;
    }


    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        $hiddenAttributes = $this->getHidden();

        if (isset($this->id)) {
            $familyAttributes = $this->checkInLoadedFamilyAttributes();

            foreach ($familyAttributes as $attribute) {
                if (in_array($attribute->code, $hiddenAttributes)) {
                    continue;
                }

                $attributes[$attribute->code] = $this->getCustomAttributeValue($attribute);
            }
        }

        return $attributes;
    }


    public function checkInLoadedFamilyAttributes(): object
    {
        return core()->getSingletonInstance(AttributeRepository::class)
            ->getFamilyAttributes($this->attribute_family);
    }


    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }


    public function setSearchEngine(string $searchEngine): self
    {
        $this->searchEngine = $searchEngine;

        return $this;
    }


    public function findBySlug(string $slug): ?Product
    {
        if ($this->searchEngine == 'elastic') {
            $indices = $this->elasticSearchRepository()->search([
                'url_key' => $slug,
            ], [
                'type'  => '',
                'from'  => 0,
                'limit' => 1,
                'sort'  => 'id',
                'order' => 'desc',
            ]);

            return $this->find(current($indices['ids']));
        }
        return $this->findByAttributeCode('url_key', $slug);
    }

    protected ?AttributeRepository $attributeRepository = null;
    protected ?CustomerRepository $kRepository = null;
    protected ?ElasticSearchRepository $elasticSearchRepository = null;

    protected function getAttributeRepository(): AttributeRepository
    {
        if (! $this->attributeRepository) {
            $this->attributeRepository = app(AttributeRepository::class);
        }

        return $this->attributeRepository;
    }

    protected function getElasticSearchRepository(): ElasticSearchRepository
    {
        if (! $this->elasticSearchRepository) {
            $this->elasticSearchRepository = app(ElasticSearchRepository::class);
        }

        return $this->elasticSearchRepository;
    }

    protected function getCustomerRepository(): CustomerRepository
    {
        if (! $this->customerRepository) {
            $this->customerRepository = app(CustomerRepository::class);
        }

        return $this->customerRepository;
    }



    public static function findByAttributeCode($code, $va)
    {
        $attribute = \Webkul\Attribute\Models\Attribute::where('code', $code)->firstOrFail();

        $query = \Webkul\Product\Models\ProductAttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->where($attribute->column_name, $va);

        if ($attribute->value_per_channel) {
            $query->where('channel', core()->getRequestedChannelCode());
        }

        if ($attribute->value_per_locale) {
            $query->where('locale', core()->getRequestedLocaleCode());
        }

        $attributeValue = $query->first();


        if (!$attributeValue && $attribute->value_per_locale) {
            $query = \Webkul\Product\Models\ProductAttributeValue::query()
                ->where('attribute_id', $attribute->id)
                ->where($attribute->column_name, $va)
                ->where('channel', core()->getRequestedChannelCode())
                ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel());

            $attributeValue = $query->first();
        }

        return $attributeValue?->product;
    }


    public function getAll(array $params = [])
    {
        if ($this->searchEngine == 'elastic') {
            return $this->searchFromElastic($params);
        }

        return $this->searchFromDatabase($params);
    }


    public function searchFromDatabase(array $params = [])
    {
        $params['url_key'] ??= null;

        if (!empty($params['query'])) {
            $params['name'] = $params['query'];
        }


        $query = $this->buildQuery($params);


        return $query->get();
    }


    protected function buildQuery(array $params)
    {
        $query = $this->with([
            'attribute_family',
            'images',
            'videos',
            'attribute_values',
            'price_indices',
            'inventory_indices',
            'reviews',
            'variants',
            'variants.attribute_family',
            'variants.attribute_values',
            'variants.price_indices',
            'variants.inventory_indices',
        ]);

        $prefix = DB::getTablePrefix();

        $qb = $query->distinct()
            ->select('products.*')
            ->leftJoin('products as variants', DB::raw('COALESCE('.$prefix.'variants.parent_id, '.$prefix.'variants.id)'), '=', 'products.id')
            ->leftJoin('product_price_indices', function ($join) {
                $kGroup = $this->getCustomerRepository()->getCurrentGroup();
                $join->on('products.id', '=', 'product_price_indices.product_id')
                    ->where('product_price_indices.customer_group_id', $kGroup->id);
            });

        $this->applyCategoryFilter($qb, $params);
        $this->applyChannelFilter($qb, $params);
        $this->applyProductTypeFilter($qb, $params);
        $this->applyPriceFilter($qb, $params);
        $this->applyAttributeFilters($qb, $params);


        $this->applySorting($qb, $params);

        return $qb->groupBy('products.id');
    }


    protected function applyCategoryFilter($qb, $params)
    {
        if (!empty($params['category_id'])) {
            $qb->leftJoin('product_categories', 'product_categories.product_id', '=', 'products.id')
                ->whereIn('product_categories.category_id', explode(',', $params['category_id']));
        }
    }


    protected function applyChannelFilter($qb, $params)
    {
        if (!empty($params['channel_id'])) {
            $qb->leftJoin('product_channels', 'products.id', '=', 'product_channels.product_id')
                ->where('product_channels.channel_id', explode(',', $params['channel_id']));
        }
    }


    protected function applyProductTypeFilter($qb, $params)
    {
        if (!empty($params['type'])) {
            $qb->where('products.type', $params['type']);

            if ($params['type'] === 'simple' && !empty($params['exclude_customizable_products'])) {
                $qb->leftJoin('product_customizable_options', 'products.id', '=', 'product_customizable_options.product_id')
                    ->whereNull('product_customizable_options.id');
            }
        }
    }


    protected function applyPriceFilter($qb, $params)
    {
        if (!empty($params['price'])) {
            $priceRange = explode(',', $params['price']);
            $qb->whereBetween('product_price_indices.min_price', [
                core()->convertToBasePrice(current($priceRange)),
                core()->convertToBasePrice(end($priceRange)),
            ]);
        }
    }


    protected function applyAttributeFilters($qb, $params)
    {
        $filterableAttributes = $this->getAttributeRepository()->getProductDefaultAttributes(array_keys($params));


        $attributes = $filterableAttributes->whereIn('code', [
            'name',
            'status',
            'visible_individually',
            'url_key',
        ]);

        foreach ($attributes as $attribute) {
            $alias = $attribute->code . '_product_attribute_values';
            $qb->leftJoin('product_attribute_values as ' . $alias, 'products.id', '=', $alias . '.product_id')
                ->where($alias . '.attribute_id', $attribute->id);

            if ($attribute->code == 'name') {
                $synonyms = $this->searchSynonymRepository->getSynonymsByQuery(urldecode($params['name']));
                $qb->where(function ($subQuery) use ($alias, $synonyms) {
                    foreach ($synonyms as $synonym) {
                        $subQuery->orWhere($alias . '.text_value', 'like', '%' . $synonym . '%');
                    }
                });
            } elseif ($attribute->code == 'url_key') {
                $this->applyUrlKeyFilter($qb, $alias, $params);
            } else {
                if (is_null($params[$attribute->code])) {
                    continue;
                }

                $qb->where($alias . '.' . $attribute->column_name, 1);
            }
        }


        $this->applyOtherAttributeFilters($qb, $params, $filterableAttributes);
    }


    protected function applyUrlKeyFilter($qb, $alias, $params)
    {
        if (empty($params['url_key'])) {
            $qb->whereNotNull($alias . '.text_value');
        } else {
            $qb->where($alias . '.text_value', 'like', '%' . urldecode($params['url_key']) . '%');
        }
    }


    protected function applyOtherAttributeFilters($qb, $params, $filterableAttributes)
    {
        $attributes = $filterableAttributes->whereNotIn('code', [
            'price',
            'name',
            'status',
            'visible_individually',
            'url_key',
        ]);

        if ($attributes->isNotEmpty()) {
            $qb->where(function ($filterQuery) use ($qb, $params, $attributes) {
                $aliases = [
                    'products' => 'product_attribute_values',
                    'variants' => 'variant_attribute_values',
                ];

                foreach ($aliases as $table => $tableAlias) {
                    $filterQuery->orWhere(function ($subFilterQuery) use ($qb, $params, $attributes, $table, $tableAlias) {
                        foreach ($attributes as $attribute) {
                            $alias = $attribute->code . '_' . $tableAlias;

                            $qb->leftJoin('product_attribute_values as ' . $alias, function ($join) use ($table, $alias, $attribute) {
                                $join->on($table . '.id', '=', $alias . '.product_id')
                                    ->where($alias . '.attribute_id', $attribute->id);
                            });

                            $subFilterQuery->whereIn($alias . '.' . $attribute->column_name, explode(',', $params[$attribute->code]));
                        }
                    });
                }
            });

            $qb->groupBy('products.id');
        }
    }


    protected function applySorting($qb, $params)
    {
        $sortOptions = $this->getSortOptions($params);

        if ($sortOptions['order'] != 'rand') {
            $attribute = $this->getAttributeRepository()->findOneByField('code', $sortOptions['sort']);

            if ($attribute) {
                if ($attribute->code === 'price') {
                    $qb->orderBy('product_price_indices.min_price', $sortOptions['order']);
                } else {
                    $alias = 'sort_product_attribute_values';
                    $qb->leftJoin('product_attribute_values as ' . $alias, function ($join) use ($alias, $attribute) {
                        $join->on('products.id', '=', $alias . '.product_id')
                            ->where($alias . '.attribute_id', $attribute->id);
                    })
                    ->orderBy($alias . '.' . $attribute->column_name, $sortOptions['order']);
                }
            } else {
                $qb->orderBy('products.created_at', $sortOptions['order']);
            }
        } else {
            $qb->inRandomOrder();
        }
    }


    public function getSortOptions(array $params): array
    {
        return product_toolbar()->getOrder($params);
    }

}
