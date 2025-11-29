<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\HoldController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\PaymentWebhookController;

Route::prefix('v1')->group(function () {
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('holds', [HoldController::class, 'store']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::post('payments/webhook', [PaymentWebhookController::class, 'handle']);
});
