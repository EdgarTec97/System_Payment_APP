<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Product routes (public)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('featured', [ProductController::class, 'featured']);
    Route::get('search', [ProductController::class, 'search']);
    Route::get('categories', [ProductController::class, 'categories']);
    Route::get('price-range', [ProductController::class, 'priceRange']);
    Route::get('statistics', [ProductController::class, 'statistics']);
    Route::get('{id}', [ProductController::class, 'show']);
    Route::get('{id}/related', [ProductController::class, 'related']);
});

// Protected routes
Route::middleware(['auth:sanctum', 'api.throttle:default'])->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
    });

    // Order routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('statistics', [OrderController::class, 'statistics']);
        Route::get('{id}', [OrderController::class, 'show']);
        Route::put('{id}', [OrderController::class, 'update']);
        Route::delete('{id}', [OrderController::class, 'destroy']);
        Route::post('{id}/cancel', [OrderController::class, 'cancel']);
    });

    // Cart routes
    Route::prefix('cart')->group(function () {
        Route::get('/', [OrderController::class, 'cart']);
        Route::post('add/{productId}', [OrderController::class, 'addToCart']);
        Route::put('update/{itemId}', [OrderController::class, 'updateCartItem']);
        Route::delete('remove/{itemId}', [OrderController::class, 'removeFromCart']);
        Route::delete('clear', [OrderController::class, 'clearCart']);
        Route::get('count', [OrderController::class, 'cartCount']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('intent', [OrderController::class, 'createPaymentIntent']);
        Route::post('confirm', [OrderController::class, 'confirmPayment']);
    });
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:ADMIN', 'api.throttle:strict'])->group(function () {
    Route::prefix('admin')->group(function () {

        // User management
        Route::prefix('users')->group(function () {
            Route::get('/', [AuthController::class, 'adminIndex']);
            Route::get('{id}', [AuthController::class, 'adminShow']);
            Route::put('{id}', [AuthController::class, 'adminUpdate']);
            Route::delete('{id}', [AuthController::class, 'adminDestroy']);
            Route::post('{id}/verify', [AuthController::class, 'adminVerify']);
            Route::post('{id}/unverify', [AuthController::class, 'adminUnverify']);
        });

        // Product management
        Route::prefix('products')->group(function () {
            Route::post('/', [ProductController::class, 'adminStore']);
            Route::put('{id}', [ProductController::class, 'adminUpdate']);
            Route::delete('{id}', [ProductController::class, 'adminDestroy']);
            Route::post('{id}/restore', [ProductController::class, 'adminRestore']);
        });

        // Order management
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'adminIndex']);
            Route::put('{id}/status', [OrderController::class, 'adminUpdateStatus']);
        });
    });
});

// Support routes
Route::middleware(['auth:sanctum', 'role:SUPPORT,ADMIN', 'api.throttle:lenient'])->group(function () {
    Route::prefix('support')->group(function () {

        // Product management
        Route::prefix('products')->group(function () {
            Route::post('/', [ProductController::class, 'supportStore']);
            Route::put('{id}', [ProductController::class, 'supportUpdate']);
            Route::delete('{id}', [ProductController::class, 'supportDestroy']);
        });
    });
});

// Health check
Route::get('health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
    ]);
});

// API Documentation route
Route::get('docs', function () {
    return redirect('/api/documentation');
});
