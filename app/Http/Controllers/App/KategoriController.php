<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::all();
        return Inertia::render('app/admin/master-data/kategori/Index', [
            'kategori' => $kategori,
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:md_kategori,nama_kategori',
            'is_active' => 'boolean',
        ]);

        Kategori::create($validated);

        return redirect()->back()->with('success', 'Kategori created successfully');
    }

    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $kategori->update($validated);
        return redirect()->back()->with('success', 'Kategori updated successfully');
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->delete();
        return redirect()->back()->with('success', 'Kategori deleted successfully');
    }
}
