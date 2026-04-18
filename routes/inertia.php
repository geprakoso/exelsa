<?php

use App\Http\Controllers\App\Auth\LoginController;
use App\Http\Controllers\App\PenjualanController;
use App\Http\Controllers\App\PembelianController;
use Illuminate\Support\Facades\Route;

// Auth routes (no auth middleware — must be accessible by guests)
Route::prefix('app')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('app.login');
    Route::post('/login', [LoginController::class, 'login'])->name('app.login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('app.logout');
});

// Additional protected routes not already in web.php
// (web.php already has: dashboard, users, master-data, inventory closures,
//  akunting, settings, and all CRUD routes for those resources)
Route::prefix('app')->middleware(['auth', 'verified'])->group(function () {
    Route::prefix('admin')->group(function () {
        // Transaction sub-routes (create/show/edit pages) that need separate GET routes
        Route::prefix('transactions')->group(function () {
            // Penjualan
            Route::get('/penjualan/create', [PenjualanController::class, 'create'])->name('app.penjualan.create');
            Route::get('/penjualan/{penjualan}', [PenjualanController::class, 'show'])->name('app.penjualan.show');
            Route::get('/penjualan/{penjualan}/edit', [PenjualanController::class, 'edit'])->name('app.penjualan.edit');
            // Pembelian
            Route::get('/pembelian/create', [PembelianController::class, 'create'])->name('app.pembelian.create');
            Route::get('/pembelian/{pembelian}', [PembelianController::class, 'show'])->name('app.pembelian.show');
            Route::get('/pembelian/{pembelian}/edit', [PembelianController::class, 'edit'])->name('app.pembelian.edit');
        });
    });
});