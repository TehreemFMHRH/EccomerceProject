<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductDownloadableLinkRepository;
use Webkul\Product\Repositories\ProductDownloadableSampleRepository;
use Webkul\Product\Models\Product;
use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Marketing\Repositories\SearchSynonymRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Repositories\ElasticSearchRepository;

class ProductController extends Controller
{
    /**
     * Search engine.
     */
    protected $searchEngine = 'database';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CustomerRepository $kRepository,
        protected AttributeRepository $attributeRepository,
        protected ElasticSearchRepository $elasticSearchRepository,
        protected SearchSynonymRepository $searchSynonymRepository,
        Container $container,
        protected ProductAttributeValueRepository $productAttributeValueRepository,
        protected ProductDownloadableSampleRepository $productDownloadableSampleRepository,
        protected ProductDownloadableLinkRepository $productDownloadableLinkRepository
    ) {
        parent::__construct($container);
    }

    /**
     * Return product by filtering through attribute values.
     *
     * @param  string  $code
     * @param  mixed  $va
     * @return \Webkul\Product\Contracts\Product
     */
    public function findByAttributeCode($code, $va)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $code);

        $attributeValues = $this->productAttributeValueRepository->findWhere([
            'attribute_id'          => $attribute->id,
            $attribute->column_name => $va,
        ]);

        if ($attribute->value_per_channel) {
            if ($attribute->value_per_locale) {
                $filteredAttributeValues = [];

                foreach ($attributeValues as $va) {
                    if ($va->channel == core()->getRequestedChannelCode() && $va->locale == core()->getRequestedLocaleCode()) {
                        $filteredAttributeValues[] = $va;
                    }
                }

                if ($attributeValues->isEmpty()) {
                    $filteredAttributeValues = $attributeValues
                        ->where('channel', core()->getRequestedChannelCode())
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel());
                }
            } else {
                $filteredAttributeValues = $attributeValues
                    ->where('channel', core()->getRequestedChannelCode());
            }
        } else {
            if ($attribute->value_per_locale) {
                $filteredAttributeValues = $attributeValues
                    ->where('locale', core()->getRequestedLocaleCode());

                if ($filteredAttributeValues->isEmpty()) {
                    $filteredAttributeValues = $attributeValues
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel());
                }
            } else {
                $filteredAttributeValues = $attributeValues;
            }
        }

        return $filteredAttributeValues->first()?->product;
    }

    /**
     * Download image or file.
     *
     * @param  int  $productId
     * @param  int  $attributeId
     * @return \Illuminate\Http\Response
     */
    public function download($productId, $attributeId)
    {
        $productAttribute = $this->productAttributeValueRepository->findOneWhere([
            'product_id'   => $productId,
            'attribute_id' => $attributeId,
        ]);

        return isset($productAttribute['text_value'])
            ? Storage::download($productAttribute['text_value'])
            : null;
    }

    /**
     * Download the for the specified resource.
     *
     * @return \Illuminate\Http\Response|\Exception
     */
    public function downloadSample()
    {
        try {
            if (request('type') == 'link') {
                $productDownloadableLink = $this->productDownloadableLinkRepository->findOrFail(request('id'));

                if ($productDownloadableLink->sample_type == 'file') {
                    $privateDisk = Storage::disk('private');

                    return $privateDisk->exists($productDownloadableLink->sample_file)
                        ? $privateDisk->download($productDownloadableLink->sample_file)
                        : abort(404);
                } else {
                    $fileName = substr($productDownloadableLink->sample_url, strrpos($productDownloadableLink->sample_url, '/') + 1);

                    $tempImage = tempnam(sys_get_temp_dir(), $fileName);

                    copy($productDownloadableLink->sample_url, $tempImage);

                    return response()->download($tempImage, $fileName);
                }
            } else {
                $productDownloadableSample = $this->productDownloadableSampleRepository->findOrFail(request('id'));
                $p= Product::find($productDownloadableSample->product_id);

                if (! $p) {
                    return redirect()->back()->with('error', 'Product not found.');
                }

                if (! $p->visible_individually) {
                    return redirect()->back();
                }

                if ($productDownloadableSample->type == 'file') {
                    return Storage::download($productDownloadableSample->file);
                } else {
                    $fileName = substr($productDownloadableSample->url, strrpos($productDownloadableSample->url, '/') + 1);

                    $tempImage = tempnam(sys_get_temp_dir(), $fileName);

                    copy($productDownloadableSample->url, $tempImage);

                    return response()->download($tempImage, $fileName);
                }
            }
        } catch (\Exception $e) {
            abort(404);
        }
    }
}
