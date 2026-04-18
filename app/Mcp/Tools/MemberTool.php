<?php

namespace App\Mcp\Tools;

use App\Models\Member;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Mencari dan menampilkan data member/pelanggan. Dapat melihat riwayat pembelian member dan informasi kontak.')]
class MemberTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $query = Member::query()
            ->withCount('penjualans')
            ->withSum('penjualans', 'total');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_member', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $limit = $request->get('limit', 20);
        $members = $query->limit($limit)->get();

        $data = $members->map(function ($member) {
            return [
                'id' => $member->id,
                'nama' => $member->nama_member,
                'no_hp' => $member->no_hp ?? '-',
                'email' => $member->email ?? '-',
                'alamat' => $member->alamat ?? '-',
            ];
        });

        return Response::structured([
            'total' => $data->count(),
            'member' => $data->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Kata kunci pencarian (nama, no HP, atau email)'),
            'limit' => $schema->integer()
                ->description('Jumlah maksimal member yang ditampilkan (default: 20)'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total' => $schema->integer()->description('Jumlah member ditemukan')->required(),
            'member' => $schema->array()
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
