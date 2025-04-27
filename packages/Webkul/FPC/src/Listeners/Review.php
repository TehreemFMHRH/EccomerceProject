<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Product\Repositories\ProductReviewRepository;

class Review
{
    
    public function __construct(protected ProductReviewRepository $productReviewRepository) {}

    
    public function afterUpdate($review)
    {
        ResponseCache::forget('/'.$review->product->url_key);
    }

    
    public function beforeDelete($reviewId)
    {
        $review = $this->productReviewRepository->find($reviewId);

        ResponseCache::forget('/'.$review->product->url_key);
    }
}
