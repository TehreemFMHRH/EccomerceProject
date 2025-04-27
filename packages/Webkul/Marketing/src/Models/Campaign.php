<?php

namespace Webkul\Marketing\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Customer\Models\CustomerGroupProxy;
use Webkul\Marketing\Contracts\Campaign as CampaignContract;
use Webkul\Marketing\Database\Factories\CampaignFactory;

class Campaign extends Model implements CampaignContract
{
    use HasFactory;

    
    protected $table = 'marketing_campaigns';

    
    protected $fillable = [
        'name',
        'subject',
        'status',
        'channel_id',
        'customer_group_id',
        'marketing_template_id',
        'spooling',
        'marketing_event_id',
    ];

    
    public function event()
    {
        return $this->belongsTo(EventProxy::modelClass(), 'marketing_event_id');
    }

    
    public function channel()
    {
        return $this->belongsTo(ChannelProxy::modelClass(), 'channel_id');
    }

    
    public function customer_group()
    {
        return $this->belongsTo(CustomerGroupProxy::modelClass(), 'customer_group_id');
    }

    
    public function email_template()
    {
        return $this->belongsTo(TemplateProxy::modelClass(), 'marketing_template_id');
    }

    
    protected static function newFactory(): Factory
    {
        return CampaignFactory::new();
    }
}
