<?php

namespace Webkul\Theme\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Theme\Contracts\ThemeCustomizationTranslation as ThemeCustomizationTranslationContract;

class ThemeCustomizationTranslation extends Model implements ThemeCustomizationTranslationContract
{
    use HasFactory;

    
    public $timestamps = false;

    
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
        'name',
        'options',
    ];
}
