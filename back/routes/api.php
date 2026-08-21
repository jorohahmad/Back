<?php

use App\Events\SayHelloEvent;
use App\Http\Controllers\adminController;
use App\Http\Controllers\adminProductController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast; 
use App\Http\Controllers\PriceOfferController;

Broadcast::routes(['middleware' => ['auth:sanctum']]);///api/broadcasting/auth

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('auth/google', [UserController::class, 'googleRegisterOrLogin']);
Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);
Route::post('logout',[UserController::class,'logout'])->middleware('auth:sanctum');

Route::post('verification1',[UserController::class,'Verification1'])->middleware('auth:sanctum');
Route::post('verification2',[UserController::class,'Verification2'])->middleware('auth:sanctum');
Route::post('verification3',[UserController::class,'AccountVerification'])->middleware('auth:sanctum');

Route::post('password/forget', [UserController::class, 'forgetPassword'])->middleware('throttle:3,1');
Route::post('password/verify-code', [UserController::class, 'verifyResetCode']);  
Route::post('password/reset', [UserController::class, 'resetPassword']);
Route::post('password/change', [UserController::class, 'changePassword'])->middleware('auth:sanctum');
//
Route::post('create-product',[ProductController::class,'create'])->middleware(['auth:sanctum','active']);
Route::get('products-for-sale',[ProductController::class,'indexForSale'])->middleware('auth:sanctum');
Route::get('products-for-rent',[ProductController::class,'indexForRent'])->middleware('auth:sanctum');

Route::get('get-lessons',[LessonController::class,'index'])->middleware('auth:sanctum');
Route::post('add-lesson',[LessonController::class,'store'])->middleware('auth:sanctum');

Route::post('favorite', [UserController::class, 'toggleFavorite'])->middleware('auth:sanctum');
Route::get('favorites', [UserController::class, 'getFavorites'])->middleware('auth:sanctum');

//
Route::post('admin/register' , [adminController::class,'register']);
Route::post('admin/login1' , [adminController::class,'login1']);
Route::post('admin/login2' , [adminController::class,'login2']);
Route::post('admin/logout' , [adminController::class,'logout'])->middleware('auth:sanctum');
Route::put('profile/update', [AdminController::class, 'updateInformation'])->middleware('auth:sanctum');
Route::post('profile/update-image', [AdminController::class, 'updateImage'])->middleware('auth:sanctum');

Route::get('/admin/users/all', [adminController::class, 'indexUsers'])->middleware('auth:sanctum');
Route::get('/admin/users/rejected', [adminController::class, 'indexRejectedUser'])->middleware('auth:sanctum');//مسار خاطئ

Route::get('/admin/users/approved', [adminController::class, 'indexApprovedUser'])->middleware('auth:sanctum');
Route::get('/admin/users/pending', [adminController::class, 'indexPendingAdmin'])->middleware('auth:sanctum');
Route::get('/admin/admins/approve/{id}', [adminController::class, 'approveAdmin'])->middleware('auth:sanctum');//post or patch
Route::get('/admin/admins/reject/{id}', [adminController::class, 'RejectAdmin'])->middleware('auth:sanctum');//post or patch
Route::get('/admin/users/verification', [adminController::class, 'acceptVerification'])->middleware('auth:sanctum');
Route::get('/admin/users/approve/{id}', [adminController::class, 'approveUser'])->middleware('auth:sanctum');//post or patch
Route::get('/admin/users/reject/{id}', [adminController::class, 'RejectUser'])->middleware('auth:sanctum');//post or patch

Route::post('notice/store' , [NoticeController::class, 'store'])->middleware(['auth:sanctum','admin']);
Route::post('notice/update' , [NoticeController::class, 'update'])->middleware(['auth:sanctum','admin']);
Route::delete('notice/delete' , [NoticeController::class, 'destroy'])->middleware(['auth:sanctum','admin']);//Route::delete('notice/{id}')
Route::get('notice/personal' , [NoticeController::class, 'personalNotice'])->middleware(['auth:sanctum','admin']);
Route::get('notice/all' , [NoticeController::class, 'index'])->middleware(['auth:sanctum']);

Route::get('seller/{seller}/profile', [UserController::class, 'getSellerProfile'])->middleware('auth:sanctum');
Route::get('seller/my-stats', [UserController::class, 'getMyStats'])->middleware('auth:sanctum');
Route::post('seller/{seller}/rate', [UserController::class, 'rateSeller'])->middleware('auth:sanctum');

Route::post('user/charge', [UserController::class, 'recharge'])->middleware('auth:sanctum');

Route::post('Cart/add',[CartController::class,'addToCart'])->middleware(['auth:sanctum','active']);
Route::get('Cart/view',[CartController::class,'viewCart'])->middleware('auth:sanctum');
Route::delete('Cart/delete',[CartController::class,'deleteFromCart'])->middleware(['auth:sanctum','active']);
Route::patch('Cart/update', [CartController::class, 'updateQuantity'])->middleware(['auth:sanctum','active']);
Route::post('Cart/checkout',[CartController::class,'checkout'])->middleware(['auth:sanctum','active']);
Route::get('receipts', [CartController::class, 'getUserReceipts'])->middleware(['auth:sanctum','active']);
Route::get('receipts/{transaction_id}', [CartController::class, 'getReceiptByTransactionId'])->middleware(['auth:sanctum','active']);
Route::get('/j', function () {
    // إطلاق الحدث وإرسال رسالة
    event(new SayHelloEvent('تم استئجار جيتار ياماها للتو!'));
    
    return 'تم إرسال الإشعار للـ WebSocket بنجاح!';
});

Route::get('admin/product/accept/{id}' ,[adminProductController::class,'accept'])->middleware(['auth:sanctum','admin']); //post or patch
Route::get('admin/product/reject/{id}' ,[adminProductController::class,'reject'])->middleware(['auth:sanctum','admin']); //post or patch
Route::get('admin/product/index' ,[adminProductController::class,'index'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/dashboard/card3',[DashboardController::class,'getStats'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/dashboard/card1',[DashboardController::class,'getSupplyDemandRatio'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/dashboard/performance_summary',[DashboardController::class,'getWeeklyPerformance'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/product/view',[ProductController::class,'viewProductsForAdmin'])->middleware(['auth:sanctum','admin']);

// الرسم البياني الشريطي (الاقسام الاكثر مبيعا و الاقسام الاكثر تأجيرا)
Route::get('dashboard/top-machines', [DashboardController::class, 'getTopMachines'])->middleware(['auth:sanctum', 'admin']);

// يطاقات الارقام السريعة "متوسط مده الايجار و اجمالي القطغ المؤجرة حاليا"و
Route::get('dashboard/quick-stats', [DashboardController::class, 'getQuickStats'])->middleware(['auth:sanctum', 'admin']);

// الدونات
Route::get('dashboard/transaction-types', [DashboardController::class, 'getTransactionTypes'])
    ->middleware('auth:sanctum', 'admin');
//الرسم البياني
Route::get('dashboard/revenue-chart', [DashboardController::class, 'getRevenueChart'])
    ->middleware('auth:sanctum','admin');
// مسارات الإشعارات
Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'delete']);
});

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('offers/send', [PriceOfferController::class, 'sendOffer']);
    Route::patch('offers/{id}/accept', [PriceOfferController::class, 'acceptOffer']);
    Route::patch('offers/{id}/reject', [PriceOfferController::class, 'rejectOffer']);
});
// مسارات أدمن النظام
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('admin/transfers', [TransferController::class, 'getAllTransfersForAdmin']);
    Route::put('admin/transfer/{transaction_id}/on-way', [TransferController::class, 'markAsOnWay']);
    Route::put('admin/transfer/{transaction_id}/reached', [TransferController::class, 'markAsReached']);
});

// مسارات المستخدم (المشتري / المستأجر)
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::put('transfer/{transaction_id}/finish', [TransferController::class, 'markAsFinished']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('admin/activity-log', [NotificationController::class, 'adminActivityLog']);
    Route::get('admin/notifications', [NotificationController::class, 'indexForAdmin']);
    Route::put('admin/notifications/mark-read', [NotificationController::class, 'markAllAsRead']);
    
});
