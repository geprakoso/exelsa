<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Arabica POS System')]
#[Version('1.0.0')]
#[Instructions(<<<'INSTRUCTIONS'
MCP Server untuk sistem POS Arabica. Server ini menyediakan tools untuk:
- Mengelola data produk, brand, kategori, supplier
- Melihat dan menganalisis data penjualan dan pembelian
- Mengelola data member dan karyawan
- Melihat laporan keuangan (laba rugi, neraca)
- Mengelola stok dan inventory

Gunakan tools yang tersedia untuk membantu user dalam mengelola bisnis mereka.
INSTRUCTIONS
)]
class ArabicaServer extends Server
{
    protected array $tools = [
        \App\Mcp\Tools\ProdukTool::class,
        \App\Mcp\Tools\PenjualanTool::class,
        \App\Mcp\Tools\MemberTool::class,
        \App\Mcp\Tools\KaryawanTool::class,
        \App\Mcp\Tools\SupplierTool::class,
        \App\Mcp\Tools\StokTool::class,
        \App\Mcp\Tools\LaporanTool::class,
    ];

    protected array $resources = [
        \App\Mcp\Resources\DashboardResource::class,
    ];

    protected array $prompts = [];
}
