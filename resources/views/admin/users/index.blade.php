@extends('layouts.app')

@section('title', 'User Directory & Office Scoping')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-users text-indigo-600 dark:text-indigo-400"></i> User Directory & Office Scoping
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage accounts, primary office boundaries, and RBAC roles</p>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ 
        editModalOpen: false, 
        editFormUrl: '', 
        editName: '', 
        editEmail: '', 
        editOfficeId: '', 
        editRoleIds: [] 
    }" 
     class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Account Creation Form -->
    <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4 h-fit">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2">Create User Account</h2>

        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="e.g. Juan Dela Cruz" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Email Address <span class="text-rose-500">*</span></label>
                <input type="email" name="email" required placeholder="user@dict.gov.ph" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Initial Password <span class="text-rose-500">*</span></label>
                <input type="password" name="password" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Primary Office Scope <span class="text-rose-500">*</span></label>
                <select name="office_id" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    <option value="">Select Office</option>
                    @foreach($offices as $office)
                        <option value="{{ $office->id }}">{{ $office->name }} ({{ $office->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Assigned Roles <span class="text-rose-500">*</span></label>
                <div class="space-y-1.5 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/80 max-h-36 overflow-y-auto">
                    @foreach($roles as $role)
                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="font-semibold">{{ $role->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">
                Create User Account
            </button>
        </form>
    </div>

    <!-- User Account Directory List -->
    <div class="lg:col-span-2 p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2">Active Accounts & Scopes</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead>
                    <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-2.5 px-3">User</th>
                        <th class="py-2.5 px-3">Primary Office Scope</th>
                        <th class="py-2.5 px-3">Assigned Roles</th>
                        <th class="py-2.5 px-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($users as $user)
                    @php
                        $isAdmin = $user->id == 1;
                    @endphp
                    <tr>
                        <td class="py-3 px-3">
                            <p class="font-bold text-slate-800 dark:text-slate-200">{{ $user->name }}</p>
                            <span class="text-[10px] text-slate-400">{{ $user->email }}</span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-semibold text-[10px]">
                                {{ $user->offices->first()->name ?? 'Enterprise (Unscoped)' }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $r)
                                <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold text-[9px] border border-indigo-200/60">
                                    {{ $r->name }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 px-3 text-center">
                            @if($isAdmin)
                                <!-- Protected badge for System Admin -->
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 dark:text-slate-500 italic px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60" title="System Administrator account cannot be modified">
                                    <i class="fa-solid fa-lock text-[9px]"></i> Protected
                                </span>
                            @else
                                <!-- Edit Button for Non-Admin Accounts -->
                                <button type="button" 
                                        @click="
                                            editModalOpen = true;
                                            editFormUrl = '{{ route('admin.users.update', $user->id) }}';
                                            editName = '{{ addslashes($user->name) }}';
                                            editEmail = '{{ addslashes($user->email) }}';
                                            editOfficeId = '{{ $user->offices->first()->id ?? '' }}';
                                            editRoleIds = {{ json_encode($user->roles->pluck('id')->toArray()) }};
                                        "
                                        class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg text-xs font-semibold transition"
                                        title="Edit User Account">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Edit User Modal -->
    <div x-show="editModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" 
         style="display: none;">
        
        <!-- Backdrop -->
        <div @click="editModalOpen = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs"></div>

        <!-- Modal Content Container -->
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10">
            <form :action="editFormUrl" method="POST">
                @csrf
                @method('PUT')

                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-indigo-600 dark:text-indigo-400"></i> Edit User Account
                    </h3>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="editName" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Email Address <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" x-model="editEmail" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">New Password (Optional)</label>
                        <input type="password" name="password" placeholder="Leave blank to keep existing password" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Primary Office Scope <span class="text-rose-500">*</span></label>
                        <select name="office_id" x-model="editOfficeId" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                            <option value="">Select Office Scope</option>
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}">{{ $office->name }} ({{ $office->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Assigned Roles <span class="text-rose-500">*</span></label>
                        <div class="space-y-1.5 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/80 max-h-36 overflow-y-auto">
                            @foreach($roles as $role)
                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" :checked="editRoleIds.includes({{ $role->id }})" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="font-semibold">{{ $role->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shadow-xs">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">Update Account</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection