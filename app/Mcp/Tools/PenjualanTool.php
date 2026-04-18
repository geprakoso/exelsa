<?php

namespace App\Mcp\Tools;

use App\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Mencari dan menganalisis data penjualan. Dapat melihat riwayat penjualan, total penjualan, dan analisis per periode.')]
class PenjualanTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $query = Penjualan::query()
            ->with(['items.produk', 'member', 'karyawan']);

        // Filter tanggal
        if ($request->has('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->get('tanggal_dari'));
        }

        if ($request->has('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->get('tanggal_sampai'));
        }

        // Filter status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter member
        if ($request->has('member_id')) {
            $query->where('member_id', $request->get('member_id'));
        }

        // Filter karyawan
        if ($request->has('karyawan_id')) {
            $query->where('karyawan_id', $request->get('karyawan_id'));
        }

        // Summary mode - hanya tampilkan ringkasan
        if ($request->get('summary', false)) {
            $totalPenjualan = $query->count();
            $totalOmset = $query->sum('total');
            $totalDiskon = $query->sum('diskon');
            $rataRata = $query->avg('total');

            return Response::structured([
                'periode' => [
                    'dari' => $request->get('tanggal_dari', 'semua'),
                    'sampai' => $request->get('tanggal_sampai', 'semua'),
                ],
                'summary' => [
                    'total_transaksi' => $totalPenjualan,
                    'total_omset' => $totalOmset,
                    'total_diskon' => $totalDiskon,
                    'rata_rata_transaksi' => round($rataRata, 2),
                ],
            ]);
        }

        // Detail mode
        $limit = $request->get('limit', 10);
        $penjualans = $query->orderBy('tanggal', 'desc')
            ->limit($limit)
            ->get();

        $data = $penjualans->map(function ($p) {
            return [
                'id' => $p->id,
                'no_nota' => $p->no_nota,
                'tanggal' => $p->tanggal->format('Y-m-d H:i:s'),
                'member' => $p->member?->nama ?? 'Umum',
                'karyawan' => $p->karyawan?->nama ?? '-',
                'total' => $p->total,
                'diskon' => $p->diskon,
                'grand_total' => $p->total - $p->diskon,
                'status' => $p->status,
                'jumlah_item' => $p->items->count(),
            ];
        });

        return Response::structured([
            'total' => $data->count(),
            'penjualan' => $data->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tanggal_dari' => $schema->string()
                ->description('Filter tanggal mulai (format: YYYY-MM-DD)'),
            'tanggal_sampai' => $schema->string()
                ->description('Filter tanggal akhir (format: YYYY-MM-DD)'),
            'status' => $schema->string()
                ->description('Filter status: pending, processing, completed, cancelled')
                ->enum(['pending', 'processing', 'completed', 'cancelled']),
            'member_id' => $schema->integer()
                ->description('Filter berdasarkan ID member'),
            'karyawan_id' => $schema->integer()
                ->description('Filter berdasarkan ID karyawan'),
            'summary' => $schema->boolean()
                ->description('Tampilkan hanya ringkasan/statistik (true) atau detail transaksi (false)'),
            'limit' => $schema->integer()
                ->description('Jumlah maksimal transaksi yang ditampilkan (default: 10)'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total' => $schema->integer()->description('Jumlah penjualan ditemukan'),
            'penjualan' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer(),
                    'no_nota' => $schema->string(),
                    'tanggal' => $schema->string(),
                    'member' => $schema->string(),
                    'karyawan' => $schema->string(),
                    'total' => $schema->number(),
                    'diskon' => $schema->number(),
                    'grand_total' => $schema->number(),
                    'status' => $schema->string(),
                    'jumlah_item' => $schema->integer(),
                ])),
            'periode' => $schema->object([
                'dari' => $schema->string(),
                'sampai' => $schema->string(),
            ]),
            'summary' => $schema->object([
                'total_transaksi' => $schema->integer(),
                'total_omset' => $schema->number(),
                'total_diskon' => $schema->number(),
                'rata_rata_transaksi' => $schema->number(),
            ]),
        ];
    }
}
