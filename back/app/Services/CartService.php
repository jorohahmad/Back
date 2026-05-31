<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductItem;
use Carbon\Carbon;
use Exception;

class CartService
{
    public function addToCart(int $userId, array $data)
    {
        $product = Product::find($data['product_id']);
        if ($data['type'] === 'sale') {
            return $this->addSaleToCart($userId, $product, $data['quantity']);
        } elseif ($data['type'] === 'rent') {
            return $this->addRentToCart($userId, $product, $data['quantity'], $data['rent_start_date'], $data['rent_end_date']);
        }
    }
    // ==========================================
    // 1. معالجة طلبات الشراء (Sale)
    // ==========================================

    private function addSaleToCart($userId, $product, $quantity)
    {
        if (!$product->is_for_sale) {
            throw new Exception("هذا المنتج غير متاح للبيع.");
        }

        $existingCartItem = Cart::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->where('type', 'sale')
            ->first();

        $currentQuantity = $existingCartItem ? $existingCartItem->quantity : 0;
        $totalRequestedQuantity = $currentQuantity + $quantity;

        // التحقق من كفاية المخزون
        if ($product->stock < $totalRequestedQuantity) {
            throw new Exception("الكمية المطلوبة غير متوفرة. المتاح حالياً إضافة: " . ($product->stock - $currentQuantity));
        }

        if ($existingCartItem) {
            // تحديث الكمية إذا كان موجوداً
            $existingCartItem->update(['quantity' => $totalRequestedQuantity]);
        } else {
            // إنشاء سطر جديد في السلة
            Cart::create([
                'user_id'    => $userId,
                'product_id' => $product->id,
                'type'       => 'sale',
                'quantity'   => $quantity,
                'unit_price' => $product->sale_price ?? 0,
            ]);
        }
    }
    // ==========================================
    // 2. معالجة طلبات الإيجار (Rent)
    // ==========================================
    private function addRentToCart($userId, $product, $quantity, $startDate, $endDate)
    {
        if (!$product->is_for_rent) {
            throw new Exception("هذا المنتج غير متاح للإيجار.");
        }
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $rentDays = $start->diffInDays($end) ?: 1;

        // استخراج القطع المحجوزة مسبقاً في سلات المستخدمين الآخرين لمنع اختيارها
        $itemsAlreadyInCarts = Cart::whereNotNull('product_item_id')->pluck('product_item_id')->toArray();

        // جلب القطع العينية النشطة والمتاحة
        $availableItems = ProductItem::where('product_id', $product->id)
            ->where('status', 'active')
            ->whereNotIn('id', $itemsAlreadyInCarts)
            ->take($quantity)
            ->get();

        if ($availableItems->count() < $quantity) {
            throw new Exception("لا يوجد قطع كافية متاحة للإيجار حالياً. المتاح: " . $availableItems->count());
        }

        // تجهيز مصفوفة لإنشاء عدة أسطر بضربة واحدة (Bulk Insert)
        $cartData = [];
        foreach ($availableItems as $item) {
            $cartData[] = [
                'user_id'         => $userId,
                'product_id'      => $product->id,
                'product_item_id' => $item->id, // ربط القطعة العينية
                'type'            => 'rent',
                'quantity'        => 1, // الكمية في سطر الإيجار دائماً 1
                'unit_price'      => $product->rent_price_daily ?? 0, // افترضنا وجود حقل rent_price
                'rent_start_date' => $start->toDateString(),
                'rent_end_date'   => $end->toDateString(),
                'rent_days'       => $rentDays,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }
        Cart::insert($cartData);
    }
}
