<?php

use App\Http\Controllers\App\Auth\LoginController;
use App\Http\Controllers\App\PenjualanController;
use App\Http\Controllers\App\PembelianController;
use Illuminate\Support\Facades\Route;

// Auth routes (no auth middleware — must be accessible by guests)
Route::middleware(['web'])->prefix('app')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('app.login');
    Route::post('/login', [LoginController::class, 'login'])->name('app.login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('app.logout');
});

// Additional protected routes not already in web.php
// (web.php already has: dashboard, users, master-data, inventory closures,
//  akunting, settings, and all CRUD routes for those resources)
Route::middleware(['web', 'auth'])->prefix('app')->group(function () {
    Route::prefix('admin')->group(function () {
        // Transaction sub-routes (create/show/edit pages) that need separate GET routes
        Route::prefix('transactions')->group(function () {
            // Penjualan - Order matters: specific routes before parameterized routes
            Route::get('/penjualan/create', [PenjualanController::class, 'create'])->name('app.penjualan.create');
            Route::post('/penjualan', [PenjualanController::class, 'store'])->name('app.penjualan.store');
            Route::get('/penjualan/{penjualan}/edit', [PenjualanController::class, 'edit'])->name('app.penjualan.edit');
            Route::put('/penjualan/{penjualan}', [PenjualanController::class, 'update'])->name('app.penjualan.update');
            Route::delete('/penjualan/{penjualan}', [PenjualanController::class, 'destroy'])->name('app.penjualan.destroy');
            Route::get('/penjualan/{penjualan}', [PenjualanController::class, 'show'])->name('app.penjualan.show');
            Route::get('/penjualan', [PenjualanController::class, 'index'])->name('app.penjualan');
            // Pembelian - Order matters: specific routes before parameterized routes
            Route::get('/pembelian/create', [PembelianController::class, 'create'])->name('app.pembelian.create');
            Route::post('/pembelian', [PembelianController::class, 'store'])->name('app.pembelian.store');
            Route::get('/pembelian/{pembelian}/edit', [PembelianController::class, 'edit'])->name('app.pembelian.edit');
            Route::put('/pembelian/{pembelian}', [PembelianController::class, 'update'])->name('app.pembelian.update');
            Route::delete('/pembelian/{pembelian}', [PembelianController::class, 'destroy'])->name('app.pembelian.destroy');
            Route::get('/pembelian/{pembelian}', [PembelianController::class, 'show'])->name('app.pembelian.show');
            Route::get('/pembelian', [PembelianController::class, 'index'])->name('app.pembelian');
        });
    });
});