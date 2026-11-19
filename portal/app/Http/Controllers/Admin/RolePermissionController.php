<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    public function rolesIndex()
    {
        $roles = Role::withCount('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function rolesStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:100','unique:roles,name'],
        ]);

        Role::create(['name' => $data['name']]);

        return back()->with('success', 'Role created.');
    }

    public function rolesEdit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $rolePerms = $role->permissions->pluck('name')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePerms'));
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['nullable','array'],
            'permissions.*' => ['string'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', 'Role permissions updated.');
    }

    public function permissionsIndex()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('admin.permissions.index', compact('permissions'));
    }

    public function permissionMatrix()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role->name] = $role->permissions->pluck('name')->flip()->toArray();
        }

        return view('admin.permissions.matrix', compact('roles', 'permissions', 'matrix'));
    }

    public function permissionsStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120','unique:permissions,name'],
        ]);

        Permission::create(['name' => $data['name']]);

        return back()->with('success', 'Permission created.');
    }
}
