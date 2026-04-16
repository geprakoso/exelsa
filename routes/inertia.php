<?php

use App\Http\Controllers\App\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Auth routes (no middleware)
Route::prefix('app')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('app.login');
    Route::post('/login', [LoginController::class, 'login'])->name('app.login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('app.logout');
});

// Protected routes that are NOT already defined in web.php
// (All other protected routes are in web.php with correct controllers)
Route::prefix('app')->middleware(['auth', 'verified'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::prefix('inventory')->group(function () {
            Route::get('/stock-adjustment', function () { return Inertia::render('app/admin/inventory/stock-adjustment/Index'); })->name('app.stock-adjustment');
            Route::get('/stock-opname', function () { return Inertia::render('app/admin/inventory/stock-opname/Index'); })->name('app.stock-opname');
        });

        Route::prefix('transactions')->group(function () {
            // Penjualan
            Route::get('/penjualan/create', [App\Http\Controllers\App\PenjualanController::class, 'create'])->name('app.penjualan.create');
            Route::get('/penjualan/{penjualan}', [App\Http\Controllers\App\PenjualanController::class, 'show'])->name('app.penjualan.show');
            Route::get('/penjualan/{penjualan}/edit', [App\Http\Controllers\App\PenjualanController::class, 'edit'])->name('app.penjualan.edit');
            // Pembelian
            Route::get('/pembelian/create', [App\Http\Controllers\App\PembelianController::class, 'create'])->name('app.pembelian.create');
            Route::get('/pembelian/{pembelian}', [App\Http\Controllers\App\PembelianController::class, 'show'])->name('app.pembelian.show');
            Route::get('/pembelian/{pembelian}/edit', [App\Http\Controllers\App\PembelianController::class, 'edit'])->name('app.pembelian.edit');
        });
    });

    Route::prefix('akunting')->group(function () {
        Route::get('/chart-of-accounts', function () { return Inertia::render('app/akunting/chart-of-accounts/Index'); })->name('app.chart-of-accounts');
        Route::get('/input-transaksi', function () { return Inertia::render('app/akunting/input-transaksi/Index'); })->name('app.input-transaksi');
        Route::get('/laporan-laba-rugi', function () { return Inertia::render('app/akunting/laporan-laba-rugi/Index'); })->name('app.laporan-laba-rugi');
        Route::get('/laporan-neraca', function () { return Inertia::render('app/akunting/laporan-neraca/Index'); })->name('app.laporan-neraca');
    });

    Route::get('/settings', function () { return Inertia::render('app/settings/Index'); })->name('app.settings');
});
