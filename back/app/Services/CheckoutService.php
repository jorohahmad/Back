<?php

namespace App\Services;

use App\Models\{Cart, Product, ProductItem, Rental, RentalItem, Order, OrderItem, PlatformEarning, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use App\Notifications\NewSaleNotification;
use App\Notifications\NewRentalNotification;

class CheckoutService
{
    /**
     * الدالة الرئيسية التي تدير عملية الدفع بالكامل
     */
    public function processCheckout(int $userId,string $receiveGov,string $receiveOffice): string
    {
        return DB::transaction(function () use ($userId, $receiveGov, $receiveOffice) {
            
            // 1. 🔐 قفل سجل المشتري أولاً! (لمنع مشكلة النقر المزدوج من الموبايل)
            $buyer = User::lockForUpdate()->find($userId);

            // 2. جلب السلة بعد القفل (لكي يرى الطلب الثاني أن السلة أصبحت فارغة)
            $cartItems = Cart::with('product')->where('user_id', $userId)->get();

            if ($cartItems->isEmpty()) {
                throw new Exception("السلة فارغة، لا يمكن إتمام العملية.");
            }

            // 3. حساب الإجمالي الكلي للسلة
            $rentTotal = $cartItems->where('type', 'rent')->sum(fn($item) => $item->unit_price * $item->rent_days);
            $saleTotal = $cartItems->where('type', 'sale')->sum(fn($item) => $item->quantity * $item->unit_price);
            $grandTotal = $rentTotal + $saleTotal;

            if ($buyer->balance < $grandTotal) {
                throw new Exception("رصيدك الحالي ({$buyer->balance}$) غير كافٍ. المطلوب: {$grandTotal}$");
            }
            $buyer->decrement('balance', $grandTotal);

            $transactionId = (string) Str::uuid();
            $rentItems = $cartItems->where('type', 'rent');
            $saleItems = $cartItems->where('type', 'sale');

            if ($rentItems->isNotEmpty()) {
                $this->processRentals($rentItems, $userId, $transactionId, $receiveGov, $receiveOffice);
            }

            if ($saleItems->isNotEmpty()) {
                $this->processSales($saleItems, $userId, $transactionId, $receiveGov, $receiveOffice);
            }

            // 4. مسح السلة بعد نجاح الدفع وتوزيع الأرباح
            Cart::where('user_id', $userId)->delete();

            return $transactionId;
        });
    }

    private function processRentals($rentItems, int $userId, string $transactionId, string $receiveGov, string $receiveOffice): void
    {
        $productItemIds = $rentItems->pluck('product_item_id')->toArray();

        // 🔐 قفل القطع العينية لمنع حجزها من شخصين في نفس اللحظة
        $lockedItems = ProductItem::whereIn('id', $productItemIds)->lockForUpdate()->get();
        foreach ($lockedItems as $lockedItem) {
            if ($lockedItem->status !== 'active') {
                throw new Exception("عذراً، القطعة ({$lockedItem->serial_number}) تم حجزها للتو.");
            }
        }

        $totalRentPrice = $rentItems->sum(fn($item) => $item->unit_price * $item->rent_days);

        $rental = Rental::create([
            'renter_id'      => $userId,
            'transaction_id' => $transactionId,
            'total_price'    => $totalRentPrice,
            'status'         => 'active',
            'receive_governorate' => $receiveGov,
            'receive_office'      => $receiveOffice
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
        
        // 💰 توزيع الأرباح الصافية على أصحاب الآلات (المؤجرين)
        $this->distributeEarningsToSellers($rentItems, 'rent', $rental->id);
    }

    private function processSales($saleItems, int $userId, string $transactionId, string $receiveGov, string $receiveOffice): void
    {
        $productIds = $saleItems->pluck('product_id')->unique()->toArray();

        // 🔐 قفل المنتجات لمنع تضارب المخزون
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
            'status'         => 'completed',
            'receive_governorate' => $receiveGov,
            'receive_office'      => $receiveOffice
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
        
        // 💰 توزيع الأرباح الصافية على البائعين
        $this->distributeEarningsToSellers($saleItems, 'sale', $order->id);
    }

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

    /**
     * دالة مساعدة لتجميع وتوزيع الأرباح الصافية على أصحاب المنتجات وإرسال الإشعارات
     */
    private function distributeEarningsToSellers($items, string $type, int $modelId): void
    {
        $sellerEarnings = [];

        foreach ($items as $item) {
            $ownerId = $item->product->owner_id;
            
            $itemTotal = $type === 'sale' 
                ? $item->quantity * $item->unit_price 
                : $item->unit_price * $item->rent_days;
                
            $netAmount = $itemTotal * 0.98; 

            if (!isset($sellerEarnings[$ownerId])) {
                $sellerEarnings[$ownerId] = 0;
            }
            $sellerEarnings[$ownerId] += $netAmount;
        }

        foreach ($sellerEarnings as $sellerId => $netAmount) {
            // جلب البائع
            $seller = User::find($sellerId);
            
            if ($seller) {
                // 1. زيادة الرصيد
                $seller->increment('balance', $netAmount);
                
                // 2. 🔔 إرسال الإشعار اللحظي لقاعدة بيانات البائع
                if ($type === 'sale') {
                    $seller->notify(new NewSaleNotification($modelId, $netAmount));
                } elseif ($type === 'rent') {
                    $seller->notify(new NewRentalNotification($modelId, $netAmount));
                }
            }
        }
    }
}