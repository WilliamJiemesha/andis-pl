<?php

use App\Http\Controllers\Api\IncomingItemApiController;
use App\Http\Controllers\Api\MasterItemApiController;
use App\Http\Controllers\Api\ExternalItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/master-items', [MasterItemApiController::class, 'store']);
    Route::post('/incoming-items', [IncomingItemApiController::class, 'store']);

    Route::prefix('non-buku')->group(function () {
        Route::get('/items/search', [ExternalItemController::class, 'search']);
        Route::post('/items/sync', [ExternalItemController::class, 'sync']);
    });
});
