<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Contracts\Locale as LocaleContract;
use Webkul\Core\Database\Factories\LocaleFactory;

class Locale extends Model implements LocaleContract
{
    use HasFactory;

    
    protected $fillable = [
        'code',
        'name',
        'direction',
    ];

    
    protected $appends = ['logo_url'];

    
    protected static function newFactory(): Factory
    {
        return LocaleFactory::new();
    }

    
    public function getLogoUrlAttribute()
    {
        return $this->logo_url();
    }

    
    public function logo_url()
    {
        if (empty($this->logo_path)) {
            return;
        }

        return Storage::url($this->logo_path);
    }
}
