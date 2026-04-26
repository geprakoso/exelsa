<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Supplier;
use App\Models\Produk;
use App\Models\Karyawan;
use App\Models\AkunTransaksi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PembelianController extends Controller
{
    /**
     * Display a listing of pembelian.
     */
    public function index(Request $request)
    {
        $query = Pembelian::with(['supplier', 'karyawan'])
            ->orderBy('tanggal', 'desc');

        // Filter by date range
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('tanggal', [$request->from, $request->to]);
        }

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            if ($request->status === 'lunas') {
                $query->where('jenis_pembayaran', 'cash');
            } elseif ($request->status === 'belum_lunas') {
                $query->where('jenis_pembayaran', '!=', 'cash');
            }
        }

        // Search by no_po or supplier name
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('no_po', 'like', '%' . $request->search . '%')
                    ->orWhereHas('supplier', function ($sq) use ($request) {
                        $sq->where('nama_supplier', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $pembelians = $query->paginate(15)->withQueryString();

        // Stats summary
        $stats = [
            'total_count' => Pembelian::count(),
            'cash_count' => Pembelian::where('jenis_pembayaran', 'cash')->count(),
            'kredit_count' => Pembelian::where('jenis_pembayaran', 'kredit')->count(),
            'total_nilai' => Pembelian::sum('harga_jual') ?? 0,
        ];

        return Inertia::render('app/admin/transactions/pembelian/Index', [
            'pembelians' => $pembelians,
            'stats' => $stats,
            'filters' => $request->only(['from', 'to', 'status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new pembelian.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('nama_supplier')->get(['id', 'kode_supplier', 'nama_supplier']);
        $karyawans = Karyawan::orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        
        $produks = Produk::with(['brand', 'kategori'])
            ->orderBy('nama_produk')
            ->get(['id', 'nama_produk', 'sku']);

        $paymentAccounts = AkunTransaksi::where('jenis', 'kas')
            ->orWhere('jenis', 'bank')
            ->orderBy('nama_akun')
            ->get(['id', 'kode_akun', 'nama_akun', 'jenis']);

        return Inertia::render('app/admin/transactions/pembelian/Create', [
            'suppliers' => $suppliers,
            'karyawans' => $karyawans,
            'produks' => $produks,
            'paymentAccounts' => $paymentAccounts,
            'jenisPembayaranOptions' => [
                ['value' => 'cash', 'label' => 'Cash (Tunai)'],
                ['value' => 'kredit', 'label' => 'Kredit (Tempo)'],
                ['value' => 'transfer', 'label' => 'Transfer'],
            ],
        ]);
    }

    /**
     * Store a newly created pembelian.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'id_supplier' => 'nullable|exists:md_suppliers,id',
            'id_karyawan' => 'nullable|exists:md_karyawan,id',
            'nota_supplier' => 'nullable|string',
            'catatan' => 'nullable|string',
            'tipe_pembelian' => 'nullable|string',
            'jenis_pembayaran' => 'nullable|string',
            'tgl_tempo' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.id_produk' => 'required|exists:md_produk,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric|min:0',
        ]);

        // Calculate total
        $total = collect($validated['items'])->sum(function ($item) {
            return $item['qty'] * $item['harga'];
        });

        // Create pembelian
        $pembelian = Pembelian::create([
            'tanggal' => $validated['tanggal'],
            'id_supplier' => $validated['id_supplier'] ?? null,
            'id_karyawan' => $validated['id_karyawan'] ?? auth()->id(),
            'nota_supplier' => $validated['nota_supplier'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'tipe_pembelian' => $validated['tipe_pembelian'] ?? 'barang',
            'jenis_pembayaran' => $validated['jenis_pembayaran'] ?? 'cash',
            'tgl_tempo' => $validated['tgl_tempo'] ?? null,
            'harga_jual' => $total, // Using harga_jual field for total purchase value
        ]);

        // Create items
        foreach ($validated['items'] as $itemData) {
            PembelianItem::create([
                'id_pembelian' => $pembelian->id_pembelian,
                'id_produk' => $itemData['id_produk'],
                'qty' => $itemData['qty'],
                'qty_masuk' => 0,
                'qty_sisa' => $itemData['qty'],
                'harga' => $itemData['harga'],
                'subtotal' => $itemData['qty'] * $itemData['harga'],
            ]);
        }

        return redirect()->route('app.pembelian.show', $pembelian->id_pembelian)
            ->with('success', 'Pembelian created successfully.');
    }

    /**
     * Display the specified pembelian.
     */
    public function show(Pembelian $pembelian)
    {
        $pembelian->load([
            'items.produk.brand',
            'supplier',
            'karyawan',
        ]);

        return Inertia::render('app/admin/transactions/pembelian/Show', [
            'pembelian' => $pembelian,
        ]);
    }

    /**
     * Show the form for editing the specified pembelian.
     */
    public function edit(Pembelian $pembelian)
    {
        $pembelian->load(['items']);

        $suppliers = Supplier::orderBy('nama_supplier')->get(['id', 'kode_supplier', 'nama_supplier']);
        $karyawans = Karyawan::orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        $produks = Produk::with(['brand', 'kategori'])
            ->orderBy('nama_produk')
            ->get(['id', 'nama_produk', 'sku']);

        $paymentAccounts = AkunTransaksi::where('jenis_akun', 'kas')
            ->orWhere('jenis_akun', 'bank')
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'jenis_akun']);

        return Inertia::render('app/admin/transactions/pembelian/Edit', [
            'pembelian' => $pembelian,
            'suppliers' => $suppliers,
            'karyawans' => $karyawans,
            'produks' => $produks,
            'paymentAccounts' => $paymentAccounts,
            'jenisPembayaranOptions' => [
                ['value' => 'cash', 'label' => 'Cash (Tunai)'],
                ['value' => 'kredit', 'label' => 'Kredit (Tempo)'],
                ['value' => 'transfer', 'label' => 'Transfer'],
            ],
        ]);
    }

    /**
     * Update the specified pembelian.
     */
    public function update(Request $request, Pembelian $pembelian)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'id_supplier' => 'nullable|exists:md_suppliers,id',
            'id_karyawan' => 'nullable|exists:md_karyawan,id',
            'nota_supplier' => 'nullable|string',
            'catatan' => 'nullable|string',
            'tipe_pembelian' => 'nullable|string',
            'jenis_pembayaran' => 'nullable|string',
            'tgl_tempo' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:tb_pembelian_item,id_pembelian_item',
            'items.*.id_produk' => 'required|exists:md_produk,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric|min:0',
        ]);

        // Calculate total
        $total = collect($validated['items'])->sum(function ($item) {
            return $item['qty'] * $item['harga'];
        });

        // Update pembelian
        $pembelian->update([
            'tanggal' => $validated['tanggal'],
            'id_supplier' => $validated['id_supplier'] ?? null,
            'id_karyawan' => $validated['id_karyawan'] ?? auth()->id(),
            'nota_supplier' => $validated['nota_supplier'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'tipe_pembelian' => $validated['tipe_pembelian'] ?? 'barang',
            'jenis_pembayaran' => $validated['jenis_pembayaran'] ?? 'cash',
            'tgl_tempo' => $validated['tgl_tempo'] ?? null,
            'harga_jual' => $total,
        ]);

        // Sync items
        $existingIds = collect($validated['items'])->pluck('id')->filter()->toArray();
        $pembelian->items()->whereNotIn('id_pembelian_item', $existingIds)->delete();

        foreach ($validated['items'] as $itemData) {
            if (!empty($itemData['id'])) {
                $item = PembelianItem::find($itemData['id']);
                $item->update([
                    'id_produk' => $itemData['id_produk'],
                    'qty' => $itemData['qty'],
                    'harga' => $itemData['harga'],
                    'subtotal' => $itemData['qty'] * $itemData['harga'],
                ]);
            } else {
                PembelianItem::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_produk' => $itemData['id_produk'],
                    'qty' => $itemData['qty'],
                    'qty_masuk' => 0,
                    'qty_sisa' => $itemData['qty'],
                    'harga' => $itemData['harga'],
                    'subtotal' => $itemData['qty'] * $itemData['harga'],
                ]);
            }
        }

        return redirect()->route('app.pembelian.show', $pembelian->id_pembelian)
            ->with('success', 'Pembelian updated successfully.');
    }

    /**
     * Remove the specified pembelian.
     */
    public function destroy(Pembelian $pembelian)
    {
        $pembelian->delete();

        return redirect()->route('app.pembelian.index')
            ->with('success', 'Pembelian deleted successfully.');
    }
}
