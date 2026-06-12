<?php

use App\Events\SayHelloEvent;
use App\Http\Controllers\adminController;
use App\Http\Controllers\adminProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('auth/google', [UserController::class, 'googleRegisterOrLogin']);
Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);
Route::post('logout',[UserController::class,'logout'])->middleware('auth:sanctum');

//
Route::post('create-product',[ProductController::class,'create'])->middleware('auth:sanctum');
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


Route::get('/admin/users/all', [adminController::class, 'indexUsers'])->middleware('auth:sanctum');
Route::get('/admin/users/approved', [adminController::class, 'indexApprovedUser'])->middleware('auth:sanctum');
Route::get('/admin/users/rejected', [adminController::class, 'indexRejectedUser'])->middleware('auth:sanctum');
Route::get('/admin/users/pending', [adminController::class, 'indexPendingUser'])->middleware('auth:sanctum');
Route::get('/admin/users/approve/{id}', [adminController::class, 'approveUser'])->middleware('auth:sanctum');
Route::get('/admin/users/reject/{id}', [adminController::class, 'RejectUser'])->middleware('auth:sanctum');

Route::post('notice/store' , [NoticeController::class, 'store'])->middleware(['auth:sanctum','admin']);
Route::post('notice/update' , [NoticeController::class, 'update'])->middleware('auth:sanctum','admin');
Route::delete('notice/delete' , [NoticeController::class, 'destroy'])->middleware('auth:sanctum','admin');
Route::get('notice/personal' , [NoticeController::class, 'personalNotice'])->middleware('auth:sanctum','admin');
Route::get('notice/all' , [NoticeController::class, 'index'])->middleware('auth:sanctum');



Route::post('Cart/add',[CartController::class,'addToCart'])->middleware('auth:sanctum');
Route::get('Cart/view',[CartController::class,'viewCart'])->middleware('auth:sanctum');
Route::delete('Cart/delete',[CartController::class,'deleteFromCart'])->middleware('auth:sanctum');
Route::post('Cart/checkout',[CartController::class,'checkout'])->middleware('auth:sanctum');
Route::get('/j', function () {
    // إطلاق الحدث وإرسال رسالة
    event(new SayHelloEvent('تم استئجار جيتار ياماها للتو!'));
    
    return 'تم إرسال الإشعار للـ WebSocket بنجاح!';
});

Route::get('admin/product/accept' ,[adminProductController::class,'accept'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/product/reject' ,[adminProductController::class,'reject'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/product/index' ,[adminProductController::class,'index'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/dashboard/card3',[DashboardController::class,'getStats'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/dashboard/card1',[DashboardController::class,'getSupplyDemandRatio'])->middleware(['auth:sanctum','admin']); 
Route::get('admin/dashboard/performance_summary',[DashboardController::class,'getWeeklyPerformance'])->middleware(['auth:sanctum','admin']); 

