@extends('layouts.app')

@section('title', 'DICT Region 2 Office Directory')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-building text-indigo-600 dark:text-indigo-400"></i> Office Directory & Regional Scope
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage DICT Region 2 regional divisions, provincial field offices, and operating units</p>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ 
        editModalOpen: false, 
        editFormUrl: '', 
        editCode: '', 
        editName: '', 
        editLocationType: 'REGIONAL', 
        editIsActive: true 
    }" 
     class="grid grid-cols-1 {{ Auth::user()->hasPermission('offices.add') ? 'lg:grid-cols-3' : '' }} gap-6">

    @can('offices.add')
        <!-- Office Registration Form (Left Column) -->
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4 h-fit">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2">
                Register Office Unit
            </h2>

            <form action="{{ route('admin.offices.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="code" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Office Code / Tag <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                        id="code" 
                        name="code" 
                        value="{{ old('code') }}" 
                        required 
                        placeholder="e.g. ORD, PFO_ISABELA" 
                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 outline-none focus:border-indigo-600 uppercase">
                    @error('code')
                        <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Full Office Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}" 
                        required 
                        placeholder="e.g. Provincial Field Office - Isabela" 
                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    @error('name')
                        <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="location_type" class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Geographic Classification <span class="text-rose-500">*</span>
                    </label>
                    <select id="location_type" 
                            name="location_type" 
                            required 
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                        <option value="REGIONAL" {{ old('location_type') == 'REGIONAL' ? 'selected' : '' }}>Regional Office (Tuguegarao Main)</option>
                        <option value="PROVINCIAL" {{ old('location_type') == 'PROVINCIAL' ? 'selected' : '' }}>Provincial Office (PFO)</option>
                    </select>
                    @error('location_type')
                        <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Active Switch -->
                <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <label for="is_active_create" class="text-xs font-bold text-slate-800 dark:text-slate-200 block cursor-pointer">Office Status</label>
                        <span class="text-[10px] text-slate-400 block">Enable for PR routing and user assignment.</span>
                    </div>
                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <label for="is_active_create" class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" id="is_active_create" name="is_active" value="1" checked class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:ring-2 peer-focus:ring-indigo-500/20 rounded-full peer-checked:bg-indigo-600 transition-colors duration-200"></div>
                            <span class="absolute left-1 top-1 bg-white w-3 h-3 rounded-full transition-transform duration-200 ease-in-out peer-checked:translate-x-4 shadow-xs"></span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i> Register Office Unit
                </button>
            </form>
        </div>
    @endcan

    <!-- Office Directory List (Right Column - 2 Cols) -->
    <div class="{{ Auth::user()->hasPermission('offices.add') ? 'lg:col-span-2' : 'lg:col-span-3' }} p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Registered Office Directory</h2>
            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                Total: {{ $offices->total() }} Units
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead>
                    <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-2.5 px-3">Code</th>
                        <th class="py-2.5 px-3">Office Name</th>
                        <th class="py-2.5 px-3">Tier</th>
                        <th class="py-2.5 px-3 text-center">Linked Users</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($offices as $office)
                    @php
                        $tierClasses = [
                            'REGIONAL' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300',
                            'PROVINCIAL' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300',
                            'FIELD' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300',
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3 px-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                            {{ $office->code }}
                        </td>
                        <td class="py-3 px-3 font-semibold text-slate-800 dark:text-slate-200">
                            {{ $office->name }}
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded text-[9px] font-bold border {{ $tierClasses[$office->location_type] ?? '' }}">
                                {{ $office->location_type }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center font-mono">
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-bold">
                                {{ $office->users_count }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $office->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300' }}">
                                {{ $office->is_active ? 'ACTIVE' : 'DISABLED' }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                @can('offices.edit')
                                    <!-- Edit Button -->
                                    <button type="button" 
                                            @click="
                                                editModalOpen = true;
                                                editFormUrl = '{{ route('admin.offices.update', $office->id) }}';
                                                editCode = '{{ addslashes($office->code) }}';
                                                editName = '{{ addslashes($office->name) }}';
                                                editLocationType = '{{ $office->location_type }}';
                                                editIsActive = {{ $office->is_active ? 'true' : 'false' }};
                                            "
                                            class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg text-xs font-semibold transition"
                                            title="Edit Office Parameters">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                @endcan
                                @can('offices.toggle_active')
                                    <!-- Toggle Active Status Form -->
                                    <form action="{{ route('admin.offices.toggle', $office->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="p-1.5 rounded-lg text-xs font-semibold transition {{ $office->is_active ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 hover:bg-rose-100' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 hover:bg-emerald-100' }}"
                                                title="{{ $office->is_active ? 'Deactivate Office' : 'Activate Office' }}">
                                            <i class="fa-solid {{ $office->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400 text-xs">No offices registered in directory.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $offices->links() }}
        </div>
    </div>

    <!-- Edit Office Modal -->
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

        <!-- Modal Box -->
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10">
            <form :action="editFormUrl" method="POST">
                @csrf
                @method('PUT')

                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square text-indigo-600 dark:text-indigo-400"></i> Edit Office Parameters
                    </h3>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Office Code / Tag <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" x-model="editCode" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 outline-none focus:border-indigo-600 uppercase">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Full Office Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="editName" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Geographic Classification <span class="text-rose-500">*</span></label>
                        <select name="location_type" x-model="editLocationType" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                            <option value="REGIONAL">Regional Office (Tuguegarao Main)</option>
                            <option value="PROVINCIAL">Provincial Field Office (PFO)</option>
                            <option value="FIELD">Remote Field / Sub-Center</option>
                        </select>
                    </div>

                    <!-- Modal Active Switch -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <label for="is_active_edit" class="text-xs font-bold text-slate-800 dark:text-slate-200 block cursor-pointer">Office Status</label>
                            <span class="text-[10px] text-slate-400 block">Enable or disable for PR routing and scoping.</span>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <label for="is_active_edit" class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="is_active_edit" name="is_active" value="1" :checked="editIsActive" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-300 dark:bg-slate-700 peer-focus:ring-2 peer-focus:ring-indigo-500/20 rounded-full peer-checked:bg-indigo-600 transition-colors duration-200"></div>
                                <span class="absolute left-1 top-1 bg-white w-3 h-3 rounded-full transition-transform duration-200 ease-in-out peer-checked:translate-x-4 shadow-xs"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shadow-xs">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">Update Office</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection