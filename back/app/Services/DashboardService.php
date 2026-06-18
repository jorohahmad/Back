<?php

namespace App\Services;

use App\Models\PlatformEarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService{
    // خطوط بيانية ارادات الايجارات و المبيعات و العمولة %2
    /**
     * جلب بيانات مخطط الإيرادات لآخر X يوم
     */
    public function getRevenueChartData(int $days = 7): array
    {
        // 1. تحديد نطاق التاريخ (منذ 7 أيام وحتى اليوم)
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate   = Carbon::now()->endOfDay();

        // 2. جلب كل الأرباح في هذا النطاق باستعلام واحد فقط
        $earnings = PlatformEarning::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            // تجميع البيانات حسب تاريخ اليوم (Y-m-d)
            ->groupBy(fn($item) => $item->created_at->format('Y-m-d'));

        $chartData = [];

        // 3. بناء المصفوفة النهائية (نمر على الأيام يوماً بيوم لضمان عدم وجود فجوات في المخطط البياني)
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayName = Carbon::now()->subDays($i)->format('D'); // اسم اليوم (Sat, Sun...)

            // جلب حركات هذا اليوم، وإذا لم يوجد نضع مصفوفة فارغة
            $dayEarnings = $earnings->get($date, collect([]));

            $chartData[] = [
                'date'            => $date,
                'day_name'        => strtoupper($dayName), // لتسهيل عرضها على المحور السيني (X-Axis)
                'sales_revenue'   => $dayEarnings->where('type', 'sale')->sum('transaction_amount'),
                'rentals_revenue' => $dayEarnings->where('type', 'rental')->sum('transaction_amount'),
                'platform_profit' => $dayEarnings->sum('commission_amount'),
            ];
        }

        return $chartData;
    }

    // مخطط الدونات توزيع عمليات البيع و الاجار 

    /**
     * جلب بيانات مخطط الدونات (توزيع العمليات بين بيع وإيجار)
     */
    public function getTransactionTypesData(): array
    {
        // 1. نطلب من قاعدة البيانات تجميع العمليات وعدّها حسب النوع مباشرة (أداء صاروخي)
        $counts = PlatformEarning::select('type', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        // 2. استخراج الأرقام (مع وضع 0 كقيمة افتراضية لو لم تكن هناك عمليات بعد)
        $salesCount   = $counts->get('sale', 0);
        $rentalsCount = $counts->get('rental', 0);
        $totalTransactions = $salesCount + $rentalsCount;

        // 3. حساب النسب المئوية (مع حماية الكود من خطأ القسمة على صفر إذا كان التطبيق جديداً)
        $salesPercentage   = 0;
        $rentalsPercentage = 0;

        if ($totalTransactions > 0) {
            $salesPercentage   = round(($salesCount / $totalTransactions) * 100, 1);
            $rentalsPercentage = round(($rentalsCount / $totalTransactions) * 100, 1);
        }

        // 4. إرجاع البيانات مهيأة للفرونت إند
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

    // يطاقات الارقام السريعة "متوسط مده الايجار و اجمالي القطغ المؤجرة حاليا"و

    /**
     * جلب بيانات البطاقات السريعة (KPIs)
     */
    public function getQuickStatsData()
    {

        // 1. حساب متوسط مدة الإيجار (Average Rent Days)
        // افترضنا أن الطلبات المكتملة تُحفظ في جدول (OrderItems) ولديك حقل (rent_days).
        // استخدم الدالة avg() لتجعل قاعدة البيانات تحسب المتوسط بسرعة فائقة.
        $averageRentDays = \App\Models\RentalItem::avg('rent_days');

        // 2. إجمالي القطع المؤجرة حالياً خارج المستودع (Currently Rented Items)
        // في السلة كنا نبحث عن القطع 'active'، القطع المؤجرة غالباً حالتها 'rented' أو 'in_use' حسب ما سميته في نظامك.
        $currentlyRentedCount = \App\Models\ProductItem::where('status', 'rented')->count();

        return [
            'average_rent_days'      => round($averageRentDays ?? 0, 1), // تقريب لرقم عشري واحد (مثلاً 4.5)
            'currently_rented_items' => $currentlyRentedCount
        ];
    }

    // الرسم البياني الشريطي (الاقسام الاكثر مبيعا و الاقسام الاكثر تأجيرا)
    
    /**
     * جلب أفضل الآلات أداءً مع التأكد من حالة الفاتورة الأساسية (مكتملة/نشطة)
     */
    public function getTopMachinesData(string $type, int $limit = 5)
    {
        if ($type === 'rent') {
            // ==================================================
            // حالة الإيجار: نربط التفاصيل مع فاتورة الإيجار الأساسية
            // ==================================================
            return DB::table('products')
                ->join('product_items', 'products.id', '=', 'product_items.product_id')
                ->join('rental_items', 'product_items.id', '=', 'rental_items.product_item_id')
                // 👇 الإضافة السحرية: نربط مع الفاتورة الأساسية
                ->join('rentals', 'rental_items.rental_id', '=', 'rentals.id')
                ->select(
                    'products.id as product_id',
                    'products.title as machine_name',
                    DB::raw('SUM(rental_items.unit_price * rental_items.rent_days) as total_earnings')
                )
                // 👇 نحسب الأرباح فقط للفواتير المعتمدة (حسب اللوجيك الخاص بك)
                ->where('rentals.status', 'active') 
                ->groupBy('products.id', 'products.title')
                ->orderByDesc('total_earnings')
                ->limit($limit)
                ->get();
        }

        // ==================================================
        // حالة البيع: نربط التفاصيل مع فاتورة الطلب الأساسية
        // ==================================================
        return DB::table('products')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            // 👇 الإضافة السحرية: نربط مع الفاتورة الأساسية
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'products.id as product_id',
                'products.title as machine_name',
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as total_earnings')
            )
            // 👇 نحسب الأرباح فقط للطلبات المكتملة
            ->where('orders.status', 'completed') 
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('total_earnings')
            ->limit($limit)
            ->get();
    }

}