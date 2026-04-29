<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('app/admin/roles/Index', [
            'roles' => Role::query()
                ->withCount('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function edit(Role $role)
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Permission $permission) {
                [$module, $action] = array_pad(explode('.', $permission->name, 2), 2, 'manage');

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $module,
                    'action' => $action,
                ];
            })
            ->groupBy('module')
            ->map(function ($items, $module) {
                return [
                    'module' => $module,
                    'label' => str($module)->replace(['-', '_'], ' ')->title()->toString(),
                    'permissions' => $items->values(),
                ];
            })
            ->values();

        return Inertia::render('app/admin/roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users()->count(),
                'permissions' => $role->permissions()->pluck('name')->values(),
            ],
            'permissionGroups' => $permissions,
            'isSuperAdmin' => $role->permissions()->count() === Permission::query()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return redirect()->back()->with('success', 'Role created successfully');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
            'is_super_admin' => 'boolean',
        ]);

        $role->update([
            'name' => $validated['name'],
        ]);

        if (($validated['is_super_admin'] ?? false) === true) {
            $role->syncPermissions(Permission::query()->pluck('name')->all());
        } else {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        return redirect()->route('app.roles.edit', $role)->with('success', 'Role updated successfully');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully');
    }
}
