<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_supplier', 'like', "%{$search}%")
                        ->orWhere('no_hp', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('app/admin/master-data/supplier/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255|unique:md_suppliers,nama_supplier',
            'email' => 'nullable|email|max:255|unique:md_suppliers,email',
            'no_hp' => 'required|string|max:20|unique:md_suppliers,no_hp',
            'alamat' => 'nullable|string|max:500',
            'provinsi' => 'nullable|string|max:100',
            'kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
        ]);

        $supplier = Supplier::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $supplier->id,
                'nama_supplier' => $supplier->nama_supplier,
            ]);
        }

        return redirect()->back()->with('success', 'Supplier created successfully');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255|unique:md_suppliers,nama_supplier,' . $supplier->id,
            'email' => 'nullable|email|max:255|unique:md_suppliers,email,' . $supplier->id,
            'no_hp' => 'required|string|max:20|unique:md_suppliers,no_hp,' . $supplier->id,
            'alamat' => 'nullable|string|max:500',
            'provinsi' => 'nullable|string|max:100',
            'kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
        ]);

        $supplier->update($validated);
        return redirect()->back()->with('success', 'Supplier updated successfully');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->back()->with('success', 'Supplier deleted successfully');
    }
}
