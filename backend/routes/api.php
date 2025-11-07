<?php

use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\AdminAnalyticsController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\TourRegistrationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('tour-registrations', TourRegistrationController::class)
    ->only(['index', 'store']);

Route::get('tour-registrations/availability', [TourRegistrationController::class, 'availability']);

Route::prefix('shop')->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product:slug}', [ProductController::class, 'show']);

    Route::post('cart', [CartController::class, 'ensure']);
    Route::post('cart/items', [CartController::class, 'addItem']);
    Route::patch('cart/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('cart/items/{item}', [CartController::class, 'removeItem']);

    Route::post('checkout', CheckoutController::class);
});

Route::get('about', [AboutController::class, 'show']);
Route::get('admin/analytics', AdminAnalyticsController::class)->middleware('admin.basic');

