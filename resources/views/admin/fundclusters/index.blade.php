@extends('layouts.app')

@section('title', 'Fund Clusters')

@section('page-header')
@include('partials.page-back-link', ['href' => route('dashboard'), 'label' => 'Dashboard'])
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-blue-600 dark:text-blue-400"></i> Fund Clusters
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage fund clusters used for procurement, budgeting, and financial transactions.</p>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="{
        editModalOpen: false,
        editFormUrl: '',
        editCode: '',
        editName: '',
        editIsActive: true
    }"
        class="grid grid-cols-1 {{ Auth::user()->hasPermission('fundclusters.add') ? 'lg:grid-cols-3' : '' }} gap-6">

        @can('fundclusters.add')
            <!-- Fund Cluster Registration Form -->
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4 h-fit">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2">
                    Register Fund Cluster
                </h2>

                <form action="{{ route('admin.fundclusters.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="code" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Fund Code / Tag <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}" required placeholder="e.g. REGULAR"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs font-mono font-bold text-blue-600 dark:text-blue-400 outline-none focus:border-blue-600 uppercase">
                        @error('code')
                            <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Fund Cluster Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Regular Fund"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">
                        @error('name')
                            <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <label for="is_active_create" class="text-xs font-bold text-slate-800 dark:text-slate-200 block cursor-pointer">Fund Cluster Status</label>
                            <span class="text-[10px] text-slate-400 block">Enable this fund cluster for transactions.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <label for="is_active_create" class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="is_active_create" name="is_active" value="1" checked class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:ring-2 peer-focus:ring-blue-500/20 rounded-full peer-checked:bg-blue-600 transition-colors duration-200"></div>
                                <span class="absolute left-1 top-1 bg-white w-3 h-3 rounded-full transition-transform duration-200 ease-in-out peer-checked:translate-x-4 shadow-xs"></span>
                            </label>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus"></i> Register Fund Cluster
                    </button>
                </form>
            </div>
        @endcan

        <!-- Fund Cluster List -->
        <div class="{{ Auth::user()->hasPermission('fundclusters.add') ? 'lg:col-span-2' : 'lg:col-span-3' }} p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <form method="GET" action="{{ route('admin.fundclusters.index') }}" data-directory-filters class="flex min-w-0 flex-1 gap-2"><input name="search" value="{{ request('search') }}" data-live-search type="search" placeholder="Search fund clusters…" class="w-full max-w-sm rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"><a href="{{ route('admin.fundclusters.index') }}" data-clear-filters @if(!request('search')) hidden @endif class="px-2 py-2 text-xs text-slate-500">Clear</a></form>
                <a href="{{ route('admin.fundclusters.archive') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300"><i class="fa-solid fa-box-archive mr-1"></i>Archive</a>
            </div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Registered Fund Clusters</h2>
                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                    Total: {{ $fundClusters->total() }} Clusters
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead>
                        <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th class="py-2.5 px-3">Code</th>
                            <th class="py-2.5 px-3">Fund Cluster</th>
                            <th class="py-2.5 px-3 text-center">Status</th>
                            <th class="py-2.5 px-3 text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($fundClusters as $fundCluster)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                    {{ $fundCluster->code }}
                                </td>

                                <td class="py-3 px-3 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $fundCluster->name }}
                                </td>

                                <td class="py-3 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $fundCluster->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300' }}">
                                        {{ $fundCluster->is_active ? 'ACTIVE' : 'DISABLED' }}
                                    </span>
                                </td>

                                <td class="py-3 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @can('fundclusters.edit')
                                            <button type="button"
                                                data-edit-url="{{ route('admin.fundclusters.update', $fundCluster->id) }}"
                                                data-edit-code="{{ $fundCluster->code }}"
                                                data-edit-name="{{ $fundCluster->name }}"
                                                data-edit-active="{{ $fundCluster->is_active ? '1' : '0' }}"
                                                @click="editModalOpen = true; editFormUrl = $el.dataset.editUrl; editCode = $el.dataset.editCode; editName = $el.dataset.editName; editIsActive = $el.dataset.editActive === '1'"
                                                class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg text-xs font-semibold transition"
                                                title="Edit Fund Cluster">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        @endcan

                                        @can('fundclusters.edit')
                                            <form action="{{ route('admin.fundclusters.toggle', $fundCluster->id) }}" method="POST" data-confirm-trigger data-confirm-title="Archive this fund cluster?" data-confirm-message="This cluster will no longer be available for new purchase requests. Existing requests remain unchanged." data-confirm-button="Archive cluster" data-confirm-method="PATCH">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button" data-confirm-open
                                                    class="p-1.5 rounded-lg text-xs font-semibold transition {{ $fundCluster->is_active ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 hover:bg-rose-100' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 hover:bg-emerald-100' }}"
                                                    title="{{ $fundCluster->is_active ? 'Deactivate Fund Cluster' : 'Activate Fund Cluster' }}">
                                                    <i class="fa-solid {{ $fundCluster->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 text-xs">No fund clusters registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-2">
                {{ $fundClusters->links() }}
            </div>
        </div>

        <!-- Edit Fund Cluster Modal -->
        <div x-show="editModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[120] overflow-y-auto flex items-center justify-center p-4"
            style="display: none;">

            <div @click="editModalOpen = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs"></div>

            <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10">
                <form :action="editFormUrl" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-blue-600 dark:text-blue-400"></i> Edit Fund Cluster
                        </h3>

                        <button type="button" @click="editModalOpen = false"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Fund Code / Tag <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="code" x-model="editCode" required
                                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs font-mono font-bold text-blue-600 dark:text-blue-400 outline-none focus:border-blue-600 uppercase">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Fund Cluster Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" x-model="editName" required
                                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">
                        </div>

                        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <label for="is_active_edit" class="text-xs font-bold text-slate-800 dark:text-slate-200 block cursor-pointer">Fund Status</label>
                                <span class="text-[10px] text-slate-400 block">Enable or disable this fund cluster.</span>
                            </div>

                            <div class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <label for="is_active_edit" class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" id="is_active_edit" name="is_active" value="1" :checked="editIsActive" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:ring-2 peer-focus:ring-blue-500/20 rounded-full peer-checked:bg-blue-600 transition-colors duration-200"></div>
                                    <span class="absolute left-1 top-1 bg-white w-3 h-3 rounded-full transition-transform duration-200 ease-in-out peer-checked:translate-x-4 shadow-xs"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-2">
                        <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shadow-xs">
                            Cancel
                        </button>

                        <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20 transition">
                            Update Fund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
@push('scripts')

<script>document.querySelectorAll('[data-directory-filters]').forEach(form=>{const input=form.querySelector('[data-live-search]');let timer;input?.addEventListener('input',()=>{clearTimeout(timer);const value=input.value.trim();if(value.length>1)timer=setTimeout(()=>form.requestSubmit(),350);else if(!value)form.requestSubmit()});form.querySelectorAll('[data-auto-submit]').forEach(el=>el.addEventListener('change',()=>form.requestSubmit()))});</script>
@include('partials.directory-clear-sync')
@endpush
