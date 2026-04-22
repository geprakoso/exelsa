<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::all();
        return Inertia::render('app/admin/master-data/brand/Index', [
            'brands' => $brands,
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_brand' => 'required|string|max:255|unique:md_brand,nama_brand',
        ]);

        $brand = Brand::create($validated);

        // Return JSON for AJAX requests (from produk form)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $brand->id,
                'nama_brand' => $brand->nama_brand,
                'message' => 'Brand created successfully',
            ], 201);
        }

        return redirect()->back()->with('success', 'Brand created successfully');
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'nama_brand' => 'required|string|max:255|unique:md_brand,nama_brand,' . $brand->id,
        ]);

        $brand->update($validated);
        return redirect()->back()->with('success', 'Brand updated successfully');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return redirect()->back()->with('success', 'Brand deleted successfully');
    }
}
