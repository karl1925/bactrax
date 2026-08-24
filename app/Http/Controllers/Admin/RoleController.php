<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Privilege;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['permissions', 'privileges', 'users'])->get();
        $permissions = Permission::all()->groupBy('module');
        $privileges = Privilege::all();

        return view('admin.roles.index', compact('roles', 'permissions', 'privileges'));
    }

    public function updateMatrix(Request $request, Role $role)
    {
        $request->validate([
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
            'privilege_ids' => ['nullable', 'array'],
            'privilege_ids.*' => ['exists:privileges,id'],
        ]);

        // Sync permissions and workflow privileges for the selected role
        $role->permissions()->sync($request->input('permission_ids', []));
        $role->privileges()->sync($request->input('privilege_ids', []));

        return back()->with('success', "RBAC capability matrix updated for role '{$role->name}'.");
    }
}
