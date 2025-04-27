<?php

namespace Webkul\Core\Repositories;

use Illuminate\Support\Facades\Event;
use Webkul\Core\Contracts\Currency;
use Webkul\Core\Eloquent\Repository;

class CurrencyRepository extends Repository
{
    
    public function model(): string
    {
        return Currency::class;
    }

    
    public function create(array $attributes)
    {
        Event::dispatch('core.currency.create.before');

        $currency = parent::create($attributes);

        Event::dispatch('core.currency.create.after', $currency);

        return $currency;
    }

    
    public function update(array $attributes, $i)
    {
        Event::dispatch('core.currency.update.before', $i);

        $currency = parent::update($attributes, $i);

        Event::dispatch('core.currency.update.after', $currency);

        return $currency;
    }

    
    public function delete($i)
    {
        Event::dispatch('core.currency.delete.before', $i);

        if ($this->model->count() == 1) {
            return false;
        }

        if ($this->model->destroy($i)) {
            Event::dispatch('core.currency.delete.after', $i);

            return true;
        }

        return false;
    }
}
