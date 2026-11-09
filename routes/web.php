<?php

use App\Http\Controllers\Admin\EmployeeDocumentDownloadController;
use App\Http\Controllers\Admin\MessageAttachmentDownloadController;
use App\Http\Controllers\BusinessAuthController;
use App\Http\Controllers\BusinessCartController;
use App\Http\Controllers\BusinessCatalogController;
use App\Http\Controllers\BusinessRequestController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\CustomerPhoneVerificationController;
use App\Http\Controllers\CustomerTelegramController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
// Старый адрес пробной страницы: ведём на новую главную магазина.
Route::get('/catalog2', fn () => redirect()->route('catalog.preview3'))->name('catalog.preview');
Route::get('/catalog3', [CatalogController::class, 'catalog3'])->name('catalog.preview3');
Route::get('/catalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/state', [CartController::class, 'state'])->name('cart.state');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroyItem'])->name('cart.items.destroy');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook'])->name('telegram.webhook');
Route::get('/logout', fn () => redirect()->route('catalog.index'));

Route::prefix('business')->name('business.')->group(function () {
    Route::get('/', [BusinessAuthController::class, 'home'])->name('home');
    Route::post('/login', [BusinessAuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
    Route::post('/register', [BusinessAuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
    Route::middleware('auth:business')->group(function () {
        Route::post('/logout', [BusinessAuthController::class, 'logout'])->name('logout');
        Route::get('/account', [BusinessAuthController::class, 'account'])->name('account');
        Route::get('/catalog', [BusinessCatalogController::class, 'index'])->name('catalog');
        Route::get('/catalog/{product}', [BusinessCatalogController::class, 'show'])->name('product');
        Route::get('/cart', [BusinessCartController::class, 'index'])->name('cart');
        Route::post('/cart/items', [BusinessCartController::class, 'add'])->name('cart.add');
        Route::patch('/cart/items/{product}', [BusinessCartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/items/{product}', [BusinessCartController::class, 'remove'])->name('cart.remove');
        Route::post('/requests', [BusinessRequestController::class, 'store'])->name('requests.store');
        Route::get('/requests', [BusinessRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{number}', [BusinessRequestController::class, 'show'])->name('requests.show');
        Route::get('/requests/{number}/invoice', [BusinessRequestController::class, 'invoice'])->name('requests.invoice');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/admin/employees/{employee}/documents/{document}/download', EmployeeDocumentDownloadController::class)
        ->name('admin.employee-documents.download');
    Route::get('/admin/messenger/attachments/{attachment}/preview', [MessageAttachmentDownloadController::class, 'preview'])
        ->name('admin.messenger.attachments.preview');
    Route::get('/admin/messenger/attachments/{attachment}/download', MessageAttachmentDownloadController::class)
        ->name('admin.messenger.attachments.download');
});

Route::middleware('guest:customer')->group(function () {
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->name('customer.register.store');
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('customer.login.store');
});

Route::middleware('auth:customer')->group(function () {
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');
    Route::get('/account', [CustomerAccountController::class, 'dashboard'])->name('customer.account');
    Route::get('/account/profile/edit', [CustomerAccountController::class, 'editProfile'])->name('customer.account.profile.edit');
    Route::patch('/account/profile', [CustomerAccountController::class, 'updateProfile'])->name('customer.account.profile.update');
    Route::post('/account/avatar', [CustomerAccountController::class, 'updateAvatar'])->name('customer.account.avatar.update');
    Route::delete('/account/avatar', [CustomerAccountController::class, 'destroyAvatar'])->name('customer.account.avatar.destroy');
    Route::post('/account/telegram/link', [CustomerTelegramController::class, 'link'])->name('customer.telegram.link');
    Route::delete('/account/telegram', [CustomerTelegramController::class, 'destroy'])->name('customer.account.telegram.destroy');
    Route::get('/account/phone-verification', [CustomerPhoneVerificationController::class, 'show'])->name('customer.account.phone-verification');
    Route::post('/account/phone-verification/request-code', [CustomerPhoneVerificationController::class, 'requestCode'])->name('customer.account.phone-verification.request-code');
    Route::post('/account/phone-verification/confirm', [CustomerPhoneVerificationController::class, 'confirm'])->name('customer.account.phone-verification.confirm');
    Route::get('/account/orders', [CustomerOrderController::class, 'index'])->name('customer.account.orders');
    Route::get('/account/orders/{order}', [CustomerOrderController::class, 'show'])->name('customer.account.orders.show');
    Route::post('/account/orders/{order}/switch-account', [CustomerOrderController::class, 'switchAccount'])->name('customer.account.orders.switch-account');
    Route::get('/account/addresses', [CustomerAddressController::class, 'index'])->name('customer.account.addresses');
    Route::post('/account/addresses', [CustomerAddressController::class, 'store'])->name('customer.account.addresses.store');
    Route::patch('/account/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customer.account.addresses.update');
    Route::delete('/account/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customer.account.addresses.destroy');
    Route::post('/account/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('customer.orders.cancel');
});
