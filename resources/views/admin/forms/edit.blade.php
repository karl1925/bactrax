@extends('layouts.app')

@section('title', 'Edit Form Template')

@section('content')
@php
    // Parse Configuration Safeguards
    $config = is_string($form->configuration ?? null) 
        ? json_decode($form->configuration, true) 
        : ($form->configuration ?? []);

    $pageSize = old('page_size', $config['page_size'] ?? $config['pageSize'] ?? 'A4');
    $customWidth = old('custom_width', $config['custom_width'] ?? $config['customWidth'] ?? '');
    $customHeight = old('custom_height', $config['custom_height'] ?? $config['customHeight'] ?? '');

    // Parse Margins (Handles Array or Legacy Single String/Value)
    $rawMargins = $config['margins'] ?? 10;
    if (is_array($rawMargins)) {
        $mTop = old('margin_top', $rawMargins['top'] ?? $rawMargins['mt'] ?? 10);
        $mRight = old('margin_right', $rawMargins['right'] ?? $rawMargins['mr'] ?? 10);
        $mBottom = old('margin_bottom', $rawMargins['bottom'] ?? $rawMargins['mb'] ?? 10);
        $mLeft = old('margin_left', $rawMargins['left'] ?? $rawMargins['ml'] ?? 10);
    } else {
        $val = (float) filter_var($rawMargins, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 10;
        $mTop = old('margin_top', $val);
        $mRight = old('margin_right', $val);
        $mBottom = old('margin_bottom', $val);
        $mLeft = old('margin_left', $val);
    }
@endphp

<div class="mx-auto max-w-[1900px] px-2 py-3 sm:px-4 lg:px-6">
    <div class="relative mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-white to-blue-50 p-4 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/30 sm:rounded-3xl sm:p-5">
        @include('partials.page-back-link', ['href' => route('admin.forms.index'), 'label' => 'Back to Form Templates', 'discardTitle' => 'Leave form editing?', 'discardMessage' => 'Any unsaved form changes will be discarded.'])
        <div class="flex items-start gap-3"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300"><i class="fa-solid fa-file-pen"></i></span><div class="min-w-0"><h1 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-2xl">Edit Form Template</h1><p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">Update template details, print layout, or replace the source HTML file.</p></div></div>
    </div>

    <!-- Main Card -->
    <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-3">
        <form action="{{ route('admin.forms.update', $form) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(350px,0.42fr)_minmax(0,1fr)]">
            @csrf
            @method('PUT')

            <div class="space-y-6 p-3 sm:p-4 xl:max-h-[calc(100vh-12rem)] xl:overflow-y-auto">
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
                               value="{{ old('code', $form->code) }}" 
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
                            <option value="draft" {{ old('status', $form->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $form->status) === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="unpublished" {{ old('status', $form->status) === 'unpublished' ? 'selected' : '' }}>Unpublished</option>
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
                              class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-400">{{ old('description', $form->description) }}</textarea>
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
                            <option value="A4" {{ $pageSize === 'A4' ? 'selected' : '' }}>A4 (210 × 297 mm)</option>
                            <option value="Letter" {{ $pageSize === 'Letter' ? 'selected' : '' }}>Letter (215.9 × 279.4 mm)</option>
                            <option value="Legal" {{ $pageSize === 'Legal' ? 'selected' : '' }}>Legal (215.9 × 355.6 mm)</option>
                            <option value="Folio" {{ $pageSize === 'Folio' ? 'selected' : '' }}>Folio (215.9 × 330.2 mm)</option>
                            <option value="A3" {{ $pageSize === 'A3' ? 'selected' : '' }}>A3 (297 × 420 mm)</option>
                            <option value="A5" {{ $pageSize === 'A5' ? 'selected' : '' }}>A5 (148 × 210 mm)</option>
                            <option value="Custom" {{ $pageSize === 'Custom' ? 'selected' : '' }}>Custom Size...</option>
                        </select>
                    </div>

                    <!-- Custom Width (mm) -->
                    <div id="custom-width-field" class="{{ $pageSize === 'Custom' ? '' : 'hidden' }}">
                        <label for="custom_width" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Width (mm)</label>
                        <input type="number" 
                               step="0.1" 
                               name="custom_width" 
                               id="custom_width" 
                               value="{{ $customWidth }}" 
                               placeholder="210"
                               class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400">
                    </div>

                    <!-- Custom Height (mm) -->
                    <div id="custom-height-field" class="{{ $pageSize === 'Custom' ? '' : 'hidden' }}">
                        <label for="custom_height" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Height (mm)</label>
                        <input type="number" 
                               step="0.1" 
                               name="custom_height" 
                               id="custom_height" 
                               value="{{ $customHeight }}" 
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
                            <input type="number" step="0.1" name="margin_top" value="{{ $mTop }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400" required>
                            @error('margin_top') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Right</span>
                            <input type="number" step="0.1" name="margin_right" value="{{ $mRight }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400" required>
                            @error('margin_right') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Bottom</span>
                            <input type="number" step="0.1" name="margin_bottom" value="{{ $mBottom }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400" required>
                            @error('margin_bottom') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Left</span>
                            <input type="number" step="0.1" name="margin_left" value="{{ $mLeft }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400" required>
                            @error('margin_left') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800" />

            <!-- Optional File Replace Group -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Replace HTML Template File <span class="text-xs text-slate-400 font-normal">(Optional)</span></label>
                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Current file: <strong class="font-semibold text-slate-700 dark:text-slate-200">{{ $form->name ?? 'Template Attached' }}</strong>
                    </span>
                </div>
                <div class="flex items-center justify-center rounded-lg border border-dashed border-slate-300 px-6 py-6 dark:border-slate-700 dark:bg-slate-800/50">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <div class="mt-2 flex text-sm text-slate-600 dark:text-slate-400">
                            <label for="file" class="relative cursor-pointer rounded-md font-semibold text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500 hover:text-blue-500 dark:text-blue-400">
                                <span>Upload a new file</span>
                                <input id="file" name="file" type="file" accept=".html,.htm,text/html" class="sr-only">
                            </label>
                            <p class="pl-1">to overwrite current template</p>
                        </div>
                        <p id="template-file-name" class="text-xs text-slate-500 dark:text-slate-500">Leave blank to keep existing HTML template file</p>
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
                    Update Form
                </button>
            </div>
            </div>

            <section class="flex min-h-[32rem] flex-col overflow-hidden rounded-lg border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-950 xl:sticky xl:top-20 xl:h-[calc(100vh-12rem)]">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">HTML Template Preview</h2>
                        <p id="template-preview-status" class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400" aria-live="polite">Loading saved template…</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <label for="preview-page-size" class="sr-only">Preview paper size</label>
                        <select id="preview-page-size" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" aria-label="Preview paper size">
                            <option value="A4">A4</option>
                            <option value="Letter">Letter</option>
                            <option value="Legal">Legal</option>
                            <option value="Folio">Folio</option>
                            <option value="A3">A3</option>
                            <option value="A5">A5</option>
                            <option value="Custom">Custom</option>
                        </select>
                        <div class="flex items-center rounded-lg border border-slate-300 dark:border-slate-700" role="group" aria-label="Preview zoom">
                            <button id="template-preview-zoom-out" type="button" class="px-2 py-1.5 text-xs text-slate-600 hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800" title="Zoom out" aria-label="Zoom out"><i class="fa-solid fa-minus"></i></button>
                            <span id="template-preview-zoom-value" class="min-w-12 text-center text-xs font-semibold text-slate-700 dark:text-slate-200">100%</span>
                            <button id="template-preview-zoom-in" type="button" class="px-2 py-1.5 text-xs text-slate-600 hover:bg-slate-100 disabled:opacity-40 dark:text-slate-300 dark:hover:bg-slate-800" title="Zoom in" aria-label="Zoom in"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        <button id="template-preview-fit" type="button" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Fit page</button>
                        <button id="template-preview-reset" type="button" class="hidden rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Discard preview</button>
                    </div>
                </div>
                <div id="template-preview-viewport" class="min-h-0 flex-1 overflow-auto bg-slate-200 p-4 dark:bg-slate-900 sm:p-6">
                    <div class="flex min-h-full min-w-full justify-center">
                        <div id="template-preview-paper" class="h-fit origin-top-left bg-white shadow-xl ring-1 ring-black/10">
                            <iframe id="template-preview" title="HTML template preview" sandbox="" referrerpolicy="no-referrer" class="block border-0 bg-white"></iframe>
                        </div>
                    </div>
                </div>
            </section>
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

    (() => {
        const fileInput = document.getElementById('file');
        const preview = document.getElementById('template-preview');
        const viewport = document.getElementById('template-preview-viewport');
        const paper = document.getElementById('template-preview-paper');
        const status = document.getElementById('template-preview-status');
        const fileName = document.getElementById('template-file-name');
        const resetButton = document.getElementById('template-preview-reset');
        const currentPreviewUrl = @json(route('admin.forms.file', $form));
        const sharedHeader = @json($sharedHeader);
        const millimetersToPixels = 96 / 25.4;
        const pageSizes = {
            A4: [210, 297], A3: [297, 420], A5: [148, 210],
            LETTER: [215.9, 279.4], LEGAL: [215.9, 355.6], FOLIO: [215.9, 330.2]
        };
        const pageSizeSelect = document.getElementById('preview-page-size');
        const zoomLabel = document.getElementById('template-preview-zoom-value');
        const zoomOut = document.getElementById('template-preview-zoom-out');
        const zoomIn = document.getElementById('template-preview-zoom-in');
        let zoom = 1;
        let pageWidth = 0;
        let pageHeight = 0;

        function getPageConfiguration() {
            const size = document.getElementById('page_size').value;
            const isCustom = size === 'Custom';
            const dimensions = isCustom
                ? [parseFloat(document.getElementById('custom_width').value) || 210, parseFloat(document.getElementById('custom_height').value) || 297]
                : pageSizes[size.toUpperCase()] || pageSizes.A4;

            return {
                name: size,
                width: dimensions[0],
                height: dimensions[1],
                top: parseFloat(document.querySelector('[name="margin_top"]').value) || 0,
                right: parseFloat(document.querySelector('[name="margin_right"]').value) || 0,
                bottom: parseFloat(document.querySelector('[name="margin_bottom"]').value) || 0,
                left: parseFloat(document.querySelector('[name="margin_left"]').value) || 0
            };
        }

        function updatePageView(fit = false) {
            const page = getPageConfiguration();
            pageSizeSelect.value = page.name;
            pageWidth = page.width * millimetersToPixels;
            pageHeight = page.height * millimetersToPixels;
            preview.style.width = `${pageWidth}px`;
            preview.style.height = `${pageHeight}px`;
            paper.style.width = `${pageWidth * zoom}px`;
            paper.style.height = `${pageHeight * zoom}px`;
            preview.style.transform = `scale(${zoom})`;
            preview.style.transformOrigin = 'top left';
            zoomLabel.textContent = `${Math.round(zoom * 100)}%`;
            zoomOut.disabled = zoom <= 0.25;
            zoomIn.disabled = zoom >= 2;

            if (fit) {
                const availableWidth = Math.max(100, viewport.clientWidth - 48);
                const availableHeight = Math.max(100, viewport.clientHeight - 48);
                zoom = Math.min(1, availableWidth / pageWidth, availableHeight / pageHeight);
                updatePageView();
            }
        }

        function sanitizeDocument(html, injectHeader) {
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            parsed.querySelectorAll('script, iframe, frame, frameset, object, embed, applet, portal, video, audio, source, track, base, meta, link, svg, math, form').forEach(element => element.remove());
            parsed.querySelectorAll('style').forEach(style => {
                style.textContent = style.textContent.replace(/@import[^;]+;?|url\s*\([^)]*\)|expression\s*\([^)]*\)/gi, '');
            });
            parsed.querySelectorAll('*').forEach(element => {
                [...element.attributes].forEach(attribute => {
                    const name = attribute.name.toLowerCase();
                    const value = attribute.value.trim();
                    if (name.startsWith('on') || ['srcdoc', 'action', 'formaction', 'formtarget', 'srcset', 'ping', 'background', 'xmlns'].includes(name)) {
                        element.removeAttribute(attribute.name);
                    } else if (['href', 'src', 'xlink:href'].includes(name)) {
                        const url = value.replace(/[\x00-\x20]+/g, '');
                        const isDataImage = name === 'src' && /^data:image\/(?:png|gif|jpeg|webp);base64,/i.test(url);
                        const isRelative = !/^[a-z][a-z0-9+.-]*:/i.test(url) && !url.startsWith('//');
                        if (!isDataImage && !isRelative) element.removeAttribute(attribute.name);
                    }
                });
            });

            if (injectHeader) {
                parsed.querySelectorAll('section[data-header]').forEach(section => {
                    const title = section.getAttribute('data-title') || '';
                    section.replaceChildren();
                    section.insertAdjacentHTML('afterbegin', sharedHeader);
                    if (title) {
                        const titleBlock = parsed.createElement('div');
                        titleBlock.setAttribute('style', 'display:flex;width:100%;min-height:35px;align-items:center;justify-content:center;background:#fff8d0;font-family:Arial,Helvetica,sans-serif;font-weight:800;text-align:center');
                        titleBlock.textContent = title;
                        section.append(titleBlock);
                    }
                });
            }

            const page = getPageConfiguration();
            const policy = parsed.createElement('meta');
            policy.httpEquiv = 'Content-Security-Policy';
            policy.content = "default-src 'none'; img-src data: blob:; style-src 'unsafe-inline'; font-src data:; base-uri 'none'; form-action 'none'";
            parsed.head.prepend(policy);

            const pageStyles = parsed.createElement('style');
            pageStyles.textContent = `
                @page { size: ${page.width}mm ${page.height}mm; margin: 0; }
                html, body { width: ${page.width}mm !important; min-height: ${page.height}mm !important; margin: 0 !important; box-sizing: border-box !important; }
                body { padding: ${page.top}mm ${page.right}mm ${page.bottom}mm ${page.left}mm !important; }
            `;
            parsed.head.append(pageStyles);
            return `<!doctype html>${parsed.documentElement.outerHTML}`;
        }

        function renderPreview(html, { injectHeader = false } = {}) {
            preview.srcdoc = sanitizeDocument(html, injectHeader);
            requestAnimationFrame(() => updatePageView(true));
        }

        async function loadCurrentTemplate() {
            status.textContent = 'Loading saved template…';
            const response = await fetch(currentPreviewUrl, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error(`Template preview request failed (${response.status}).`);
            renderPreview(await response.text());
            status.textContent = 'Showing the saved template';
        }

        pageSizeSelect.value = document.getElementById('page_size').value;
        pageSizeSelect.addEventListener('change', () => {
            const size = pageSizeSelect.value;
            document.getElementById('page_size').value = size;
            toggleCustomPaperSize(size);
            updatePageView(true);
        });
        zoomOut.addEventListener('click', () => { zoom = Math.max(0.25, zoom - 0.1); updatePageView(); });
        zoomIn.addEventListener('click', () => { zoom = Math.min(2, zoom + 0.1); updatePageView(); });
        document.getElementById('template-preview-fit').addEventListener('click', () => updatePageView(true));
        window.addEventListener('resize', () => updatePageView(true));
        document.querySelectorAll('#page_size, #custom_width, #custom_height, [name^="margin_"]').forEach(input => {
            input.addEventListener('input', () => updatePageView(true));
            input.addEventListener('change', () => {
                pageSizeSelect.value = document.getElementById('page_size').value;
                updatePageView(true);
            });
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (!file) return;

            if (!['text/html', 'text/plain', ''].includes(file.type) || !/\.(html?|htm)$/i.test(file.name)) {
                fileInput.value = '';
                status.textContent = 'Choose a valid .html or .htm file to preview.';
                fileName.textContent = 'Leave blank to keep existing HTML template file';
                return;
            }

            const reader = new FileReader();
            reader.addEventListener('load', () => {
                renderPreview(String(reader.result), { injectHeader: true });
                status.textContent = `Previewing selected file: ${file.name}`;
                fileName.textContent = `Selected replacement: ${file.name}`;
                resetButton.classList.remove('hidden');
            });
            reader.addEventListener('error', () => {
                status.textContent = 'The selected HTML file could not be read.';
            });
            reader.readAsText(file);
        });

        resetButton.addEventListener('click', () => {
            fileInput.value = '';
            loadCurrentTemplate().catch(error => {
                status.textContent = error.message;
            });
            fileName.textContent = 'Leave blank to keep existing HTML template file';
            resetButton.classList.add('hidden');
        });

        updatePageView(true);
        loadCurrentTemplate().catch(error => {
            status.textContent = error.message;
        });
    })();
</script>
@endsection
