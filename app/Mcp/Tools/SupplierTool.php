<?php

namespace App\Mcp\Tools;

use App\Models\Supplier;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Mencari dan menampilkan data supplier. Dapat melihat informasi kontak dan daftar produk dari supplier.')]
class SupplierTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $query = Supplier::query()
            ->withCount('produks');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_supplier', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $limit = $request->get('limit', 20);
        $suppliers = $query->limit($limit)->get();

        $data = $suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'nama' => $supplier->nama_supplier,
                'no_hp' => $supplier->no_hp ?? '-',
                'email' => $supplier->email ?? '-',
                'alamat' => $supplier->alamat ?? '-',
            ];
        });

        return Response::structured([
            'total' => $data->count(),
            'supplier' => $data->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Kata kunci pencarian (nama, no HP, atau email)'),
            'limit' => $schema->integer()
                ->description('Jumlah maksimal supplier yang ditampilkan (default: 20)'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total' => $schema->integer()->description('Jumlah supplier ditemukan')->required(),
            'supplier' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer(),
                    'nama' => $schema->string(),
                    'no_hp' => $schema->string(),
                    'email' => $schema->string(),
                    'alamat' => $schema->string(),
                ]))
                ->required(),
        ];
    }
}
