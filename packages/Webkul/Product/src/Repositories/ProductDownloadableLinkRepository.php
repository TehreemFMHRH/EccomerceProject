<?php

namespace Webkul\Product\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class ProductDownloadableLinkRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Product\Contracts\ProductDownloadableLink';
    }

    
    public function upload($dat, $productId)
    {
        foreach ($dat as $type => $file) {
            if (! request()->hasFile($type)) {
                continue;
            }

            return [
                $type           => $path = request()->file($type)->store('product_downloadable_links/'.$productId, 'private'),
                $type.'_name'   => $file->getClientOriginalName(),
                $type.'_url'    => Storage::url($path),
            ];
        }

        return [];
    }

    
    public function saveLinks(array $dat, $product)
    {
        $previousLinkIds = $product->downloadable_links()->pluck('id');

        if (isset($dat['downloadable_links'])) {
            foreach ($dat['downloadable_links'] as $linkId => $dat) {
                if (Str::contains($linkId, 'link_')) {
                    $this->create(array_merge([
                        'product_id' => $product->id,
                    ], $dat));
                } else {
                    if (is_numeric($index = $previousLinkIds->search($linkId))) {
                        $previousLinkIds->forget($index);
                    }

                    $this->update($dat, $linkId);
                }
            }
        }

        foreach ($previousLinkIds as $linkId) {
            $this->delete($linkId);
        }
    }
}
