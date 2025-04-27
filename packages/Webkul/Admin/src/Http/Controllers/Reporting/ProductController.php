<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

class ProductController extends Controller
{
    
    protected $typeFunctions = [
        'total-sold-quantities'            => 'getTotalSoldQuantitiesStats',
        'total-products-added-to-wishlist' => 'getTotalProductsAddedToWishlistStats',
        'top-selling-products-by-revenue'  => 'getTopSellingProductsByRevenue',
        'top-selling-products-by-quantity' => 'getTopSellingProductsByQuantity',
        'products-with-most-reviews'       => 'getProductsWithMostReviews',
        'products-with-most-visits'        => 'getProductsWithMostVisits',
        'last-search-terms'                => 'getLastSearchTerms',
        'top-search-terms'                 => 'getTopSearchTerms',
    ];

    
    public function index()
    {
        return view('admin::reporting.products.index')->with([
            'startDate' => $this->reportingHelper->getStartDate(),
            'endDate'   => $this->reportingHelper->getEndDate(),
        ]);
    }

    
    public function view()
    {
        return view('admin::reporting.view')->with([
            'entity'    => 'products',
            'startDate' => $this->reportingHelper->getStartDate(),
            'endDate'   => $this->reportingHelper->getEndDate(),
        ]);
    }
}
