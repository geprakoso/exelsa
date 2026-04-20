<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Produk;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProdukController extends Controller
{
    public function index()
    {
        $produks = Produk::with(['brand', 'kategori', 'images' => function ($query) {
            $query->ordered();
        }])->paginate(15);
        
        $brands = Brand::all();
        $kategoris = Kategori::all();

        return Inertia::render('app/admin/master-data/produk/Index', [
            'produks' => $produks,
            'brands' => $brands,
            'kategoris' => $kategoris,
        ]);
    }

    public function store(Request $request, ImageUploadService $uploadService)
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
        ]);

        if (empty($validated['sku'])) {
            $validated['sku'] = Produk::generateDefaultSku();
        }

        $produk = Produk::create($validated);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $uploadService->uploadMultiple($request->file('images'), $produk->id);
        }

        return redirect()->route('app.produk')->with('success', 'Produk berhasil dibuat');
    }

    public function update(Request $request, Produk $produk, ImageUploadService $uploadService)
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
        ]);

        $produk->update($validated);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $currentCount = $produk->images()->count();
            $maxUpload = 10 - $currentCount;
            
            if ($maxUpload > 0) {
                $files = array_slice($request->file('images'), 0, $maxUpload);
                $uploadService->uploadMultiple($files, $produk->id);
            }
        }

        return redirect()->route('app.produk')->with('success', 'Product updated successfully');
    }

    public function destroy(Produk $produk)
    {
        $produk->delete();
        return redirect()->back()->with('success', 'Product deleted successfully');
    }
}
