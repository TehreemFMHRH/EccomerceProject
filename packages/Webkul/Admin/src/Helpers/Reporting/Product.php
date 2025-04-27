<?php

namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Customer\Repositories\WishlistRepository;
use Webkul\Marketing\Repositories\SearchTermRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductReviewRepository;
use Webkul\Sales\Repositories\OrderItemRepository;

class Product extends AbstractReporting
{
    
    public function __construct(
        protected ProductInventoryRepository $productInventoryRepository,
        protected WishlistRepository $wishlistRepository,
        protected ProductReviewRepository $reviewRepository,
        protected OrderItemRepository $orderItemRepository,
        protected SearchTermRepository $searchTermRepository
    ) {
        parent::__construct();
    }

    
    public function getTotalSoldQuantitiesProgress()
    {
        return [
            'previous' => $previous = $this->getTotalSoldQuantities($this->lastStartDate, $this->lastEndDate),
            'current'  => $current = $this->getTotalSoldQuantities($this->startDate, $this->endDate),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getPreviousTotalSoldQuantitiesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalSoldQuantitiesOverTime($this->lastStartDate, $this->lastEndDate, $period);
    }

    
    public function getCurrentTotalSoldQuantitiesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalSoldQuantitiesOverTime($this->startDate, $this->endDate, $period);
    }

    
    public function getTotalSoldQuantities($startDate, $endDate): int
    {
        return $this->orderItemRepository
            ->resetModel()
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->value(DB::raw('SUM(qty_invoiced - qty_refunded)')) ?? 0;
    }

    
    public function getTotalProductsAddedToWishlistProgress()
    {
        return [
            'previous' => $previous = $this->getTotalProductsAddedToWishlist($this->lastStartDate, $this->lastEndDate),
            'current'  => $current = $this->getTotalProductsAddedToWishlist($this->startDate, $this->endDate),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getPreviousTotalProductsAddedToWishlistOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalProductsAddedToWishlistOverTime($this->lastStartDate, $this->lastEndDate, $period);
    }

    
    public function getCurrentTotalProductsAddedToWishlistOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalProductsAddedToWishlistOverTime($this->startDate, $this->endDate, $period);
    }

    
    public function getTotalProductsAddedToWishlist($startDate, $endDate): int
    {
        return $this->wishlistRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    
    public function getTotalReviewsProgress(): array
    {
        return [
            'previous' => $previous = $this->getTotalReviews($this->lastStartDate, $this->lastEndDate),
            'current'  => $current = $this->getTotalReviews($this->startDate, $this->endDate),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getTotalReviews($startDate, $endDate): int
    {
        return $this->reviewRepository
            ->resetModel()
            ->leftJoin('product_channels', 'product_reviews.product_id', '=', 'product_channels.product_id')
            ->where('status', 'approved')
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    
    public function getStockThresholdProducts($limit = null): EloquentCollection
    {
        return $this->productInventoryRepository
            ->resetModel()
            ->with(['product', 'product.attribute_family', 'product.attribute_values', 'product.images'])
            ->leftJoin('product_channels', 'product_inventories.product_id', '=', 'product_channels.product_id')
            ->select('*', DB::raw('SUM(qty) as total_qty'))
            ->whereIn('channel_id', $this->channelIds)
            ->groupBy('product_inventories.product_id')
            ->orderBy('total_qty', 'ASC')
            ->limit($limit)
            ->get();
    }

    
    public function getTopSellingProductsByRevenue($limit = null): Collection
    {
        $items = $this->orderItemRepository
            ->resetModel()
            ->with(['product', 'product.attribute_family', 'product.attribute_values', 'product.images'])
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->addSelect('*', DB::raw('SUM(base_total_invoiced - base_amount_refunded) as revenue'))
            ->whereNull('parent_id')
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('order_items.created_at', [$this->startDate, $this->endDate])
            ->having(DB::raw('SUM(base_total_invoiced - base_amount_refunded)'), '>', 0)
            ->groupBy('product_id')
            ->orderBy('revenue', 'DESC')
            ->limit($limit)
            ->get();

        $items = $items->map(function ($item) {
            return [
                'id'                => $item->product_id,
                'name'              => $item->name,
                'price'             => $item->product?->price,
                'formatted_price'   => core()->formatBasePrice($item->price),
                'revenue'           => $item->revenue,
                'formatted_revenue' => core()->formatBasePrice($item->revenue),
                'images'            => $item->product?->images,
            ];
        });

        return $items;
    }

    
    public function getTopSellingProductsByQuantity($limit = null): Collection
    {
        $items = $this->orderItemRepository
            ->resetModel()
            ->with(['product', 'product.attribute_family', 'product.attribute_values', 'product.images'])
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->addSelect('*', DB::raw('SUM(qty_invoiced - qty_refunded) as total_qty_ordered'))
            ->whereNull('parent_id')
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('order_items.created_at', [$this->startDate, $this->endDate])
            ->having(DB::raw('SUM(qty_invoiced - qty_refunded)'), '>', 0)
            ->groupBy('product_id')
            ->orderBy('total_qty_ordered', 'DESC')
            ->limit($limit)
            ->get();

        $items = $items->map(function ($item) {
            return [
                'id'                => $item->product_id,
                'name'              => $item->name,
                'price'             => $item->product?->price,
                'formatted_price'   => core()->formatBasePrice($item->price),
                'total_qty_ordered' => $item->total_qty_ordered,
                'images'            => $item->product?->images,
            ];
        });

        return $items;
    }

    
    public function getProductsWithMostReviews($limit = null): EloquentCollection
    {
        $tablePrefix = DB::getTablePrefix();

        $products = $this->reviewRepository
            ->resetModel()
            ->leftJoin('product_channels', 'product_reviews.product_id', '=', 'product_channels.product_id')
            ->addSelect(
                'product_reviews.product_id',
                DB::raw('COUNT(*) as reviews')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', 'approved')
            ->groupBy('product_reviews.product_id')
            ->orderByDesc('reviews')
            ->limit($limit)
            ->get();

        $products->map(function ($product) {
            $product->product_name = $product->product->name;
        });

        return $products;
    }

    
    public function getLastSearchTerms($limit = null): EloquentCollection
    {
        return $this->searchTermRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('updated_at', [$this->startDate, $this->endDate])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    
    public function getTopSearchTerms($limit = null): EloquentCollection
    {
        return $this->searchTermRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->orderByDesc('uses')
            ->limit($limit)
            ->get();
    }

    
    public function getTotalSoldQuantitiesOverTime($startDate, $endDate, $period = 'auto'): array
    {
        $tablePrefix = DB::getTablePrefix();

        $config = $this->getTimeInterval($startDate, $endDate, $period);

        $groupColumn = str_replace('created_at', "{$tablePrefix}order_items.created_at", $config['group_column']);

        $results = $this->orderItemRepository
            ->resetModel()
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                DB::raw("$groupColumn AS date"),
                DB::raw('COUNT(*) AS total')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get();

        $stats = [];

        foreach ($config['intervals'] as $interval) {
            $t = $results->where('date', $interval['filter'])->first();

            $stats[] = [
                'label' => $interval['start'],
                'total' => $t?->total ?? 0,
            ];
        }

        return $stats;
    }

    
    public function getTotalProductsAddedToWishlistOverTime($startDate, $endDate, $period = 'auto'): array
    {
        $config = $this->getTimeInterval($startDate, $endDate, $period);

        $groupColumn = $config['group_column'];

        $results = $this->wishlistRepository
            ->resetModel()
            ->select(
                DB::raw("$groupColumn AS date"),
                DB::raw('COUNT(*) AS total')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get();

        $stats = [];

        foreach ($config['intervals'] as $interval) {
            $t = $results->where('date', $interval['filter'])->first();

            $stats[] = [
                'label' => $interval['start'],
                'total' => $t?->total ?? 0,
            ];
        }

        return $stats;
    }
}
