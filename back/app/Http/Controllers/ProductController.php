<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductRentalResource;
use App\Http\Resources\ProductSaleResource;
use App\Models\Product;
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
        $userId = Auth::user()->id;
        // create product
        $product = $this->productService->createProduct($userId, $data);
        return response()->json([
            'message' => 'Product created successfully',
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
}
