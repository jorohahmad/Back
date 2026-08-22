<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\PriceOffer;
use App\Models\ProductItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

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

    private function addSaleToCart(int $userId, Product $product, int $quantity)
    {
        if (!$product->is_for_sale) {
            throw new Exception("هذا المنتج غير متاح للبيع.");
        }
        $acceptedOffer = PriceOffer::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->where('type', 'sale')
            ->where('status', 'accepted')
            ->first();

        $finalPrice = $acceptedOffer ? $acceptedOffer->proposed_price : ($product->sale_price ?? 0);

        $existingCartItem = Cart::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->where('type', 'sale')
            ->first();

        $currentQuantity = $existingCartItem ? $existingCartItem->quantity : 0;
        $totalRequestedQuantity = $currentQuantity + $quantity;

        if ($product->stock < $totalRequestedQuantity) {
            throw new Exception("الكمية المطلوبة غير متوفرة. المتاح حالياً إضافة: " . ($product->stock - $currentQuantity));
        }

        if ($existingCartItem) {
            $existingCartItem->update([
                'quantity' => $totalRequestedQuantity,
                'unit_price' => $finalPrice,
                ]);
        } else {
            Cart::create([
                'user_id'    => $userId,
                'product_id' => $product->id,
                'type'       => 'sale',
                'quantity'   => $quantity,
                'unit_price' => $finalPrice,
            ]);
        }
    }
    // ==========================================
    // 2. معالجة طلبات الإيجار (Rent)
    // ==========================================
    private function addRentToCart(int $userId, Product $product, int $quantity, string $startDate, string $endDate)
    {
        if (!$product->is_for_rent) {
            throw new Exception("هذا المنتج غير متاح للإيجار.");
        }

        $acceptedOffer = PriceOffer::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->where('type', 'rent')
            ->where('status', 'accepted')
            ->first();

        $finalPrice = $acceptedOffer ? $acceptedOffer->proposed_price : ($product->rent_price_daily ?? 0);

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $rentDays = $start->diffInDays($end) ?: 1;

        $itemsAlreadyInCarts = Cart::whereNotNull('product_item_id')->pluck('product_item_id')->toArray();

        $availableItems = ProductItem::where('product_id', $product->id)
            ->where('status', 'active')
            ->whereNotIn('id', $itemsAlreadyInCarts)
            ->take($quantity)
            ->get();

        if ($availableItems->count() < $quantity) {
            throw new Exception("لا يوجد قطع كافية متاحة للإيجار حالياً. المتاح: " . $availableItems->count());
        }

        $cartData = [];
        foreach ($availableItems as $item) {
            $cartData[] = [
                'user_id'         => $userId,
                'product_id'      => $product->id,
                'product_item_id' => $item->id, 
                'type'            => 'rent',
                'quantity'        => 1, 
                'unit_price'      => $finalPrice,
                'rent_start_date' => $start->toDateString(),
                'rent_end_date'   => $end->toDateString(),
                'rent_days'       => $rentDays,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }
        Cart::insert($cartData);
    }


    // view cart
    public function getFormattedCart(int $userId): array
    {
        $cartItems = Cart::with('product.items')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return [
                'items'       => [],
                'grand_total' => 0
            ];
        }

        $groupedCart = $this->groupAndFormatCartItems($cartItems);

        return [
            'items'       => $groupedCart,
            'grand_total' => $groupedCart->sum('total_price'),
        ];
    }

    private function groupAndFormatCartItems(Collection $cartItems)
    {
        return $cartItems->groupBy(function ($item) {
            return $this->generateGroupKey($item);
        })->map(function ($group) {
            return $this->formatSingleGroup($group);
        })->values();
    }

    private function generateGroupKey($item): string
    {
        return $item->type === 'sale'
            ? "sale_{$item->product_id}"
            : "rent_{$item->product_id}_{$item->rent_start_date}_{$item->rent_end_date}";
    }

    private function formatSingleGroup(Collection $group): array
    {
        $firstItem     = $group->first();
        $totalQuantity = $firstItem->type === 'sale'
            ? $group->sum('quantity')
            : $group->count();

        if ($firstItem->type === 'sale') {
            $itemsCount = $firstItem->product?->stock ?? 0;
            $availableRentItemIds = []; 
        } else {
            $activeItems = $firstItem->product ? $firstItem->product->items->where('status', 'active') : collect([]);
            $itemsCount  = $activeItems->count();
            $availableRentItemIds = $activeItems->pluck('id')->toArray();
        }
        return [
            'cart_ids'        => $group->pluck('id')->toArray(),
            'product_id'      => $firstItem->product_id,
            'product_name'    => $firstItem->product->title ?? 'منتج غير معروف',
            'product_image'   => asset('storage/' . $firstItem->product->image1) ?? null,
            'type'            => $firstItem->type,
            'unit_price'      => $firstItem->unit_price,
            'quantity'        => $totalQuantity,
            'items_count'     => $itemsCount,
            'total_price'     => $this->calculateTotalPrice($firstItem, $totalQuantity),
            'rent_start_date' => $firstItem->rent_start_date,
            'rent_end_date'   => $firstItem->rent_end_date,
            'rent_days'       => $firstItem->rent_days,
        ];
    }

    private function calculateTotalPrice($item, int $quantity): float
    {
        $totalPrice = $item->type === 'sale'
            ? $item->unit_price * $quantity
            : $item->unit_price * $quantity * $item->rent_days;
        return $totalPrice;
    }

    //update cart
    public function updateItemQuantity(int $userId, array $cartIds, string $action, int $steps = 1): void
    {
        $referenceCartId = $cartIds[0];
        $cartItem = Cart::where('user_id', $userId)->findOrFail($referenceCartId);
        $product = Product::findOrFail($cartItem->product_id);

        // ==========================================
        // حالة الشراء (Sale): تعديل حقل quantity فقط
        // ==========================================
        if ($cartItem->type === 'sale') {
            if ($action === 'increase') {
                if ($product->stock < ($cartItem->quantity + $steps)) {
                    throw new Exception("عذراً، الكمية المطلوبة غير متوفرة. المتاح في المخزن: " . $product->stock);
                }

                $cartItem->increment('quantity', $steps);
            } elseif ($action === 'decrease') {
                if (($cartItem->quantity - $steps) >= 1) {
                    $cartItem->decrement('quantity', $steps);
                } else {
                    throw new Exception("لا يمكن إنقاص الكمية عن 1. استخدم زر الحذف لإزالة العنصر من السلة.");
                }
            }
        }
        // ==========================================
        // حالة الإيجار (Rent): إضافة أو حذف أسطر كاملة
        // ==========================================
        elseif ($cartItem->type === 'rent') {
            if ($action === 'increase') {
                $itemsAlreadyInCarts = Cart::whereNotNull('product_item_id')->pluck('product_item_id')->toArray();

                $availableItems = ProductItem::where('product_id', $product->id)
                    ->where('status', 'active')
                    ->whereNotIn('id', $itemsAlreadyInCarts)
                    ->limit($steps) 
                    ->get();

                if ($availableItems->count() < $steps) {
                    throw new Exception("عذراً، لا يوجد قطع إضافية كافية للإيجار. المتاح للإضافة: " . $availableItems->count());
                }

                foreach ($availableItems as $item) {
                    $newCartItem = $cartItem->replicate();
                    $newCartItem->product_item_id = $item->id;
                    $newCartItem->quantity = 1;
                    $newCartItem->created_at = now();
                    $newCartItem->updated_at = now();
                    $newCartItem->save();
                }
            } elseif ($action === 'decrease') {
                if (count($cartIds) >= $steps) {
                    $idsToDelete = array_slice($cartIds, -$steps);
                    Cart::whereIn('id', $idsToDelete)->where('user_id', $userId)->delete();
                } else {
                    throw new Exception("لا يمكن إنقاص الكمية عن 1. استخدم زر الحذف لإزالة العنصر بالكامل.");
                }
            }
        }
    }
}
