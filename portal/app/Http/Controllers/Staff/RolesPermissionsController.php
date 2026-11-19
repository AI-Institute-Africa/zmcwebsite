<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesPermissionsController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions', 'users')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $permissionGroups = $permissions->groupBy(function ($perm) {
            $parts = explode('_', $perm->name, 2);
            return $parts[0] ?? 'general';
        });

        return view('staff.it.roles-permissions.index', compact('roles', 'permissions', 'permissionGroups'));
    }

    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:80|unique:roles,name',
            'display_name' => 'nullable|string|max:150',
        ]);

        $role = Role::create([
            'name' => strtolower(str_replace(' ', '_', $request->name)),
            'guard_name' => 'web',
        ]);

        return redirect()->route('staff.it.roles-permissions')
            ->with('success', "Role '{$role->name}' created.");
    }

    public function editRole(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $permissionGroups = $permissions->groupBy(function ($perm) {
            $parts = explode('_', $perm->name, 2);
            return $parts[0] ?? 'general';
        });
        $usersWithRole = User::role($role->name)->get();

        return view('staff.it.roles-permissions.edit', compact('role', 'permissions', 'rolePermissions', 'permissionGroups', 'usersWithRole'));
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('staff.it.roles-permissions')
            ->with('success', "Permissions updated for '{$role->name}'.");
    }

    public function deleteRole(Role $role)
    {
        $protectedRoles = ['super_admin', 'it_admin'];
        if (in_array($role->name, $protectedRoles)) {
            return redirect()->route('staff.it.roles-permissions')
                ->with('error', 'This role is protected and cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('staff.it.roles-permissions')
                ->with('error', 'Cannot delete role with assigned users. Remove users from this role first.');
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('staff.it.roles-permissions')
            ->with('success', "Role '{$roleName}' deleted.");
    }

    public function createPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name',
        ]);

        Permission::create([
            'name' => strtolower(str_replace(' ', '_', $request->name)),
            'guard_name' => 'web',
        ]);

        return redirect()->route('staff.it.roles-permissions')
            ->with('success', 'Permission created.');
    }

    public function deletePermission(Permission $permission)
    {
        $permName = $permission->name;
        $permission->delete();

        return redirect()->route('staff.it.roles-permissions')
            ->with('success', "Permission '{$permName}' deleted.");
    }

    public function assignRoleToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->assignRole($request->role);

        return redirect()->back()->with('success', "Role assigned to {$user->email}.");
    }

    public function removeRoleFromUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->removeRole($request->role);

        return redirect()->back()->with('success', "Role removed from {$user->email}.");
    }
}
