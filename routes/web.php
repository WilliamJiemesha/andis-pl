<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AppNotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceEntryController;
use App\Http\Controllers\IncomingCheckTaskController;
use App\Http\Controllers\ItemSearchController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MasterItemController;
use App\Http\Controllers\MasterItemHistoryController;
use App\Http\Controllers\PriceReviewTaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/language/{locale}', LanguageController::class)->name('language.switch');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/notifications', [AppNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [AppNotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [AppNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    Route::middleware('role:admin,invoice_handler,price_handler,sales_user')->group(function () {
        Route::get('/search-items', [ItemSearchController::class, 'index'])->name('item-search.index');
    });

    Route::middleware('role:admin,invoice_handler,price_handler')->group(function () {
        Route::get('/master-items', [MasterItemController::class, 'index'])->name('master-items.index');
        Route::get('/invoice-entries', [InvoiceEntryController::class, 'index'])->name('invoice-entries.index');
    });

    Route::middleware('role:admin,price_handler')->group(function () {
        Route::get('/master-items/create', [MasterItemController::class, 'create'])->name('master-items.create');
        Route::post('/master-items', [MasterItemController::class, 'store'])->name('master-items.store');
        Route::get('/master-items/{master_item}/edit', [MasterItemController::class, 'edit'])->name('master-items.edit');
        Route::put('/master-items/{master_item}', [MasterItemController::class, 'update'])->name('master-items.update');
        Route::patch('/master-items/{master_item}/status', [MasterItemController::class, 'updateStatus'])->name('master-items.update-status');
        Route::get('/price-review-tasks', [PriceReviewTaskController::class, 'index'])->name('price-review-tasks.index');
        Route::post('/price-review-tasks/batch-review', [PriceReviewTaskController::class, 'batchReview'])->name('price-review-tasks.batch-review');
        Route::get('/price-review-tasks/{price_review_task}', [PriceReviewTaskController::class, 'show'])->name('price-review-tasks.show');
        Route::post('/price-review-tasks/{price_review_task}/review', [PriceReviewTaskController::class, 'review'])->name('price-review-tasks.review');
    });

    Route::middleware('role:admin,invoice_handler')->group(function () {
        Route::get('/invoice-entries/create', [InvoiceEntryController::class, 'create'])->name('invoice-entries.create');
        Route::post('/invoice-entries', [InvoiceEntryController::class, 'store'])->name('invoice-entries.store');
        Route::post('/master-items/quick-create', [MasterItemController::class, 'quickStore'])->name('master-items.quick-store');
    });

    Route::middleware('role:admin,invoice_handler,price_handler')->group(function () {
        Route::get('/invoice-entries/{invoice_entry}', [InvoiceEntryController::class, 'show'])->name('invoice-entries.show');
    });

    Route::middleware('role:admin,incoming_checker')->group(function () {
        Route::get('/incoming-check-tasks', [IncomingCheckTaskController::class, 'index'])->name('incoming-check-tasks.index');
        Route::get('/incoming-check-tasks/{incoming_check_task}', [IncomingCheckTaskController::class, 'show'])->name('incoming-check-tasks.show');
        Route::post('/incoming-check-tasks/{incoming_check_task}', [IncomingCheckTaskController::class, 'update'])->name('incoming-check-tasks.update');
    });

    Route::middleware('role:admin,invoice_handler,price_handler,sales_user')->group(function () {
        Route::get('/master-items/{master_item}', [MasterItemController::class, 'show'])->name('master-items.show');
        Route::get('/master-items/{master_item}/price-history', [MasterItemHistoryController::class, 'price'])->name('master-items.price-history');
    });

    Route::get('/master-items/{master_item}/cost-history', [MasterItemHistoryController::class, 'cost'])->name('master-items.cost-history');

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::delete('/master-items/{master_item}', [MasterItemController::class, 'destroy'])->name('master-items.destroy');
    });
});
