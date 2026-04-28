<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Produk;
use App\Models\PembelianItem;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['gudang', 'user'])
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

        $adjustments = $query->paginate(15)->withQueryString();

        return Inertia::render('app/admin/inventory/stock-adjustment/Index', [
            'adjustments' => $adjustments,
            'gudangs'     => Gudang::where('is_active', true)->orderBy('nama_gudang')->get(),
            'filters'     => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('app/admin/inventory/stock-adjustment/Create', [
            'gudangs' => Gudang::where('is_active', true)->orderBy('nama_gudang')->get(),
            'produks' => $this->getStockableProducts(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal'    => 'required|date',
            'gudang_id'  => 'nullable|exists:md_gudang,id',
            'catatan'    => 'nullable|string|max:1000',
            'items'      => 'required|array|min:1',
            'items.*.produk_id'        => 'required|exists:md_produk,id',
            'items.*.pembelian_item_id' => 'nullable|exists:tb_pembelian_item,id_pembelian_item',
            'items.*.qty'              => 'required|integer',
            'items.*.keterangan'       => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data) {
            $adjustment = StockAdjustment::create([
                'tanggal'   => $data['tanggal'],
                'gudang_id' => $data['gudang_id'] ?? null,
                'catatan'   => $data['catatan'] ?? null,
                'user_id'   => Auth::id(),
                'status'    => 'draft',
            ]);

            foreach ($data['items'] as $item) {
                $adjustment->items()->create([
                    'produk_id'        => $item['produk_id'],
                    'pembelian_item_id'=> $item['pembelian_item_id'] ?? null,
                    'qty'              => $item['qty'],
                    'keterangan'       => $item['keterangan'] ?? null,
                ]);
            }
        });

        return redirect()->route('app.stock-adjustment')
            ->with('success', 'Stock adjustment created successfully.');
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load([
            'items.produk.brand',
            'items.produk.kategori',
            'items.pembelianItem',
            'gudang',
            'user',
            'postedBy',
        ]);

        return Inertia::render('app/admin/inventory/stock-adjustment/Show', [
            'adjustment' => $stockAdjustment,
        ]);
    }

    public function edit(StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->isPosted()) {
            return redirect()->route('app.stock-adjustment')
                ->with('error', 'Cannot edit a posted stock adjustment.');
        }

        $stockAdjustment->load(['items.produk', 'items.pembelianItem', 'gudang']);

        return Inertia::render('app/admin/inventory/stock-adjustment/Edit', [
            'adjustment' => $stockAdjustment,
            'gudangs'    => Gudang::where('is_active', true)->orderBy('nama_gudang')->get(),
            'produks'    => $this->getStockableProducts(),
        ]);
    }

    public function update(Request $request, StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->isPosted()) {
            return back()->withErrors(['status' => 'Cannot edit a posted stock adjustment.']);
        }

        $data = $request->validate([
            'tanggal'    => 'required|date',
            'gudang_id'  => 'nullable|exists:md_gudang,id',
            'catatan'    => 'nullable|string|max:1000',
            'items'      => 'required|array|min:1',
            'items.*.produk_id'        => 'required|exists:md_produk,id',
            'items.*.pembelian_item_id' => 'nullable|exists:tb_pembelian_item,id_pembelian_item',
            'items.*.qty'              => 'required|integer',
            'items.*.keterangan'       => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data, $stockAdjustment) {
            $stockAdjustment->update([
                'tanggal'   => $data['tanggal'],
                'gudang_id' => $data['gudang_id'] ?? null,
                'catatan'   => $data['catatan'] ?? null,
            ]);

            $stockAdjustment->items()->delete();

            foreach ($data['items'] as $item) {
                $stockAdjustment->items()->create([
                    'produk_id'        => $item['produk_id'],
                    'pembelian_item_id'=> $item['pembelian_item_id'] ?? null,
                    'qty'              => $item['qty'],
                    'keterangan'       => $item['keterangan'] ?? null,
                ]);
            }
        });

        return redirect()->route('app.stock-adjustment')
            ->with('success', 'Stock adjustment updated successfully.');
    }

    public function destroy(StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->isPosted()) {
            return back()->withErrors(['status' => 'Cannot delete a posted stock adjustment.']);
        }

        $stockAdjustment->items()->delete();
        $stockAdjustment->delete();

        return redirect()->route('app.stock-adjustment')
            ->with('success', 'Stock adjustment deleted.');
    }

    public function post(StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->isPosted()) {
            return back()->withErrors(['status' => 'Already posted.']);
        }

        DB::transaction(function () use ($stockAdjustment) {
            $stockAdjustment->post(Auth::user());
        });

        return back()->with('success', 'Stock adjustment posted successfully.');
    }

    private function getStockableProducts(): \Illuminate\Support\Collection
    {
        $qtySisaCol = PembelianItem::qtySisaColumn();
        $fkCol      = PembelianItem::productForeignKey();

        return Produk::select('md_produk.id', 'nama_produk', 'sku')
            ->with(['brand:id,nama_brand', 'kategori:id,nama_kategori'])
            ->whereExists(function ($q) {
                $q->from('tb_pembelian_item')
                  ->whereColumn('tb_pembelian_item.' . PembelianItem::productForeignKey(), 'md_produk.id')
                  ->where('tb_pembelian_item.' . PembelianItem::qtySisaColumn(), '>', 0);
            })
            ->orderBy('nama_produk')
            ->get();
    }
}
