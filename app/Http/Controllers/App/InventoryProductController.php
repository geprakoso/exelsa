<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\Brand;
use App\Models\Kategori;
use App\Models\PembelianItem;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryProductController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request)
    {
        $fk         = PembelianItem::productForeignKey();
        $qtySisa    = PembelianItem::qtySisaColumn();
        $threshold  = self::LOW_STOCK_THRESHOLD;

        // ── Sub-query fragments ───────────────────────────────────────────
        $stockSub = "COALESCE((SELECT SUM(pi.`{$qtySisa}`) FROM tb_pembelian_item pi WHERE pi.`{$fk}` = md_produk.id), 0)";
        $valueSub = "COALESCE((SELECT SUM(pi.`{$qtySisa}` * pi.hpp) FROM tb_pembelian_item pi WHERE pi.`{$fk}` = md_produk.id), 0)";
        $hppSub   = "COALESCE((SELECT AVG(pi.hpp) FROM tb_pembelian_item pi WHERE pi.`{$fk}` = md_produk.id AND pi.hpp > 0), 0)";
        $hjSub    = "COALESCE((SELECT MAX(pi.harga_jual) FROM tb_pembelian_item pi WHERE pi.`{$fk}` = md_produk.id AND pi.harga_jual > 0), 0)";

        // Recent 30-day inbound qty (from new purchases)
        $inSub  = "COALESCE((SELECT SUM(pi.qty) FROM tb_pembelian_item pi WHERE pi.`{$fk}` = md_produk.id AND pi.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)), 0)";
        // Recent 30-day outbound qty (from sales)
        $outSub = "COALESCE((SELECT SUM(pji.qty) FROM tb_penjualan_item pji INNER JOIN tb_pembelian_item pi2 ON pi2.id_pembelian_item = pji.id_pembelian_item WHERE pi2.`{$fk}` = md_produk.id AND pji.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)), 0)";

        // ── Global stats ──────────────────────────────────────────────────
        $totalProducts   = Produk::count();
        $totalStockValue = (float) DB::table('md_produk')
            ->whereNull('deleted_at')
            ->selectRaw("SUM({$valueSub}) as v")
            ->value('v');
        $lowStockCount   = Produk::whereRaw("({$stockSub}) BETWEEN 1 AND {$threshold}")->count();
        $outOfStockCount = Produk::whereRaw("({$stockSub}) = 0")->count();

        // ── Tab counts ────────────────────────────────────────────────────
        $activeCount = Produk::whereRaw("({$stockSub}) > {$threshold}")->count();
        $discCount   = Produk::onlyTrashed()->count();

        // ── Main query ────────────────────────────────────────────────────
        $query = Produk::select([
            'md_produk.id',
            'md_produk.nama_produk',
            'md_produk.sku',
            'md_produk.image_url',
            'md_produk.kategori_id',
            'md_produk.brand_id',
            'md_produk.sn',
            'md_produk.garansi',
            'md_produk.berat',
            'md_produk.panjang',
            'md_produk.lebar',
            'md_produk.tinggi',
            'md_produk.deskripsi',
            'md_produk.tipe_produk',
            'md_produk.is_sellable',
            'md_produk.is_purchasable',
            DB::raw("({$stockSub}) as stok_on_hand"),
            DB::raw("({$valueSub}) as nilai_stok"),
            DB::raw("({$hppSub})  as avg_hpp"),
            DB::raw("({$hjSub})   as harga_jual_display"),
            DB::raw("({$inSub})   as recent_in"),
            DB::raw("({$outSub})  as recent_out"),
        ])->with(['brand:id,nama_brand', 'kategori:id,nama_kategori', 'images' => function ($query) {
            $query->ordered();
        }]);

        // Tab filter
        $tab = $request->get('tab', 'all');
        match ($tab) {
            'active'        => $query->whereRaw("({$stockSub}) > {$threshold}"),
            'low_stock'     => $query->whereRaw("({$stockSub}) BETWEEN 1 AND {$threshold}"),
            'out_of_stock'  => $query->whereRaw("({$stockSub}) = 0"),
            'discontinued'  => $query->onlyTrashed(),
            default         => null,
        };

        // Search
        if ($s = $request->search) {
            $query->where(fn ($q) =>
                $q->where('nama_produk', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%")
            );
        }

        // Category filter
        if ($kid = $request->kategori_id) {
            $query->where('kategori_id', $kid);
        }

        // Tipe Produk filter
        $tpFilter = $request->get('tipe_produk');
        if ($tpFilter && $tpFilter !== 'all') {
            $query->where('tipe_produk', $tpFilter);
        }

        $perPage  = in_array((int) $request->per_page, [10, 25, 50, 100]) ? (int) $request->per_page : 10;
        $products = $query->orderBy('nama_produk')->paginate($perPage)->withQueryString();

        return Inertia::render('app/admin/inventory/products/Index', [
            'products'  => $products,
            'brands'    => Brand::orderBy('nama_brand')->get(['id', 'nama_brand']),
            'kategoris' => Kategori::orderBy('nama_kategori')->get(['id', 'nama_kategori']),
            'gudangs'   => Gudang::where('is_active', true)->orderBy('nama_gudang')->get(['id', 'nama_gudang']),
            'filters'   => $request->only(['search', 'kategori_id', 'tab', 'per_page']),
            'stats'     => [
                'total_products'    => $totalProducts,
                'total_stock_value' => $totalStockValue,
                'low_stock_count'   => $lowStockCount,
                'out_of_stock_count'=> $outOfStockCount,
            ],
            'tab_counts' => [
                'all'          => $totalProducts,
                'active'       => $activeCount,
                'low_stock'    => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
                'discontinued' => $discCount,
            ],
            'low_stock_threshold' => $threshold,
        ]);
    }
}
