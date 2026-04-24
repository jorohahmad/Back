<?php

use App\Http\Controllers\adminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('admin/rigester' , [adminController::class,'register']);
Route::post('admin/login1' , [adminController::class,'login1']);
Route::post('admin/login2' , [adminController::class,'login2']);
Route::post('admin/login2' , [adminController::class,'logout'])->middleware('auth:sanctum');