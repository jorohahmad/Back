<?php

namespace App\Services;

use App\Models\User;
use App\Models\OrderItem;
use App\Models\RentalItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Rating;
class UserService
{
    /**
     * جلب بيانات البائع مع منتجاته المفعلة
     */
    public function getSellerWithActiveProducts(User $seller): User
    {
        $seller->load(['products' => function ($query) {
            $query->with(['items', 'favorites' => function ($q) {
                // جلب حالة المفضلة للمستخدم الحالي
                $q->where('user_id', Auth::id());
            }])
                ->where('is_active', true)
                ->where('delated', false);
        }]);
        $seller->loadAvg('receivedRatings', 'score');

        return $seller;
    }
    /**
     * جلب إحصائيات البائع لوحة التحكم الخاصة به
     */
    public function getSellerStats(User $seller): array
    {
        // 1. إحصائيات عمليات البيع (نجلب العدد والأرباح باستعلام واحد)
        $salesStats = OrderItem::where('order_items.seller_id', $seller->id)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed') // الطلبات المكتملة فقط
            ->select(
                DB::raw('SUM(order_items.quantity) as total_items'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->first();

        // 2. إحصائيات عمليات الإيجار (نجلب العدد والأرباح باستعلام واحد)
        $rentalsStats = RentalItem::where('rental_items.lessor_id', $seller->id)
            ->join('rentals', 'rental_items.rental_id', '=', 'rentals.id')
            ->whereIn('rentals.status', ['active', 'returned']) // الفواتير المعتمدة
            ->select(
                DB::raw('COUNT(rental_items.id) as total_items'),
                DB::raw('SUM(rental_items.rent_days * rental_items.unit_price) as total_revenue')
            )
            ->first();

        // 3. حساب الأرباح الصافية (بعد خصم 2% عمولة المنصة كما في الـ Checkout)
        $netSalesProfit = ($salesStats->total_revenue ?? 0) * 0.98;
        $netRentalsProfit = ($rentalsStats->total_revenue ?? 0) * 0.98;

        $soldItemsCount = $salesStats->total_items ?? 0;
        $rentedItemsCount = $rentalsStats->total_items ?? 0;

        // 4. تحديد مستوى البائع
        $totalTransactions = $soldItemsCount + $rentedItemsCount;
        $sellerLevel = $this->calculateSellerLevel($totalTransactions);

        // 5. إرجاع النتيجة منظمة
        return [
            'level'              => $sellerLevel,
            'join_date'          => $seller->created_at->format('Y-m-d'),
            'image'             => $seller->image ? asset('storage/' . $seller->image) : null,
            // الرصيد الحالي (المتاح للسحب أو الشراء)
            'current_balance'    => $seller->balance,

           
            'numbers'=>[
                round($netSalesProfit + $netRentalsProfit, 2),
                (int) $rentedItemsCount,
                (int) $soldItemsCount
            ]
        ];
    }
    /**
     * خوارزمية تحديد مستوى البائع
     */
    private function calculateSellerLevel(int $totalTransactions): string
    {
        if ($totalTransactions < 10) {
            return 'مبتدئ';
        } elseif ($totalTransactions < 50) {
            return 'متوسط';
        } elseif ($totalTransactions < 100) {
            return 'متقدم';
        } else {
            return 'خبير';
        }
    }

    //Rating
    public function rateUser( User $rater, User $rated, int $score, ?string $comment = null)
    {
        if ($rater->id === $rated->id) {
            throw new \Exception(__('messages.cannot_rate_self'));
        }

        return Rating::updateOrCreate(
            [
                'rater_id' => $rater->id,
                'rated_id' => $rated->id,
            ],
            [
                'score'   => $score,
                'comment' => $comment,
            ]
        );
    }
}
