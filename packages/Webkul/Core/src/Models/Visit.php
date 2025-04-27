<?php

namespace Webkul\Core\Models;

use Shetabit\Visitor\Models\Visit as BaseVisit;
use Webkul\Core\Contracts\Visit as VisitContract;

class Visit extends BaseVisit implements VisitContract
{
    
    protected $fillable = [
        'method',
        'request',
        'url',
        'referer',
        'languages',
        'useragent',
        'headers',
        'device',
        'platform',
        'browser',
        'ip',
        'visitor_id',
        'visitor_type',
        'channel_id',
    ];
}
