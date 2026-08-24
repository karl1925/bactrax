@extends('layouts.app')

@section('title', 'Add Procurement Modality')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-layer-group text-indigo-600 dark:text-indigo-400"></i> Add Procurement Modality
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Register a new procurement method under Republic Act 12009 statutory rules</p>
    </div>
    
    <a href="{{ route('procurement-modes.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 transition">
        <i class="fa-solid fa-arrow-left"></i> Back to Registry
    </a>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-6">
        
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Modality Parameters & Thresholds</h2>
            <p class="text-[11px] text-slate-400 mt-0.5">Define statutory ABC financial limits and method codes before designing sequence phases.</p>
        </div>

        <form action="{{ route('procurement-modes.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Mode Code & Name Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Mode Code <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           value="{{ old('code') }}" 
                           required 
                           placeholder="e.g. SVP, DIRECT" 
                           class="w-full bg-slate-50 dark:bg-slate-800 border @error('code') border-rose-500 @else border-slate-300 dark:border-slate-700 @enderror rounded-xl px-3.5 py-2 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 outline-none focus:border-indigo-600 dark:focus:border-indigo-400 uppercase">
                    @error('code')
                        <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Modality Name / Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}" 
                           required 
                           placeholder="e.g. Small Value Procurement" 
                           class="w-full bg-slate-50 dark:bg-slate-800 border @error('name') border-rose-500 @else border-slate-300 dark:border-slate-700 @enderror rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600 dark:focus:border-indigo-400">
                    @error('name')
                        <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Financial Thresholds Row (Min & Max ABC) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50/80 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800">
                <div>
                    <label for="min_abc" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Minimum ABC (₱) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-mono text-slate-400">₱</span>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               id="min_abc" 
                               name="min_abc" 
                               value="{{ old('min_abc', '0.00') }}" 
                               required 
                               class="w-full pl-8 bg-white dark:bg-slate-800 border @error('min_abc') border-rose-500 @else border-slate-300 dark:border-slate-700 @enderror rounded-xl px-3.5 py-2 text-xs font-mono text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600 dark:focus:border-indigo-400">
                    </div>
                    @error('min_abc')
                        <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="max_abc" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Maximum ABC Cap (₱)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-mono text-slate-400">₱</span>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               id="max_abc" 
                               name="max_abc" 
                               value="{{ old('max_abc') }}" 
                               placeholder="Leave blank if uncapped" 
                               class="w-full pl-8 bg-white dark:bg-slate-800 border @error('max_abc') border-rose-500 @else border-slate-300 dark:border-slate-700 @enderror rounded-xl px-3.5 py-2 text-xs font-mono text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600 dark:focus:border-indigo-400">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Keep empty if there is no statutory upper financial limit.</p>
                    @error('max_abc')
                        <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description / Statutory Notes -->
            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    Statutory Notes / RA 12009 IRR Rules
                </label>
                <textarea id="description" 
                          name="description" 
                          rows="3" 
                          placeholder="Specify legal provisions, target application criteria, or statutory thresholds..." 
                          class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600 dark:focus:border-indigo-400">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status Toggle -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">Enable Modality</span>
                    <span class="text-[10px] text-slate-400 block">Allow end-users to select this method when creating Purchase Requests.</span>
                </div>
                
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <!-- Form Action Controls -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('procurement-modes.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition flex items-center gap-2">
                    <i class="fa-solid fa-diagram-project"></i> Save & Open Workflow Builder
                </button>
            </div>

        </form>
    </div>
</div>
@endsection