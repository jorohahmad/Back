<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Rental;
use Exception;

class ReceiptService
{
    /**
     * جلب تفاصيل الإيصال الموحد
     */
    public function getReceiptDetails(string $transactionId, int $userId): array
    {
        $order = Order::with('orderItems.product')
            ->where('transaction_id', $transactionId)
            ->where('buyer_id', $userId)
            ->first();

        $rental = Rental::with('rentalItems.productItem.product')
            ->where('transaction_id', $transactionId)
            ->where('renter_id', $userId)
            ->first();

        if (!$order && !$rental) {
            throw new Exception('الإيصال غير موجود أو لا تملك صلاحية للوصول إليه.', 404);
        }

        return $this->buildReceiptArray($transactionId, $order, $rental);
    }

    /**
     * بناء الهيكل النهائي للإيصال
     */
    private function buildReceiptArray(string $transactionId, ?Order $order, ?Rental $rental): array
    {
        $orderTotal  = $order ? $order->total_price : 0;
        $rentalTotal = $rental ? $rental->total_price : 0;
        $date        = $order ? $order->created_at : $rental->created_at;
        $transferStatus = $order ? $order->transfer_status : ($rental->transfer_status ?? 'pending');
        $expectedArrival = $order ? $order->expected_arrival_at : ($rental->expected_arrival_at ?? null);
        $remainingTime = '00:00:00';
        if ($expectedArrival && $transferStatus === 'onWay') {
            $target = \Carbon\Carbon::parse($expectedArrival);
            if ($target->isFuture()) {
                $diff = now()->diff($target); // حساب الفرق بين الآن ووقت الوصول
                $hours = ($diff->days * 24) + $diff->h; // جمع الأيام إن وجدت مع الساعات
                // تنسيق الوقت إلى HH:mm:ss
                $remainingTime = sprintf('%02d:%02d:%02d', $hours, $diff->i, $diff->s); 
            }
        }
        return [
            'transaction_id' => $transactionId,
            'date'           => $date->format('Y-m-d H:i:s'),
            'grand_total'    => $orderTotal + $rentalTotal,
            'remaining_time'      => $remainingTime,
            'transfer_status'     => $transferStatus,
            'sales'          => $this->formatSales($order),
            'rentals'        => $this->formatRentals($rental),
        ];
    }

    /**
     * تنسيق مصفوفة المشتريات
     */
    private function formatSales(?Order $order): ?array
    {
        if (!$order) {
            return null;
        }

        // 👈 سطر الحماية واستخدام اسم العلاقة الصحيح حسب الموديل: orderItems
        $items = $order->orderItems ?? collect([]);

        return [
            'order_id'    => $order->id,
            'total_price' => $order->total_price,
            'items'       => $items->map(function ($item) {
                return [
                    'product_name' => $item->product->title ?? 'منتج غير معروف',
                    'product_image' => $item->product->image1 ? asset('storage/' . $item->product->image1) : null,
                    'user_name'    => $item->product->owner->name ?? 'مستخدم غير معروف',
                    'user_id' => $item->product->owner->id ?? null,
                    'quantity'     => $item->quantity,
                    'unit_price'   => $item->unit_price,
                ];
            })->toArray(),
        ];
    }

    /**
     * تنسيق مصفوفة الإيجارات
     */
    private function formatRentals(?Rental $rental): ?array
    {
        if (!$rental) {
            return null;
        }

        // 👈 سطر الحماية واستخدام اسم العلاقة الصحيح حسب الموديل: rentalItems
        $items = $rental->rentalItems ?? collect([]);

        return [
            'rental_id'   => $rental->id,
            'total_price' => $rental->total_price,
            'items'       => $items->map(function ($item) {
                return [
                    // الانتباه هنا أيضاً لسلسلة العلاقات لتجنب أخطاء null أخرى
                    'product_name' => $item->productItem->product->title ?? 'عنصر غير معروف',
                    'product_image' => $item->productItem->product->image1 ? asset('storage/' . $item->productItem->product->image1) : null,
                    'user_name'    => $item->lessor->name ?? 'مستخدم غير معروف',
                    'user_id' => $item->lessor->id ?? null,
                    'rent_days'    => $item->rent_days,
                    'start_date'   => $item->start_date,
                    'end_date'     => $item->end_date,
                    'unit_price'   => $item->unit_price,
                ];
            })->toArray(),
        ];
    }

    public function getAllUserReceipts(int $userId)
    {
        // 1. جلب المشتريات (تعديل item إلى orderItems لتطابق الموديل)
        $orders = Order::with('orderItems.product')
            ->where('buyer_id', $userId)
            ->whereNotNull('transaction_id')
            ->get()
            ->keyBy('transaction_id');

        // 2. جلب الإيجارات (تعديل item إلى rentalItems لتطابق الموديل)
        $rentals = Rental::with('rentalItems.productItem.product')
            ->where('renter_id', $userId)
            ->whereNotNull('transaction_id')
            ->get()
            ->keyBy('transaction_id');

        // 3. دمج أرقام المعاملات بدون تكرار
        $transactionIds = $orders->keys()->merge($rentals->keys())->unique();

        $receipts = [];

        // 4. بناء هيكل كل إيصال باستخدام الدالة المساعدة الموجودة مسبقاً! (إعادة استخدام الكود DRY)
        foreach ($transactionIds as $transactionId) {
            $order  = $orders->get($transactionId);
            $rental = $rentals->get($transactionId);

            $receipts[] = $this->buildReceiptArray($transactionId, $order, $rental);
        }

        // 5. ترتيب الإيصالات من الأحدث إلى الأقدم بناءً على حقل date
        usort($receipts, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $receipts;
    }

    public function getAllReceiptsForAdmin()
    {
        // 1. جلب المشتريات والإيجارات مع بيانات صاحب الفاتورة فقط (أسرع وأخف على الداتابيز)
        $orders =Order::with('buyer')
            ->whereNotNull('transaction_id')
            ->get()
            ->keyBy('transaction_id');

        $rentals = Rental::with('renter')
            ->whereNotNull('transaction_id')
            ->get()
            ->keyBy('transaction_id');

        $transactionIds = $orders->keys()->merge($rentals->keys())->unique();
        $receipts = [];

        foreach ($transactionIds as $transactionId) {
            $order  = $orders->get($transactionId);
            $rental = $rentals->get($transactionId);

            // 2. استخراج المالك، التاريخ، والحالة
            $owner = null;
            $date = null;
            $transferStatus = 'pending';
            $expectedArrival = null;

            if ($order) {
                $owner = $order->buyer;
                $date = $order->created_at;
                $transferStatus = $order->transfer_status;
                $expectedArrival = $order->expected_arrival_at;
            } elseif ($rental) {
                $owner = $rental->renter;
                $date = $rental->created_at;
                $transferStatus = $rental->transfer_status;
                $expectedArrival = $rental->expected_arrival_at;
            }

            // 3. حساب الوقت المتبقي بدقة
            $remainingTime = '00:00:00';
            if ($expectedArrival && $transferStatus === 'onWay') {
                $target = \Carbon\Carbon::parse($expectedArrival);
                if ($target->isFuture()) {
                    $diff = now()->diff($target);
                    $hours = ($diff->days * 24) + $diff->h;
                    $remainingTime = sprintf('%02d:%02d:%02d', $hours, $diff->i, $diff->s);
                }
            }

            // 4. بناء الهيكل المطابق للصورة تماماً
            $receipts[] = [
                'transaction_id'  => $transactionId,
                'date'            => $date ? $date->format('Y-m-d H:i:s') : null,
                'grand_total'     => (float) (($order ? $order->total_price : 0) + ($rental ? $rental->total_price : 0)),
                'remaining_time'  => $remainingTime,
                'transfer_status' => $transferStatus,
                'invoice_owner'   => $owner ? [
                    'id'    => $owner->id,
                    'name'  => $owner->name,
                    'email' => $owner->email,
                    'phone' => $owner->phone ?? null, 
                    'image' => $owner->imagePersonal ? asset('storage/' . $owner->imagePersonal) : null,
                ] : null,
            ];
        }

        // 5. الترتيب من الأحدث إلى الأقدم
        usort($receipts, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $receipts;
    }
}
