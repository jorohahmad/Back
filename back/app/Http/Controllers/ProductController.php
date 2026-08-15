<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductRentalResource;
use App\Http\Resources\ProductSaleResource;
use App\Models\Product;
use App\Http\Resources\productAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductService;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function create(ProductRequest $request)
    {
        $data = $request->validated();
        // 1. جلب المستخدم الحالي
        $user = Auth::user(); 
        
        // 2. التحقق من الصلاحية: إذا كان أدمن يتم التفعيل فوراً، وإلا يحتاج لموافقة
        $data['is_active'] = ($user->role === 'admin');

        // 3. إنشاء المنتج عبر تمرير معرف المستخدم والبيانات
        $product = $this->productService->createProduct($user->id, $data);
        return response()->json([
            'message' => __('messages.product_created_successfully'),
            'product' => $product,
        ], 201);
    }

    public function indexForSale()
    {
        $products = Product::with(['items', 'owner', 'favorites' => function ($query) {
            $query->where('user_id', Auth::id());
        }])
            ->where('is_for_sale', true)->where('is_active', true)
            ->get();
        return ProductSaleResource::collection($products);
    }
    public function indexForRent()
    {
        $products = Product::with(['items', 'owner', 'favorites' => function ($query) {
            $query->where('user_id', Auth::id());
        }])->where('is_for_rent', true)->where('is_active', true)->get();
        return ProductRentalResource::collection($products);
    } //$products = Product::with(['items', 'owner'])

    public function viewProductsForAdmin()
    {
        $allproduct = Product::with(['items', 'owner']) 
            ->whereHas('owner', function ($query) {
                $query->where('role', 'admin');
            })
            ->where('is_active', true) 
            ->get();

        return productAdmin::collection($allproduct);
    }
}
