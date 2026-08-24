<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'offices'])->paginate(15);
        $roles = Role::all();
        $offices = Office::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'roles', 'offices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
            'office_id' => ['required', 'exists:offices,id'],
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        // Attach roles
        $user->roles()->sync($request->input('role_ids'));

        // Assign primary office scope
        $user->offices()->sync([
            $request->input('office_id') => ['is_primary' => true]
        ]);

        return back()->with('success', "User account '{$user->name}' created and assigned to office scope.");
    }

    public function update(Request $request, User $user)
    {
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
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
            'office_id' => ['required', 'exists:offices,id'],
        ]);

        $user->name = $request->input('name');
        $user->email = $request->input('email');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        // Update assigned roles
        $user->roles()->sync($request->input('role_ids'));

        // Update primary office scope
        $user->offices()->sync([
            $request->input('office_id') => ['is_primary' => true]
        ]);

        return back()->with('success', "User account '{$user->name}' updated successfully.");
    }
}
