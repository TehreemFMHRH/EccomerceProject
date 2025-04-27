<?php

namespace Webkul\Core\Models;

use Webkul\Core\Contracts\Country as CountryContract;
use Webkul\Core\Eloquent\TranslatableModel;

class Country extends TranslatableModel implements CountryContract
{
    public $timestamps = false;

    public $translatedAttributes = ['name'];

    protected $with = ['translations'];

    
    public function states()
    {
        return $this->hasMany(CountryStateProxy::modelClass());
    }
}
