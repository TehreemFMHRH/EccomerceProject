<?php

namespace Webkul\Marketing\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Marketing\Contracts\URLRewrite as URLRewriteContract;
use Webkul\Marketing\Database\Factories\UrlRewriteFactory;

class URLRewrite extends Model implements URLRewriteContract
{
    use HasFactory;

    
    protected $table = 'url_rewrites';

    
    protected $fillable = [
        'entity_type',
        'request_path',
        'target_path',
        'redirect_type',
        'locale',
    ];

    
    protected static function newFactory(): Factory
    {
        return UrlRewriteFactory::new();
    }
}
