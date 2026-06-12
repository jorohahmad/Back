<?php

namespace App\Http\Controllers;

use App\Http\Resources\productAdmin;
use App\Models\Product;
use Illuminate\Http\Request;

class adminProductController extends Controller
{
    public function index()
    {
        $allproduct = Product::where('is_active', false)->where('delated', false);
        return productAdmin::collection($allproduct);
    }

    public function accept(Request $request)
    {
        $product = Product::find($request->id);
        $product->is_active = true;
        $product->save();
        return response()->json(['message' => 'accepted successful'], 200);
    }
    public function reject(Request $request)
    {
        $product = Product::find($request->id);
        $product->is_active = false;
        $product->delated = true;
        $product->save();
        return response()->json(['message' => 'rejected successful'], 200);
    }

}
