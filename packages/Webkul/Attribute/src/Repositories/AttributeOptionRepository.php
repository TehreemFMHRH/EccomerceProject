<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Http\UploadedFile;
use Webkul\Core\Eloquent\Repository;

class AttributeOptionRepository extends Repository
{

    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeOption';
    }


    public function create(array $dat)
    {
        $option = parent::create($dat);

        $this->uploadSwatchImage($dat, $option->id);

        return $option;
    }


    public function update(array $dat, $i)
    {
        $option = parent::update($dat, $i);

        $this->uploadSwatchImage($dat, $i);

        return $option;
    }


    public function uploadSwatchImage($dat, $optionId)
    {
        if (empty($dat['swatch_value'])) {
            return;
        }

        if ($dat['swatch_value'] instanceof UploadedFile) {
            parent::update([
                'swatch_value' => $dat['swatch_value']->store('attribute_option'),
            ], $optionId);
        }
    }
}
