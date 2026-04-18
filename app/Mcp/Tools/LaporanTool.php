<?php

namespace App\Mcp\Tools;

use App\Models\LaporanLabaRugi;
use App\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Melihat laporan keuangan seperti laba rugi dan ringkasan finansial per periode.')]
class LaporanTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $tipe = $request->get('tipe', 'laba_rugi');

        switch ($tipe) {
            case 'laba_rugi':
                return $this->getLabaRugi($request);
            case 'penjualan_harian':
                return $this->getPenjualanHarian($request);
            case 'summary':
                return $this->getSummary($request);
            default:
                return Response::text('Tipe laporan tidak valid. Pilih: laba_rugi, penjualan_harian, summary');
        }
    }

    private function getLabaRugi(Request $request): ResponseFactory
    {
        $query = LaporanLabaRugi::query();

        if ($request->has('bulan')) {
            $query->where('bulan', $request->get('bulan'));
        }

        if ($request->has('tahun')) {
            $query->where('tahun', $request->get('tahun'));
        }

        $laporan = $query->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->limit(12)
            ->get();

        $data = $laporan->map(function ($l) {
            return [
                'periode' => $l->nama_bulan . ' ' . $l->tahun,
                'bulan' => $l->bulan,
                'tahun' => $l->tahun,
                'pendapatan' => $l->pendapatan,
                'hpp' => $l->hpp,
                'laba_kotor' => $l->laba_kotor,
                'beban_operasional' => $l->beban_operasional,
                'laba_bersih' => $l->laba_bersih,
                'margin_laba' => $l->pendapatan > 0 ? round(($l->laba_bersih / $l->pendapatan) * 100, 2) : 0,
            ];
        });

        return Response::structured([
            'tipe' => 'laba_rugi',
            'total_periode' => $data->count(),
            'laporan' => $data->toArray(),
        ]);
    }

    private function getPenjualanHarian(Request $request): ResponseFactory
    {
        $tanggalDari = $request->get('tanggal_dari', Carbon::now()->subDays(7)->format('Y-m-d'));
        $tanggalSampai = $request->get('tanggal_sampai', Carbon::now()->format('Y-m-d'));

        $penjualan = Penjualan::query()
            ->whereDate('tanggal', '>=', $tanggalDari)
            ->whereDate('tanggal', '<=', $tanggalSampai)
            ->selectRaw('DATE(tanggal) as tanggal, COUNT(*) as jumlah_transaksi, SUM(total) as total_penjualan, SUM(diskon) as total_diskon')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        $data = $penjualan->map(function ($p) {
            return [
                'tanggal' => $p->tanggal,
                'jumlah_transaksi' => $p->jumlah_transaksi,
                'total_penjualan' => $p->total_penjualan,
                'total_diskon' => $p->total_diskon,
                'net_sales' => $p->total_penjualan - $p->total_diskon,
            ];
        });

        return Response::structured([
            'tipe' => 'penjualan_harian',
            'periode' => [
                'dari' => $tanggalDari,
                'sampai' => $tanggalSampai,
            ],
            'summary' => [
                'total_hari' => $data->count(),
                'total_transaksi' => $penjualan->sum('jumlah_transaksi'),
                'total_penjualan' => $penjualan->sum('total_penjualan'),
                'total_diskon' => $penjualan->sum('total_diskon'),
            ],
            'data' => $data->toArray(),
        ]);
    }

    private function getSummary(Request $request): ResponseFactory
    {
        $tahun = $request->get('tahun', Carbon::now()->year);

        $penjualanTahunIni = Penjualan::whereYear('tanggal', $tahun)->sum('total');
        $transaksiTahunIni = Penjualan::whereYear('tanggal', $tahun)->count();
        $penjualanBulanIni = Penjualan::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', Carbon::now()->month)
            ->sum('total');
        $transaksiBulanIni = Penjualan::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', Carbon::now()->month)
            ->count();

        $labaRugi = LaporanLabaRugi::where('tahun', $tahun)
            ->where('bulan', Carbon::now()->month)
            ->first();

        return Response::structured([
            'tipe' => 'summary',
            'periode' => [
                'tahun' => $tahun,
                'bulan' => Carbon::now()->month,
            ],
            'penjualan' => [
                'tahun_ini' => $penjualanTahunIni,
                'bulan_ini' => $penjualanBulanIni,
                'transaksi_tahun_ini' => $transaksiTahunIni,
                'transaksi_bulan_ini' => $transaksiBulanIni,
            ],
            'laba_rugi_bulan_ini' => $labaRugi ? [
                'pendapatan' => $labaRugi->pendapatan,
                'laba_kotor' => $labaRugi->laba_kotor,
                'laba_bersih' => $labaRugi->laba_bersih,
                'margin' => $labaRugi->pendapatan > 0 ? round(($labaRugi->laba_bersih / $labaRugi->pendapatan) * 100, 2) : 0,
            ] : null,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tipe' => $schema->string()
                ->description('Jenis laporan: laba_rugi, penjualan_harian, summary')
                ->enum(['laba_rugi', 'penjualan_harian', 'summary'])
                ->default('laba_rugi'),
            'bulan' => $schema->integer()
                ->description('Filter bulan untuk laba rugi (1-12)'),
            'tahun' => $schema->integer()
                ->description('Filter tahun untuk laba rugi atau summary'),
            'tanggal_dari' => $schema->string()
                ->description('Tanggal mulai untuk penjualan harian (format: YYYY-MM-DD)'),
            'tanggal_sampai' => $schema->string()
                ->description('Tanggal akhir untuk penjualan harian (format: YYYY-MM-DD)'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'tipe' => $schema->string()->description('Jenis laporan'),
            'periode' => $schema->object([
                'dari' => $schema->string(),
                'sampai' => $schema->string(),
                'tahun' => $schema->integer(),
                'bulan' => $schema->integer(),
            ]),
            'summary' => $schema->object([
                'total_periode' => $schema->integer(),
                'total_hari' => $schema->integer(),
                'total_transaksi' => $schema->integer(),
                'total_penjualan' => $schema->number(),
                'total_diskon' => $schema->number(),
            ]),
            'laporan' => $schema->array()->items($schema->object([
                'periode' => $schema->string(),
                'bulan' => $schema->integer(),
                'tahun' => $schema->integer(),
                'pendapatan' => $schema->number(),
                'hpp' => $schema->number(),
                'laba_kotor' => $schema->number(),
                'beban_operasional' => $schema->number(),
                'laba_bersih' => $schema->number(),
                'margin_laba' => $schema->number(),
            ])),
            'data' => $schema->array()->items($schema->object([
                'tanggal' => $schema->string(),
                'jumlah_transaksi' => $schema->integer(),
                'total_penjualan' => $schema->number(),
                'total_diskon' => $schema->number(),
                'net_sales' => $schema->number(),
            ])),
            'penjualan' => $schema->object([
                'tahun_ini' => $schema->number(),
                'bulan_ini' => $schema->number(),
                'transaksi_tahun_ini' => $schema->integer(),
                'transaksi_bulan_ini' => $schema->integer(),
            ]),
            'laba_rugi_bulan_ini' => $schema->object([
                'pendapatan' => $schema->number(),
                'laba_kotor' => $schema->number(),
                'laba_bersih' => $schema->number(),
                'margin' => $schema->number(),
            ]),
        ];
    }
}
