<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Http\Requests\deleteCartRequest;
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
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function checkout(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            return DB::transaction(function () use ($userId) {
                // 1. جلب عناصر سلة المستخدم الحالي
                $cartItems = Cart::with('product')->where('user_id', $userId)->get();

                if ($cartItems->isEmpty()) {
                    throw new Exception("السلة فارغة، لا يمكن إتمام العملية.");
                }

                // فصل عناصر البيع عن عناصر الإيجار لعمل معالجة مخصصة وقفل منفصل لكل نوع
                $saleItems = $cartItems->where('type', 'sale');
                $rentItems = $cartItems->where('type', 'rent');

                // ==========================================================
                // أولاً: معالجة الإيجار (Rentals) مع القفل المتشائم للقطع العينية
                // ==========================================================
                if ($rentItems->count() > 0) {
                    $productItemIds = $rentItems->pluck('product_item_id')->toArray();
                    // قفل سطور القطع الملموسة لمنع أي مستخدم آخر من حجزها أو قراءتها للتحقق
                    $lockedItems = ProductItem::whereIn('id', $productItemIds)
                        ->lockForUpdate()
                        ->get();

                    // التحقق من أن جميع القطع المحجوزة لا تزال متاحة بنسبة 100%
                    foreach ($lockedItems as $lockedItem) {
                        if ($lockedItem->status !== 'active') {
                            throw new Exception("عذراً، القطعة ذات الرقم التسلسلي ({$lockedItem->serial_number}) تم حجزها للتو من قبل مستخدم آخر.");
                        }
                    }
                    // حساب إجمالي عقد الإيجار (السعر اليومي * الأيام) لكل السطور المضافة
                    $totalRentPrice = $rentItems->sum(fn($item) => $item->unit_price * $item->rent_days);

                    // إنشاء غلاف العقد الرئيسي
                    $rental = Rental::create([
                        'renter_id' => $userId,
                        'total_price' => $totalRentPrice,
                        'status' => 'active'
                    ]);
                    // تجهيز تفاصيل أصناف العقد الفرعية لادراجها دفعة واحدة
                    $rentalItemsData = [];
                    foreach ($rentItems as $item) {
                        $rentalItemsData[] = [
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
                    }
                    RentalItem::insert($rentalItemsData);
                    // تحويل حالة القطع في المخزن إلى مؤجرة لحظرها رسمياً
                    ProductItem::whereIn('id', $productItemIds)->update(['status' => 'rented']);
                    // حساب واستقطاع عمولة التطبيق (2%) من الإيجار
                    PlatformEarning::create([
                        'rental_id'          => $rental->id,
                        'transaction_amount' => $rental->total_price,
                        'commission_amount'  => $rental->total_price * 0.02,
                        'type'               => 'rental',
                    ]);
                }
                // ==========================================================
                // ثانياً: معالجة الشراء (Orders) مع القفل المتشائم للمخزن (Stock)
                // ==========================================================
                if ($saleItems->count() > 0) {
                    $productIds = $saleItems->pluck('product_id')->unique()->toArray();
                    // قفل سطور المنتجات لمنع تضارب تعديل الكمية وحماية المخزن من الأرصدة السالبة
                    $lockedProducts = Product::whereIn('id', $productIds)
                        ->lockForUpdate()
                        ->get();
                    // التحقق من كفاية المخزن قبل سحب أي قطعة للبيع
                    foreach ($lockedProducts as $lockedProduct) {
                        $requestedQuantity = $saleItems->where('product_id', $lockedProduct->id)->sum('quantity');

                        if ($lockedProduct->stock < $requestedQuantity) {
                            throw new Exception("عذراً، الكمية المطلوبة من '{$lockedProduct->title}' غير متوفرة حالياً في المخزن.");
                        }
                    }
                    // حساب إجمالي الفاتورة (السعر * الكمية)
                    $totalSalePrice = $saleItems->sum(fn($item) => $item->quantity * $item->unit_price);
                    // إنشاء الفاتورة الرئيسية
                    $order = Order::create([
                        'buyer_id' => $userId,
                        'total_price' => $totalSalePrice,
                        'status' => 'completed'
                    ]);
                    // ترحيل البيانات وتخزين تفاصيل الأصناف المشتراة لحفظ السجلات
                    $orderItemsData = [];
                    foreach ($saleItems as $item) {
                        $orderItemsData[] = [
                            'order_id'   => $order->id,
                            'product_id' => $item->product_id,
                            'seller_id'  => $item->product->owner_id,
                            'quantity'   => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    OrderItem::insert($orderItemsData);
                    // خصم الكميات المباعة من المخزون بأمان تام
                    foreach ($lockedProducts as $lockedProduct) {
                        $requestedQuantity = $saleItems->where('product_id', $lockedProduct->id)->sum('quantity');
                        $lockedProduct->decrement('stock', $requestedQuantity);
                    }
                    // حساب واستقطاع عمولة التطبيق (2%) من المبيعات
                    PlatformEarning::create([
                        'order_id'           => $order->id,
                        'transaction_amount' => $order->total_price,
                        'commission_amount'  => $order->total_price * 0.02,
                        'type'               => 'sale',
                    ]);
                }
                // ==========================================================
                // ثالثاً: مسح السلة وتصفيرها بعد نجاح كافة العمليات بأمان
                // ==========================================================
                Cart::where('user_id', $userId)->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'تمت عملية الدفع وتوثيق العقود والعمولات بنجاح فائق!'
                ], 200);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء عملية الدفع.',
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
            ], 204);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء محاولة الحذف من السلة.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
