<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'audio' => 'nullable|max:10240',
            'is_for_sale' => 'boolean',
            'sale_price' => 'required_if:is_for_sale,1|numeric|min:0',
            'is_for_rent' => 'boolean',
            'rent_price_daily' => 'required_if:is_for_rent,1|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.condition' => 'required|in:new,used',
            'items.*.status' => 'required|in:active,pending,rented,sold',
        ]);
        // create product
        $product = new Product();
        $product->owner_id = Auth::user()->id;
        $product->title = $request->title;
        $product->description = $request->description;
        if ($request->has('image')) {
            $image = $request->image;
            $extension = $image->getClientOriginalExtension();
            $fileName = time() . rand(1, 1000) . '.' . $extension;
            $image->move(public_path("imageInst"), $fileName);
            $product->image = $fileName;
        }
        if ($request->has('audio')) {
            $audio = $request->audio;
            $extension = $audio->getClientOriginalExtension();
            $fileName = time() . rand(1, 1000) . '.' . $extension;
            $audio->move(public_path("audioInst"), $fileName);
            $product->audio = $fileName;
        }

        $product->is_for_sale = $request->is_for_sale ?? false;
        $product->sale_price = $request->sale_price;
        $product->is_for_rent = $request->is_for_rent ?? false;
        $product->rent_price_daily = $request->rent_price_daily;
        $product->save();
        // create product items
        foreach ($request->items as $itemData) {
            $p = $product->items()->create([
                'serial_number' => 'TEMP',
                'condition' => $itemData['condition'],
                'status' => $itemData['status'],
            ]);

            $p->update([
                'serial_number' => $p->id
            ]);
        }
        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
            'items' => $product->items,
        ], 201);
    }

    public function indexForSale()
    {
        $products = Product::where('is_for_sale', true)->with('items')->get();
        return response()->json($products);
    }
    public function indexForRent()
    {
        $products = Product::where('is_for_rent', true)->with('items')->get();
        return response()->json($products);
    }
}
