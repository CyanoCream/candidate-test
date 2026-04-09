<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect('/');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('suppliers/api/export-csv', [\App\Http\Controllers\Web\SupplierController::class, 'exportCsv'])->name('suppliers.export_csv');
    Route::get('suppliers/import/template', [\App\Http\Controllers\Web\SupplierController::class, 'downloadTemplate'])->name('suppliers.download_template');
    Route::resource('suppliers', \App\Http\Controllers\Web\SupplierController::class);
    Route::get('suppliers/{supplier}/export', [\App\Http\Controllers\Web\SupplierController::class, 'export'])->name('suppliers.export');
    Route::post('suppliers/{supplier}/import', [\App\Http\Controllers\Web\SupplierController::class, 'import'])->name('suppliers.import');
    Route::get('import/conflicts/{importId}', [\App\Http\Controllers\Web\SupplierController::class, 'conflicts'])->name('suppliers.conflicts');
    Route::post('import/conflicts/{importId}', [\App\Http\Controllers\Web\SupplierController::class, 'resolveConflicts'])->name('suppliers.resolve');

    // Layup Builder Routes
    Route::get('layups/{layup}', [\App\Http\Controllers\Web\WebLayupController::class, 'show'])->name('layups.show');
    Route::post('layups/{layup}', [\App\Http\Controllers\Web\WebLayupController::class, 'update'])->name('layups.update');
});

require __DIR__.'/auth.php';
