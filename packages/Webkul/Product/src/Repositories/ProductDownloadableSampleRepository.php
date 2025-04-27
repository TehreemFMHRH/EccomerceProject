<?php

namespace Webkul\Product\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class ProductDownloadableSampleRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Product\Contracts\ProductDownloadableSample';
    }

    
    public function upload($dat, $productId)
    {
        if (! request()->hasFile('file')) {
            return [];
        }

        return [
            'file'      => $path = request()->file('file')->store('product_downloadable_links/'.$productId),
            'file_name' => request()->file('file')->getClientOriginalName(),
            'file_url'  => Storage::url($path),
        ];
    }

    
    public function saveSamples(array $dat, $product)
    {
        $previousSampleIds = $product->downloadable_samples()->pluck('id');

        if (isset($dat['downloadable_samples'])) {
            foreach ($dat['downloadable_samples'] as $sampleId => $dat) {
                if (Str::contains($sampleId, 'sample_')) {
                    $this->create(array_merge([
                        'product_id' => $product->id,
                    ], $dat));
                } else {
                    if (is_numeric($index = $previousSampleIds->search($sampleId))) {
                        $previousSampleIds->forget($index);
                    }

                    $this->update($dat, $sampleId);
                }
            }
        }

        foreach ($previousSampleIds as $sampleId) {
            $this->delete($sampleId);
        }
    }
}
