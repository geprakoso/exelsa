<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GudangController extends Controller
{
    public function index()
    {
        $gudang = Gudang::all();
        return Inertia::render('app/admin/master-data/gudang/Index', [
            'gudang' => $gudang,
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_gudang' => 'required|string|max:255',
            'lokasi_gudang' => 'nullable|string|max:500',
            'provinsi' => 'nullable|string|max:100',
            'kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kelurahan' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius_km' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Filter out null values to allow database defaults to be used
        $validated = array_filter($validated, fn ($value) => $value !== null);

        Gudang::create($validated);
        return redirect()->back()->with('success', 'Gudang created successfully');
    }

    public function update(Request $request, Gudang $gudang)
    {
        $validated = $request->validate([
            'nama_gudang' => 'required|string|max:255',
            'lokasi_gudang' => 'nullable|string|max:500',
            'provinsi' => 'nullable|string|max:100',
            'kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kelurahan' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius_km' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Filter out null values to allow database defaults to be used
        $validated = array_filter($validated, fn ($value) => $value !== null);

        $gudang->update($validated);
        return redirect()->back()->with('success', 'Gudang updated successfully');
    }

    public function destroy(Gudang $gudang)
    {
        $gudang->delete();
        return redirect()->back()->with('success', 'Gudang deleted successfully');
    }
}
