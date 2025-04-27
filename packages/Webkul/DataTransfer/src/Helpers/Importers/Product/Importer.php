<?php

namespace Webkul\DataTransfer\Helpers\Importers\Product;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Rules\Decimal;
use Webkul\Core\Rules\Slug;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\DataTransfer\Contracts\ImportBatch as ImportBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\ImportBatchRepository;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\Product\Jobs\ElasticSearch\DeleteIndex as DeleteIndexJob;
use Webkul\Product\Jobs\ElasticSearch\UpdateCreateIndex as UpdateCreateElasticSearchIndexJob;
use Webkul\Product\Jobs\UpdateCreateInventoryIndex as UpdateCreateInventoryIndexJob;
use Webkul\Product\Jobs\UpdateCreatePriceIndex as UpdateCreatePriceIndexJob;
use Webkul\Product\Models\Product as ProductModel;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Models\ProductBundleOptionProduct;
use Webkul\Product\Repositories\ProductBundleOptionRepository;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\Product\Repositories\ProductFlatRepository;
use Webkul\Product\Models\ProductGroupedProduct;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Models\Product;

class Importer extends AbstractImporter
{
    
    const PRODUCT_TYPE_SIMPLE = 'simple';

    
    const PRODUCT_TYPE_VIRTUAL = 'virtual';

    
    const PRODUCT_TYPE_DOWNLOADABLE = 'downloadable';

    
    const PRODUCT_TYPE_CONFIGURABLE = 'configurable';

    
    const PRODUCT_TYPE_BUNDLE = 'bundle';

    
    const PRODUCT_TYPE_GROUPED = 'grouped';

    
    const ERROR_INVALID_TYPE = 'invalid_product_type';

    
    const ERROR_SKU_NOT_FOUND_FOR_DELETE = 'sku_not_found_to_delete';

    
    const ERROR_DUPLICATE_URL_KEY = 'duplicated_url_key';

    
    const ERROR_INVALID_ATTRIBUTE_FAMILY_CODE = 'attribute_family_code_not_found';

    
    const ERROR_SUPER_ATTRIBUTE_CODE_NOT_FOUND = 'attribute_family_code_not_found';

    
    protected array $messages = [
        self::ERROR_INVALID_TYPE                   => 'data_transfer::app.importers.products.validation.errors.invalid-type',
        self::ERROR_SKU_NOT_FOUND_FOR_DELETE       => 'data_transfer::app.importers.products.validation.errors.sku-not-found',
        self::ERROR_DUPLICATE_URL_KEY              => 'data_transfer::app.importers.products.validation.errors.duplicate-url-key',
        self::ERROR_INVALID_ATTRIBUTE_FAMILY_CODE  => 'data_transfer::app.importers.products.validation.errors.invalid-attribute-family',
        self::ERROR_SUPER_ATTRIBUTE_CODE_NOT_FOUND => 'data_transfer::app.importers.products.validation.errors.super-attribute-not-found',
    ];

    
    protected array $permanentAttributes = ['sku'];

    
    protected string $masterAttributeCode = 'sku';

    
    protected mixed $attributeFamilies = [];

    
    protected mixed $attributes = [];

    
    protected array $typeFamilyAttributes = [];

    
    protected array $typeFamilyValidationRules = [];

    
    protected array $categories = [];

    
    protected Collection $channels;

    
    protected mixed $customerGroups = [];

    
    protected array $urlKeys = [];

    
    protected array $productFlatColumns = [];

    
    protected bool $linkingRequired = true;

    
    protected bool $indexingRequired = true;

    
    protected array $validColumnNames = [
        'locale',
        'type',
        'attribute_family_code',
        'parent_sku',
        'categories',
        'images',
        'customer_group_prices',
        'tax_category_name',
        'inventories',
        'related_skus',
        'cross_sell_skus',
        'up_sell_skus',
        'configurable_variants',
        'bundle_options',
        'associated_skus',
    ];

    
    public function __construct(
        protected ImportBatchRepository $importBatchRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected CategoryRepository $categoryRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected ChannelRepository $channelRepository,
        protected InventorySourceRepository $inventorySourceRepository,

        protected ProductFlatRepository $productFlatRepository,
        protected ProductAttributeValueRepository $productAttributeValueRepository,
        protected ProductImageRepository $productImageRepository,
        protected ProductInventoryRepository $productInventoryRepository,
        protected ProductBundleOptionRepository $productBundleOptionRepository,

        protected ProductCustomerGroupPriceRepository $productCustomerGroupPriceRepository,

        protected SKUStorage $skuStorage
    ) {
        parent::__construct($importBatchRepository);

        $this->initAttributes();
    }

    
    protected function initAttributes(): void
    {
        $this->attributeFamilies = $this->attributeFamilyRepository->all();

        $this->attributes = $this->attributeRepository->all();

        foreach ($this->attributes as $key => $attribute) {
            $this->validColumnNames[] = $attribute->code;
        }
    }

    
    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    
    protected function saveValidatedBatches(): self
    {
        $source = $this->getSource();

        $source->rewind();

        $this->skuStorage->init();

        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $source->next();

                continue;
            }

            $this->validateRow($rowData, $source->getCurrentRowNumber());

            $source->next();
        }

        $this->checkForDuplicateUrlKeys();

        parent::saveValidatedBatches();

        return $this;
    }

    
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        
        if ($this->import->action == Import::ACTION_DELETE) {
            if (! $this->isSKUExist($rowData['sku'])) {
                $this->skipRow($rowNumber, self::ERROR_SKU_NOT_FOUND_FOR_DELETE);

                return false;
            }

            return true;
        }

        
        if (
            $rowData['type'] == self::PRODUCT_TYPE_DOWNLOADABLE
            || ! config('product_types.'.$rowData['type'])
        ) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_TYPE, 'type');

            return false;
        }

        
        if (! $this->attributeFamilies->where('code', $rowData['attribute_family_code'])->first()) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_ATTRIBUTE_FAMILY_CODE, 'attribute_family_code');

            return false;
        }

        if (! isset($this->typeFamilyValidationRules[$rowData['type']][$rowData['attribute_family_code']])) {
            $this->typeFamilyValidationRules[$rowData['type']][$rowData['attribute_family_code']] = $this->getValidationRules($rowData);
        }

        
        $validator = Validator::make($rowData, $this->typeFamilyValidationRules[$rowData['type']][$rowData['attribute_family_code']]);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
            }
        }

        
        if (
            empty($this->urlKeys[$rowData['url_key']])
            || ($this->urlKeys[$rowData['url_key']]['sku'] == $rowData['sku'])
        ) {
            $this->urlKeys[$rowData['url_key']] = [
                'sku'        => $rowData['sku'],
                'row_number' => $rowNumber,
            ];
        } else {
            $message = sprintf(
                trans($this->messages[self::ERROR_DUPLICATE_URL_KEY]),
                'url_key',
                $this->urlKeys[$rowData['url_key']]['sku']
            );

            $this->skipRow($rowNumber, self::ERROR_DUPLICATE_URL_KEY, 'url_key', $message);
        }

        
        $optionsData = [];

        $validationRules = [];

        if ($rowData['type'] == self::PRODUCT_TYPE_BUNDLE) {
            $validationRules = [
                'bundle_options.*.name'     => 'sometimes|required',
                'bundle_options.*.type'     => 'sometimes|required|in:select,radio,checkbox,multiselect',
                'bundle_options.*.required' => 'sometimes|required|boolean',
                'bundle_options.*.sku'      => 'sometimes|required',
                'bundle_options.*.price'    => ['sometimes', 'required', new Decimal],
                'bundle_options.*.qty'      => 'sometimes|required|integer',
                'bundle_options.*.default'  => 'sometimes|required|boolean',
            ];

            $options = explode('|', $rowData['bundle_options'] ?? '');

            foreach ($options as $option) {
                parse_str(str_replace(',', '&', $option), $attributes);

                $optionsData['bundle_options'][] = $attributes;
            }
        } elseif ($rowData['type'] == self::PRODUCT_TYPE_GROUPED) {
            $validationRules = [
                'associated_skus.*.sku' => 'sometimes|required',
                'associated_skus.*.qty' => 'sometimes|required|integer',
            ];

            $associatedSkus = explode(',', $rowData['associated_skus'] ?? '');

            foreach ($associatedSkus as $row) {
                [$sku, $qty] = explode('=', $row);

                $optionsData['associated_skus'][] = [
                    'sku' => $sku ?? '',
                    'qty' => $qty ?? null,
                ];
            }
        } elseif ($rowData['type'] == self::PRODUCT_TYPE_CONFIGURABLE) {
            $validationRules = [
                'configurable_variants.*.sku' => 'sometimes|required',
            ];

            $options = explode('|', $rowData['configurable_variants'] ?? '');

            foreach ($options as $option) {
                parse_str(str_replace(',', '&', $option), $attributes);

                $optionsData['configurable_variants'][] = $attributes;
            }
        } else {
            
            $validationRules = [
                'customer_group_prices.*.group' => 'sometimes|required',
                'customer_group_prices.*.qty'   => 'sometimes|required|integer',
                'customer_group_prices.*.type'  => 'sometimes|required|in:fixed,discount',
                'customer_group_prices.*.price' => ['sometimes', 'required', new Decimal],
            ];

            $customerGroupPrices = explode('|', $rowData['customer_group_prices'] ?? '');

            foreach ($customerGroupPrices as $customerGroupPrice) {
                parse_str(str_replace(',', '&', $customerGroupPrice), $attributes);

                $optionsData['customer_group_prices'][] = $attributes;
            }
        }

        if (! empty($optionsData)) {
            $validator = Validator::make($optionsData, $validationRules);

            if ($validator->fails()) {
                foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                    $failedAttributes = $validator->failed();

                    $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                    $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
                }
            }
        }

        
        if ($rowData['type'] == self::PRODUCT_TYPE_CONFIGURABLE) {
            $variants = explode('|', $rowData['configurable_variants'] ?? '');

            $familyAttributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData['attribute_family_code']);

            foreach ($variants as $variant) {
                parse_str(str_replace(',', '&', $variant), $variantAttributes);

                $configurableVariants = Arr::except($variantAttributes, 'sku');

                foreach ($configurableVariants as $superAttribute => $optionLabel) {
                    if (! $familyAttributes->where('code', $superAttribute)->first()) {
                        $this->skipRow(
                            $rowNumber,
                            self::ERROR_SUPER_ATTRIBUTE_CODE_NOT_FOUND,
                            'configurable_variants',
                            sprintf(
                                trans($this->messages[self::ERROR_SUPER_ATTRIBUTE_CODE_NOT_FOUND]),
                                $superAttribute,
                                $rowData['attribute_family_code']
                            )
                        );
                    }
                }
            }
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    
    public function getValidationRules(array $rowData): array
    {
        $rules = [
            'sku'                => ['required', new Slug],
            'url_key'            => ['required'],
            'special_price_from' => ['nullable', 'date'],
            'special_price_to'   => ['nullable', 'date', 'after_or_equal:special_price_from'],
            'special_price'      => ['nullable', new Decimal, 'lt:price'],
        ];

        $attributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData['attribute_family_code']);

        foreach ($attributes as $attribute) {
            if (in_array($attribute->code, ['sku', 'url_key'])) {
                continue;
            }

            $validations = [];

            if (! isset($rules[$attribute->code])) {
                $validations[] = $attribute->is_required ? 'required' : 'nullable';
            } else {
                $validations = $rules[$attribute->code];
            }

            if (
                $attribute->type == 'text'
                && $attribute->validation
            ) {
                if ($attribute->validation === 'decimal') {
                    $validations[] = new Decimal;
                } elseif ($attribute->validation === 'regex') {
                    $validations[] = 'regex:'.$attribute->regex;
                } else {
                    $validations[] = $attribute->validation;
                }
            }

            if ($attribute->type == 'price') {
                $validations[] = new Decimal;
            }

            if ($attribute->is_unique) {
                array_push($validations, function ($field, $va, $fail) use ($attribute, $rowData) {
                    $product = $this->skuStorage->get($rowData['sku']);

                    $count = $this->productAttributeValueRepository
                        ->where($attribute->column_name, $rowData[$attribute->code])
                        ->where('attribute_id', '=', $attribute->id)
                        ->where('product_attribute_values.product_id', '!=', $product['id'])
                        ->count('product_attribute_values.id');

                    if ($count) {
                        $fail(__('admin::app.catalog.products.index.already-taken', ['name' => ':attribute']));
                    }
                });
            }

            $rules[$attribute->code] = $validations;
        }

        return $rules;
    }

    
    protected function checkForDuplicateUrlKeys(): void
    {
        if (empty($this->urlKeys)) {
            return;
        }

        $products = Product::resetScope()
            ->select('products.id', 'product_attribute_values.text_value as url_key', 'products.sku')
            ->leftJoin('product_attribute_values', 'products.id', 'product_attribute_values.product_id')
            ->leftJoin('attributes', 'product_attribute_values.attribute_id', 'attributes.id')
            ->where('attributes.code', 'url_key')
            ->where('product_attribute_values.text_value', array_keys($this->urlKeys))
            ->whereNotIn('products.sku', Arr::pluck($this->urlKeys, 'sku'))
            ->get();

        foreach ($products as $product) {
            $this->skipRow(
                $this->urlKeys[$product->url_key]['row_number'],
                self::ERROR_DUPLICATE_URL_KEY,
                'url_key',
                sprintf(
                    trans($this->messages[self::ERROR_DUPLICATE_URL_KEY]),
                    $product->url_key,
                    $product->sku
                )
            );
        }
    }

    
    public function importBatch(ImportBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->import->action == Import::ACTION_DELETE) {
            $this->deleteProducts($batch);
        } else {
            $this->saveProductsData($batch);
        }

        
        $batch = $this->importBatchRepository->update([
            'state' => Import::STATE_PROCESSED,

            'summary'      => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    
    public function linkBatch(ImportBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.linking.before', $batch);

        
        $this->skuStorage->load(Arr::pluck($batch->data, 'sku'));

        $configurableVariants = [];

        $groupAssociations = [];

        $bundleOptions = [];

        $links = [];

        foreach ($batch->data as $rowData) {
            
            $this->prepareConfigurableVariants($rowData, $configurableVariants);

            
            $this->prepareGroupAssociations($rowData, $groupAssociations);

            
            $this->prepareBundleOptions($rowData, $bundleOptions);

            
            $this->prepareLinks($rowData, $links);
        }

        $this->saveConfigurableVariants($configurableVariants);

        $this->saveGroupAssociations($groupAssociations);

        $this->saveBundleOptions($bundleOptions);

        $this->saveLinks($links);

        
        $this->importBatchRepository->update([
            'state' => Import::STATE_LINKED,
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.linking.after', $batch);

        return true;
    }

    
    public function indexBatch(ImportBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.indexing.before', $batch);

        
        $this->skuStorage->load(Arr::pluck($batch->data, 'sku'));

        $typeProductIds = [];

        foreach ($batch->data as $rowData) {
            $product = $this->skuStorage->get($rowData['sku']);

            $typeProductIds[$product['type']][] = (int) $product['id'];
        }

        $productIdsToIndex = [];

        foreach ($typeProductIds as $type => $productIds) {
            switch ($type) {
                case self::PRODUCT_TYPE_SIMPLE:
                case self::PRODUCT_TYPE_VIRTUAL:
                    $productIdsToIndex = [
                        ...$productIds,
                        ...$productIdsToIndex,
                    ];

                    
                    $parentBundleProductIds = $this->productBundleOptionRepository
                        ->select('product_bundle_options.product_id')
                        ->leftJoin('product_bundle_option_products', 'product_bundle_options.id', 'product_bundle_option_products.product_bundle_option_id')
                        ->whereIn('product_bundle_option_products.product_id', $productIds)
                        ->pluck('product_id')
                        ->toArray();

                    $productIdsToIndex = [
                        ...$productIdsToIndex,
                        ...$parentBundleProductIds,
                    ];

                    
                    $parentGroupedProductIds = ProductGroupedProduct::select('product_id')
                        ->whereIn('associated_product_id', $productIds)
                        ->pluck('product_id')
                        ->toArray();

                    $productIdsToIndex = [
                        ...$productIdsToIndex,
                        ...$parentGroupedProductIds,
                    ];

                    
                    $parentConfigurableProductIds = Product::select('parent_id')
                        ->whereIn('id', $productIds)
                        ->whereNotNull('parent_id')
                        ->pluck('parent_id')
                        ->toArray();

                    $productIdsToIndex = [
                        ...$productIdsToIndex,
                        ...$parentConfigurableProductIds,
                    ];

                    break;

                case self::PRODUCT_TYPE_CONFIGURABLE:
                    $productIdsToIndex = [
                        ...$productIdsToIndex,
                        ...$productIds,
                    ];

                    
                    $associatedProductIds = Product::select('id')
                        ->whereIn('parent_id', $productIds)
                        ->pluck('id')
                        ->toArray();

                    $productIdsToIndex = [
                        ...$associatedProductIds,
                        ...$productIdsToIndex,
                    ];

                    break;

                case self::PRODUCT_TYPE_BUNDLE:
                    $productIdsToIndex = [
                        ...$productIdsToIndex,
                        ...$productIds,
                    ];

                    
                    $associatedProductIds = ProductBundleOptionProduct::
                        select('product_bundle_option_products.product_id')
                        ->leftJoin('product_bundle_options', 'product_bundle_option_products.product_bundle_option_id', 'product_bundle_options.id')
                        ->whereIn('product_bundle_options.product_id', $productIds)
                        ->pluck('product_id')
                        ->toArray();

                    $productIdsToIndex = [
                        ...$associatedProductIds,
                        ...$productIdsToIndex,
                    ];

                    break;

                case self::PRODUCT_TYPE_GROUPED:
                    $productIdsToIndex = [
                        ...$productIdsToIndex,
                        ...$productIds,
                    ];

                    
                    $associatedProductIds = ProductGroupedProduct::select('associated_product_id')
                        ->whereIn('product_id', $productIds)
                        ->pluck('associated_product_id')
                        ->toArray();

                    $productIdsToIndex = [
                        ...$associatedProductIds,
                        ...$productIdsToIndex,
                    ];

                    break;
            }
        }

        $productIdsToIndex = array_unique($productIdsToIndex);

        Bus::chain([
            new UpdateCreateInventoryIndexJob($productIdsToIndex),
            new UpdateCreatePriceIndexJob($productIdsToIndex),
            new UpdateCreateElasticSearchIndexJob($productIdsToIndex),
        ])->onConnection('sync')->dispatch();

        
        $this->importBatchRepository->update([
            'state' => Import::STATE_INDEXED,
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.indexing.after', $batch);

        return true;
    }

    
    protected function deleteProducts(ImportBatchContract $batch): bool
    {
        
        $this->skuStorage->load(Arr::pluck($batch->data, 'sku'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isSKUExist($rowData['sku'])) {
                continue;
            }

            $product = $this->skuStorage->get($rowData['sku']);

            $idsToDelete[] = $product['id'];
        }

        $idsToDelete = array_unique($idsToDelete);

        $this->deletedItemsCount = count($idsToDelete);

        Product::deleteWhere([['id', 'IN', $idsToDelete]]);

        
        foreach ($idsToDelete as $i) {
            $imageDirectory = $this->productImageRepository->getProductDirectory((object) ['id' => $i]);

            if (! Storage::exists($imageDirectory)) {
                continue;
            }

            Storage::deleteDirectory($imageDirectory);
        }

        DeleteIndexJob::dispatch($idsToDelete)->onConnection('sync');

        return true;
    }

    
    protected function saveProductsData(ImportBatchContract $batch): bool
    {
        
        $this->skuStorage->load(Arr::pluck($batch->data, 'sku'));

        $products = [];

        $channels = [];

        $customerGroupPrices = [];

        $categories = [];

        $attributeValues = [];

        $inventories = [];

        $imagesData = [];

        $flatData = [];

        foreach ($batch->data as $rowData) {
            
            $this->prepareProducts($rowData, $products);

            
            $this->prepareChannels($rowData, $channels);

            
            $this->prepareCustomerGroupPrices($rowData, $customerGroupPrices);

            
            $this->prepareCategories($rowData, $categories);

            
            $this->prepareAttributeValues($rowData, $attributeValues);

            
            $this->prepareInventories($rowData, $inventories);

            
            $this->prepareImages($rowData, $imagesData);

            
            $this->prepareFlatData($rowData, $flatData);
        }

        $this->saveProducts($products);

        $this->saveChannels($channels);

        $this->saveCustomerGroupPrices($customerGroupPrices);

        $this->saveCategories($categories);

        $this->saveAttributeValues($attributeValues);

        $this->saveInventories($inventories);

        $this->saveImages($imagesData);

        $this->saveFlatData($flatData);

        return true;
    }

    
    public function prepareProducts(array $rowData, array &$products): void
    {
        $attributeFamilyId = $this->attributeFamilies
            ->where('code', $rowData['attribute_family_code'])
            ->first()->id;

        if ($this->isSKUExist($rowData['sku'])) {
            $products['update'][$rowData['sku']] = [
                'type'                => $rowData['type'],
                'sku'                 => $rowData['sku'],
                'attribute_family_id' => $attributeFamilyId,
            ];
        } else {
            $products['insert'][$rowData['sku']] = [
                'type'                => $rowData['type'],
                'sku'                 => $rowData['sku'],
                'attribute_family_id' => $attributeFamilyId,
                'created_at'          => $rowData['created_at'] ?? now(),
                'updated_at'          => $rowData['updated_at'] ?? now(),
            ];
        }
    }

    
    public function saveProducts(array $products): void
    {
        if (! empty($products['update'])) {
            $this->updatedItemsCount += count($products['update']);

            Product::upsert(
                $products['update'],
                $this->masterAttributeCode
            );
        }

        if (! empty($products['insert'])) {
            $this->createdItemsCount += count($products['insert']);

            Product::insert($products['insert']);

            
            $newProducts = Product::whereIn(
                'sku',
                array_keys($products['insert']),
                [
                    'id',
                    'type',
                    'sku',
                    'attribute_family_id',
                ]
            )->get();

            foreach ($newProducts as $product) {
                $this->skuStorage->set($product->sku, [
                    'id'                  => $product->id,
                    'type'                => $product->type,
                    'attribute_family_id' => $product->attribute_family_id,
                ]);
            }
        }
    }

    
    public function prepareCustomerGroupPrices(array $rowData, array &$customerGroupPrices): void
    {
        if (empty($rowData['customer_group_prices'])) {
            return;
        }

        $prices = explode('|', $rowData['customer_group_prices']);

        $customerGroups = $this->getCustomerGroups();

        foreach ($prices as $r) {
            parse_str(str_replace(',', '&', $r), $attributes);

            $customerGroupPrices[$rowData['sku']][] = [
                'qty'               => $attributes['qty'],
                'value_type'        => $attributes['type'],
                'value'             => $attributes['price'],
                'customer_group_id' => $customerGroups->where('code', $attributes['group'])->first()?->id,
            ];
        }
    }

    
    public function saveCustomerGroupPrices(array $customerGroupPrices): void
    {
        $productCustomerGroupPrices = [];

        foreach ($customerGroupPrices as $sku => $skuCustomerGroupPrices) {
            foreach ($skuCustomerGroupPrices as $customerGroupPrices) {
                $product = $this->skuStorage->get($sku);

                $customerGroupPrices['product_id'] = (int) $product['id'];

                $customerGroupPrices['unique_id'] = implode('|', array_filter([
                    $customerGroupPrices['qty'],
                    $customerGroupPrices['product_id'],
                    $customerGroupPrices['customer_group_id'],
                ]));

                $productCustomerGroupPrices[$customerGroupPrices['unique_id']] = $customerGroupPrices;
            }
        }

        $this->productCustomerGroupPriceRepository->upsert($productCustomerGroupPrices, 'unique_id');
    }

    
    public function prepareCategories(array $rowData, array &$categories): void
    {
        if (empty($rowData['categories'])) {
            return;
        }

        
        $categories[$rowData['sku']] = [];

        $names = explode('/', $rowData['categories'] ?? '');

        $categoryIds = [];

        foreach ($names as $na) {
            if (isset($this->categories[$na])) {
                $categoryIds = array_merge($categoryIds, $this->categories[$na]);

                continue;
            }

            $this->categories[$na] = $this->categoryRepository
                ->whereTranslation('name', $na)
                ->pluck('id')
                ->toArray();

            $categoryIds = array_merge($categoryIds, $this->categories[$na]);
        }

        $categories[$rowData['sku']] = $categoryIds;
    }

    
    public function saveCategories(array $categories): void
    {
        if (empty($categories)) {
            return;
        }

        $productCategories = [];

        foreach ($categories as $sku => $categoryIds) {
            $product = $this->skuStorage->get($sku);

            foreach ($categoryIds as $categoryId) {
                $productCategories[] = [
                    'product_id'  => $product['id'],
                    'category_id' => $categoryId,
                ];
            }
        }

        DB::table('product_categories')->upsert(
            $productCategories,
            [
                'product_id',
                'category_id',
            ],
        );
    }

    
    public function prepareChannels(array $rowData, array &$channels): void
    {
        $channels[$rowData['sku']][] = $this->getChannels()
            ->where('code', $rowData['channel'])
            ->first()
            ->id;
    }

    
    public function saveChannels(array $channels): void
    {
        $productChannels = [];

        foreach ($channels as $sku => $channelIds) {
            $product = $this->skuStorage->get($sku);

            foreach (array_unique($channelIds) as $channelId) {
                $productChannels[] = [
                    'product_id' => $product['id'],
                    'channel_id' => $channelId,
                ];
            }
        }

        DB::table('product_channels')->upsert(
            $productChannels,
            [
                'product_id',
                'channel_id',
            ],
        );
    }

    
    public function prepareAttributeValues(array $rowData, array &$attributeValues): void
    {
        $dat = [];

        $familyAttributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData['attribute_family_code']);

        foreach ($rowData as $attributeCode => $va) {
            if (is_null($va)) {
                continue;
            }

            $attribute = $familyAttributes->where('code', $attributeCode)->first();

            if (! $attribute) {
                continue;
            }

            $attributeTypeValues = array_fill_keys(array_values($attribute->attributeTypeFields), null);

            $attributeValues[$rowData['sku']][] = array_merge($attributeTypeValues, [
                'attribute_id'          => $attribute->id,
                $attribute->column_name => $va,
                'channel'               => $attribute->value_per_channel ? $rowData['channel'] : null,
                'locale'                => $attribute->value_per_locale ? $rowData['locale'] : null,
            ]);
        }
    }

    
    public function saveAttributeValues(array $attributeValues): void
    {
        $productAttributeValues = [];

        foreach ($attributeValues as $sku => $skuAttributes) {
            foreach ($skuAttributes as $attribute) {
                $product = $this->skuStorage->get($sku);

                $attribute['product_id'] = (int) $product['id'];

                $attribute['unique_id'] = implode('|', array_filter([
                    $attribute['channel'],
                    $attribute['locale'],
                    $attribute['product_id'],
                    $attribute['attribute_id'],
                ]));

                $productAttributeValues[$attribute['unique_id']] = $attribute;
            }
        }

        $this->productAttributeValueRepository->upsert($productAttributeValues, 'unique_id');
    }

    
    public function prepareInventories(array $rowData, array &$inventories): void
    {
        if (empty($rowData['inventories'])) {
            return;
        }

        
        $inventories[$rowData['sku']] = [];

        $inventorySources = explode(',', $rowData['inventories'] ?? '');

        foreach ($inventorySources as $inventorySource) {
            [$inventorySource, $qty] = explode('=', $inventorySource ?? '');

            $inventories[$rowData['sku']][] = [
                'source' => $inventorySource,
                'qty'    => $qty,
            ];
        }
    }

    
    public function saveInventories(array $inventories): void
    {
        if (empty($inventories)) {
            return;
        }

        $inventorySources = $this->inventorySourceRepository
            ->findWhereIn('code', Arr::flatten(Arr::pluck($inventories, '*.source')));

        $productInventories = [];

        foreach ($inventories as $sku => $skuInventories) {
            $product = $this->skuStorage->get($sku);

            foreach ($skuInventories as $inventory) {
                $inventorySource = $inventorySources->where('code', $inventory['source'])->first();

                if (! $inventorySource) {
                    continue;
                }

                $productInventories[] = [
                    'inventory_source_id' => $inventorySource->id,
                    'product_id'          => $product['id'],
                    'qty'                 => $inventory['qty'],
                    'vendor_id'           => 0,
                ];
            }
        }

        $this->productInventoryRepository->upsert(
            $productInventories,
            [
                'product_id',
                'inventory_source_id',
                'vendor_id',
            ],
        );
    }

    
    public function prepareImages(array $rowData, array &$imagesData): void
    {
        if (empty($rowData['images'])) {
            return;
        }

        
        if ($this->skuStorage->has($rowData['sku'])) {
            return;
        }

        
        $imagesData[$rowData['sku']] = [];

        $imageNames = array_map('trim', explode(',', $rowData['images']));

        foreach ($imageNames as $key => $image) {
            $path = 'import/'.$this->import->images_directory_path.'/'.$image;

            if (! Storage::disk('local')->has($path)) {
                continue;
            }

            $imagesData[$rowData['sku']][] = [
                'name' => $image,
                'path' => Storage::disk('local')->path($path),
            ];
        }
    }

    
    public function saveImages(array $imagesData): void
    {
        if (empty($imagesData)) {
            return;
        }

        $productImages = [];

        foreach ($imagesData as $sku => $images) {
            $product = $this->skuStorage->get($sku);

            foreach ($images as $key => $image) {
                $file = new UploadedFile($image['path'], $image['name']);

                $image = (new ImageManager)->make($file)->encode('webp');

                $imageDirectory = $this->productImageRepository->getProductDirectory((object) $product);

                $path = $imageDirectory.'/'.Str::random(40).'.webp';

                $productImages[] = [
                    'type'       => 'images',
                    'path'       => $path,
                    'product_id' => $product['id'],
                    'position'   => $key + 1,
                ];

                Storage::put($path, $image);
            }
        }

        $this->productImageRepository->insert($productImages);
    }

    
    public function prepareFlatData(array $rowData, array &$flatData): void
    {
        $attributeFamily = $this->attributeFamilies->where('code', $rowData['attribute_family_code'])->first();

        $flatColumns = $this->getProductFlatColumns();

        $dat = [];

        foreach ($flatColumns as $column) {
            if (in_array($column, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            $dat[$column] = $rowData[$column] ?? null;
        }

        $dat = array_merge($dat, [
            'locale'  => $rowData['locale'],
            'channel' => $rowData['channel'],
        ]);

        $flatData[] = $dat;
    }

    
    public function saveFlatData(array &$flatData): void
    {
        $products = [];

        foreach ($flatData as $attributes) {
            $product = $this->skuStorage->get($attributes['sku']);

            $products[] = array_merge($attributes, [
                'product_id'          => $product['id'],
                'attribute_family_id' => $product['attribute_family_id'],
            ]);
        }

        $this->productFlatRepository->upsert(
            $products,
            [
                'product_id',
                'channel',
                'locale',
            ],
        );
    }

    
    public function prepareConfigurableVariants(array $rowData, array &$configurableVariants): void
    {
        if (
            $rowData['type'] != self::PRODUCT_TYPE_CONFIGURABLE
            && empty($rowData['configurable_variants'])
        ) {
            return;
        }

        $variants = explode('|', $rowData['configurable_variants']);

        foreach ($variants as $variant) {
            parse_str(str_replace(',', '&', $variant), $variantAttributes);

            $configurableVariants[$rowData['sku']][$variantAttributes['sku']] = Arr::except($variantAttributes, 'sku');
        }
    }

    
    public function saveConfigurableVariants(array $configurableVariants): void
    {
        if (empty($configurableVariants)) {
            return;
        }

        $variantSkus = array_map('array_keys', $configurableVariants);

        
        $this->loadUnloadedSKUs(array_unique(Arr::flatten($variantSkus)));

        $superAttributeOptions = $this->getSuperAttributeOptions($configurableVariants);

        $parentAssociations = [];

        $superAttributes = [];

        $superAttributeValues = [];

        foreach ($configurableVariants as $sku => $variants) {
            $product = $this->skuStorage->get($sku);

            foreach ($variants as $variantSku => $variantSuperAttributes) {
                $variant = $this->skuStorage->get($variantSku);

                $parentAssociations[] = [
                    'sku'       => $variantSku,
                    'parent_id' => $product['id'],
                ];

                foreach ($variantSuperAttributes as $superAttributeCode => $optionLabel) {
                    $attribute = $this->attributes->where('code', $superAttributeCode)->first();

                    $attributeOption = $superAttributeOptions->where('attribute_id', $attribute->id)
                        ->where('admin_name', $optionLabel)
                        ->first();

                    $attributeTypeValues = array_fill_keys(array_values($attribute->attributeTypeFields), null);

                    $attributeTypeValues = array_merge($attributeTypeValues, [
                        'product_id'            => $variant['id'],
                        'attribute_id'          => $attribute->id,
                        $attribute->column_name => $attributeOption->id,
                        'channel'               => null,
                        'locale'                => null,
                    ]);

                    $attributeTypeValues['unique_id'] = implode('|', array_filter([
                        $attributeTypeValues['channel'],
                        $attributeTypeValues['locale'],
                        $attributeTypeValues['product_id'],
                        $attributeTypeValues['attribute_id'],
                    ]));

                    $superAttributeValues[] = $attributeTypeValues;
                }
            }

            $superAttributeCodes = array_keys(current($variants));

            foreach ($superAttributeCodes as $attributeCode) {
                $attribute = $this->attributes->where('code', $attributeCode)->first();

                $superAttributes[] = [
                    'product_id'   => $product['id'],
                    'attribute_id' => $attribute->id,
                ];
            }
        }

        
        Product::upsert($parentAssociations, 'sku');

        
        DB::table('product_super_attributes')->upsert(
            $superAttributes,
            [
                'product_id',
                'attribute_id',
            ],
        );

        
        $this->productAttributeValueRepository->upsert($superAttributeValues, 'unique_id');
    }

    
    public function prepareGroupAssociations(array $rowData, array &$groupAssociations): void
    {
        if (
            $rowData['type'] != self::PRODUCT_TYPE_GROUPED
            && empty($rowData['associated_skus'])
        ) {
            return;
        }

        $associatedSkus = explode(',', $rowData['associated_skus']);

        foreach ($associatedSkus as $row) {
            [$sku, $qty] = explode('=', $row);

            $groupAssociations[$rowData['sku']][$sku] = $qty;
        }
    }

    
    public function saveGroupAssociations(array $groupAssociations): void
    {
        if (empty($groupAssociations)) {
            return;
        }

        $associatedSkus = array_map('array_keys', $groupAssociations);

        
        $this->loadUnloadedSKUs(array_unique(Arr::flatten($associatedSkus)));

        $associatedProducts = [];

        foreach ($groupAssociations as $sku => $associatedSkus) {
            $product = $this->skuStorage->get($sku);

            $sortOrder = 0;

            foreach ($associatedSkus as $associatedSku => $qty) {
                $associatedProduct = $this->skuStorage->get($associatedSku);

                if (! $associatedProduct) {
                    continue;
                }

                $associatedProducts[] = [
                    'qty'                   => $qty,
                    'sort_order'            => $sortOrder++,
                    'product_id'            => $product['id'],
                    'associated_product_id' => $associatedProduct['id'],
                ];
            }
        }

        ProductGroupedProduct::upsert(
            $associatedProducts,
            [
                'product_id',
                'associated_product_id',
            ],
        );
    }

    
    public function prepareBundleOptions(array $rowData, array &$bundleOptions): void
    {
        if (
            $rowData['type'] != self::PRODUCT_TYPE_BUNDLE
            && empty($rowData['bundle_options'])
        ) {
            return;
        }

        $options = explode('|', $rowData['bundle_options']);

        $optionSortOrder = 0;

        foreach ($options as $option) {
            parse_str(str_replace(',', '&', $option), $attributes);

            if (! isset($bundleOptions[$rowData['sku']][$rowData['locale']][$attributes['name']])) {
                $productSortOrder = 0;

                $bundleOptions[$rowData['sku']][$rowData['locale']][$attributes['name']]['attributes'] = [
                    'type'        => $attributes['type'],
                    'is_required' => $attributes['required'],
                    'sort_order'  => $optionSortOrder++,
                ];
            }

            $bundleOptions[$rowData['sku']][$rowData['locale']][$attributes['name']]['skus'][$attributes['sku']] = [
                'qty'        => $attributes['qty'],
                'is_default' => $attributes['default'],
                'sort_order' => $productSortOrder++,
            ];
        }
    }

    
    public function saveBundleOptions(array &$bundleOptions): void
    {
        if (empty($bundleOptions)) {
            return;
        }

        $associatedSkus = [];

        foreach (data_get($bundleOptions, '*.*.*.skus') as $options) {
            $associatedSkus = array_merge($associatedSkus, array_keys($options));
        }

        
        $this->loadUnloadedSKUs(array_unique(Arr::flatten($associatedSkus)));

        $upsertData = [];

        $existingOptions = $this->getExistingBundleOptions($bundleOptions);

        foreach ($bundleOptions as $sku => $localeOptions) {
            $product = $this->skuStorage->get($sku);

            $createdUpdatedOptionIds = [];

            foreach (current($localeOptions) as $optionName => $option) {
                $optionAlreadyCreated = true;

                $bundleOption = $existingOptions->where('product_id', $product['id'])
                    ->where('label', $optionName)
                    ->first();

                if (! $bundleOption) {
                    $bundleOption = $this->productBundleOptionRepository->create([
                        'product_id'  => $product['id'],
                        'type'        => $option['attributes']['type'],
                        'is_required' => $option['attributes']['is_required'],
                        'sort_order'  => $option['attributes']['sort_order'],
                    ]);
                } else {
                    $upsertData['options'][] = [
                        'id'          => $bundleOption->id,
                        'product_id'  => $product['id'],
                        'type'        => $option['attributes']['type'],
                        'is_required' => $option['attributes']['is_required'],
                        'sort_order'  => $option['attributes']['sort_order'],
                    ];
                }

                $createdUpdatedOptionIds[] = $bundleOption->id;

                foreach ($option['skus'] as $associatedSKU => $optionProduct) {
                    $associatedProduct = $this->skuStorage->get($associatedSKU);

                    $upsertData['products'][] = [
                        'product_bundle_option_id' => $bundleOption->id,
                        'product_id'               => $associatedProduct['id'],
                        'qty'                      => $optionProduct['qty'],
                        'is_default'               => $optionProduct['is_default'],
                        'sort_order'               => $optionProduct['sort_order'],
                    ];
                }
            }

            
            foreach ($localeOptions as $locale => $options) {
                $key = 0;

                foreach ($options as $optionName => $option) {
                    $bundleOptionId = $createdUpdatedOptionIds[$key++] ?? null;

                    if (! $bundleOptionId) {
                        continue;
                    }

                    $upsertData['translations'][] = [
                        'product_bundle_option_id' => $bundleOptionId,
                        'label'                    => $optionName,
                        'locale'                   => $locale,
                    ];
                }
            }
        }

        if (! empty($upsertData['options'])) {
            $this->productBundleOptionRepository->upsert($upsertData['options'], 'id');
        }

        if (! empty($upsertData['products'])) {
            DB::table('product_bundle_option_translations')->upsert(
                $upsertData['translations'],
                [
                    'product_bundle_option_id',
                    'label',
                    'locale',
                ],
            );
        }

        if (! empty($upsertData['products'])) {
            ProductBundleOptionProduct::upsert(
                $upsertData['products'],
                [
                    'product_id',
                    'product_bundle_option_id',
                ],
            );
        }
    }

    
    public function prepareLinks(array $rowData, array &$links): void
    {
        $linkTableMapping = [
            'related'    => 'product_relations',
            'cross_sell' => 'product_cross_sells',
            'up_sell'    => 'product_up_sells',
        ];

        foreach ($linkTableMapping as $type => $table) {
            if (empty($rowData[$type.'_skus'])) {
                continue;
            }

            
            $links[$table][$rowData['sku']] = [];

            foreach (explode(',', $rowData[$type.'_skus'] ?? '') as $sku) {
                $links[$table][$rowData['sku']][] = $sku;
            }
        }
    }

    
    public function saveLinks(array $links): void
    {
        
        $this->loadUnloadedSKUs(array_unique(Arr::flatten($links)));

        foreach ($links as $table => $linksData) {
            $productLinks = [];

            foreach ($linksData as $sku => $linkedSkus) {
                $product = $this->skuStorage->get($sku);

                foreach ($linkedSkus as $linkedSku) {
                    $linkedProduct = $this->skuStorage->get($linkedSku);

                    if (! $linkedProduct) {
                        continue;
                    }

                    $productLinks[] = [
                        'parent_id' => $product['id'],
                        'child_id'  => $linkedProduct['id'],
                    ];
                }
            }

            DB::table($table)->upsert(
                $productLinks,
                [
                    'parent_id',
                    'child_id',
                ],
            );
        }
    }

    
    public function getExistingBundleOptions(array $bundleOptions): mixed
    {
        $queryBuilder = $this->productBundleOptionRepository
            ->select('product_bundle_options.id', 'label', 'locale', 'product_id', 'type', 'is_required', 'sort_order')
            ->leftJoin('product_bundle_option_translations', 'product_bundle_option_translations.product_bundle_option_id', 'product_bundle_options.id');

        foreach ($bundleOptions as $sku => $localeOptions) {
            $product = $this->skuStorage->get($sku);

            foreach ($localeOptions as $locale => $options) {
                foreach ($options as $optionName => $option) {
                    $queryBuilder->orWhere(function ($query) use ($product, $optionName, $locale) {
                        $query->where('product_bundle_options.product_id', $product['id'])
                            ->where('product_bundle_option_translations.label', $optionName)
                            ->where('product_bundle_option_translations.locale', $locale);
                    });
                }
            }
        }

        return $queryBuilder->get();
    }

    
    public function getSuperAttributeOptions(array $variants): mixed
    {
        $optionLabels = array_unique(Arr::flatten($variants));

        return $this->attributeOptionRepository->findWhereIn('admin_name', $optionLabels);
    }

    
    public function loadUnloadedSKUs(array $skus): void
    {
        $notLoadedSkus = [];

        foreach ($skus as $sku) {
            if ($this->skuStorage->has($sku)) {
                continue;
            }

            $notLoadedSkus[] = $sku;
        }

        
        if (! empty($notLoadedSkus)) {
            $this->skuStorage->load($notLoadedSkus);
        }
    }

    
    public function getProductTypeFamilyAttributes(string $type, string $attributeFamilyCode): mixed
    {
        if (isset($this->typeFamilyAttributes[$type][$attributeFamilyCode])) {
            return $this->typeFamilyAttributes[$type][$attributeFamilyCode];
        }

        $attributeFamily = $this->attributeFamilies->where('code', $attributeFamilyCode)->first();

        $product = ProductModel::make([
            'type'                => $type,
            'attribute_family_id' => $attributeFamily->id,
        ]);

        return $this->typeFamilyAttributes[$type][$attributeFamilyCode] = $product->getEditableAttributes();
    }

    
    public function getCustomerGroups(): mixed
    {
        if (! empty($this->customerGroups)) {
            return $this->customerGroups;
        }

        return $this->customerGroups = $this->customerGroupRepository->all();
    }

    
    public function getChannels(): mixed
    {
        if (! empty($this->channels)) {
            return $this->channels;
        }

        return $this->channels = $this->channelRepository->all();
    }

    
    protected function getProductFlatColumns(): array
    {
        if (! empty($this->productFlatColumns)) {
            return $this->productFlatColumns;
        }

        return $this->productFlatColumns = Schema::getColumnListing('product_flat');
    }

    
    public function isSKUExist(string $sku): bool
    {
        return $this->skuStorage->has($sku);
    }

    
    protected function prepareRowForDb(array $rowData): array
    {
        $rowData = parent::prepareRowForDb($rowData);

        $rowData['locale'] = $rowData['locale'] ?? app()->getLocale();

        $rowData['channel'] = $rowData['channel'] ?? core()->getDefaultChannelCode();

        return $rowData;
    }
}
