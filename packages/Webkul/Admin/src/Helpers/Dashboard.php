<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Webkul\Admin\Helpers\Reporting\Customer;
use Webkul\Admin\Helpers\Reporting\Product;
use Webkul\Admin\Helpers\Reporting\Sale;
use Webkul\Admin\Helpers\Reporting\Visitor;

class Dashboard
{
    
    public function __construct(
        protected Sale $saleReporting,
        protected Product $productReporting,
        protected Customer $customerReporting,
        protected Visitor $visitorReporting
    ) {}

    
    public function getOverAllStats(): array
    {
        return [
            'total_customers'       => $this->customerReporting->getTotalCustomersProgress(),
            'total_orders'          => $this->saleReporting->getTotalOrdersProgress(),
            'total_sales'           => $this->saleReporting->getTotalSalesProgress(),
            'avg_sales'             => $this->saleReporting->getAverageSalesProgress(),
            'total_unpaid_invoices' => [
                'total'           => $t = $this->saleReporting->getTotalPendingInvoicesAmount(),
                'formatted_total' => core()->formatBasePrice($t),
            ],
        ];
    }

    
    public function getTodayStats(): array
    {
        $orders = $this->saleReporting->getTodayOrders();

        $orders = $orders->map(function ($o) {
            return [
                'id'                         => $o->id,
                'increment_id'               => $o->id,
                'status'                     => $o->status,
                'status_label'               => $o->status_label,
                'payment_method'             => core()->getConfigData('sales.payment_methods.'.$o->payment->method.'.title'),
                'base_grand_total'           => $o->base_grand_total,
                'formatted_base_grand_total' => core()->formatPrice($o->base_grand_total),
                'channel_name'               => $o->channel_name,
                'customer_email'             => $o->customer_email,
                'customer_name'              => $o->customer_full_name,
                'items'                      => view('admin::sales.orders.items', compact('order'))->render(),
                'billing_address'            => $o?->billing_address->city.($o?->billing_address->country ? ', '.core()->country_name($o?->billing_address->country) : ''),
                'created_at'                 => $o->created_at->format('d M Y, H:i:s'),
            ];
        });

        return [
            'total_sales'     => $this->saleReporting->getTodaySalesProgress(),
            'total_orders'    => $this->saleReporting->getTodayOrdersProgress(),
            'total_customers' => $this->customerReporting->getTodayCustomersProgress(),
            'orders'          => $orders,
        ];
    }

    
    public function getStockThresholdProducts()
    {
        $products = $this->productReporting->getStockThresholdProducts(5);

        $products = $products->map(function ($product) {
            return [
                'id'              => $product->product_id,
                'sku'             => $product->product->sku,
                'name'            => $product->product->name,
                'price'           => $product->product->price,
                'formatted_price' => core()->formatPrice($product->product->price),
                'total_qty'       => $product->total_qty,
                'image'           => $product->product->base_image_url,
            ];
        });

        return $products;
    }

    
    public function getSalesStats(): array
    {
        return [
            'total_orders' => $this->saleReporting->getTotalOrdersProgress(),
            'total_sales'  => $this->saleReporting->getTotalSalesProgress(),
            'over_time'    => $this->saleReporting->getCurrentTotalSalesOverTime(),
        ];
    }

    
    public function getVisitorStats(): array
    {
        return [
            'total'     => $this->visitorReporting->getTotalVisitorsProgress(),
            'unique'    => $this->visitorReporting->getTotalUniqueVisitorsProgress(),
            'over_time' => $this->visitorReporting->getCurrentTotalVisitorsOverTime(),
        ];
    }

    
    public function getTopSellingProducts(): Collection
    {
        return $this->productReporting->getTopSellingProductsByRevenue(5);
    }

    
    public function getTopCustomers(): EloquentCollection
    {
        $customers = $this->customerReporting->getCustomersWithMostSales(5);

        $customers->map(function ($k) {
            $k->formatted_total = core()->formatBasePrice($k->total);
        });

        return $customers;
    }

    
    public function getStartDate(): Carbon
    {
        return $this->saleReporting->getStartDate();
    }

    
    public function getEndDate(): Carbon
    {
        return $this->saleReporting->getEndDate();
    }

    
    public function getDateRange(): string
    {
        return $this->getStartDate()->format('d M').' - '.$this->getEndDate()->format('d M');
    }
}
