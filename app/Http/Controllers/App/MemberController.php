<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_member', 'like', "%{$search}%")
                        ->orWhere('kode_member', 'like', "%{$search}%")
                        ->orWhere('no_hp', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('app/admin/master-data/member/Index', [
            'members' => $members,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_member' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'required|string|max:20|unique:md_members,no_hp',
            'alamat' => 'nullable|string|max:500',
            'provinsi' => 'nullable|string|max:100',
            'kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'image_url' => 'nullable|string|max:500',
        ]);

        // Kode member akan digenerate otomatis oleh model
        Member::create($validated);
        return redirect()->back()->with('success', 'Member created successfully');
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'nama_member' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'required|string|max:20|unique:md_members,no_hp,' . $member->id,
            'alamat' => 'nullable|string|max:500',
            'provinsi' => 'nullable|string|max:100',
            'kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'image_url' => 'nullable|string|max:500',
        ]);

        $member->update($validated);
        return redirect()->back()->with('success', 'Member updated successfully');
    }

    public function destroy(Member $member)
    {
        $member->delete();
        return redirect()->back()->with('success', 'Member deleted successfully');
    }
}
