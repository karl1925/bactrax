@extends('layouts.app')

@section('title', 'Profile')

@section('page-header') <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">
            Profile </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Manage your account information
        </p>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="space-y-6">
            <div
                class="p-6 bg-white dark:bg-slate-900
                rounded-2xl border border-slate-200 dark:border-slate-800
                shadow-xs space-y-5">
                <form action="{{ route('user.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name"
                                class="block text-xs font-bold uppercase tracking-wider
                                text-slate-700 dark:text-slate-300 mb-1">
                                Name
                                <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" required
                                value="{{ old('name', $user->name) }}" autocomplete="name"
                                class="w-full bg-slate-50 dark:bg-slate-800
                            border border-slate-300 dark:border-slate-700
                            rounded-xl px-3.5 py-2
                            text-xs text-slate-800 dark:text-slate-100
                            outline-none focus:border-indigo-600">
                        </div>
                        <div>
                            <label for="email"
                                class="block text-xs font-bold uppercase tracking-wider
                            text-slate-700 dark:text-slate-300 mb-1">
                                Email Address
                                <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" required
                                value="{{ old('email', $user->email) }}" autocomplete="email"
                                class="w-full bg-slate-50 dark:bg-slate-800
                            border border-slate-300 dark:border-slate-700
                            rounded-xl px-3.5 py-2
                            text-xs text-slate-800 dark:text-slate-100
                            outline-none focus:border-indigo-600">
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700
                            text-white rounded-xl text-xs font-semibold
                            shadow-md shadow-indigo-500/20 transition">
                            <i class="fa-solid fa-floppy-disk mr-1.5"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            <div
                class="mt-6 p-6 bg-white dark:bg-slate-900
                rounded-2xl border border-slate-200 dark:border-slate-800
                shadow-xs space-y-5">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                        Change Password
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Update your account password.
                    </p>
                </div>
                <form action="{{ route('user.profile.password.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    {{-- CURRENT PASSWORD --}}
                    <div>
                        <label for="current_password"
                            class="block text-xs font-bold uppercase tracking-wider
                            text-slate-700 dark:text-slate-300 mb-1">
                            Current Password
                            <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="current_password" name="current_password" required
                            autocomplete="current-password"
                            class="w-full bg-slate-50 dark:bg-slate-800
                            border border-slate-300 dark:border-slate-700
                            rounded-xl px-3.5 py-2
                            text-xs text-slate-800 dark:text-slate-100
                            outline-none focus:border-indigo-600">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password"
                                class="block text-xs font-bold uppercase tracking-wider
                                text-slate-700 dark:text-slate-300 mb-1">
                                New Password
                                <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" id="password" name="password" required autocomplete="new-password"
                                class="w-full bg-slate-50 dark:bg-slate-800
                                border border-slate-300 dark:border-slate-700
                                rounded-xl px-3.5 py-2
                                text-xs text-slate-800 dark:text-slate-100
                                outline-none focus:border-indigo-600">
                        </div>
                        <div>
                            <label for="password_confirmation"
                                class="block text-xs font-bold uppercase tracking-wider
                                text-slate-700 dark:text-slate-300 mb-1">
                                Confirm New Password
                                <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                autocomplete="new-password"
                                class="w-full bg-slate-50 dark:bg-slate-800
                                border border-slate-300 dark:border-slate-700
                                rounded-xl px-3.5 py-2
                                text-xs text-slate-800 dark:text-slate-100
                                outline-none focus:border-indigo-600">
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit"
                            class="px-6 py-2 bg-slate-800 hover:bg-slate-900
                            dark:bg-slate-700 dark:hover:bg-slate-600
                            text-white rounded-xl text-xs font-semibold transition">
                            <i class="fa-solid fa-lock mr-1.5"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
