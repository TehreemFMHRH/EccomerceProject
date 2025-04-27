<?php

namespace Webkul\Product\Repositories;

use Webkul\Core\Eloquent\Repository;

class ProductReviewRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Product\Contracts\ProductReview';
    }

    
    public function getCustomerReview()
    {
        $reviews = $this->model
            ->where(['customer_id' => auth()->guard('customer')->user()->id])
            ->with('product')
            ->paginate(5);

        return $reviews;
    }
}
