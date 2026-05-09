<?php

use App\Http\Controllers\Api\IndonesiaController;
use App\Http\Controllers\Api\ProdukImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth', 'web'])->group(function () {
    // Produk Image API Routes
    Route::get('/produk/{produk}/images', [ProdukImageController::class, 'index']);
    Route::post('/produk/{produk}/images', [ProdukImageController::class, 'store']);
    Route::post('/produk-images/{image}/primary', [ProdukImageController::class, 'setPrimary']);
    Route::delete('/produk-images/{image}', [ProdukImageController::class, 'destroy']);
    Route::post('/produk/{produk}/images/reorder', [ProdukImageController::class, 'reorder']);

    // Indonesia Location API Routes
    Route::get('/indonesia/provinces', [IndonesiaController::class, 'provinces']);
    Route::get('/indonesia/cities', [IndonesiaController::class, 'cities']);
    Route::get('/indonesia/districts', [IndonesiaController::class, 'districts']);
});
