<?php

namespace Webkul\CatalogRule\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Admin\Database\Factories\CatalogRuleFactory;
use Webkul\CatalogRule\Contracts\CatalogRule as CatalogRuleContract;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Customer\Models\CustomerGroupProxy;

class CatalogRule extends Model implements CatalogRuleContract
{
    use HasFactory;

    
    protected $fillable = [
        'name',
        'description',
        'starts_from',
        'ends_till',
        'status',
        'condition_type',
        'conditions',
        'end_other_rules',
        'action_type',
        'discount_amount',
        'sort_order',
    ];

    
    protected $casts = [
        'conditions' => 'array',
    ];

    
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(ChannelProxy::modelClass(), 'catalog_rule_channels');
    }

    
    public function customer_groups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroupProxy::modelClass(), 'catalog_rule_customer_groups');
    }

    
    public function catalog_rule_products(): HasMany
    {
        return $this->hasMany(CatalogRuleProductProxy::modelClass());
    }

    
    public function catalog_rule_product_prices(): HasMany
    {
        return $this->hasMany(CatalogRuleProductPriceProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return CatalogRuleFactory::new();
    }
}
