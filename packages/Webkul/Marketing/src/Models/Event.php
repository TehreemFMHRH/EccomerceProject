<?php

namespace Webkul\Marketing\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Marketing\Contracts\Event as EventContract;
use Webkul\Marketing\Database\Factories\EventFactory;

class Event extends Model implements EventContract
{
    use HasFactory;

    
    protected $table = 'marketing_events';

    
    protected $fillable = [
        'name',
        'description',
        'date',
    ];

    
    protected static function newFactory(): Factory
    {
        return EventFactory::new();
    }
}
