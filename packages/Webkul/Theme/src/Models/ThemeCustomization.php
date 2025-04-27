<?php

namespace Webkul\Theme\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\Admin\Database\Factories\ThemeFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Theme\Contracts\ThemeCustomization as ThemeCustomizationContract;

class ThemeCustomization extends TranslatableModel implements ThemeCustomizationContract
{
    use HasFactory;

    
    public $translatedAttributes = [
        'options',
    ];

    
    protected $with = ['translations'];

    
    public const IMAGE_CAROUSEL = 'image_carousel';

    
    public const PRODUCT_CAROUSEL = 'product_carousel';

    
    public const CATEGORY_CAROUSEL = 'category_carousel';

    
    public const FOOTER_LINKS = 'footer_links';

    
    public const STATIC_CONTENT = 'static_content';

    
    public const SERVICES_CONTENT = 'services_content';

    
    protected $casts = [
        'options' => 'array',
    ];

    
    protected $fillable = [
        'type',
        'name',
        'options',
        'sort_order',
        'status',
        'channel_id',
        'theme_code',
    ];

    
    protected static function newFactory(): Factory
    {
        return ThemeFactory::new();
    }
}
