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

        return [
            'transaction_id' => $transactionId,
            'date'           => $date->format('Y-m-d H:i:s'),
            'grand_total'    => $orderTotal + $rentalTotal,
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
}
