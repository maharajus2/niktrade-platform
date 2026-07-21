<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\Admin\EmployeeDocumentDownloadController;
use App\Http\Controllers\Admin\MessageAttachmentDownloadController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\CustomerPhoneVerificationController;
use App\Http\Controllers\CustomerTelegramController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog2', [CatalogController::class, 'catalog2'])->name('catalog.preview');
Route::get('/catalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroyItem'])->name('cart.items.destroy');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook'])->name('telegram.webhook');
Route::get('/logout', fn () => redirect()->route('catalog.index'));

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
