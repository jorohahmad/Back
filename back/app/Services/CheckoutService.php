<?php

namespace App\Services;

use App\Models\{Cart, Product, ProductItem, Rental, RentalItem, Order, OrderItem, PlatformEarning};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CheckoutService
{

    /**
     * الدالة الرئيسية التي تدير عملية الدفع بالكامل
     */
    public function processCheckout(int $userId): string
    {
        return DB::transaction(function () use ($userId) {
            $cartItems = Cart::with('product')->where('user_id', $userId)->get();

            if ($cartItems->isEmpty()) {
                throw new Exception("السلة فارغة، لا يمكن إتمام العملية.");
            }

            $transactionId = (string) Str::uuid();
            $rentItems = $cartItems->where('type', 'rent');
            $saleItems = $cartItems->where('type', 'sale');

            if ($rentItems->isNotEmpty()) {
                $this->processRentals($rentItems, $userId, $transactionId);
            }

            if ($saleItems->isNotEmpty()) {
                $this->processSales($saleItems, $userId, $transactionId);
            }

            // مسح السلة بعد النجاح
            Cart::where('user_id', $userId)->delete();

            return $transactionId;
        });
    }
    /**
     * معالجة الإيجارات بشكل منفصل
     */
    private function processRentals($rentItems, int $userId, string $transactionId): void
    {
        $productItemIds = $rentItems->pluck('product_item_id')->toArray();

        $lockedItems = ProductItem::whereIn('id', $productItemIds)->lockForUpdate()->get();

        foreach ($lockedItems as $lockedItem) {
            if ($lockedItem->status !== 'active') {
                throw new Exception("عذراً، القطعة ذات الرقم التسلسلي ({$lockedItem->serial_number}) تم حجزها للتو.");
            }
        }

        $totalRentPrice = $rentItems->sum(fn($item) => $item->unit_price * $item->rent_days);

        $rental = Rental::create([
            'renter_id'      => $userId,
            'transaction_id' => $transactionId,
            'total_price'    => $totalRentPrice,
            'status'         => 'active'
        ]);

        $rentalItemsData = $rentItems->map(function ($item) use ($rental) {
            return [
                'rental_id'       => $rental->id,
                'lessor_id'       => $item->product->owner_id,
                'product_item_id' => $item->product_item_id,
                'start_date'      => $item->rent_start_date,
                'end_date'        => $item->rent_end_date,
                'rent_days'       => $item->rent_days,
                'unit_price'      => $item->unit_price,
                'status'          => 'rented',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        })->toArray();

        RentalItem::insert($rentalItemsData);
        ProductItem::whereIn('id', $productItemIds)->update(['status' => 'rented']);

        $this->recordCommission($rental->id, null, $totalRentPrice, 'rental');
    }

    /**
     * معالجة المشتريات بشكل منفصل
     */
    private function processSales($saleItems, int $userId, string $transactionId): void
    {
        $productIds = $saleItems->pluck('product_id')->unique()->toArray();

        $lockedProducts = Product::whereIn('id', $productIds)->lockForUpdate()->get();

        foreach ($lockedProducts as $lockedProduct) {
            $requestedQuantity = $saleItems->where('product_id', $lockedProduct->id)->sum('quantity');
            if ($lockedProduct->stock < $requestedQuantity) {
                throw new Exception("الكمية المطلوبة من '{$lockedProduct->title}' غير متوفرة حالياً.");
            }
        }

        $totalSalePrice = $saleItems->sum(fn($item) => $item->quantity * $item->unit_price);

        $order = Order::create([
            'buyer_id'       => $userId,
            'transaction_id' => $transactionId,
            'total_price'    => $totalSalePrice,
            'status'         => 'completed'
        ]);

        $orderItemsData = $saleItems->map(function ($item) use ($order) {
            return [
                'order_id'   => $order->id,
                'product_id' => $item->product_id,
                'seller_id'  => $item->product->owner_id,
                'quantity'   => $item->quantity,
                'unit_price' => $item->unit_price,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        OrderItem::insert($orderItemsData);

        foreach ($lockedProducts as $lockedProduct) {
            $requestedQuantity = $saleItems->where('product_id', $lockedProduct->id)->sum('quantity');
            $lockedProduct->decrement('stock', $requestedQuantity);
        }

        $this->recordCommission(null, $order->id, $totalSalePrice, 'sale');
    }
    /**
     * دالة مساعدة لتسجيل العمولات (DRY Principle)
     */
    private function recordCommission(?int $rentalId, ?int $orderId, float $amount, string $type): void
    {
        PlatformEarning::create([
            'rental_id'          => $rentalId,
            'order_id'           => $orderId,
            'transaction_amount' => $amount,
            'commission_amount'  => $amount * 0.02,
            'type'               => $type,
        ]);
    }
}
