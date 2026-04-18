<?php

namespace App\Mcp\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;

#[Description('Ringkasan dashboard dan statistik sistem Arabica POS')]
class DashboardResource extends Resource
{
    public function handle(Request $request): ResponseFactory
    {
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');

        // Statistik hari ini
        $penjualanHariIni = \App\Models\Penjualan::whereDate('tanggal', $today)->count();
        $omsetHariIni = \App\Models\Penjualan::whereDate('tanggal', $today)->sum('total');

        // Statistik bulan ini
        $penjualanBulanIni = \App\Models\Penjualan::where('tanggal', 'like', $thisMonth . '%')->count();
        $omsetBulanIni = \App\Models\Penjualan::where('tanggal', 'like', $thisMonth . '%')->sum('total');

        // Statistik produk
        $totalProduk = \App\Models\Produk::count();
        $produkStokRendah = \App\Models\Produk::whereColumn('stok', '<=', 'stok_minimum')->count();
        $produkHabis = \App\Models\Produk::where('stok', '<=', 0)->count();

        // Statistik member
        $totalMember = \App\Models\Member::where('status', 'active')->count();
        $memberBaruBulanIni = \App\Models\Member::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return Response::structured([
            'tanggal' => $today,
            'penjualan_hari_ini' => [
                'jumlah_transaksi' => $penjualanHariIni,
                'total_omset' => $omsetHariIni,
            ],
            'penjualan_bulan_ini' => [
                'jumlah_transaksi' => $penjualanBulanIni,
                'total_omset' => $omsetBulanIni,
            ],
            'produk' => [
                'total' => $totalProduk,
                'stok_rendah' => $produkStokRendah,
                'stok_habis' => $produkHabis,
            ],
            'member' => [
                'total_aktif' => $totalMember,
                'baru_bulan_ini' => $memberBaruBulanIni,
            ],
        ]);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'tanggal' => $schema->string()->description('Tanggal data diambil')->required(),
            'penjualan_hari_ini' => $schema->object([
                'jumlah_transaksi' => $schema->integer()->required(),
                'total_omset' => $schema->number()->required(),
            ])->required(),
            'penjualan_bulan_ini' => $schema->object([
                'jumlah_transaksi' => $schema->integer()->required(),
                'total_omset' => $schema->number()->required(),
            ])->required(),
            'produk' => $schema->object([
                'total' => $schema->integer()->required(),
                'stok_rendah' => $schema->integer()->required(),
                'stok_habis' => $schema->integer()->required(),
            ])->required(),
            'member' => $schema->object([
                'total_aktif' => $schema->integer()->required(),
                'baru_bulan_ini' => $schema->integer()->required(),
            ])->required(),
        ];
    }
}
