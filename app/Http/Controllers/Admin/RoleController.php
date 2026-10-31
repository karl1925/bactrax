<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Privilege;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::withCount(['permissions', 'privileges', 'users'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('description', 'like', '%' . trim($request->search) . '%')))
            ->get();
        $permissions = Permission::all()->groupBy('module');
        $privileges = Privilege::all();

        return view('admin.roles.index', compact('roles', 'permissions', 'privileges'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Role::create($request->only('name', 'description'));

        return back()->with('success', 'Role created successfully.');
    }

    public function updateMatrix(Request $request, Role $role)
    {
        // Only an administrator may grant permissions or workflow privileges to a role.
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can change role capabilities.');
        // Guard critical admin role from privilege changes
        if (strtolower($role->name) === 'admin') {
            return back()->with('error', 'The Admin role capabilities are protected and cannot be modified.');
        }

        $request->validate([
            'permission_ids'   => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
            'privilege_ids'    => ['nullable', 'array'],
            'privilege_ids.*'  => ['exists:privileges,id'],
        ]);

        $role->permissions()->sync($request->input('permission_ids', []));
        $role->privileges()->sync($request->input('privilege_ids', []));

        return back()->with('success', "RBAC capability matrix updated for role '{$role->name}'.");
    }

    public function destroy(Role $role)
    {
        // Guard critical admin role from deletion
        if (strtolower($role->name) === 'admin') {
            return back()->with('error', 'The core Admin role cannot be deleted.');
        }

        // Prevent deletion if role currently has assigned users
        if ($role->users()->count() > 0) {
            return back()->with('error', "Cannot delete role '{$role->name}' because it is assigned to users.");
        }

        $role->permissions()->detach();
        $role->privileges()->detach();
        $role->delete();

        return back()->with('success', "Role '{$role->name}' deleted successfully.");
    }
}
