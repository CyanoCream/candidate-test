<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CltLayupController;
use App\Http\Controllers\Api\CltLayerController;
use App\Http\Controllers\Api\ImportExportController;

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('suppliers.layups', CltLayupController::class);
    Route::apiResource('layups.layers', CltLayerController::class);

    Route::get('suppliers/{supplier}/export', [ImportExportController::class, 'export']);
    Route::post('suppliers/{supplier}/import', [ImportExportController::class, 'import']);
});
