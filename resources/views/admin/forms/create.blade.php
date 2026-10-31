@extends('layouts.app')

@section('title', 'Create Form Template')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="relative mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-white to-blue-50 p-5 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/30 sm:mb-8 sm:rounded-3xl sm:p-7">
        @include('partials.page-back-link', ['href' => route('admin.forms.index'), 'label' => 'Back to Form Templates', 'discardTitle' => 'Leave form creation?', 'discardMessage' => 'Any form details entered will be discarded.'])
        <div class="flex items-start gap-3"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300"><i class="fa-solid fa-file-circle-plus"></i></span><div class="min-w-0"><h1 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-2xl">Create Form Template</h1><p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">Upload an HTML template and configure its print layout and publishing status.</p></div></div>
    </div>

    <!-- Main Card -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
        <form action="{{ route('admin.forms.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Form Details Group -->
            <div class="space-y-4">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">General Information</h2>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <!-- Form Code -->
                    <div class="sm:col-span-2">
                        <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Form Code <span class="text-rose-500">*</span></label>
                        <input type="text" 
                               name="code" 
                               id="code" 
                               value="{{ old('code') }}" 
                               placeholder="e.g. PR-2026-01"
                               class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-400 @error('code') border-rose-500 dark:border-rose-500 @enderror" 
                               required>
                        @error('code') 
                            <p class="mt-1 text-xs text-rose-500 dark:text-rose-400">{{ $message }}</p> 
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                        <select name="status" 
                                id="status" 
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400">
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="unpublished" {{ old('status') === 'unpublished' ? 'selected' : '' }}>Unpublished</option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                    <textarea name="description" 
                              id="description" 
                              rows="3" 
                              placeholder="Brief description of this form's purpose..."
                              class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-400">{{ old('description') }}</textarea>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800" />

            <!-- Page Layout Configuration Group -->
            <div class="space-y-4">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Paper & Page Setup</h2>

                <!-- Page Size Selection -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="page_size" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Preset Paper Size</label>
                        <select name="page_size" 
                                id="page_size" 
                                onchange="toggleCustomPaperSize(this.value)"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400">
                            <option value="A4" {{ old('page_size', 'A4') === 'A4' ? 'selected' : '' }}>A4 (210 × 297 mm)</option>
                            <option value="Letter" {{ old('page_size') === 'Letter' ? 'selected' : '' }}>Letter (215.9 × 279.4 mm)</option>
                            <option value="Legal" {{ old('page_size') === 'Legal' ? 'selected' : '' }}>Legal (215.9 × 355.6 mm)</option>
                            <option value="Folio" {{ old('page_size') === 'Folio' ? 'selected' : '' }}>Folio (215.9 × 330.2 mm)</option>
                            <option value="A3" {{ old('page_size') === 'A3' ? 'selected' : '' }}>A3 (297 × 420 mm)</option>
                            <option value="A5" {{ old('page_size') === 'A5' ? 'selected' : '' }}>A5 (148 × 210 mm)</option>
                            <option value="Custom" {{ old('page_size') === 'Custom' ? 'selected' : '' }}>Custom Size...</option>
                        </select>
                    </div>

                    <!-- Custom Width (mm) -->
                    <div id="custom-width-field" class="{{ old('page_size') === 'Custom' ? '' : 'hidden' }}">
                        <label for="custom_width" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Width (mm)</label>
                        <input type="number" 
                               step="0.1" 
                               name="custom_width" 
                               id="custom_width" 
                               value="{{ old('custom_width') }}" 
                               placeholder="210"
                               class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400">
                    </div>

                    <!-- Custom Height (mm) -->
                    <div id="custom-height-field" class="{{ old('page_size') === 'Custom' ? '' : 'hidden' }}">
                        <label for="custom_height" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Height (mm)</label>
                        <input type="number" 
                               step="0.1" 
                               name="custom_height" 
                               id="custom_height" 
                               value="{{ old('custom_height') }}" 
                               placeholder="297"
                               class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400">
                    </div>
                </div>

                <!-- Margins Section (Top, Right, Bottom, Left) -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Page Margins (mm)</label>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Top</span>
                            <input type="number" step="0.1" name="margin_top" value="{{ old('margin_top', '10') }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400" required>
                            @error('margin_top') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Right</span>
                            <input type="number" step="0.1" name="margin_right" value="{{ old('margin_right', '10') }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400" required>
                            @error('margin_right') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Bottom</span>
                            <input type="number" step="0.1" name="margin_bottom" value="{{ old('margin_bottom', '10') }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400" required>
                            @error('margin_bottom') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Left</span>
                            <input type="number" step="0.1" name="margin_left" value="{{ old('margin_left', '10') }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400" required>
                            @error('margin_left') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800" />

            <!-- File Upload Group -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">HTML Template File <span class="text-rose-500">*</span></label>
                <div class="flex items-center justify-center rounded-lg border border-dashed border-slate-300 px-6 py-8 dark:border-slate-700 dark:bg-slate-800/50">
                    <div class="text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div class="mt-2 flex text-sm text-slate-600 dark:text-slate-400">
                            <label for="file" class="relative cursor-pointer rounded-md font-semibold text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500 hover:text-blue-500 dark:text-blue-400">
                                <span>Upload an HTML file</span>
                                <input id="file" name="file" type="file" accept=".html,.htm" class="sr-only" required>
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-500">.html or .htm files up to 5MB</p>
                    </div>
                </div>
                @error('file') 
                    <p class="text-xs text-rose-500 dark:text-rose-400">{{ $message }}</p> 
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('admin.forms.index') }}" data-discard-url="{{ route('admin.forms.index') }}" 
                   class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition-colors">
                    Save Form Configuration
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleCustomPaperSize(val) {
        const widthField = document.getElementById('custom-width-field');
        const heightField = document.getElementById('custom-height-field');
        if (val === 'Custom') {
            widthField.classList.remove('hidden');
            heightField.classList.remove('hidden');
        } else {
            widthField.classList.add('hidden');
            heightField.classList.add('hidden');
        }
    }
</script>
@endsection
