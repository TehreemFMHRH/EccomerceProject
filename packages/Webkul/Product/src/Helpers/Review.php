<?php

namespace Webkul\Product\Helpers;

use Illuminate\Support\Facades\DB;

class Review
{
    
    public function getReviews($product)
    {
        return $product->reviews()->where('status', 'approved');
    }

    
    public function getAverageRating($product)
    {
        return number_format(round($product->reviews->where('status', 'approved')->avg('rating'), 2), 1);
    }

    
    public function getTotalReviews($product)
    {
        return $product->reviews->where('status', 'approved')->count();
    }

    
    public function getTotalRating($product)
    {
        return $product->reviews->where('status', 'approved')->sum('rating');
    }

    
    public function getTotalFeedback($product)
    {
        return core()->getConfigData('catalog.products.review.summary') == 'star_counts'
            ? $this->getTotalRating($product)
            : $this->getTotalReviews($product);
    }

    
    public function getReviewsWithRatings($product)
    {
        return $product->reviews()
            ->where('status', 'approved')
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get();
    }

    
    public function getPercentageRating($product)
    {
        $reviews = $this->getReviewsWithRatings($product);

        $totalReviews = $this->getTotalReviews($product);

        for ($i = 5; $i >= 1; $i--) {
            if (! $reviews->isEmpty()) {
                foreach ($reviews as $review) {
                    if ($review->rating == $i) {
                        $percentage[$i] = round(($review->total / $totalReviews) * 100);

                        break;
                    } else {
                        $percentage[$i] = 0;
                    }
                }
            } else {
                $percentage[$i] = 0;
            }
        }

        return $percentage;
    }
}
