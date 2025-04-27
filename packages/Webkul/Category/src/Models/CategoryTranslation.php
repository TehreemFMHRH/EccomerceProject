<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Contracts\CategoryTranslation as CategoryTranslationContract;
use Webkul\Category\Database\Factories\CategoryTranslationFactory;

class CategoryTranslation extends Model implements CategoryTranslationContract
{
    use HasFactory;

    
    public $timestamps = false;

    
    protected $fillable = [
        'name',
        'description',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'locale_id',
    ];

    
    protected static function newFactory(): Factory
    {
        return CategoryTranslationFactory::new();
    }
}
