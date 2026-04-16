<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\Member;
use App\Models\Jasa;
use App\Models\Gudang;
use App\Models\AkunTransaksi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProdukController extends Controller
{
    public function index()
    {
        $produks = Produk::with(['brand', 'kategori'])->paginate(15);
        $brands = Brand::all();
        $kategoris = Kategori::all();

        return Inertia::render('app/admin/master-data/produk/Index', [
            'produks' => $produks,
            'brands' => $brands,
            'kategoris' => $kategoris,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'kategori_id' => 'nullable|exists:md_kategori,id',
            'brand_id' => 'nullable|exists:md_brand,id',
            'sku' => 'nullable|string|max:255|unique:md_produk,sku',
            'sn' => 'nullable|string|max:255',
            'garansi' => 'nullable|string|max:255',
            'berat' => 'nullable|numeric|min:0',
            'panjang' => 'nullable|numeric|min:0',
            'lebar' => 'nullable|numeric|min:0',
            'tinggi' => 'nullable|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'image_url' => 'nullable|string',
        ]);

        if (empty($validated['sku'])) {
            $validated['sku'] = Produk::generateDefaultSku();
        }

        Produk::create($validated);

        return redirect()->back()->with('success', 'Product created successfully');
    }

    public function update(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'kategori_id' => 'nullable|exists:md_kategori,id',
            'brand_id' => 'nullable|exists:md_brand,id',
            'sku' => 'required|string|max:255|unique:md_produk,sku,' . $produk->id,
            'sn' => 'nullable|string|max:255',
            'garansi' => 'nullable|string|max:255',
            'berat' => 'nullable|numeric|min:0',
            'panjang' => 'nullable|numeric|min:0',
            'lebar' => 'nullable|numeric|min:0',
            'tinggi' => 'nullable|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'image_url' => 'nullable|string',
        ]);

        $produk->update($validated);

        return redirect()->back()->with('success', 'Product updated successfully');
    }

    public function destroy(Produk $produk)
    {
        $produk->delete();
        return redirect()->back()->with('success', 'Product deleted successfully');
    }
}
