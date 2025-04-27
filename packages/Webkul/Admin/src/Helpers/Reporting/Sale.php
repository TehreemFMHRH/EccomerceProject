<?php

namespace Webkul\Admin\Helpers\Reporting;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\RefundRepository;

class Sale extends AbstractReporting
{
    
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemRepository $orderItemRepository,
        protected InvoiceRepository $invoiceRepository,
        protected RefundRepository $refundRepository
    ) {
        parent::__construct();
    }

    
    public function getTotalOrdersProgress()
    {
        return [
            'previous' => $previous = $this->getTotalOrders($this->lastStartDate, $this->lastEndDate),
            'current'  => $current = $this->getTotalOrders($this->startDate, $this->endDate),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getPreviousTotalOrdersOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalOrdersOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    
    public function getCurrentTotalOrdersOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalOrdersOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    
    public function getTotalOrders($startDate, $endDate): int
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    
    public function getTotalOrdersOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'COUNT(*)',
            $period
        );
    }

    
    public function getTodayOrdersProgress(): array
    {
        return [
            'previous' => $previous = $this->getTotalOrders(now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'current'  => $current = $this->getTotalOrders(now()->today(), now()->endOfDay()),
            'progress' => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getTodayOrders()
    {
        return $this->orderRepository
            ->resetModel()
            ->with(['addresses', 'payment', 'items'])
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('orders.created_at', [now()->today(), now()->endOfDay()])
            ->get();
    }

    
    public function getTotalSalesProgress(): array
    {
        return [
            'previous'        => $previous = $this->getTotalSales($this->lastStartDate, $this->lastEndDate),
            'current'         => $current = $this->getTotalSales($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress'        => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getSubTotalSalesProgress(): array
    {
        return [
            'previous'        => $previous = $this->getSubTotalSales($this->lastStartDate, $this->lastEndDate),
            'current'         => $current = $this->getSubTotalSales($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress'        => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getTodaySalesProgress(): array
    {
        return [
            'previous'        => $previous = $this->getTotalSales(now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'current'         => $current = $this->getTotalSales(now()->today(), now()->endOfDay()),
            'formatted_total' => core()->formatBasePrice($current),
            'progress'        => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getTotalSales($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('base_grand_total_invoiced - base_grand_total_refunded'));
    }

    
    public function getSubTotalSales($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('base_sub_total_invoiced - base_sub_total_refunded'));
    }

    
    public function getPreviousTotalSalesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalSalesOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    
    public function getCurrentTotalSalesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTotalSalesOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    
    public function getTotalSalesOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'SUM(base_grand_total_invoiced - base_grand_total_refunded)',
            $period
        );
    }

    
    public function getAverageSalesProgress(): array
    {
        return [
            'previous'        => $previous = $this->getAverageSales($this->lastStartDate, $this->lastEndDate),
            'current'         => $current = $this->getAverageSales($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress'        => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getAverageSales($startDate, $endDate): ?float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg(DB::raw('base_grand_total_invoiced - base_grand_total_refunded'));
    }

    
    public function getPreviousAverageSalesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getAverageSalesOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    
    public function getCurrentAverageSalesOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getAverageSalesOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    
    public function getAverageSalesOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'AVG(base_grand_total_invoiced - base_grand_total_refunded)',
            $period
        );
    }

    
    public function getRefundsProgress(): array
    {
        return [
            'previous'        => $previous = $this->getRefunds($this->lastStartDate, $this->lastEndDate),
            'current'         => $current = $this->getRefunds($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress'        => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getRefunds($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('base_grand_total_refunded'));
    }

    
    public function getPreviousRefundsOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getRefundsOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    
    public function getCurrentRefundsOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getRefundsOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    
    public function getRefundsOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'SUM(base_grand_total_refunded)',
            $period
        );
    }

    
    public function getTaxCollectedProgress(): array
    {
        return [
            'previous'        => $previous = $this->getTaxCollected($this->lastStartDate, $this->lastEndDate),
            'current'         => $current = $this->getTaxCollected($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress'        => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getTaxCollected($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('base_tax_amount_invoiced - base_tax_amount_refunded'));
    }

    
    public function getPreviousTaxCollectedOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTaxCollectedOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    
    public function getCurrentTaxCollectedOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getTaxCollectedOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    
    public function getTaxCollectedOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'SUM(base_tax_amount_invoiced - base_tax_amount_refunded)',
            $period
        );
    }

    
    public function getTopTaxCategories($limit = null): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        return $this->orderItemRepository
            ->resetModel()
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('tax_categories', 'order_items.tax_category_id', '=', 'tax_categories.id')
            ->select('tax_categories.id as tax_category_id', 'tax_categories.name')
            ->addSelect(DB::raw("SUM({$tablePrefix}order_items.base_tax_amount_invoiced - {$tablePrefix}order_items.base_tax_amount_refunded) as total"))
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('order_items.created_at', [$this->startDate, $this->endDate])
            ->whereNotNull('tax_category_id')
            ->groupBy('tax_category_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    
    public function getShippingCollectedProgress(): array
    {
        return [
            'previous'        => $previous = $this->getShippingCollected($this->lastStartDate, $this->lastEndDate),
            'current'         => $current = $this->getShippingCollected($this->startDate, $this->endDate),
            'formatted_total' => core()->formatBasePrice($current),
            'progress'        => $this->getPercentageChange($previous, $current),
        ];
    }

    
    public function getShippingCollected($startDate, $endDate): float
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum(DB::raw('base_shipping_invoiced - base_shipping_refunded'));
    }

    
    public function getPreviousShippingCollectedOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getShippingCollectedOverTime($this->lastStartDate, $this->lastEndDate, $period, $includeEmpty);
    }

    
    public function getCurrentShippingCollectedOverTime($period = 'auto', $includeEmpty = true): array
    {
        return $this->getShippingCollectedOverTime($this->startDate, $this->endDate, $period, $includeEmpty);
    }

    
    public function getShippingCollectedOverTime($startDate, $endDate, $period, $includeEmpty): array
    {
        return $this->getOverTimeStats(
            $startDate,
            $endDate,
            'SUM(base_shipping_invoiced - base_shipping_refunded)',
            $period
        );
    }

    
    public function getTopShippingMethods($limit = null): Collection
    {
        return $this->orderRepository
            ->resetModel()
            ->select('shipping_title as title')
            ->addSelect(DB::raw('SUM(base_shipping_invoiced - base_shipping_refunded) as total'))
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereNotNull('shipping_method')
            ->groupBy('shipping_method')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    
    public function getTopPaymentMethods($limit = null): Collection
    {
        return $this->orderRepository
            ->resetModel()
            ->leftJoin('order_payment', 'orders.id', '=', 'order_payment.order_id')
            ->select('method', 'method_title as title')
            ->addSelect(DB::raw('COUNT(*) as total'))
            ->addSelect(DB::raw('SUM(base_grand_total) as base_total'))
            ->whereIn('orders.channel_id', $this->channelIds)
            ->whereBetween('orders.created_at', [$this->startDate, $this->endDate])
            ->groupBy('method')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    
    public function getTotalPendingInvoicesAmount(): float
    {
        return $this->invoiceRepository->getTotalPendingInvoicesAmount();
    }

    
    public function getTotalUniqueOrdersUsers($startDate, $endDate): int
    {
        return $this->orderRepository
            ->resetModel()
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('CONCAT(customer_email, "-", customer_id)'))
            ->get()
            ->count();
    }

    
    public function getOverTimeStats($startDate, $endDate, $valueColumn, $period = 'auto'): array
    {
        $config = $this->getTimeInterval($startDate, $endDate, $period);

        $groupColumn = $config['group_column'];

        $results = $this->orderRepository
            ->resetModel()
            ->select(
                DB::raw("$groupColumn AS date"),
                DB::raw("$valueColumn AS total"),
                DB::raw('COUNT(*) AS count')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get();

        foreach ($config['intervals'] as $interval) {
            $t = $results->where('date', $interval['filter'])->first();

            $stats[] = [
                'label' => $interval['start'],
                'total' => $t?->total ?? 0,
                'count' => $t?->count ?? 0,
            ];
        }

        return $stats ?? [];
    }
}
