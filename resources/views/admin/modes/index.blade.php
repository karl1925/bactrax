@extends('layouts.app')

@section('title', 'Procurement Modes Registry')

@section('page-header')
@include('partials.page-back-link', ['href' => route('dashboard'), 'label' => 'Dashboard'])
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Procurement Modes</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Configure statutory ABC caps, phase sequence rules, and SLA turnaround limits</p>
    </div>
    <a href="{{ route('admin.modes.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20 transition">
        <i class="fa-solid fa-plus"></i> Add New Modality
    </a>
</div>
@endsection
@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" action="{{ route('admin.modes.index') }}" data-directory-filters class="flex min-w-0 flex-1 gap-2"><input name="search" value="{{ request('search') }}" data-live-search type="search" placeholder="Search modes…" class="w-full max-w-sm rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"><a href="{{ route('admin.modes.index') }}" data-clear-filters @if(!request('search')) hidden @endif class="px-2 py-2 text-xs text-slate-500">Clear</a></form>
    <a href="{{ route('admin.modes.archive') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300"><i class="fa-solid fa-box-archive mr-1"></i>Archive</a>
</div>
<div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-x-auto">
    <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
        <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                <th class="py-3 px-4">Code</th>
                <th class="py-3 px-4">Modality Name</th>
                <th class="py-3 px-4 text-right">Min ABC</th>
                <th class="py-3 px-4 text-right">Max ABC Limit</th>
                <th class="py-3 px-4 text-center">Phases</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($modes as $mode)
            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                <td class="py-3.5 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $mode->code }}</td>
                <td class="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-200">{{ $mode->name }}</td>
                <td class="py-3.5 px-4 text-right font-mono">₱ {{ number_format($mode->min_abc, 2) }}</td>
                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                    {{ $mode->max_abc ? '₱ ' . number_format($mode->max_abc, 2) : 'Uncapped' }}
                </td>
                <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200/60 dark:border-blue-800/60">
                        {{ $mode->phases_count }} Steps
                    </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $mode->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                        {{ $mode->is_active ? 'ACTIVE' : 'DISABLED' }}
                    </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.modes.builder', $mode->id) }}" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition" title="Design Workflow Phases">
                            <i class="fa-solid fa-diagram-project mr-1"></i> Workflow Builder
                        </a>
                        <a href="{{ route('admin.modes.edit', $mode->id) }}" class="p-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition" title="Edit Parameters"><i class="fa-solid fa-pen"></i></a>
                        @can('modes.edit')
                        <form method="POST" action="{{ route('admin.modes.archive-toggle', $mode) }}" data-confirm-trigger data-confirm-title="Archive this procurement mode?" data-confirm-message="This mode will no longer be available for new purchase requests. Existing requests remain unchanged." data-confirm-button="Archive mode" data-confirm-method="PATCH"><button type="button" data-confirm-open class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300" title="Archive mode"><i class="fa-solid fa-box-archive"></i></button></form>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">No procurement modalities configured yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $modes->links() }}</div>

@endsection
@push('scripts')

<script>document.querySelectorAll('[data-directory-filters]').forEach(form=>{const input=form.querySelector('[data-live-search]');let timer;input?.addEventListener('input',()=>{clearTimeout(timer);const value=input.value.trim();if(value.length>1)timer=setTimeout(()=>form.requestSubmit(),350);else if(!value)form.requestSubmit()})});</script>
@include('partials.directory-clear-sync')
@endpush
