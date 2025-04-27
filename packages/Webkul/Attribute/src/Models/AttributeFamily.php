<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attribute\Contracts\AttributeFamily as AttributeFamilyContract;
use Webkul\Attribute\Database\Factories\AttributeFamilyFactory;
use Webkul\Product\Models\ProductProxy;

class AttributeFamily extends Model implements AttributeFamilyContract
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
    ];

    
    public function custom_attributes()
    {
        return (AttributeProxy::modelClass())::join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->join('attribute_groups', 'attribute_group_mappings.attribute_group_id', '=', 'attribute_groups.id')
            ->join('attribute_families', 'attribute_groups.attribute_family_id', '=', 'attribute_families.id')
            ->where('attribute_families.id', $this->id)
            ->select('attributes.*');
    }

    
    public function getComparableAttributesBelongsToFamily()
    {
        return (AttributeProxy::modelClass())::join('attribute_group_mappings', 'attribute_group_mappings.attribute_id', '=', 'attributes.id')
            ->select('attributes.*')
            ->where('attributes.is_comparable', 1)
            ->distinct()
            ->get();
    }

    
    public function getCustomAttributesAttribute()
    {
        return $this->custom_attributes()->get();
    }

    
    public function attribute_groups(): HasMany
    {
        return $this->hasMany(AttributeGroupProxy::modelClass())->orderBy('position');
    }

    
    public function getConfigurableAttributesAttribute()
    {
        return $this->custom_attributes()
            ->where('attributes.is_configurable', 1)
            ->where('attributes.type', 'select')
            ->get();
    }

    
    public function products(): HasMany
    {
        return $this->hasMany(ProductProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return AttributeFamilyFactory::new();
    }
}
