<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use App\Models\PenjualanPembayaran;
use App\Models\Member;
use App\Models\Produk;
use App\Models\Karyawan;
use App\Models\Gudang;
use App\Models\AkunTransaksi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PenjualanController extends Controller
{
    /**
     * Display a listing of penjualan.
     */
    public function index(Request $request)
    {
        $query = Penjualan::with(['member', 'karyawan'])
            ->orderBy('tanggal_penjualan', 'desc');

        // Filter by date range
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('tanggal_penjualan', [$request->from, $request->to]);
        }

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status_pembayaran', $request->status);
        }

        // Search by no_nota or member name
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('no_nota', 'like', '%' . $request->search . '%')
                    ->orWhereHas('member', function ($mq) use ($request) {
                        $mq->where('nama_member', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $penjualans = $query->paginate(15)->withQueryString();

        // Stats summary
        $stats = [
            'total_count' => Penjualan::count(),
            'lunas_count' => Penjualan::where('status_pembayaran', 'lunas')->count(),
            'belum_lunas_count' => Penjualan::where('status_pembayaran', 'belum_lunas')->count(),
            'total_revenue' => Penjualan::where('status_pembayaran', 'lunas')->sum('grand_total'),
        ];

        return Inertia::render('app/admin/transactions/penjualan/Index', [
            'penjualans' => $penjualans,
            'stats' => $stats,
            'filters' => $request->only(['from', 'to', 'status', 'search']),
            'members' => Member::orderBy('nama_member')->get(['id', 'kode_member', 'nama_member']),
            'karyawans' => Karyawan::orderBy('nama_karyawan')->get(['id', 'nama_karyawan']),
            'gudangs' => Gudang::orderBy('nama_gudang')->get(['id', 'nama_gudang']),
            'produks' => Produk::with(['brand', 'kategori'])->orderBy('nama_produk')->get(['id', 'nama_produk', 'sku']),
            'paymentAccounts' => AkunTransaksi::where('jenis', 'kas')->orWhere('jenis', 'bank')->orderBy('nama_akun')->get(['id', 'kode_akun', 'nama_akun', 'jenis']),
            'metodeBayarOptions' => [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'card', 'label' => 'Kartu'],
                ['value' => 'transfer', 'label' => 'Transfer'],
                ['value' => 'ewallet', 'label' => 'E-Wallet'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new penjualan.
     */
    public function create()
    {
        $members = Member::orderBy('nama_member')->get(['id', 'kode_member', 'nama_member']);
        $karyawans = Karyawan::orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        $gudangs = Gudang::orderBy('nama_gudang')->get(['id', 'nama_gudang']);
        
        // Get products with stock info
        $produks = Produk::with(['brand', 'kategori'])
            ->orderBy('nama_produk')
            ->get(['id', 'nama_produk', 'sku']);

        // Get payment accounts for payments
        $paymentAccounts = AkunTransaksi::where('jenis', 'kas')
            ->orWhere('jenis', 'bank')
            ->orderBy('nama_akun')
            ->get(['id', 'kode_akun', 'nama_akun', 'jenis']);

        return Inertia::render('app/admin/transactions/penjualan/Create', [
            'members' => $members,
            'karyawans' => $karyawans,
            'gudangs' => $gudangs,
            'produks' => $produks,
            'paymentAccounts' => $paymentAccounts,
            'metodeBayarOptions' => [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'card', 'label' => 'Kartu'],
                ['value' => 'transfer', 'label' => 'Transfer'],
                ['value' => 'ewallet', 'label' => 'E-Wallet'],
            ],
        ]);
    }

    /**
     * Store a newly created penjualan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_penjualan' => 'required|date',
            'id_member' => 'nullable|exists:md_members,id',
            'id_karyawan' => 'nullable|exists:md_karyawan,id',
            'gudang_id' => 'nullable|exists:md_gudang,id',
            'catatan' => 'nullable|string',
            'diskon_total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id_produk' => 'required|exists:md_produk,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0',
            'pembayarans' => 'nullable|array',
            'pembayarans.*.metode_bayar' => 'required_with:pembayarans|string',
            'pembayarans.*.akun_transaksi_id' => 'nullable|exists:akun_transaksis,id',
            'pembayarans.*.jumlah' => 'required_with:pembayarans|numeric|min:0',
        ]);

        // Generate no_nota
        $prefix = 'JL' . date('ymd');
        $latestNota = Penjualan::where('no_nota', 'like', $prefix . '%')
            ->orderBy('no_nota', 'desc')
            ->first();
        
        $sequence = $latestNota ? (intval(substr($latestNota->no_nota, -4)) + 1) : 1;
        $noNota = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $itemsTotal = collect($validated['items'])->sum(function ($item) {
            return $item['qty'] * $item['selling_price'];
        });
        
        $diskonTotal = $validated['diskon_total'] ?? 0;
        $grandTotal = $itemsTotal - $diskonTotal;

        // Create penjualan
        $penjualan = Penjualan::create([
            'no_nota' => $noNota,
            'tanggal_penjualan' => $validated['tanggal_penjualan'],
            'id_member' => $validated['id_member'] ?? null,
            'id_karyawan' => $validated['id_karyawan'] ?? auth()->id(),
            'gudang_id' => $validated['gudang_id'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'total' => $itemsTotal,
            'diskon_total' => $diskonTotal,
            'grand_total' => $grandTotal,
            'status_pembayaran' => $grandTotal <= 0 ? 'lunas' : 'belum_lunas',
        ]);

        // Create items
        foreach ($validated['items'] as $itemData) {
            PenjualanItem::create([
                'id_penjualan' => $penjualan->id_penjualan,
                'id_produk' => $itemData['id_produk'],
                'qty' => $itemData['qty'],
                'selling_price' => $itemData['selling_price'],
            ]);
        }

        // Create payments if provided
        if (!empty($validated['pembayarans'])) {
            foreach ($validated['pembayarans'] as $paymentData) {
                if (($paymentData['jumlah'] ?? 0) > 0) {
                    PenjualanPembayaran::create([
                        'id_penjualan' => $penjualan->id_penjualan,
                        'tanggal' => $validated['tanggal_penjualan'],
                        'metode_bayar' => $paymentData['metode_bayar'],
                        'akun_transaksi_id' => $paymentData['akun_transaksi_id'] ?? null,
                        'jumlah' => $paymentData['jumlah'],
                    ]);
                }
            }

            // Check if fully paid
            $totalPaid = collect($validated['pembayarans'])->sum('jumlah');
            if ($totalPaid >= $grandTotal) {
                $penjualan->update(['status_pembayaran' => 'lunas']);
            }
        }

        return redirect()->route('app.penjualan.show', $penjualan->id_penjualan)
            ->with('success', 'Penjualan created successfully.');
    }

    /**
     * Display the specified penjualan.
     */
    public function show(Penjualan $penjualan)
    {
        $penjualan->load([
            'items.produk.brand',
            'items.pembelianItem',
            'jasaItems.jasa',
            'member',
            'karyawan',
            'pembayaran.akunTransaksi',
            'gudang',
        ]);

        return Inertia::render('app/admin/transactions/penjualan/Show', [
            'penjualan' => $penjualan,
        ]);
    }

    /**
     * Show the form for editing the specified penjualan.
     */
    public function edit(Penjualan $penjualan)
    {
        $penjualan->load([
            'items',
            'jasaItems',
            'pembayaran',
            'member',
            'karyawan',
        ]);

        $members = Member::orderBy('nama_member')->get(['id', 'kode_member', 'nama_member']);
        $karyawans = Karyawan::orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        $gudangs = Gudang::orderBy('nama_gudang')->get(['id', 'nama_gudang']);
        $produks = Produk::with(['brand', 'kategori'])
            ->orderBy('nama_produk')
            ->get(['id', 'nama_produk', 'sku']);

        return Inertia::render('app/admin/transactions/penjualan/Edit', [
            'penjualan' => $penjualan,
            'members' => $members,
            'karyawans' => $karyawans,
            'gudangs' => $gudangs,
            'produks' => $produks,
        ]);
    }

    /**
     * Update the specified penjualan.
     */
    public function update(Request $request, Penjualan $penjualan)
    {
        $validated = $request->validate([
            'tanggal_penjualan' => 'required|date',
            'id_member' => 'nullable|exists:md_members,id',
            'id_karyawan' => 'nullable|exists:md_karyawan,id',
            'gudang_id' => 'nullable|exists:md_gudang,id',
            'catatan' => 'nullable|string',
            'diskon_total' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:tb_penjualan_item,id_penjualan_item',
            'items.*.id_produk' => 'required|exists:md_produk,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0',
        ]);

        // Calculate totals
        $itemsTotal = collect($validated['items'])->sum(function ($item) {
            return $item['qty'] * $item['selling_price'];
        });
        
        $diskonTotal = $validated['diskon_total'] ?? 0;
        $grandTotal = $itemsTotal - $diskonTotal;

        // Update penjualan
        $penjualan->update([
            'tanggal_penjualan' => $validated['tanggal_penjualan'],
            'id_member' => $validated['id_member'] ?? null,
            'id_karyawan' => $validated['id_karyawan'] ?? auth()->id(),
            'gudang_id' => $validated['gudang_id'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'total' => $itemsTotal,
            'diskon_total' => $diskonTotal,
            'grand_total' => $grandTotal,
        ]);

        // Sync items - delete removed, update/add existing
        $existingIds = collect($validated['items'])->pluck('id')->filter()->toArray();
        
        // Delete items not in the new list
        $penjualan->items()->whereNotIn('id_penjualan_item', $existingIds)->delete();

        // Update or create items
        foreach ($validated['items'] as $itemData) {
            if (!empty($itemData['id'])) {
                // Update existing
                $item = PenjualanItem::find($itemData['id']);
                $item->update([
                    'id_produk' => $itemData['id_produk'],
                    'qty' => $itemData['qty'],
                    'selling_price' => $itemData['selling_price'],
                ]);
            } else {
                // Create new
                PenjualanItem::create([
                    'id_penjualan' => $penjualan->id_penjualan,
                    'id_produk' => $itemData['id_produk'],
                    'qty' => $itemData['qty'],
                    'selling_price' => $itemData['selling_price'],
                ]);
            }
        }

        return redirect()->route('app.penjualan.show', $penjualan->id_penjualan)
            ->with('success', 'Penjualan updated successfully.');
    }

    /**
     * Remove the specified penjualan.
     */
    public function destroy(Penjualan $penjualan)
    {
        $penjualan->delete();

        return redirect()->route('app.penjualan.index')
            ->with('success', 'Penjualan deleted successfully.');
    }
}
