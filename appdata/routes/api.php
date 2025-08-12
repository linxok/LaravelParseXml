<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\FilterController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::prefix('catalog')->group(function(){
    Route::get('products',  [ProductController::class,'index']);
    Route::get('filters',   [FilterController::class,'index']);
});
