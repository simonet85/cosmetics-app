<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MoneyFusion\WebhookController;
use App\Http\Controllers\MoneyFusion\PaymentCallbackController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// API Routes for Cart and Wishlist Counters
Route::get('/api/cart-wishlist-count', function () {
    $cartCount = 0;
    $wishlistCount = 0;

    // Get cart count from session
    $cart = session('cart', []);
    foreach ($cart as $item) {
        $cartCount += $item['quantity'];
    }

    // Get wishlist count
    if (auth()->check()) {
        $wishlistCount = auth()->user()->wishlist()->count();
    } else {
        $wishlist = session('wishlist', []);
        $wishlistCount = count($wishlist);
    }

    return response()->json([
        'cart_count' => $cartCount,
        'wishlist_count' => $wishlistCount,
    ]);
});

// Shop Routes
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::get('/category/{category:slug}', [ShopController::class, 'category'])->name('category');
    Route::get('/tag/{tag:slug}', [ShopController::class, 'tag'])->name('tag');
    Route::get('/{slug}', [ShopController::class, 'show'])->name('show');
});

// Product Routes
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/{product:slug}', [ProductController::class, 'show'])->name('show');
    Route::get('/{product:slug}/quick-view', [ProductController::class, 'quickView'])->name('quickView');
    Route::post('/{product}/reviews', [ProductController::class, 'storeReview'])->name('reviews.store');
});

// Cart Routes
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::post('/update', [CartController::class, 'update'])->name('update');
    Route::post('/remove', [CartController::class, 'remove'])->name('remove');
    Route::post('/clear', [CartController::class, 'clear'])->name('clear');
    Route::get('/count', [CartController::class, 'count'])->name('count');
});

// Wishlist Routes
Route::prefix('wishlist')->name('wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('/add', [WishlistController::class, 'add'])->name('add');
    Route::post('/remove', [WishlistController::class, 'remove'])->name('remove');
    Route::post('/toggle', [WishlistController::class, 'toggle'])->name('toggle');
    Route::post('/clear', [WishlistController::class, 'clear'])->name('clear');
    Route::get('/count', [WishlistController::class, 'count'])->name('count');
});

// Checkout Routes (Guest checkout allowed)
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/process', [CheckoutController::class, 'process'])->name('process');
    Route::get('/success/{order}', [CheckoutController::class, 'success'])->name('success');
});

// Static Pages
Route::prefix('pages')->name('pages.')->group(function () {
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/contact', [PageController::class, 'contactSubmit'])->name('contact.submit');
    Route::get('/faq', [PageController::class, 'faq'])->name('faq');
});

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

// MoneyFusion Payment Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/callback', [PaymentCallbackController::class, 'callback'])->name('callback');
});

// MoneyFusion Webhook (POST endpoint - no CSRF protection)
// Note: Package also registers a webhook route, but we use our custom controller
Route::post('/api/moneyfusion/webhook', [WebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Account Routes (Auth required)
Route::middleware(['auth'])->prefix('account')->name('account.')->group(function () {
    Route::get('/dashboard', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'orderDetail'])->name('orders.show');
    Route::put('/orders/{order}/cancel', [AccountController::class, 'cancelOrder'])->name('orders.cancel');
});

// Admin Routes (Admin/Super Admin only)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Products Management
    Route::resource('products', AdminProductController::class);
    Route::post('products/{product}/images', [AdminProductController::class, 'uploadImages'])->name('products.images.upload');

    // Categories Management
    Route::resource('categories', AdminCategoryController::class)->except(['show']);

    // Orders Management
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::put('orders/{order}/payment-status', [AdminOrderController::class, 'updatePaymentStatus'])->name('orders.update-payment-status');

    // Customers Management
    Route::resource('customers', AdminCustomerController::class)->only(['index', 'show', 'destroy']);

    // Reviews Management
    Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Coupons Management
    Route::resource('coupons', AdminCouponController::class);

    // Banners Management
    Route::resource('banners', AdminBannerController::class);

    // Newsletter
    Route::get('newsletter', [AdminSettingController::class, 'newsletter'])->name('newsletter.index');

    // Settings
    Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
});
