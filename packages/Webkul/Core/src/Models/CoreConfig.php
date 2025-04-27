<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\CoreConfig as CoreConfigContract;
use Webkul\Core\Database\Factories\CoreConfigFactory;

class CoreConfig extends Model implements CoreConfigContract
{
    use HasFactory;

    
    protected $table = 'core_config';

    
    protected $fillable = [
        'code',
        'value',
        'channel_code',
        'locale_code',
    ];

    
    protected $hidden = ['token'];

    
    protected static function newFactory(): Factory
    {
        return CoreConfigFactory::new();
    }
}
