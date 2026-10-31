@extends('layouts.app')

@section('title', 'Archived RFQ Documents')

@section('page-header')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Archived RFQ Documents</h1>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Restore documents to make them available on new RFQ forms.</p>
        </div>
        @include('partials.page-back-link', ['href' => route('admin.rfq-documents.index'), 'label' => 'Back to active'])
    </div>
@endsection

@section('content')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[36rem] text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/50 dark:text-slate-400">
                    <tr>
                        <th class="w-20 px-4 py-3 text-center">Row</th>
                        <th class="px-4 py-3">Document</th>
                        <th class="w-28 px-4 py-3 text-center">Status</th>
                        <th class="w-36 px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($documents as $document)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 text-center font-mono font-bold text-slate-500">{{ $document->sequence }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $document->title }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-400">Archived</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    @can('rfq_documents.edit')
                                        <form method="POST" action="{{ route('admin.rfq-documents.toggle', $document) }}" data-confirm-trigger data-confirm-title="Restore this RFQ document?" data-confirm-message="This document will become available on new RFQ forms." data-confirm-button="Restore document" data-confirm-tone="archive" data-confirm-method="PATCH">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" data-confirm-open class="rounded-lg border border-blue-200 px-2.5 py-1.5 text-[10px] font-bold text-blue-700 transition hover:bg-blue-50 dark:border-blue-900 dark:text-blue-300 dark:hover:bg-blue-950/30">
                                                <i class="fa-solid fa-rotate-left mr-1"></i>Restore
                                            </button>
                                        </form>
                                    @endcan
                                    @can('rfq_documents.delete')
                                        <form method="POST" action="{{ route('admin.rfq-documents.destroy', $document) }}" data-confirm-trigger data-confirm-title="Delete this RFQ document permanently?" data-confirm-message="This cannot be undone. Documents assigned to procurements cannot be deleted." data-confirm-button="Delete permanently" data-confirm-tone="delete" data-confirm-method="DELETE">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" data-confirm-open class="rounded-lg border border-rose-200 px-2.5 py-1.5 text-[10px] font-bold text-rose-700 transition hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                                <i class="fa-solid fa-trash-can mr-1"></i>Delete
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-xs text-slate-500">No archived RFQ documents.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
