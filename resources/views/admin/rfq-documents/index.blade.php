@extends('layouts.app')

@section('title', 'RFQ Documents')

@section('page-header')
    @include('partials.page-back-link', ['href' => route('dashboard'), 'label' => 'Dashboard'])
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-bold text-slate-800 dark:text-slate-100">
                <i class="fa-solid fa-file-circle-check text-blue-600 dark:text-blue-400"></i> RFQ Docs
            </h1>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Manage the required supplier documents shown on Request for Quotation forms.</p>
        </div>
    </div>
@endsection

@section('content')
    @php
        $showModal = $errors->any();
        $oldDocumentId = old('rfq_document_id');
        $oldDocument = $oldDocumentId ? $documents->firstWhere('id', (int) $oldDocumentId) : null;
    @endphp
    <div x-data="{
        modalOpen: @js($showModal),
        editing: @js((bool) $oldDocument),
        action: @js($oldDocument ? route('admin.rfq-documents.update', $oldDocument) : route('admin.rfq-documents.store')),
        title: @js(old('title', $oldDocument?->title ?? '')),
        active: @js((bool) old('is_active', $oldDocument?->is_active ?? true)),
        openAdd() { this.editing = false; this.action = @js(route('admin.rfq-documents.store')); this.title = ''; this.active = true; this.modalOpen = true; this.$nextTick(() => this.$refs.title.focus()); },
        openEdit(id, title, active) { this.editing = true; this.action = @js(route('admin.rfq-documents.index')) + '/' + id; this.title = title; this.active = active; this.modalOpen = true; this.$nextTick(() => this.$refs.title.focus()); }
    }" @keydown.escape.window="modalOpen = false">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Active RFQ Documents</h2>
                    <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">Drag rows using the handle to change their order on RFQ forms.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.rfq-documents.archive') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        <i class="fa-solid fa-box-archive mr-1"></i>Archive
                    </a>
                    @can('rfq_documents.add')
                        <button type="button" @click="openAdd()" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-700">
                            <i class="fa-solid fa-plus mr-1"></i>Add Document
                        </button>
                    @endcan
                </div>
            </div>

            <p id="rfq-document-order-status" class="hidden px-5 py-2 text-xs font-semibold" role="status" aria-live="polite"></p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[42rem] text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/50 dark:text-slate-400">
                        <tr>
                            <th class="w-16 px-4 py-3 text-center">Row</th>
                            <th class="px-4 py-3">Document</th>
                            <th class="w-28 px-4 py-3 text-center">Status</th>
                            <th class="w-32 px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rfq-document-rows" data-reorder-url="{{ route('admin.rfq-documents.reorder') }}" data-csrf="{{ csrf_token() }}" class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($documents as $document)
                            <tr data-document-id="{{ $document->id }}" class="bg-white transition-colors hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-3 text-center">
                                    @can('rfq_documents.edit')
                                        <button type="button" draggable="true" class="rfq-document-drag-handle cursor-grab rounded-md p-2 text-slate-400 transition hover:bg-slate-100 hover:text-blue-600 active:cursor-grabbing dark:hover:bg-slate-800" title="Drag to reorder" aria-label="Drag to reorder {{ $document->title }}">
                                            <i class="fa-solid fa-grip-vertical"></i>
                                        </button>
                                    @endcan
                                    <span class="rfq-document-sequence ml-1 font-mono font-bold text-slate-500">{{ $document->sequence }}</span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $document->title }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold {{ $document->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">Active</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        @can('rfq_documents.edit')
                                            <button type="button" @click="openEdit(@js($document->id), @js($document->title), @js((bool) $document->is_active))" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10px] font-bold text-slate-600 transition hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-blue-950/30 dark:hover:text-blue-300">
                                                <i class="fa-solid fa-pen mr-1"></i>Edit
                                            </button>
                                        @endcan
                                        @can('rfq_documents.edit')
                                            <form method="POST" action="{{ route('admin.rfq-documents.toggle', $document) }}" data-confirm-trigger data-confirm-title="Archive this RFQ document?" data-confirm-message="It will no longer appear on new RFQ forms. Existing procurement selections remain unchanged." data-confirm-button="Archive document" data-confirm-method="PATCH">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button" data-confirm-open class="rounded-lg border border-amber-200 px-2.5 py-1.5 text-[10px] font-bold text-amber-700 transition hover:bg-amber-50 dark:border-amber-900 dark:text-amber-300 dark:hover:bg-amber-950/30">
                                                    <i class="fa-solid fa-box-archive mr-1"></i>Archive
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr data-empty-row><td colspan="4" class="px-5 py-10 text-center text-xs text-slate-500">No active RFQ documents. Add one or restore a document from the archive.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div x-show="modalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/60 p-4" @click.self="modalOpen = false">
            <section x-show="modalOpen" x-transition class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900" role="dialog" aria-modal="true" aria-labelledby="rfq-document-modal-title">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="rfq-document-modal-title" class="text-base font-bold text-slate-900 dark:text-white" x-text="editing ? 'Edit RFQ Document' : 'Add RFQ Document'"></h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">The row order controls the sequence displayed to suppliers.</p>
                    </div>
                    <button type="button" @click="modalOpen = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white" aria-label="Close dialog"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form method="POST" :action="action" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" :disabled="!editing">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300" for="rfq-document-title">Document title
                        <textarea id="rfq-document-title" x-ref="title" x-model="title" name="title" rows="4" maxlength="255" required class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></textarea>
                    </label>
                    @error('title')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                        <input type="checkbox" name="is_active" value="1" x-model="active" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Active on RFQ forms
                    </label>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button type="button" @click="modalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-blue-700" x-text="editing ? 'Save Changes' : 'Add Document'"></button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    @can('rfq_documents.edit')
        <script>
            (() => {
                const body = document.getElementById('rfq-document-rows');
                if (!body || body.dataset.reorderBound === '1') return;
                body.dataset.reorderBound = '1';
                let dragging = null;
                let originalOrder = [];
                const status = document.getElementById('rfq-document-order-status');
                const rows = () => [...body.querySelectorAll('tr[data-document-id]')];
                const announce = (message, isError = false) => {
                    status.textContent = message;
                    status.classList.remove('hidden', 'text-rose-600', 'text-emerald-600');
                    status.classList.add(isError ? 'text-rose-600' : 'text-emerald-600');
                };
                const renumber = () => rows().forEach((row, index) => {
                    const number = row.querySelector('.rfq-document-sequence');
                    if (number) number.textContent = String(index + 1);
                });
                body.addEventListener('dragstart', event => {
                    const handle = event.target.closest('.rfq-document-drag-handle');
                    if (!handle) return event.preventDefault();
                    dragging = handle.closest('tr[data-document-id]');
                    if (!dragging) return event.preventDefault();
                    originalOrder = rows().map(row => row.dataset.documentId);
                    dragging.classList.add('opacity-50');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', dragging.dataset.documentId);
                });
                body.addEventListener('dragover', event => {
                    if (!dragging) return;
                    event.preventDefault();
                    const target = event.target.closest('tr[data-document-id]');
                    if (!target || target === dragging) return;
                    const rect = target.getBoundingClientRect();
                    body.insertBefore(dragging, event.clientY < rect.top + rect.height / 2 ? target : target.nextSibling);
                });
                body.addEventListener('dragend', async () => {
                    if (!dragging) return;
                    dragging.classList.remove('opacity-50');
                    dragging = null;
                    const order = rows().map(row => Number(row.dataset.documentId));
                    if (JSON.stringify(order) === JSON.stringify(originalOrder.map(Number))) return;
                    try {
                        const response = await fetch(body.dataset.reorderUrl, {
                            method: 'PATCH',
                            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': body.dataset.csrf},
                            body: JSON.stringify({order}),
                        });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message || 'Unable to save the RFQ document order.');
                        renumber();
                        announce('RFQ document order saved.');
                    } catch (error) {
                        originalOrder.forEach(id => {
                            const row = body.querySelector(`tr[data-document-id="${CSS.escape(id)}"]`);
                            if (row) body.appendChild(row);
                        });
                        renumber();
                        announce(error.message || 'Unable to save the RFQ document order.', true);
                    }
                });
            })();
        </script>
    @endcan
@endsection
