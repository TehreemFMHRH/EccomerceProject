<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\ChannelTranslation as ChannelTranslationContract;
use Webkul\Core\Database\Factories\ChannelTranslationFactory;

class ChannelTranslation extends Model implements ChannelTranslationContract
{
    use HasFactory;

    
    protected $guarded = [];

    
    protected $casts = [
        'home_seo' => 'array',
    ];

    
    protected static function newFactory(): Factory
    {
        return ChannelTranslationFactory::new();
    }
}
