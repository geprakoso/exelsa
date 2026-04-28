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
    public function index(Request $request)
    {
        $produks = Produk::with(['brand', 'kategori', 'images' => function ($query) {
            $query->ordered();
        }])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_produk', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->brand_id, function ($query, $brandId) {
                $query->where('brand_id', $brandId);
            })
            ->when($request->kategori_id, function ($query, $kategoriId) {
                $query->where('kategori_id', $kategoriId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $brands = Brand::all();
        $kategoris = Kategori::all();

        return Inertia::render('app/admin/master-data/produk/Index', [
            'produks' => $produks,
            'brands' => $brands,
            'kategoris' => $kategoris,
            'filters' => $request->only(['search', 'brand_id', 'kategori_id']),
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

        return redirect()->back()->with('success', 'Produk berhasil dibuat');
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

        return redirect()->back()->with('success', 'Product updated successfully');
    }

    public function destroy(Produk $produk)
    {
        $produk->delete();
        return redirect()->back()->with('success', 'Product deleted successfully');
    }

    public function show(Produk $produk)
    {
        $produk->load(['brand', 'kategori', 'primaryImage']);

        return response()->json([
            'id' => $produk->id,
            'nama_produk' => $produk->nama_produk,
            'sku' => $produk->sku,
            'brand' => $produk->brand ? ['id' => $produk->brand->id, 'nama_brand' => $produk->brand->nama_brand] : null,
            'kategori' => $produk->kategori ? ['id' => $produk->kategori->id, 'nama_kategori' => $produk->kategori->nama_kategori] : null,
            'image_url' => $produk->primaryImage?->url ?? $produk->image_url,
        ]);
    }

    public function search(Request $request)
    {
        $search = $request->query('q', '');
        $limit = min((int) $request->query('limit', 50), 100);

        $produks = Produk::with(['brand', 'kategori', 'primaryImage'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_produk', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->query('brand_id'), function ($query, $brandId) {
                $query->where('brand_id', $brandId);
            })
            ->when($request->query('kategori_id'), function ($query, $kategoriId) {
                $query->where('kategori_id', $kategoriId);
            })
            ->orderBy('nama_produk')
            ->limit($limit)
            ->get();

        return response()->json($produks->map(function ($produk) {
            return [
                'id' => $produk->id,
                'nama_produk' => $produk->nama_produk,
                'sku' => $produk->sku,
                'brand' => $produk->brand ? ['id' => $produk->brand->id, 'nama_brand' => $produk->brand->nama_brand] : null,
                'kategori' => $produk->kategori ? ['id' => $produk->kategori->id, 'nama_kategori' => $produk->kategori->nama_kategori] : null,
                'image_url' => $produk->primaryImage?->url ?? $produk->image_url,
            ];
        }));
    }
}
