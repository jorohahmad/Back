<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    function addToCart(CartRequest $request)
    {
        $user = auth()->user;
        $validated = $request->validated();

        // تحقق من وجود المنتج
        $product =Product::find($validated['product_id']);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // تحديد السعر بناءً على نوع العملية
        $unitPrice = $validated['type'] === 'sale' ? $product->sale_price : $product->rent_price_daily;

        // حساب عدد أيام الإيجار إذا كان النوع rent
        $rentDays = null;
        if ($validated['type'] === 'rent') {
            $rentStartDate = \Carbon\Carbon::parse($validated['rent_start_date']);
            $rentEndDate = \Carbon\Carbon::parse($validated['rent_end_date']);
            $rentDays = $rentStartDate->diffInDays($rentEndDate) + 1; // +1 لحساب اليوم الأول
        }

        // إنشاء أو تحديث عنصر السلة
        $cartItem =Cart::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $validated['product_id'],
                'type' => $validated['type'],
                'status' => 'active',
            ],
            [
                'quantity' => $validated['quantity'],
                'unit_price' => $unitPrice,
                'rent_start_date' => $validated['type'] === 'rent' ? $validated['rent_start_date'] : null,
                'rent_end_date' => $validated['type'] === 'rent' ? $validated['rent_end_date'] : null,
                'rent_days' => $rentDays,
            ]
        );

        return response()->json(['message' => 'Item added to cart', 'cart_item' => $cartItem], 201);
    }
}
