@extends('layouts.app')

@section('title', 'Role & Privilege Capability Matrix')

@section('page-header')
@include('partials.page-back-link', ['href' => route('dashboard'), 'label' => 'Dashboard'])
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-blue-600 dark:text-blue-400"></i> Role & Capability Matrix
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage module CRUD access rights and state
                transition action privileges for DICT R2 personnel</p>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="{ activeRoleId: {{ $roles->first()->id ?? 0 }}, showAddModal: false }" class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Navigation: Role Selection Directory (4 Cols) -->
        <div class="lg:col-span-4 space-y-3">
            <div
                class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-2">
                <div class="px-2 pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Configured System
                        Roles</span>

                    <div class="flex items-center gap-2">
                        <span
                            class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                            {{ $roles->count() }} Roles
                        </span>
                        @can('roles.add')
                            <!-- Trigger Add Role Modal -->
                            <button @click="showAddModal = true"
                                class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-600 hover:bg-blue-700 text-white transition flex items-center gap-1">
                                <i class="fa-solid fa-plus text-[8px]"></i> New
                            </button>
                        @endcan
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.roles.index') }}" data-directory-filters class="flex gap-2 px-1 py-2"><input name="search" value="{{ request('search') }}" data-live-search type="search" placeholder="Search roles…" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"><a href="{{ route('admin.roles.index') }}" data-clear-filters @if(!request('search')) hidden @endif class="px-2 py-2 text-xs text-slate-500">Clear</a></form>
                <div class="space-y-1 pt-1">
                    @foreach ($roles as $role)
                        <button @click="activeRoleId = {{ $role->id }}"
                            :class="activeRoleId === {{ $role->id }} ?
                                'bg-blue-600 text-white shadow-md shadow-blue-500/20' :
                                'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60'"
                            class="w-full text-left p-3 rounded-xl transition-all duration-150 flex items-center justify-between group">
                            <div class="truncate">
                                <p class="text-xs font-bold truncate">{{ $role->name }}</p>
                                <p class="text-[10px] opacity-80 truncate font-normal mt-0.5"
                                    :class="activeRoleId === {{ $role->id }} ? 'text-blue-100' : 'text-slate-400'">
                                    {{ $role->description ?? 'No description provided' }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                <span
                                    :class="activeRoleId === {{ $role->id }} ?
                                        'bg-blue-700/60 text-white border-blue-500/40' :
                                        'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'"
                                    class="text-[9px] font-mono font-bold px-2 py-0.5 rounded-full border">
                                    {{ $role->users_count }} Users
                                </span>
                                <i
                                    class="fa-solid fa-chevron-right text-[10px] opacity-60 group-hover:translate-x-0.5 transition-transform"></i>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Panel: Capability Configuration Forms (8 Cols) -->
        <div class="lg:col-span-8">
            @foreach ($roles as $role)
                @php
                    $isAdmin = strtolower($role->name) === 'admin';
                @endphp
                <div x-show="activeRoleId === {{ $role->id }}" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                    class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-6">

                    <!-- Panel Header -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-extrabold text-slate-800 dark:text-slate-100">{{ $role->name }}
                                </h2>
                                <span
                                    class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">ID:
                                    {{ $role->id }}</span>
                                @if ($isAdmin)
                                    <span
                                        class="text-[9px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200">Protected
                                        System Role</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $role->description }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ $role->permissions->count() }} Perms / {{ $role->privileges->count() }} Privs
                            </span>

                            <!-- Delete Role Action -->
                            @if (!$isAdmin)
                                @can('roles.delete')
                                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" data-confirm-trigger data-confirm-title="Delete this role?" data-confirm-message="This role can only be deleted when it has no assigned users. Its permissions will also be removed." data-confirm-button="Delete role" data-confirm-method="DELETE" data-confirm-tone="delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" data-confirm-open
                                            class="px-2.5 py-1 text-xs font-semibold rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 border border-rose-200 dark:border-rose-800 transition flex items-center gap-1">
                                            <i class="fa-solid fa-trash-can text-[10px]"></i> Delete Role
                                        </button>
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </div>

                    <form action="{{ route('admin.roles.update-matrix', $role->id) }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Section 1: Workflow Action Privileges -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <i class="fa-solid fa-key text-amber-500"></i> State Transition Action Privileges
                                </h3>
                                <span class="text-[10px] text-slate-400">Controls state changes and routing
                                    signatures</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($privileges as $priv)
                                    @php
                                        $hasPriv = $role->privileges->contains($priv->id);
                                    @endphp
                                    <label
                                        class="p-3 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-700/80 flex items-start gap-3 cursor-pointer hover:border-amber-300 dark:hover:border-amber-700 transition">
                                        <input type="checkbox" name="privilege_ids[]" value="{{ $priv->id }}"
                                            {{ $hasPriv ? 'checked' : '' }} {{ $isAdmin ? 'disabled' : '' }}
                                            class="mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                        <div>
                                            <span
                                                class="font-bold text-xs text-slate-800 dark:text-slate-200 block">{{ $priv->name }}</span>
                                            <span
                                                class="font-mono text-[10px] text-blue-600 dark:text-blue-400 block mt-0.5">{{ $priv->code }}</span>
                                            <span
                                                class="text-[10px] text-slate-400 block mt-1 leading-tight">{{ $priv->description }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Section 2: Module CRUD Permissions -->
                        <div class="space-y-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <i class="fa-solid fa-lock text-blue-500"></i> Module Access Rights
                                </h3>
                                <span class="text-[10px] text-slate-400">Controls resource access across system
                                    modules</span>
                            </div>

                            @php
                                $permissionsByModule = $permissions->flatten()->groupBy(function ($permission) {
                                    return explode('.', $permission->name)[0];
                                });
                            @endphp

                            <div class="space-y-4">
                                @forelse($permissionsByModule as $module => $modulePermissions)
                                    <div
                                        class="p-4 bg-slate-50/60 dark:bg-slate-800/30 rounded-xl border border-slate-200/60 dark:border-slate-800">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center">
                                                    <i
                                                        class="fa-solid fa-layer-group text-xs text-blue-600 dark:text-blue-400"></i>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                                        {{ ucfirst($module) }}
                                                    </span>
                                                    <span class="block text-[10px] text-slate-400">
                                                        {{ $modulePermissions->count() }}
                                                        {{ Str::plural('permission', $modulePermissions->count()) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                            @foreach ($modulePermissions as $perm)
                                                @php $hasPerm = $role->permissions->contains($perm->id); @endphp
                                                <label
                                                    class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 flex items-center gap-2.5 cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 transition">
                                                    <input type="checkbox" name="permission_ids[]"
                                                        value="{{ $perm->id }}" {{ $isAdmin ? 'disabled' : '' }}
                                                        {{ $hasPerm ? 'checked' : '' }}
                                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                                    <div class="min-w-0">
                                                        <span
                                                            class="font-mono font-bold text-xs text-slate-800 dark:text-slate-200 block truncate">
                                                            {{ $perm->name }}
                                                        </span>
                                                        <span class="text-[10px] text-slate-400 block truncate">
                                                            {{ $perm->description }}
                                                        </span>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="p-6 text-center rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
                                        <i class="fa-solid fa-lock-open text-slate-300 text-xl mb-2"></i>
                                        <p class="text-xs font-semibold text-slate-500">No permissions available</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="submit" @disabled($isAdmin)
                                class="px-6 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition {{ $isAdmin ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none' : 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20' }}">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Save Capabilities for {{ $role->name }}
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

        <!-- Add Role Modal (Alpine.js Managed) -->
        <div x-show="showAddModal" x-cloak
            class="fixed inset-0 z-[120] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div @click.away="showAddModal = false"
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Create New System Role</h3>
                    <button @click="showAddModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <form action="{{ route('admin.roles.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Role Identifier /
                            Name</label>
                        <input type="text" name="name" required placeholder="e.g. manager"
                            class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea name="description" rows="3" placeholder="Describe role responsibilities..."
                            class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 transition">
                            Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection

@push('scripts')

<script>document.querySelectorAll('[data-directory-filters]').forEach(form=>{const input=form.querySelector('[data-live-search]');let timer;input?.addEventListener('input',()=>{clearTimeout(timer);const value=input.value.trim();if(value.length>1)timer=setTimeout(()=>form.requestSubmit(),350);else if(!value)form.requestSubmit()})});</script>
@include('partials.directory-clear-sync')
@endpush
