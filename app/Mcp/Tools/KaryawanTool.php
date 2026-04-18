<?php

namespace App\Mcp\Tools;

use App\Models\Karyawan;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Mencari dan menampilkan data karyawan. Dapat melihat informasi kontak, posisi, dan status kepegawaian.')]
class KaryawanTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $query = Karyawan::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_karyawan', 'like', "%{$search}%")
                  ->orWhere('telepon', 'like', "%{$search}%");
            });
        }

        $limit = $request->get('limit', 20);
        $karyawans = $query->limit($limit)->get();

        $data = $karyawans->map(function ($karyawan) {
            return [
                'id' => $karyawan->id,
                'nama' => $karyawan->nama_karyawan,
                'telepon' => $karyawan->telepon ?? '-',
                'alamat' => $karyawan->alamat ?? '-',
            ];
        });

        return Response::structured([
            'total' => $data->count(),
            'karyawan' => $data->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Kata kunci pencarian (nama atau telepon)'),
            'limit' => $schema->integer()
                ->description('Jumlah maksimal karyawan yang ditampilkan (default: 20)'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total' => $schema->integer()->description('Jumlah karyawan ditemukan')->required(),
            'karyawan' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer(),
                    'nama' => $schema->string(),
                    'telepon' => $schema->string(),
                    'alamat' => $schema->string(),
                ]))
                ->required(),
        ];
    }
}
