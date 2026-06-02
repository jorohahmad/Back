<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Http\Requests\deleteCartRequest;
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

    public function viewCart()
    {
        $userId = Auth::user()->id;
        try {
            $cartData = $this->cartService->getFormattedCart($userId);

            if (empty($cartData['items'])) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'السلة فارغة حالياً.',
                    'data'    => [],
                    'grand_total' => 0
                ], 200);
            }
            return response()->json([
                'status'      => 'success',
                'data'        => $cartData['items'],
                'grand_total' => $cartData['grand_total'],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء جلب محتويات السلة',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function deleteFromCart(deleteCartRequest $request)
    {
        try {
            $userId = Auth::user()->id;

            $deletedCount = Cart::where('user_id', $userId)
                ->whereIn('id', $request->cart_ids)
                ->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'لم يتم العثور على العناصر أو أنك لا تملك صلاحية حذفها.'
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'تم حذف العناصر من السلة بنجاح.',
                'deleted_count' => $deletedCount
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء محاولة الحذف من السلة.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
