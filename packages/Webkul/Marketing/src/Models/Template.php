<?php

namespace Webkul\Marketing\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Marketing\Contracts\Template as TemplateContract;
use Webkul\Marketing\Database\Factories\TemplateFactory;

class Template extends Model implements TemplateContract
{
    use HasFactory;

    
    protected $table = 'marketing_templates';

    
    protected $fillable = [
        'name',
        'status',
        'content',
    ];

    
    protected static function newFactory(): Factory
    {
        return TemplateFactory::new();
    }
}
