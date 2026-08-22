<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PlatformEarning;
use App\Models\Product;
use App\Models\Rental;
use App\Services\DashboardService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getRevenueChart()
    {
        try {
            $data = $this->dashboardService->getRevenueChartData(7);

            return response()->json([
                'status'  => 'success',
                'message' => 'success getting data from dashboard',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' =>'error in get data from dashboard',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getStats()
    {
        try {
            $startOfThisWeek = Carbon::now()->startOfWeek();
            $endOfThisWeek   = Carbon::now()->endOfWeek();

            $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
            $endOfLastWeek   = Carbon::now()->subWeek()->endOfWeek();

            $startOfThisMonth = Carbon::now()->startOfMonth();
            $endOfThisMonth   = Carbon::now()->endOfMonth();

            $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
            $endOfLastMonth   = Carbon::now()->subMonth()->endOfMonth();

            // ==========================================
            // مصفوفة طلبات الشراء (Orders)
            // ==========================================
            $thisWeekOrders  = Order::whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->count();
            $lastWeekOrders  = Order::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();

            $thisMonthOrders = Order::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
            $lastMonthOrders = Order::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

            // ==========================================
            // مصفوفة عقود الإيجار (Rentals)
            // ==========================================
            $thisWeekRentals  = Rental::whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->count();
            $lastWeekRentals  = Rental::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();

            $thisMonthRentals = Rental::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
            $lastMonthRentals = Rental::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

            // ==========================================
            //  إحصائيات صافي الأرباح (جمع العمولات)
            // ==========================================
            $thisWeekEarnings  = (float) PlatformEarning::whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->sum('commission_amount');
            $lastWeekEarnings  = (float) PlatformEarning::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->sum('commission_amount');

            $thisMonthEarnings = (float) PlatformEarning::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->sum('commission_amount');
            $lastMonthEarnings = (float) PlatformEarning::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('commission_amount');
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'orders' => [
                        'title' => 'orders',
                        'weekly' => [
                            'current_value'     => $thisWeekOrders,
                            'previous_value'    => $lastWeekOrders,
                            'percentage' => $this->calculateGrowthPercentage($thisWeekOrders, $lastWeekOrders),
                        ],
                        'monthly' => [
                            'current_value'     => $thisMonthOrders,
                            'previous_value'    => $lastMonthOrders,
                            'percentage' => $this->calculateGrowthPercentage($thisMonthOrders, $lastMonthOrders),
                        ]
                    ],
                    'rentals' => [
                        'title' => 'rentals',
                        'weekly' => [
                            'current_value'     => $thisWeekRentals,
                            'previous_value'    => $lastWeekRentals,
                            'percentage' => $this->calculateGrowthPercentage($thisWeekRentals, $lastWeekRentals),
                        ],
                        'monthly' => [
                            'current_value'     => $thisMonthRentals,
                            'previous_value'    => $lastMonthRentals,
                            'percentage' => $this->calculateGrowthPercentage($thisMonthRentals, $lastMonthRentals),
                        ],
                    ],
                    'earnings' => [
                        'title' => 'earnings',
                        'weekly' => [
                            'current_value'     => round($thisWeekEarnings, 2),
                            'previous_value'    => round($lastWeekEarnings, 2),
                            'percentage' => $this->calculateGrowthPercentage($thisWeekEarnings, $lastWeekEarnings),
                        ],
                        'monthly' => [
                            'current_value'     => round($thisMonthEarnings, 2),
                            'previous_value'    => round($lastMonthEarnings, 2),
                            'percentage' => $this->calculateGrowthPercentage($thisMonthEarnings, $lastMonthEarnings),
                        ]
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء معالجة الإحصائيات المقارنة.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    private function calculateGrowthPercentage(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }


    /**
     * حساب نسبة العرض مقابل الطلب في المنصة (أسبوعياً، شهرياً، وكلياً)
     */
    public function getSupplyDemandRatio()
    {
        try {
            $startOfWeek  = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $endOfWeek    = Carbon::now()->endOfWeek(Carbon::SUNDAY);

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth   = Carbon::now()->endOfMonth();

            // ==========================================
            // 2. حساب العرض (Supply)
            // ==========================================
            $activeProductsQuery = Product::where('is_active', true)->where('delated', false);

            $overallSupply = (clone $activeProductsQuery)->count();
            $weeklySupply  = (clone $activeProductsQuery)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            $monthlySupply = (clone $activeProductsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            // ==========================================
            // 3. حساب الطلب (Demand)
            // ==========================================
            $completedOrdersQuery = Order::where('status', 'completed');
            $overallOrders = (clone $completedOrdersQuery)->count();
            $weeklyOrders  = (clone $completedOrdersQuery)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            $monthlyOrders = (clone $completedOrdersQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            $activeRentalsQuery = Rental::whereIn('status', ['active', 'completed']);
            $overallRentals = (clone $activeRentalsQuery)->count();
            $weeklyRentals  = (clone $activeRentalsQuery)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            $monthlyRentals = (clone $activeRentalsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            $overallDemand = $overallOrders + $overallRentals;
            $weeklyDemand  = $weeklyOrders + $weeklyRentals;
            $monthlyDemand = $monthlyOrders + $monthlyRentals;

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'conversion' => [
                        'title' => 'conversion',
                        'weekly' => [
                            'supply'     => $weeklySupply,
                            'demand'     => $weeklyDemand,
                            'percentage' => $this->calculateRatio($weeklyDemand, $weeklySupply)
                        ],
                        'monthly' => [
                            'supply'     => $monthlySupply,
                            'demand'     => $monthlyDemand,
                            'percentage' => $this->calculateRatio($monthlyDemand, $monthlySupply)
                        ],
                        'overall' => [
                            'supply'     => $overallSupply,
                            'demand'     => $overallDemand,
                            'percentage' => $this->calculateRatio($overallDemand, $overallSupply)
                        ]
                    ]
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء حساب مؤشر العرض والطلب.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    private function calculateRatio(int $demand, int $supply): float
    {
        if ($supply > 0) {
            return round(($demand / $supply) * 100, 2);
        }
        return 0.0;
    }

    public function getWeeklyPerformance()
    {
        try {
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $endOfWeek   = Carbon::now()->endOfWeek(Carbon::SUNDAY);
            $earnings = PlatformEarning::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(transaction_amount) as total_amount'),
                    DB::raw('SUM(commission_amount) as total_commission')
                )
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $weeklyData = [];
            $maxAmount = 0;

            for ($i = 0; $i < 7; $i++) {
                $currentDate = $startOfWeek->copy()->addDays($i);
                $dateString  = $currentDate->format('Y-m-d');
                $dayName     = $currentDate->format('D'); 

                $amount     = $earnings->has($dateString) ? (float) $earnings->get($dateString)->total_amount : 0.0;
                $commission = $earnings->has($dateString) ? (float) $earnings->get($dateString)->total_commission : 0.0; // 👈 استخراج العمولة

                if ($amount > $maxAmount) {
                    $maxAmount = $amount;
                }

                $weeklyData[] = [
                    'date'              => $dateString,
                    'day'          => $dayName,
                    'revenue1'            => $amount,
                    'commission_revenue1' => $commission, 
                ];
            }

            foreach ($weeklyData as &$day) {
                if ($maxAmount > 0) {
                    $day['activeDotsCount'] = (int) round(($day['revenue1'] / $maxAmount) * 10);
                } else {
                    $day['activeDotsCount'] = 0;
                }

                $day['revenue']     = $this->formatAmountNumber($day['revenue1']);
                $day['commission'] = $this->formatAmountNumber($day['commission_revenue1']);
            }

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'max_amount_in_week' => $maxAmount,
                    'daily_performance'  => $weeklyData
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء جلب ملخص الأداء.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    private function formatAmountNumber(float $number): string
    {
        if ($number >= 1000) {
            return '$' . round($number / 1000, 1) . 'k';
        }
        return '$' . round($number, 2);
    }

    

    public function getTransactionTypes()
    {
        try {
            $data = $this->dashboardService->getTransactionTypesData();

            return response()->json([
                'status'  => 'success',
                'message' => 'تم جلب بيانات توزيع العمليات بنجاح',
                'data'    => $data
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


        public function getQuickStats()
    {
        try {
            $data = $this->dashboardService->getQuickStatsData();

            return response()->json([
                'status'  => 'success',
                'message' => 'تم جلب الإحصائيات السريعة بنجاح',
                'data'    => $data
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    
    public function getTopMachines(Request $request)
    {
        try {
            $type = $request->query('type', 'sale');

            $data = $this->dashboardService->getTopMachinesData($type, 3);

            return response()->json([
                'status'  => 'success',
                'message' => "تم جلب أفضل الآلات بنجاح لحالة: {$type}",
                'data'    => $data
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء جلب بيانات الداشبورد',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

}
