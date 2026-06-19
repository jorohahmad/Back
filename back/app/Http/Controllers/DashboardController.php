<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PlatformEarning;
use App\Models\Product;
use App\Models\Rental;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends   Controller
{
    public function getStats()
    {
        try {
            // 1. تهيئة فترات الأسبوع (الحالي والماضي)
            $startOfThisWeek = Carbon::now()->startOfWeek();
            $endOfThisWeek   = Carbon::now()->endOfWeek();

            $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
            $endOfLastWeek   = Carbon::now()->subWeek()->endOfWeek();

            // 2. تهيئة فترات الشهر (الحالي والماضي)
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
            // 🌟 إحصائيات صافي الأرباح (جمع العمولات)
            // ==========================================
            // استخدمنا (float) لضمان تحويل القيمة الراجعة من قاعدة البيانات إلى رقم عشري حقيقي في الـ JSON
            $thisWeekEarnings  = (float) PlatformEarning::whereBetween('created_at', [$startOfThisWeek, $endOfThisWeek])->sum('commission_amount');
            $lastWeekEarnings  = (float) PlatformEarning::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->sum('commission_amount');

            $thisMonthEarnings = (float) PlatformEarning::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->sum('commission_amount');
            $lastMonthEarnings = (float) PlatformEarning::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('commission_amount');
            // 3. بناء الاستجابة المنظمة مع حساب نسب النمو
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
                    // 🌟 الحزمة المالية الجديدة للفرونت إند
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

    private function calculateGrowthPercentage(int $current, int $previous): float
    {
        if ($previous === 0) {
            // إذا كانت الفترة السابقة صفر والحالية أكبر من صفر فالنمو 100%، وإلا فهو 0%
            return $current > 0 ? 100.0 : 0.0;
        }

        // المعادلة الحسابية للنمو وتقريب الناتج لخانين بعد الفاصلة
        return round((($current - $previous) / $previous) * 100, 2);
    }


    /**
     * حساب نسبة العرض مقابل الطلب في المنصة (أسبوعياً، شهرياً، وكلياً)
     */
    public function getSupplyDemandRatio()
    {
        try {
            // 1. تحديد الفترات الزمنية
            $startOfWeek  = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $endOfWeek    = Carbon::now()->endOfWeek(Carbon::SUNDAY);

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth   = Carbon::now()->endOfMonth();

            // ==========================================
            // 2. حساب العرض (Supply)
            // ==========================================
            // استعلام أساسي للمنتجات المعتمدة
            $activeProductsQuery = Product::where('is_active', true)->where('delated', false);

            $overallSupply = (clone $activeProductsQuery)->count();
            $weeklySupply  = (clone $activeProductsQuery)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            $monthlySupply = (clone $activeProductsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            // ==========================================
            // 3. حساب الطلب (Demand)
            // ==========================================
            // الطلبات (Orders)
            $completedOrdersQuery = Order::where('status', 'completed');
            $overallOrders = (clone $completedOrdersQuery)->count();
            $weeklyOrders  = (clone $completedOrdersQuery)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            $monthlyOrders = (clone $completedOrdersQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            // الإيجارات (Rentals)
            $activeRentalsQuery = Rental::whereIn('status', ['active', 'completed']);
            $overallRentals = (clone $activeRentalsQuery)->count();
            $weeklyRentals  = (clone $activeRentalsQuery)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            $monthlyRentals = (clone $activeRentalsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            // إجمالي الطلب لكل فترة
            $overallDemand = $overallOrders + $overallRentals;
            $weeklyDemand  = $weeklyOrders + $weeklyRentals;
            $monthlyDemand = $monthlyOrders + $monthlyRentals;

            // ==========================================
            // 4. بناء الاستجابة المنظمة
            // ==========================================
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
    /**
     * دالة مساعدة لحساب النسبة المئوية ومنع خطأ القسمة على صفر
     */
    private function calculateRatio(int $demand, int $supply): float
    {
        if ($supply > 0) {
            return round(($demand / $supply) * 100, 2);
        }
        return 0.0;
    }
    /**
     * إرجاع ملخص الأداء اليومي للأسبوع الحالي بناءً على حجم المعاملات والعمولات
     */
    public function getWeeklyPerformance()
    {
        try {
            // 1. تحديد بداية ونهاية الأسبوع (يبدأ يوم الإثنين وينتهي يوم الأحد)
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $endOfWeek   = Carbon::now()->endOfWeek(Carbon::SUNDAY);

            // 2. جلب إجمالي المعاملات والعمولات مجمعة حسب اليوم
            $earnings = PlatformEarning::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(transaction_amount) as total_amount'),
                    DB::raw('SUM(commission_amount) as total_commission') // 👈 إضافة حقل العمولة هنا
                )
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $weeklyData = [];
            $maxAmount = 0;

            // 3. بناء هيكل الأيام السبعة (من الإثنين إلى الأحد)
            for ($i = 0; $i < 7; $i++) {
                $currentDate = $startOfWeek->copy()->addDays($i);
                $dateString  = $currentDate->format('Y-m-d');
                $dayName     = $currentDate->format('D'); // تعيد Mon, Tue, Wed...

                // جلب قيمة المعاملات والعمولات لهذا اليوم (أو صفر إذا لم تكن موجودة)
                $amount     = $earnings->has($dateString) ? (float) $earnings->get($dateString)->total_amount : 0.0;
                $commission = $earnings->has($dateString) ? (float) $earnings->get($dateString)->total_commission : 0.0; // 👈 استخراج العمولة

                // تحديث أعلى قيمة في الأسبوع لاعتمادها في حساب القلوب
                if ($amount > $maxAmount) {
                    $maxAmount = $amount;
                }

                $weeklyData[] = [
                    'date'              => $dateString,
                    'day'          => $dayName,
                    'revenue1'            => $amount,
                    'commission_revenue1' => $commission, // 👈 إضافتها للمصفوفة
                ];
            }

            // 4. حساب عدد القلوب (من 0 إلى 10) وتنسيق الأرقام
            foreach ($weeklyData as &$day) {
                if ($maxAmount > 0) {
                    // المعادلة: (قيمة اليوم / أعلى قيمة) * 10
                    $day['activeDotsCount'] = (int) round(($day['revenue1'] / $maxAmount) * 10);
                } else {
                    $day['activeDotsCount'] = 0;
                }

                // إضافة حقول مهيأة للقراءة للفرونت إند
                $day['revenue']     = $this->formatAmountNumber($day['revenue1']);
                $day['commission'] = $this->formatAmountNumber($day['commission_revenue1']); // 👈 تنسيق العمولة أيضاً
            }

            // 5. إرجاع الاستجابة
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

    /**
     * دالة مساعدة لتنسيق المبالغ المالية
     */
    private function formatAmountNumber(float $number): string
    {
        if ($number >= 1000) {
            return '$' . round($number / 1000, 1) . 'k';
        }
        return '$' . round($number, 2);
    }
}
