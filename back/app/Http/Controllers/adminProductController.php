<?php

namespace App\Http\Controllers;

use App\Http\Resources\productAdmin;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Notifications\ProductApprovedNotification;
use App\Notifications\ProductRejectedNotification;
use App\Notifications\NewProductAnnouncementNotification;
use App\Models\PlatformEarning;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
class adminProductController extends Controller
{
    public function index()
    {
        $allproduct = Product::where('is_active', false)->where('delated', false)->get();
        return productAdmin::collection($allproduct);
    }

    public function accept(Request $request)
    {
        DB::transaction(function () use ($request) {
            
            $product = Product::findOrFail($request->id);
            
            $product->is_active = true;
            $product->save();

            if ($product->owner) {
                $product->owner->notify(new ProductApprovedNotification());
                if ($product->announcement == true && $product->owner->balance >= 1) {                    
                    $product->owner->decrement('balance', 1);                    
                    PlatformEarning::create([
                        'transaction_amount' => 1,
                        'commission_amount'  => 1,
                        'type'               => 'announcement',
                    ]);
                    
                    $users = User::where('id', '!=', $product->owner_id)->get();
                    
                    Notification::send($users, new NewProductAnnouncementNotification($product->id, $product->title));
                }
            }
        }); 

        return response()->json(['message' => 'accepted successful'], 200);
    }
    public function reject(Request $request)
    {
        $product = Product::findOrFail($request->id);
        
        $owner = $product->owner;

        $product->delete();

        if ($owner) {
            $owner->notify(new ProductRejectedNotification());
        }

        return response()->json(['message' => 'rejected and deleted successfully'], 200);
    }
    
}
