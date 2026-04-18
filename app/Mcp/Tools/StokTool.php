<?php

namespace App\Mcp\Tools;

use App\Models\Produk;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Melihat daftar produk di sistem Arabica.')]
class StokTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $query = Produk::query()
            ->with(['brand', 'kategori']);

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->get('brand_id'));
        }

        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->get('kategori_id'));
        }

        $limit = $request->get('limit', 50);
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
            'total_produk' => $data->count(),
            'produk' => $data->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'brand_id' => $schema->integer()
                ->description('Filter berdasarkan ID brand'),
            'kategori_id' => $schema->integer()
                ->description('Filter berdasarkan ID kategori'),
            'limit' => $schema->integer()
                ->description('Jumlah maksimal produk yang ditampilkan (default: 50)'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total_produk' => $schema->integer()->description('Total produk ditemukan')->required(),
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
