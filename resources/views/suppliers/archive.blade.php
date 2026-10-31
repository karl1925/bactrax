@extends('layouts.app')

@section('title', 'Archived Suppliers')

@section('page-header')
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div><span class="mb-2 inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.15em] text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300"><i class="fa-solid fa-box-archive"></i> Supplier directory</span><h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">Archived suppliers</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Inactive profiles stay here with their bid history and documents intact.</p></div>
    @include('partials.page-back-link', ['href' => route('suppliers.index'), 'label' => 'Back to suppliers'])
</div>
@endsection

@section('content')
<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <form method="GET" action="{{ route('suppliers.archive') }}" class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-950/30 sm:p-4"><label class="relative min-w-[12rem] flex-1"><span class="sr-only">Search archived suppliers</span><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="search" value="{{ request('search') }}" type="search" placeholder="Search supplier, TIN, or contact…" class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"></label><button class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-bold text-white">Search</button>@if(request('search'))<a href="{{ route('suppliers.archive') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-500">Clear</a>@endif</form>
    <div class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse($suppliers as $supplier)
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"><div class="min-w-0"><a href="{{ route('suppliers.show', $supplier) }}" class="truncate text-sm font-bold text-slate-900 hover:text-blue-700 dark:text-white">{{ $supplier->business_name }}</a><p class="mt-1 text-xs text-slate-500">{{ $supplier->tin ?: 'TIN not recorded' }} · {{ $supplier->bids_count }} bids · Archived profile</p></div>@can('suppliers.edit')<form method="POST" action="{{ route('suppliers.toggle-active', $supplier) }}" data-confirmation data-confirm-title="Restore this supplier?" data-confirm-message="The profile will return to the active supplier directory." data-confirm-button="Restore supplier" data-confirm-method="PATCH" data-confirm-tone="archive">@csrf @method('PATCH')<button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-300"><i class="fa-solid fa-rotate-left"></i>Restore</button></form>@endcan</div>
        @empty<div class="p-12 text-center text-sm font-semibold text-slate-500">No archived suppliers found.</div>@endforelse
    </div>
    @if($suppliers->hasPages())<div class="border-t border-slate-100 p-4 dark:border-slate-800">{{ $suppliers->links() }}</div>@endif
</section>
@endsection
