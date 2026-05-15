<?php

use App\Http\Controllers\adminController;
use App\Http\Controllers\NoticeController;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('admin/rigester' , [adminController::class,'register']);
Route::post('admin/login1' , [adminController::class,'login1']);
Route::post('admin/login2' , [adminController::class,'login2']);
Route::post('admin/logout' , [adminController::class,'logout'])->middleware('auth:sanctum');


Route::post('/admin/users/all', [adminController::class, 'indexUsers'])->middleware('auth:sanctum');
Route::post('/admin/users*/approved', [adminController::class, 'indexApprovedUser'])->middleware('auth:sanctum');
Route::post('/admin/users/rejected', [adminController::class, 'indexRejectedUser'])->middleware('auth:sanctum');
Route::post('/admin/users/pending', [adminController::class, 'indexPendingUser'])->middleware('auth:sanctum');
Route::post('/admin/users/approve', [adminController::class, 'approveUser'])->middleware('auth:sanctum');

Route::post('notice/store' , [NoticeController::class, 'store'])->middleware(['auth:sanctum','admin']);
Route::post('notice/update' , [NoticeController::class, 'update'])->middleware('auth:sanctum','admin');
Route::delete('notice/delete' , [NoticeController::class, 'destroy'])->middleware('auth:sanctum','admin');
Route::get('notice/personal' , [NoticeController::class, 'personalNotice'])->middleware('auth:sanctum','admin');
Route::get('notice/all' , [NoticeController::class, 'index'])->middleware('auth:sanctum');
