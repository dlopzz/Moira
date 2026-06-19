<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\CmsPageController;
use App\Http\Controllers\Api\V1\SiteSettingsController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('products/featured', [ProductController::class,  'featured'])->name('products.featured');
    Route::get('products', [ProductController::class,  'index'])->name('products.index');
    Route::get('products/{slug}', [ProductController::class,  'show'])->name('products.show');

    Route::get('settings', [SiteSettingsController::class, 'show']);

    Route::get('pages/footer', [CmsPageController::class, 'footer']);
    Route::get('pages/{slug}', [CmsPageController::class, 'show']);

    Route::get('reviews/{token}', [ReviewController::class, 'show']);
    Route::post('reviews/{token}', [ReviewController::class, 'submit']);

    Route::get('checkout/payment-config', [PaymentController::class, 'config']);

    Route::post('auth/register', [RegisterController::class,       'store'])->name('auth.register');
    Route::post('auth/login', [LoginController::class,          'store'])->name('auth.login');
    Route::post('auth/forgot-password', [ForgotPasswordController::class, 'store'])->name('auth.forgot-password');
    Route::post('auth/reset-password', [ResetPasswordController::class,  'store'])->name('auth.reset-password');
    Route::get('auth/verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])->name('auth.verify-email');
    Route::get('auth/google',          [SocialAuthController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout',         [LogoutController::class,     'store'])->name('auth.logout');
        Route::post('auth/verify-email/resend', [VerifyEmailController::class, 'resend'])->name('auth.verify-email.resend');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::put('addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.default');
        Route::apiResource('addresses', AddressController::class);

        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/items', [CartController::class, 'addItem']);
        Route::put('cart/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('cart/items/{item}', [CartController::class, 'removeItem']);
        Route::post('cart/coupon', [CartController::class, 'applyCoupon']);
        Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);

        Route::get('reviews', [ReviewController::class, 'myReviews']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);

        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::get('wishlist/ids', [WishlistController::class, 'ids']);
        Route::post('wishlist/{product}', [WishlistController::class, 'toggle']);

        Route::get('checkout', [CheckoutController::class, 'show']);
        Route::post('checkout/address', [CheckoutController::class, 'setAddress']);
        Route::get('checkout/shipping-rates', [ShippingController::class, 'rates']);
        Route::post('checkout/shipping', [ShippingController::class, 'select']);
        Route::post('checkout/pay', [PaymentController::class, 'pay']);
        Route::post('checkout/simulate-pay', [PaymentController::class, 'simulate']);
    });
});
