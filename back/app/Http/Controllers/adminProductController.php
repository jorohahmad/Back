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
        // 👈 تغليف العملية بالكامل داخل Transaction لضمان الأمان المالي
        DB::transaction(function () use ($request) {
            
            $product = Product::findOrFail($request->id);
            
            $product->is_active = true;
            $product->save();

            if ($product->owner) {
                // 1. إرسال إشعار القبول العادي لصاحب المنتج
                $product->owner->notify(new ProductApprovedNotification());
                
                // 2. معالجة ميزة "الإعلان" إذا كان المستخدم قد طلبها ويملك رصيداً كافياً
                if ($product->announcement == true && $product->owner->balance >= 1) {
                    
                    // أ- خصم 1$ من رصيد المستخدم (عملية مالية)
                    $product->owner->decrement('balance', 1);
                    
                    // ب- تسجيل الـ 1$ في جدول أرباح المنصة (عملية مالية)
                    PlatformEarning::create([
                        'transaction_amount' => 1,
                        'commission_amount'  => 1,
                        'type'               => 'announcement',
                    ]);
                    
                    // ج- جلب جميع المستخدمين في التطبيق (ما عدا صاحب الآلة نفسه)
                    $users = User::where('id', '!=', $product->owner_id)->get();
                    
                    // د- إرسال الإشعار للجميع (سيعمل في الخلفية Queue)
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
