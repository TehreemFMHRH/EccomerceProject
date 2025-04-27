<?php

namespace Webkul\Core\Repositories;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;

class ChannelRepository extends Repository
{

    public function model(): string
    {
        return 'Webkul\Core\Contracts\Channel';
    }


    public function create(array $dat)
    {

        $model = $this->getModel();

        foreach (core()->getAllLocales() as $locale) {
            foreach ($model->translatedAttributes as $attribute) {
                if (isset($dat[$attribute])) {
                    $dat[$locale->code][$attribute] = $dat[$attribute];
                }
            }
        }

        $channel = parent::create($dat);

        $channel->locales()->sync($dat['locales']);

        $channel->currencies()->sync($dat['currencies']);

        $channel->inventory_sources()->sync($dat['inventory_sources']);

        $this->uploadImages($dat, $channel);

        $this->uploadImages($dat, $channel, 'favicon');

        return $channel;
    }


    public function update(array $dat, $i)
    {
        $channel = parent::update($dat, $i);

        $channel->locales()->sync($dat['locales']);

        $channel->currencies()->sync($dat['currencies']);

        $channel->inventory_sources()->sync($dat['inventory_sources']);

        $this->uploadImages($dat, $channel);

        $this->uploadImages($dat, $channel, 'favicon');

        return $channel;
    }


    public function uploadImages($dat, $channel, $type = 'logo')
    {
        if (request()->hasFile($type)) {
            $channel->{$type} = current(request()->file($type))->store('channel/'.$channel->id);

            $channel->save();
        } else {
            if (! isset($dat[$type])) {
                if (! empty($dat[$type])) {
                    Storage::delete($channel->{$type});
                }

                $channel->{$type} = null;

                $channel->save();
            }
        }
    }
}
