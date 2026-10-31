@extends('layouts.app')

@section('title', 'Form Templates')

@section('page-header')
@include('partials.page-back-link', ['href' => route('dashboard'), 'label' => 'Dashboard']) 
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-file-signature text-blue-600 dark:text-blue-400"></i>
                Form Templates
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Manage reusable fillable PDF forms, datasets, dynamic tables, formulas, and signature placeholders.
            </p>
        </div>
        @can('forms.add')
        <div>
            <a href="{{ route('admin.forms.create') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20 transition">
                <i class="fa-solid fa-plus"></i>
                Create Form Template
            </a>
        </div>
        @endcan
    </div>


@endsection

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Form Templates --}}
            <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Total Form Templates
                        </p>
                        <p class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1">
                            {{ $forms->total() }}
                        </p>
                    </div>
                    <div
                        class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>

                </div>

            </div>

            {{-- Published --}}
            <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Published
                        </p>

                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ $publishedCount ?? 0 }}
                        </p>
                    </div>

                    <div
                        class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                </div>

            </div>

            {{-- Drafts --}}
            <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Drafts
                        </p>

                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                            {{ $draftCount ?? 0 }}
                        </p>
                    </div>

                    <div
                        class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-pen-ruler"></i>
                    </div>

                </div>

            </div>

            {{-- Archived --}}
            <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">

                <div class="flex items-center justify-between">

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Archived
                        </p>

                        <p class="text-2xl font-bold text-slate-500 dark:text-slate-400 mt-1">
                            {{ $archivedCount ?? 0 }}
                        </p>
                    </div>

                    <div
                        class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>

                </div>

            </div>

        </div>


        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('admin.forms.index') }}" data-directory-filters class="flex min-w-0 flex-1 flex-wrap gap-2">
                <input name="search" value="{{ request('search') }}" data-live-search type="search" placeholder="Search form templates…" class="w-full max-w-sm rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                <select name="status" data-auto-submit class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"><option value="">All statuses</option>@foreach(['draft'=>'Draft','published'=>'Published','unpublished'=>'Unpublished'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
                <a href="{{ route('admin.forms.index') }}" data-clear-filters @if(!request()->hasAny(['search','status'])) hidden @endif class="px-2 py-2 text-xs text-slate-500">Clear</a>
            </form>
            <a href="{{ route('admin.forms.archive') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300"><i class="fa-solid fa-box-archive mr-1"></i>Archive ({{ $archivedCount }})</a>
        </div>

        {{-- Form Template Directory --}}
        <div
            class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">

            {{-- Header --}}
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">

                <div>
                    <h2
                        class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-blue-600 dark:text-blue-400"></i>
                        Form Template Library
                    </h2>

                    <p class="text-[10px] text-slate-400 mt-1">
                        Reusable PDF form definitions and their published versions.
                    </p>
                </div>

                <span
                    class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 w-fit">
                    Total: {{ $forms->total() }} Form Templates
                </span>

            </div>


            {{-- Desktop Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead>
                        <tr
                            class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">

                            <th class="py-2.5 px-3">
                                Form
                            </th>

                            <th class="py-2.5 px-3">
                                Code
                            </th>

                            <th class="py-2.5 px-3 text-center">
                                Status
                            </th>

                            <th class="py-2.5 px-3">
                                Updated
                            </th>

                            <th class="py-2.5 px-3 text-center">
                                Actions
                            </th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($forms as $form)
                            @php
                                $statusClasses = [
                                    'draft' =>
                                        'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300',
                                    'published' =>
                                        'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300',
                                    'unpublished' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400',
                                    'archived' =>
                                        'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400',
                                ];

                                $statusIcons = [
                                    'draft' => 'fa-pen-ruler',
                                    'published' => 'fa-circle-check',
                                    'unpublished' => 'fa-circle-pause',
                                    'archived' => 'fa-box-archive',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                {{-- Form --}}
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-file-signature"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div
                                                class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[280px]">
                                                {{ $form->name }}
                                            </div>
                                            @if ($form->description)
                                                <div class="text-[10px] text-slate-400 truncate max-w-[280px]">
                                                    {{ $form->description }}
                                                </div>
                                            @else
                                                <div class="text-[10px] text-slate-400">
                                                    No description
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Code --}}
                                <td class="py-3 px-3">
                                    <span
                                        class="font-mono font-bold text-blue-600 dark:text-blue-400 bg-blue-50/60 dark:bg-blue-950/30 px-2 py-1 rounded-lg">
                                        {{ $form->code }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="py-3 px-3 text-center">

                                    @php
                                        $status = strtolower($form->status ?? 'draft');
                                    @endphp

                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $statusClasses[$status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">

                                        <i class="fa-solid {{ $statusIcons[$status] ?? 'fa-circle' }} text-[8px]"></i>

                                        {{ strtoupper($status) }}

                                    </span>

                                </td>


                                {{-- Updated --}}
                                <td class="py-3 px-3">

                                    <div class="text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                                        {{ $form->updated_at?->format('M d, Y') ?? '—' }}
                                    </div>

                                    @if ($form->updated_at)
                                        <div class="text-[9px] text-slate-400">
                                            {{ $form->updated_at->format('h:i A') }}
                                        </div>
                                    @endif

                                </td>


                                {{-- Actions --}}
                                <td class="py-3 px-3 text-center">

                                    <div class="flex items-center justify-center gap-1.5">
                                        @if (($status === 'draft' || $status === 'unpublished') && auth()->user()->hasPermission('forms.edit'))
                                        {{-- Design --}}
                                        <a href="{{ route('admin.forms.edit', $form->id) }}"
                                            class="p-1.5 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-600 dark:text-blue-400 rounded-lg text-xs font-semibold transition"
                                            title="Open Form Designer">

                                            <i class="fa-solid fa-pen-ruler"></i>

                                        </a>
                                        @endif


                                        {{-- View --}}
                                        {{-- @can('pdf_forms.view') --}}
                                        {{-- <a href="{{ route('admin.forms.edit', $form->id) }}"
                                            class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-xs font-semibold transition"
                                            title="View Form Template">

                                            <i class="fa-solid fa-eye"></i>

                                        {{-- @endcan --}}

                                       


                                        {{-- Publish --}}
                                        @if ($status === 'draft' || $status === 'unpublished')
                                            <form action="{{ route('admin.forms.publish', $form->id) }}" method="POST"
                                                data-confirmation data-confirm-title="Publish this PDF form template?" data-confirm-message="This template will become available for use in procurement workflows." data-confirm-eyebrow="Publish template" data-confirm-button="Publish template" data-confirm-tone="emerald" data-confirm-icon="fa-file-arrow-up">

                                                @csrf

                                                @method('PATCH')

                                                <button type="submit"
                                                    class="p-1.5 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-lg text-xs font-semibold transition"
                                                    title="Publish Form Template">

                                                    <i class="fa-solid fa-file-arrow-up"></i>

                                                </button>

                                            </form>


                                                {{-- Delete --}}
                                                {{-- @can('pdf_forms.delete') --}}
@can('forms.delete')
                                                <form action="{{ route('admin.forms.archive-action', $form) }}" method="POST" data-confirm-trigger data-confirm-title="Archive this form template?" data-confirm-message="The template will be removed from active use. You can restore it later from the archive." data-confirm-button="Archive template" data-confirm-method="PATCH">
                                                    @csrf @method('PATCH')
                                                    <button type="button" data-confirm-open class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300" title="Archive Template"><i class="fa-solid fa-box-archive"></i></button>
                                                </form>
@endcan
                                        {{-- @endcan --}}
                                        @else 
                                            @if ($status === 'published' && auth()->user()->hasPermission('forms.edit'))
                                                <form action="{{ route('admin.forms.unpublish', $form->id) }}" method="POST"
                                                    data-confirmation data-confirm-title="Unpublish this PDF form template?" data-confirm-message="This template will no longer be available for new workflow use." data-confirm-eyebrow="Unpublish template" data-confirm-button="Unpublish template" data-confirm-tone="amber" data-confirm-icon="fa-file-arrow-down">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="p-1.5 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-lg text-xs font-semibold transition"
                                                        title="Unublish Form Template">
                                                        <i class="fa-solid fa-file-arrow-down"></i>
                                                    </button>

                                                </form>
                                            @endif
                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="py-12 text-center">

                                    <div
                                        class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">

                                        <i class="fa-solid fa-file-circle-plus text-xl"></i>

                                    </div>

                                    <div class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                        No PDF form templates found.
                                    </div>

                                    <p class="text-[10px] text-slate-400 mt-1">
                                        Create your first reusable fillable PDF template.
                                    </p>

                                    @can('forms.add')
                                        <a href="{{ route('admin.forms.create') }}"
                                            class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[10px] font-semibold transition">

                                            <i class="fa-solid fa-plus"></i>
                                            Create Form Template
                                        </a>
                                    @endcan

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Mobile-friendly pagination --}}
            @if ($forms->hasPages())
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">

                    {{ $forms->links() }}

                </div>
            @endif

        </div>

    </div>



@endsection

@push('scripts')
        
@endpush

@push('scripts')

<script>document.querySelectorAll('[data-directory-filters]').forEach(form=>{const input=form.querySelector('[data-live-search]');let timer;input?.addEventListener('input',()=>{clearTimeout(timer);const value=input.value.trim();if(value.length>1)timer=setTimeout(()=>form.requestSubmit(),350);else if(!value)form.requestSubmit()});form.querySelectorAll('[data-auto-submit]').forEach(el=>el.addEventListener('change',()=>form.requestSubmit()))});</script>
@include('partials.directory-clear-sync')
@endpush
