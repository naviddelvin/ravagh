<?php

use App\Http\Controllers\Api\Admin\AdvertisementManagementController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\ShopManagementController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\WithdrawManagementController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای عمومی (بدون نیاز به ورود)
|--------------------------------------------------------------------------
*/
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/shops', [ShopController::class, 'index']);
Route::get('/shops/{shop}', [ShopController::class, 'show']);
Route::get('/shops/{shop}/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/shops/{shop}/services', [ServiceController::class, 'index']);
Route::get('/services/{service}', [ServiceController::class, 'show']);
Route::get('/stories', [StoryController::class, 'index']);
Route::get('/advertisements', [AdvertisementController::class, 'index']);
Route::get('/reviews/{type}/{id}', [ReviewController::class, 'index']);
Route::post('/coupons/check', [CouponController::class, 'check']);

/*
|--------------------------------------------------------------------------
| مسیرهای نیازمند ورود (کاربر عادی / غرفه‌دار)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/request-shop-owner', [AuthController::class, 'requestShopOwner']);

    // کیف پول
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/charge', [WalletController::class, 'requestCharge']);
    Route::post('/wallet/charge/{paymentId}/confirm', [WalletController::class, 'confirmCharge']);

    // سبد خرید و سفارش
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{itemId}', [CartController::class, 'removeItem']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);

    // رزرو نوبت خدمات
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::post('/services/{service}/bookings', [BookingController::class, 'store']);

    // استوری (تعامل کاربر)
    Route::post('/stories/{story}/interact', [StoryController::class, 'interact']);
    Route::post('/stories/{story}/click', [StoryController::class, 'click']);

    // نظرات
    Route::post('/reviews/{type}/{id}', [ReviewController::class, 'store']);

    // اعلان‌ها
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // ایجاد غرفه (کاربر عادی -> غرفه‌دار)
    Route::post('/shops', [ShopController::class, 'store']);

    // درخواست تسویه
    Route::get('/withdraw-requests', [WithdrawRequestController::class, 'index']);
    Route::post('/withdraw-requests', [WithdrawRequestController::class, 'store']);

    /*
    |----------------------------------------------------------------------
    | مسیرهای مخصوص غرفه‌دار (پنل غرفه‌دار - بند ۱۲ سند)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:shop_owner')->group(function () {
        Route::patch('/shops/{shop}', [ShopController::class, 'update']);
        Route::get('/shops/{shop}/report', [ShopController::class, 'report']);

        Route::post('/shops/{shop}/products', [ProductController::class, 'store']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        Route::post('/shops/{shop}/services', [ServiceController::class, 'store']);
        Route::patch('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        Route::post('/shops/{shop}/stories', [StoryController::class, 'store']);
        Route::get('/stories/{story}/stats', [StoryController::class, 'stats']);
        Route::post('/stories/{story}/renew', [StoryController::class, 'renew']);

        Route::get('/shops/{shop}/coupons', [CouponController::class, 'index']);
        Route::post('/shops/{shop}/coupons', [CouponController::class, 'store']);
        Route::patch('/coupons/{coupon}', [CouponController::class, 'update']);

        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

        Route::get('/shops/{shop}/bookings', [BookingController::class, 'shopBookings']);
        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | مسیرهای مخصوص مدیر سیستم (پنل مدیریت - بند ۱۷ سند)
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/reports/stories', [ReportController::class, 'storyIncome']);

        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive']);

        Route::get('/shops', [ShopManagementController::class, 'index']);
        Route::patch('/shops/{shop}/status', [ShopManagementController::class, 'updateStatus']);
        Route::post('/shops/{shop}/toggle-verified', [ShopManagementController::class, 'toggleVerified']);

        Route::get('/withdraw-requests', [WithdrawManagementController::class, 'index']);
        Route::post('/withdraw-requests/{withdrawRequest}/approve', [WithdrawManagementController::class, 'approve']);
        Route::post('/withdraw-requests/{withdrawRequest}/reject', [WithdrawManagementController::class, 'reject']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::patch('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('/advertisements', [AdvertisementManagementController::class, 'index']);
        Route::post('/advertisements', [AdvertisementManagementController::class, 'store']);
        Route::patch('/advertisements/{advertisement}', [AdvertisementManagementController::class, 'update']);
        Route::delete('/advertisements/{advertisement}', [AdvertisementManagementController::class, 'destroy']);

        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);
    });
});
