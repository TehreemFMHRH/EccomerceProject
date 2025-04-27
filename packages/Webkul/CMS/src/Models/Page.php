<?php

namespace Webkul\CMS\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\CMS\Contracts\Page as PageContract;
use Webkul\CMS\Database\Factories\PageFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Core\Models\ChannelProxy;

class Page extends TranslatableModel implements PageContract
{
    use HasFactory;

    
    protected $table = 'cms_pages';

    
    protected $translationForeignKey = 'cms_page_id';

    
    protected $fillable = ['layout'];

    
    public $translatedAttributes = [
        'content',
        'meta_description',
        'meta_title',
        'page_title',
        'meta_keywords',
        'html_content',
        'url_key',
    ];

    
    protected $with = ['translations'];

    
    public function channels()
    {
        return $this->belongsToMany(ChannelProxy::modelClass(), 'cms_page_channels', 'cms_page_id');
    }

    
    protected static function newFactory(): Factory
    {
        return PageFactory::new();
    }
}
