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
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewProductCreatedAdminNotification;

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
        $user = Auth::user();
        $data['is_active'] = ($user->role === 'admin');

        $product = $this->productService->createProduct($user->id, $data);
        if ($user->role !== 'admin') {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new NewProductCreatedAdminNotification($product->title, $user->name));
        }
        return response()->json([
            'message' => __('messages.product_created_successfully'),
            'product' => $product,
        ], 201);
    }

    public function indexForSale(\Illuminate\Http\Request $request)
    {
        $perPage = $request->query('per_page', 5);

        $products = Product::with(['items', 'owner', 'favorites' => function ($query) {
            $query->where('user_id', Auth::id());
        }])
            ->where('owner_id', '!=', Auth::id())
            ->where('is_for_sale', true)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->latest()
            ->paginate($perPage);
        return ProductSaleResource::collection($products);
    }

    public function indexForRent(\Illuminate\Http\Request $request)
    {
        $perPage = $request->query('per_page', 2);

        $products = Product::with(['items', 'owner', 'favorites' => function ($query) {
            $query->where('user_id', Auth::id());
        }])
            ->where('owner_id', '!=', Auth::id())
            ->where('is_for_rent', true)
            ->where('is_active', true)
            ->paginate($perPage);
        return ProductRentalResource::collection($products);
    }

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
