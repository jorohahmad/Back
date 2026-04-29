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
            'image1' => 'nullable|image|max:2048',
            'image2' => 'nullable|image|max:2048',
            'image3' => 'nullable|image|max:2048',
            'audio' => 'nullable|max:10240',
            'is_for_sale' => 'boolean',
            'sale_price' => 'required_if:is_for_sale,1|numeric|min:0',
            'is_for_rent' => 'boolean',
            'rent_price_daily' => 'required_if:is_for_rent,1|numeric|min:0',
            'count' => 'required|integer|min:1',
            'condition' => 'required|in:new,used',
        ]);
        // create product
        $product = new Product();
        $product->owner_id = Auth::user()->id;
        $product->title = $request->title;
        $product->description = $request->description;
        if ($request->has('image1')) {
            for ($i = 1; $i <= 3; $i++) {
                if ($request->hasFile('image' . $i)) {
                    $s = saveFile($request->file('image' . $i), 'imageInst');
                    $product->{'image' . $i} = $s;
                }
            }
        }
        if ($request->has('audio')) {
            $fileName = saveFile($request->file('audio'), 'audioInst');
            $product->audio = $fileName;
        }

        $product->is_for_sale = $request->is_for_sale ?? false;
        $product->sale_price = $request->sale_price;
        $product->is_for_rent = $request->is_for_rent ?? false;
        $product->rent_price_daily = $request->rent_price_daily;
        $product->save();
        // create product items
        for ($i = 0; $i < $request->count; $i++) {
            $p = $product->items()->create([
                'serial_number' => rand(1, 999999),
                'condition' => $request->condition,
                'status' =>'active',
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
        $products = Product::with('items')->where('is_for_sale', true)->get();
        return response()->json($products);
    }
    public function indexForRent()
    {
        $products = Product::with('items')->with('owner')->where('is_for_rent', true)->get()->pluck('items')->flatten();
        return response()->json($products);
    }
}
