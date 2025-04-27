<?php

namespace Webkul\CatalogRule\Listeners;

use Webkul\CatalogRule\Jobs\DeleteCatalogRuleIndex as DeleteCatalogRuleIndexJob;
use Webkul\CatalogRule\Jobs\UpdateCreateCatalogRuleIndex as UpdateCreateCatalogRuleIndexJob;
use Webkul\CatalogRule\Repositories\CatalogRuleProductPriceRepository;
use Webkul\CatalogRule\Repositories\CatalogRuleRepository;

class CatalogRule
{
    
    public function __construct(
        protected CatalogRuleRepository $catalogRuleRepository,
        protected CatalogRuleProductPriceRepository $catalogRuleProductPriceRepository
    ) {}

    
    public function afterUpdateCreate($catalogRule)
    {
        UpdateCreateCatalogRuleIndexJob::dispatch($catalogRule);
    }

    
    public function beforeUpdate($catalogRuleId)
    {
        $catalogRule = $this->catalogRuleRepository->find($catalogRuleId);

        $productIds = $catalogRule->catalog_rule_products->pluck('product_id')->unique();

        $this->catalogRuleProductPriceRepository->deleteWhere(['catalog_rule_id' => $catalogRuleId]);

        DeleteCatalogRuleIndexJob::dispatch($productIds->toArray());
    }

    
    public function beforeDelete($catalogRuleId)
    {
        $catalogRule = $this->catalogRuleRepository->find($catalogRuleId);

        $productIds = $catalogRule->catalog_rule_products->pluck('product_id')->unique();

        $this->catalogRuleProductPriceRepository->deleteWhere(['catalog_rule_id' => $catalogRuleId]);

        DeleteCatalogRuleIndexJob::dispatch($productIds->toArray());
    }
}
