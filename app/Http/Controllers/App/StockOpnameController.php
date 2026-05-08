<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Produk;
use App\Models\PembelianItem;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        $query = StockOpname::with(['gudang', 'user'])
            ->latest('tanggal')
            ->latest('id');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('kode', 'like', "%{$request->search}%")
                  ->orWhere('catatan', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $opnames = $query->paginate(15)->withQueryString();

        return Inertia::render('app/admin/inventory/stock-opname/Index', [
            'opnames'  => $opnames,
            'gudangs'  => Gudang::where('is_active', true)->orderBy('nama_gudang')->get(),
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('app/admin/inventory/stock-opname/Create', [
            'gudangs' => Gudang::where('is_active', true)->orderBy('nama_gudang')->get(),
            'produks' => $this->getAllProductBatches(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal'   => 'required|date',
            'gudang_id' => 'nullable|exists:md_gudang,id',
            'catatan'   => 'nullable|string|max:1000',
            'items'     => 'required|array|min:1',
            'items.*.produk_id'        => 'required|exists:md_produk,id',
            'items.*.pembelian_item_id' => 'nullable|exists:tb_pembelian_item,id_pembelian_item',
            'items.*.stok_fisik'       => 'required|integer|min:0',
            'items.*.catatan'          => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data) {
            $opname = StockOpname::create([
                'tanggal'   => $data['tanggal'],
                'gudang_id' => $data['gudang_id'] ?? null,
                'catatan'   => $data['catatan'] ?? null,
                'user_id'   => Auth::id(),
                'status'    => 'draft',
            ]);

            $qtySisaCol = PembelianItem::qtySisaColumn();

            foreach ($data['items'] as $item) {
                $stokSistem = 0;
                if (!empty($item['pembelian_item_id'])) {
                    $batch = PembelianItem::where('id_pembelian_item', $item['pembelian_item_id'])->first();
                    $stokSistem = $batch ? (int) ($batch->{$qtySisaCol} ?? 0) : 0;
                }

                $opname->items()->create([
                    'produk_id'        => $item['produk_id'],
                    'pembelian_item_id'=> $item['pembelian_item_id'] ?? null,
                    'stok_sistem'      => $stokSistem,
                    'stok_fisik'       => $item['stok_fisik'],
                    'catatan'          => $item['catatan'] ?? null,
                    // selisih auto-calculated by model booted
                ]);
            }
        });

        return redirect()->route('app.stock-opname')
            ->with('success', 'Stock opname created successfully.');
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load([
            'items.produk.brand',
            'items.produk.kategori',
            'items.pembelianItem',
            'gudang',
            'user',
            'postedBy',
        ]);

        return Inertia::render('app/admin/inventory/stock-opname/Show', [
            'opname' => $stockOpname,
        ]);
    }

    public function edit(StockOpname $stockOpname)
    {
        if ($stockOpname->isPosted()) {
            return redirect()->route('app.stock-opname')
                ->with('error', 'Cannot edit a posted stock opname.');
        }

        $stockOpname->load(['items.produk', 'items.pembelianItem', 'gudang']);

        return Inertia::render('app/admin/inventory/stock-opname/Edit', [
            'opname'  => $stockOpname,
            'gudangs' => Gudang::where('is_active', true)->orderBy('nama_gudang')->get(),
            'produks' => $this->getAllProductBatches(),
        ]);
    }

    public function update(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->isPosted()) {
            return back()->withErrors(['status' => 'Cannot edit a posted stock opname.']);
        }

        $data = $request->validate([
            'tanggal'   => 'required|date',
            'gudang_id' => 'nullable|exists:md_gudang,id',
            'catatan'   => 'nullable|string|max:1000',
            'items'     => 'required|array|min:1',
            'items.*.produk_id'        => 'required|exists:md_produk,id',
            'items.*.pembelian_item_id' => 'nullable|exists:tb_pembelian_item,id_pembelian_item',
            'items.*.stok_fisik'       => 'required|integer|min:0',
            'items.*.catatan'          => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data, $stockOpname) {
            $stockOpname->update([
                'tanggal'   => $data['tanggal'],
                'gudang_id' => $data['gudang_id'] ?? null,
                'catatan'   => $data['catatan'] ?? null,
            ]);

            $stockOpname->items()->delete();

            $qtySisaCol = PembelianItem::qtySisaColumn();

            foreach ($data['items'] as $item) {
                $stokSistem = 0;
                if (!empty($item['pembelian_item_id'])) {
                    $batch = PembelianItem::where('id_pembelian_item', $item['pembelian_item_id'])->first();
                    $stokSistem = $batch ? (int) ($batch->{$qtySisaCol} ?? 0) : 0;
                }

                $stockOpname->items()->create([
                    'produk_id'        => $item['produk_id'],
                    'pembelian_item_id'=> $item['pembelian_item_id'] ?? null,
                    'stok_sistem'      => $stokSistem,
                    'stok_fisik'       => $item['stok_fisik'],
                    'catatan'          => $item['catatan'] ?? null,
                ]);
            }
        });

        return redirect()->route('app.stock-opname')
            ->with('success', 'Stock opname updated successfully.');
    }

    public function destroy(StockOpname $stockOpname)
    {
        if ($stockOpname->isPosted()) {
            return back()->withErrors(['status' => 'Cannot delete a posted stock opname.']);
        }

        $stockOpname->items()->delete();
        $stockOpname->delete();

        return redirect()->route('app.stock-opname')
            ->with('success', 'Stock opname deleted.');
    }

    public function post(StockOpname $stockOpname)
    {
        if ($stockOpname->isPosted()) {
            return back()->withErrors(['status' => 'Already posted.']);
        }

        DB::transaction(function () use ($stockOpname) {
            $stockOpname->post(Auth::user());
        });

        return back()->with('success', 'Stock opname posted successfully.');
    }

    private function getAllProductBatches(): \Illuminate\Support\Collection
    {
        $qtySisaCol = PembelianItem::qtySisaColumn();
        $fkCol      = PembelianItem::productForeignKey();

        return Produk::select('md_produk.id', 'nama_produk', 'sku')
            ->with([
                'brand:id,nama_brand',
                'kategori:id,nama_kategori',
            ])
            ->orderBy('nama_produk')
            ->get()
            ->map(function ($p) use ($qtySisaCol, $fkCol) {
                $batches = PembelianItem::where($fkCol, $p->id)
                    ->select(['id_pembelian_item', $qtySisaCol, 'cost_price', 'kondisi'])
                    ->with('pembelian:id_pembelian,no_po')
                    ->get()
                    ->map(fn ($b) => [
                        'id'          => $b->id_pembelian_item,
                        'qty_sisa'    => (int) ($b->{$qtySisaCol} ?? 0),
                        'cost_price'  => (float) $b->cost_price,
                        'kondisi'     => $b->kondisi,
                        'no_po'       => $b->pembelian?->no_po,
                    ]);

                return [
                    'id'          => $p->id,
                    'nama_produk' => $p->nama_produk,
                    'sku'         => $p->sku,
                    'brand'       => $p->brand,
                    'kategori'    => $p->kategori,
                    'batches'     => $batches,
                ];
            });
    }
}
