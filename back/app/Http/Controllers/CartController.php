<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductItem;
use App\Services\CartService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function addToCart(CartRequest $request)
    {
        $validated = $request->validated();
        try {
            $userId = Auth::user()->id;
            $this->cartService->addToCart($userId, $validated);
            return response()->json([
                'status'  => 'success',
                'message' => 'تمت إضافة العنصر إلى السلة بنجاح.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
