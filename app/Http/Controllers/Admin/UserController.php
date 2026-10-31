<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roles', 'offices'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('email', 'like', '%' . trim($request->search) . '%')))
            ->when($request->filled('role_id'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('roles.id', $request->role_id)))
            ->when($request->filled('office_id'), fn ($q) => $q->whereHas('offices', fn ($o) => $o->where('offices.id', $request->office_id)))
            ->orderBy('name')->paginate(15)->withQueryString();
        $roles = Role::all();
        $offices = Office::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'roles', 'offices'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can assign roles to user accounts.');
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'role_ids' => ['required', 'array', 'min:1', 'max:50'],
            'role_ids.*' => ['required', 'integer', 'distinct', 'exists:roles,id'],
            'office_id' => ['required', 'integer', Rule::exists('offices', 'id')->where(fn ($query) => $query->where('is_active', true))],
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
            ]);
            $user->roles()->sync($request->input('role_ids'));
            $user->offices()->sync([$request->input('office_id') => ['is_primary' => true]]);

            return $user;
        });

        return back()->with('success', "User account '{$user->name}' created and assigned to office scope.");
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Only administrators can change user roles.');
        // Backend Protection: Prevent editing System Administrator accounts
        if ($user->id == 1) {
            return back()->with('error', 'System Administrator accounts are protected and cannot be edited.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', Password::defaults()],
            'role_ids' => ['required', 'array', 'min:1', 'max:50'],
            'role_ids.*' => ['required', 'integer', 'distinct', 'exists:roles,id'],
            'office_id' => ['required', 'integer', Rule::exists('offices', 'id')->where(fn ($query) => $query->where('is_active', true))],
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            if ($request->filled('password')) $user->password = Hash::make($request->input('password'));
            $user->save();
            $user->roles()->sync($request->input('role_ids'));
            $user->offices()->sync([$request->input('office_id') => ['is_primary' => true]]);
        });

        return back()->with('success', "User account '{$user->name}' updated successfully.");
    }
}
