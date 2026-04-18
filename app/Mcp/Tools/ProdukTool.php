<?php

namespace App\Mcp\Tools;

use App\Models\Produk;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Mencari dan menampilkan data produk dari sistem POS Arabica. Dapat mencari berdasarkan nama, SKU, brand, atau kategori.')]
class ProdukTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $query = Produk::query()
            ->with(['brand', 'kategori', 'supplier']);

        // Filter berdasarkan parameter
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->get('brand_id'));
        }

        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->get('kategori_id'));
        }

        // Pagination
        $limit = $request->get('limit', 20);
        $produks = $query->limit($limit)->get();

        $data = $produks->map(function ($produk) {
            return [
                'id' => $produk->id,
                'nama' => $produk->nama_produk,
                'sku' => $produk->sku,
                'brand' => $produk->brand?->nama_brand ?? '-',
                'kategori' => $produk->kategori?->nama_kategori ?? '-',
            ];
        });

        return Response::structured([
            'total' => $data->count(),
            'produk' => $data->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Kata kunci pencarian (nama produk atau SKU)'),
            'brand_id' => $schema->integer()
                ->description('Filter berdasarkan ID brand'),
            'kategori_id' => $schema->integer()
                ->description('Filter berdasarkan ID kategori'),
            'limit' => $schema->integer()
                ->description('Jumlah maksimal produk yang ditampilkan (default: 20)'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total' => $schema->integer()->description('Jumlah produk ditemukan')->required(),
            'produk' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer(),
                    'nama' => $schema->string(),
                    'sku' => $schema->string(),
                    'brand' => $schema->string(),
                    'kategori' => $schema->string(),
                ]))
                ->required(),
        ];
    }
}
