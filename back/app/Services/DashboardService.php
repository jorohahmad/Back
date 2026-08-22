<?php

namespace App\Services;

use App\Models\PlatformEarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService{

    public function getRevenueChartData(int $days = 7): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate   = Carbon::now()->endOfDay();

        $earnings = PlatformEarning::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($item) => $item->created_at->format('Y-m-d'));

        $chartData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayName = Carbon::now()->subDays($i)->format('D');

            $dayEarnings = $earnings->get($date, collect([]));

            $chartData[] = [
                'date'            => $date,
                'day_name'        => strtoupper($dayName), 
                'sales_revenue'   => $dayEarnings->where('type', 'sale')->sum('transaction_amount'),
                'rentals_revenue' => $dayEarnings->where('type', 'rental')->sum('transaction_amount'),
                'platform_profit' => $dayEarnings->sum('commission_amount'),
            ];
        }

        return $chartData;
    }


    public function getTransactionTypesData(): array
    {
        $counts = PlatformEarning::select('type', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $salesCount   = $counts->get('sale', 0);
        $rentalsCount = $counts->get('rental', 0);
        $totalTransactions = $salesCount + $rentalsCount;

        $salesPercentage   = 0;
        $rentalsPercentage = 0;

        if ($totalTransactions > 0) {
            $salesPercentage   = round(($salesCount / $totalTransactions) * 100, 1);
            $rentalsPercentage = round(($rentalsCount / $totalTransactions) * 100, 1);
        }

        return [
            'total_transactions' => $totalTransactions,
            'distribution' => [
                'sales' => [
                    'count'      => $salesCount,
                    'percentage' => $salesPercentage,
                ],
                'rentals' => [
                    'count'      => $rentalsCount,
                    'percentage' => $rentalsPercentage,
                ]
            ]
        ];
    }


   
    public function getQuickStatsData()
    {

        $averageRentDays = \App\Models\RentalItem::avg('rent_days');

        $currentlyRentedCount = \App\Models\ProductItem::where('status', 'rented')->count();

        return [
            'average_rent_days'      => round($averageRentDays ?? 0, 1),
            'currently_rented_items' => $currentlyRentedCount
        ];
    }

    public function getTopMachinesData(string $type, int $limit = 5)
    {
        if ($type === 'rent') {

            return DB::table('products')
                ->join('product_items', 'products.id', '=', 'product_items.product_id')
                ->join('rental_items', 'product_items.id', '=', 'rental_items.product_item_id')
                ->join('rentals', 'rental_items.rental_id', '=', 'rentals.id')
                ->select(
                    'products.id as product_id',
                    'products.title as machine_name',
                    DB::raw('SUM(rental_items.unit_price * rental_items.rent_days) as total_earnings')
                )
                ->where('rentals.status', 'active') 
                ->groupBy('products.id', 'products.title')
                ->orderByDesc('total_earnings')
                ->limit($limit)
                ->get();
        }

        return DB::table('products')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'products.id as product_id',
                'products.title as machine_name',
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as total_earnings')
            )
            ->where('orders.status', 'completed') 
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('total_earnings')
            ->limit($limit)
            ->get();
    }

}