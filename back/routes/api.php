<?php

use App\Http\Controllers\adminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);
Route::post('logout',[UserController::class,'logout'])->middleware('auth:sanctum');

//
Route::post('create-product',[ProductController::class,'create'])->middleware('auth:sanctum');
Route::get('products-for-sale',[ProductController::class,'indexForSale'])->middleware('auth:sanctum');
Route::get('products-for-rent',[ProductController::class,'indexForRent'])->middleware('auth:sanctum');

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

