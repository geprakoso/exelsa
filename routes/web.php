<?php

use App\Http\Controllers\Mcp\McpSseController;
use App\Filament\Pages\AppDashboard;
use App\Http\Controllers\App\AkunTransaksiController;
use App\Http\Controllers\App\BrandController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\GudangController;
use App\Http\Controllers\App\JasaController;
use App\Http\Controllers\App\MemberController;
use App\Http\Controllers\App\ProdukController;
use App\Http\Controllers\App\SupplierController;
use App\Http\Controllers\App\UserController;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Mcp\Facades\Mcp;
use App\Mcp\Servers\ArabicaServer;

// MCP SSE Endpoint untuk Opencode - harus didaftarkan SEBELUM Mcp::web()
Route::get('/mcp/arabica', McpSseController::class)->name('mcp.arabica.sse');

// Register MCP endpoints
Mcp::web('/mcp/arabica-json', ArabicaServer::class);
Mcp::local('arabica-server', ArabicaServer::class);

Route::get('/test-auth', function () {
    $user = Auth::user();

    return response()->json([
        'is_logged_in' => Auth::check(),
        'user_id' => $user->id ?? null,
        'user_name' => $user->name ?? null,
        'roles' => $user ? $user->getRoleNames() : [],
        'can_access_panel' => $user ? $user->canAccessPanel(Filament::getPanel('admin')) : false,
    ]);
});

// Login route for auth redirects
Route::get('/login', fn() => redirect()->route('filament.admin.auth.login'))->name('login');

// TEMP TEST ROUTE - REMOVE LATER
Route::get('/test-inertia', function () {
    return Inertia::render('app/dashboard', ['test' => 'hello from inertia']);
});

Route::get('/test-minimal', function () {
    return Inertia::render('app/minimal-test', ['test' => 'minimal test']);
});

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('filament.admin.auth.login');
    }

    // Redirect to Inertia app instead of Filament
    return redirect()->route('app.dashboard');
})->name('home');

// Inertia App Routes
Route::prefix('app')->middleware(['auth', 'verified'])->group(function () {
    // NOTE: /app/login is handled by inertia.php (no middleware)
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('app.dashboard');
    
    Route::prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('app.users');
        Route::post('/users', [UserController::class, 'store'])->name('app.users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('app.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('app.users.destroy');
        
        Route::prefix('master-data')->group(function () {
            Route::get('/produk', [ProdukController::class, 'index'])->name('app.produk');
            Route::post('/produk', [ProdukController::class, 'store'])->name('app.produk.store');
            Route::match(['put', 'post'], '/produk/{produk}', [ProdukController::class, 'update'])->name('app.produk.update');
            Route::delete('/produk/{produk}', [ProdukController::class, 'destroy'])->name('app.produk.destroy');
            
            Route::get('/brand', [BrandController::class, 'index'])->name('app.brand');
            Route::post('/brand', [BrandController::class, 'store'])->name('app.brand.store');
            Route::put('/brand/{brand}', [BrandController::class, 'update'])->name('app.brand.update');
            Route::delete('/brand/{brand}', [BrandController::class, 'destroy'])->name('app.brand.destroy');
            
            Route::get('/kategori', [App\Http\Controllers\App\KategoriController::class, 'index'])->name('app.kategori');
            Route::post('/kategori', [App\Http\Controllers\App\KategoriController::class, 'store'])->name('app.kategori.store');
            Route::put('/kategori/{kategori}', [App\Http\Controllers\App\KategoriController::class, 'update'])->name('app.kategori.update');
            Route::delete('/kategori/{kategori}', [App\Http\Controllers\App\KategoriController::class, 'destroy'])->name('app.kategori.destroy');
            
            Route::get('/supplier', [SupplierController::class, 'index'])->name('app.supplier');
            Route::post('/supplier', [SupplierController::class, 'store'])->name('app.supplier.store');
            Route::put('/supplier/{supplier}', [SupplierController::class, 'update'])->name('app.supplier.update');
            Route::delete('/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('app.supplier.destroy');
            
            Route::get('/member', [MemberController::class, 'index'])->name('app.member');
            Route::post('/member', [MemberController::class, 'store'])->name('app.member.store');
            Route::put('/member/{member}', [MemberController::class, 'update'])->name('app.member.update');
            Route::delete('/member/{member}', [MemberController::class, 'destroy'])->name('app.member.destroy');
            
            Route::get('/jasa', [JasaController::class, 'index'])->name('app.jasa');
            Route::post('/jasa', [JasaController::class, 'store'])->name('app.jasa.store');
            Route::put('/jasa/{jasa}', [JasaController::class, 'update'])->name('app.jasa.update');
            Route::delete('/jasa/{jasa}', [JasaController::class, 'destroy'])->name('app.jasa.destroy');
            
            Route::get('/gudang', [GudangController::class, 'index'])->name('app.gudang');
            Route::post('/gudang', [GudangController::class, 'store'])->name('app.gudang.store');
            Route::put('/gudang/{gudang}', [GudangController::class, 'update'])->name('app.gudang.update');
            Route::delete('/gudang/{gudang}', [GudangController::class, 'destroy'])->name('app.gudang.destroy');
            
            Route::get('/akun-transaksi', [AkunTransaksiController::class, 'index'])->name('app.akun-transaksi');
            Route::post('/akun-transaksi', [AkunTransaksiController::class, 'store'])->name('app.akun-transaksi.store');
            Route::put('/akun-transaksi/{akunTransaksi}', [AkunTransaksiController::class, 'update'])->name('app.akun-transaksi.update');
            Route::delete('/akun-transaksi/{akunTransaksi}', [AkunTransaksiController::class, 'destroy'])->name('app.akun-transaksi.destroy');
        });
        
        Route::prefix('inventory')->group(function () {
            Route::get('/stock-adjustment', function () { return Inertia::render('app/admin/inventory/stock-adjustment/Index'); })->name('app.stock-adjustment');
            Route::get('/stock-opname', function () { return Inertia::render('app/admin/inventory/stock-opname/Index'); })->name('app.stock-opname');
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

// POS receipt preview/print
Route::get('/pos/receipt/{penjualan}', function (\App\Models\Penjualan $penjualan) {
    return view('pos.receipt', [
        'penjualan' => $penjualan->load(['items.produk', 'items.pembelianItem', 'karyawan']),
    ]);
})->name('pos.receipt');

Route::get('/penjualan/invoice/{penjualan}', function (\App\Models\Penjualan $penjualan) {
    return view('penjualan.invoice', [
        'penjualan' => $penjualan->load([
            'items.produk',
            'items.pembelianItem.pembelian',
            'jasaItems.jasa',
            'member',
            'karyawan',
            'akunTransaksi',
            'pembayaran.akunTransaksi',
        ]),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjualan.invoice');

Route::get('/penjualan/invoice-simple/{penjualan}', function (\App\Models\Penjualan $penjualan) {
    return view('penjualan.invoice-simple', [
        'penjualan' => $penjualan->load([
            'items.produk',
            'jasaItems.jasa',
            'member',
            'karyawan',
            'pembayaran.akunTransaksi',
        ]),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjualan.invoice.simple');

Route::get('/penjadwalan-service/print/{record}', function (\App\Models\PenjadwalanService $record) {
    return view('filament.resources.penjadwalan-service.print', [
        'record' => $record->load(['member', 'technician', 'jasa']),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjadwalan-service.print');

Route::get('/penjadwalan-service/invoice-simple/{record}', function (\App\Models\PenjadwalanService $record) {
    return view('filament.resources.penjadwalan-service.invoice-simple', [
        'record' => $record->load(['member', 'technician', 'jasa']),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjadwalan-service.invoice.simple');

Route::get('/tukar-tambah/invoice/{tukarTambah}', function (\App\Models\TukarTambah $tukarTambah) {
    return view('tukar-tambah.invoice', [
        'tukarTambah' => $tukarTambah->load([
            'karyawan',
            'penjualan.items.produk',
            'penjualan.jasaItems.jasa',
            'penjualan.member',
            'penjualan.karyawan',
            'penjualan.pembayaran.akunTransaksi',
            'pembelian.items.produk',
            'pembelian.supplier',
            'pembelian.karyawan',
        ]),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('tukar-tambah.invoice');

Route::get('/tukar-tambah/invoice-simple/{tukarTambah}', function (\App\Models\TukarTambah $tukarTambah) {
    return view('tukar-tambah.invoice-simple', [
        'tukarTambah' => $tukarTambah->load([
            'karyawan',
            'penjualan.items.produk',
            'penjualan.jasaItems.jasa',
            'penjualan.member',
            'penjualan.karyawan',
            'penjualan.pembayaran.akunTransaksi',
            'pembelian.items.produk',
            'pembelian.supplier',
            'pembelian.karyawan',
        ]),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('tukar-tambah.invoice.simple');

Route::get('/penjadwalan-service/print-crosscheck/{record}', function (\App\Models\PenjadwalanService $record) {
    return view('filament.resources.penjadwalan-service.print-crosscheck', [
        'record' => $record->load(['member', 'technician', 'jasa', 'crosschecks', 'listAplikasis', 'listGames', 'listOs']),
        'profile' => \App\Models\ProfilePerusahaan::first(),
    ]);
})->name('penjadwalan-service.print-crosscheck');

// PWA offline fallback route
Route::get('/offline', function () {
    return view('vendor.laravelpwa.offline');
})->name('offline');

// Inertia routes (included from inertia.php)
require_once __DIR__.'/inertia.php';
