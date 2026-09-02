<?php

// Crafted By Karl and ChatGPT

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('users.profiles.edit', [
            'user' => $request->user(),
        ]);
    }
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);
        $user->update($validated);
        return back()->with(
            'success',
            'Your profile has been updated successfully.'
        );
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
        ]);
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);
        return back()->with(
            'success',
            'Your password has been updated successfully.'
        );
    }
}