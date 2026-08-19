<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Http\Requests\deleteCartRequest;
use App\Http\Requests\UpdateCart;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformEarning;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ReceiptService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewCheckoutAdminNotification;

class CartController extends Controller
{
    protected $cartService;
    protected $receiptService;
    protected $checkoutService;

    public function __construct(CartService $cartService, ReceiptService $receiptService, CheckoutService $checkoutService)
    {
        $this->cartService = $cartService;
        $this->receiptService = $receiptService;
        $this->checkoutService = $checkoutService;
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'receive_governorate' => 'required|string|max:100',
            'receive_office'      => 'required|string|max:100',
        ]);
        try {
            $userId = Auth::user()->id;
            $transactionId = $this->checkoutService->processCheckout(
                $userId,
                $validated['receive_governorate'],
                $validated['receive_office']
            );
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new NewCheckoutAdminNotification($transactionId, auth()->user()->name));

            return response()->json([
                'status'         => 'success',
                'transaction_id' => $transactionId,
                'message'        => __('messages.checkout_successful')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.checkout_error'),
                'error'   => $e->getMessage()
            ], 400);
        }
    }

    public function addToCart(CartRequest $request)
    {
        $validated = $request->validated();
        try {
            $userId = Auth::user()->id;
            $this->cartService->addToCart($userId, $validated);
            return response()->json([
                'status'  => 'success',
                'message' => __('messages.added_to_cart_successfully')
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
                    'message'     => __('messages.cart_is_empty'),
                    'data'    => [],
                    'grand_total' => doubleval(0)
                ], 200);
            }
            return response()->json([
                'status'      => 'success',
                'data'        => $cartData['items'],
                'grand_total' => doubleval($cartData['grand_total']),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.error_fetching_cart'),
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
                    'message' => __('messages.items_not_found_or_unauthorized')
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message'       => __('messages.items_deleted_from_cart'),
                'deleted_count' => $deletedCount
            ], 204);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.error_deleting_from_cart'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function updateQuantity(UpdateCart $request)
    {
        try {
            $userId = Auth::user()->id;

            $steps = $request->validated('steps') ?? 1;

            $this->cartService->updateItemQuantity(
                $userId,
                $request->validated('cart_ids'), // استخدام validated() لضمان الأمان
                $request->validated('action'),
                $steps
            );
            return response()->json([
                'status'  => 'success',
                'message' => __('messages.quantity_updated_successfully')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getReceiptByTransactionId(string $transactionId)
    {
        try {
            $userId = Auth::user()->id;

            // تمرير المهمة للخدمة
            $receipt = $this->receiptService->getReceiptDetails($transactionId, $userId);

            return response()->json([
                'status' => 'success',
                'data'   => $receipt
            ], 200);
        } catch (Exception $e) {
            // تحديد كود الخطأ (404 إذا لم يتم العثور عليه، أو 500 للأخطاء العامة)
            $statusCode = $e->getCode() === 404 ? 404 : 500;

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function getUserReceipts()
    {
        try {
            $userId = Auth::user()->id;

            // تمرير المهمة للخدمة لتتولى الاستعلام والترتيب
            $receipts = $this->receiptService->getAllUserReceipts($userId);

            return response()->json([
                'status' => 'success',
                'data'   => $receipts
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.error_fetching_receipts'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
