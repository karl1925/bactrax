@extends('layouts.app')

@section('title', 'Suppliers')

@section('page-header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <span class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.15em] text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/50 dark:text-blue-300"><i class="fa-solid fa-truck-field"></i> Supplier directory</span>
            <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">Suppliers</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Supplier profiles, registration details, and bid history.</p>
            <span class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300"><i class="fa-solid fa-address-book text-blue-600 dark:text-blue-400"></i>{{ number_format($suppliers->total()) }} supplier profiles</span>
        </div>
        <div class="flex flex-wrap gap-2">
        @can('suppliers.view')
            <a href="{{ route('suppliers.archive') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-xs font-extrabold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><i class="fa-solid fa-box-archive text-blue-600"></i>Archive ({{ $archivedCount }})</a>
        @endcan
        @can('suppliers.add')
            <a href="{{ route('suppliers.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-xs font-extrabold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800"><i class="fa-solid fa-plus"></i>Add supplier</a>
        @endcan
        </div>
    </div>
@endsection

@section('content')
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Supplier directory</h2>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Showing {{ $suppliers->firstItem() ?? 0 }}–{{ $suppliers->lastItem() ?? 0 }} of {{ number_format($suppliers->total()) }} suppliers</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Supplier records</span>
        </div>

        <form method="GET" action="{{ route('suppliers.index') }}" class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-950/30 sm:p-4">
            <label class="relative min-w-[12rem] flex-1"><span class="sr-only">Search suppliers</span><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="search" value="{{ request('search') }}" type="search" placeholder="Search supplier, TIN, or contact…" aria-label="Search suppliers" class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-800 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-950"></label>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-xs font-bold text-white transition hover:bg-blue-800"><i class="fa-solid fa-magnifying-glass"></i>Search</button>
            @if(filled(request('search')))<a href="{{ route('suppliers.index') }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"><i class="fa-solid fa-xmark"></i>Clear</a>@endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] border-collapse text-left">
                <thead><tr class="border-b border-slate-200 bg-slate-50/80 text-[9px] font-extrabold uppercase tracking-[.15em] text-slate-400 dark:border-slate-800 dark:bg-slate-950/60"><th class="px-5 py-3.5">Supplier</th><th class="px-4 py-3.5">Contact</th><th class="px-4 py-3.5">TIN / PhilGEPS</th><th class="px-4 py-3.5 text-center">Bids</th><th class="px-4 py-3.5 text-center">Status</th><th class="px-5 py-3.5 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                    @forelse($suppliers as $supplier)
                        <tr class="group transition-colors hover:bg-blue-50/40 dark:hover:bg-blue-950/15">
                            <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/50 dark:text-blue-300"><i class="fa-solid fa-building"></i></span><span class="min-w-0"><a href="{{ route('suppliers.show', $supplier) }}" class="block truncate font-bold text-slate-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-300">{{ $supplier->business_name }}</a><span class="mt-1 block text-[10px] text-slate-400">{{ $supplier->business_type ?: 'Supplier' }}</span></span></div></td>
                            <td class="px-4 py-4"><span class="font-semibold text-slate-700 dark:text-slate-300">{{ $supplier->contact_person ?: '—' }}</span><span class="mt-1 block text-[10px] text-slate-500">{{ $supplier->email ?: $supplier->phone ?: 'No contact details' }}</span></td>
                            <td class="px-4 py-4"><span class="font-mono text-[11px] text-slate-700 dark:text-slate-300">{{ $supplier->tin ?: '—' }}</span><span class="mt-1 block text-[10px] text-slate-500">{{ $supplier->philgeps_registration_number ?: 'No PhilGEPS number' }}</span></td>
                            <td class="px-4 py-4 text-center"><span class="inline-flex min-w-8 justify-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 font-mono font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $supplier->bids_count }}</span></td>
                            <td class="px-4 py-4 text-center"><span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-wide {{ $supplier->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }}"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-right"><div class="inline-flex items-center justify-end gap-1"><a href="{{ route('suppliers.show', $supplier) }}" title="View supplier" aria-label="View supplier" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300"><i class="fa-solid fa-eye"></i></a>@can('suppliers.edit')<a href="{{ route('suppliers.edit', $supplier) }}" title="Edit supplier" aria-label="Edit supplier" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300"><i class="fa-solid fa-pen"></i></a><form method="POST" action="{{ route('suppliers.toggle-active', $supplier) }}" data-confirmation data-confirm-title="Archive this supplier?" data-confirm-message="This profile will leave the active supplier directory. Bid history and documents remain available." data-confirm-button="Archive supplier" data-confirm-method="PATCH" data-confirm-tone="archive">@csrf @method('PATCH')<button type="submit" title="Archive supplier" aria-label="Archive supplier" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-amber-50 hover:text-amber-700 dark:hover:bg-amber-950/40 dark:hover:text-amber-300"><i class="fa-solid fa-box-archive"></i></button></form>@endcan</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800"><i class="fa-regular fa-building text-xl"></i></span><p class="mt-4 text-sm font-extrabold text-slate-800 dark:text-slate-100">No suppliers found</p><p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-500 dark:text-slate-400">Adjust your search or add a supplier profile to get started.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())<div class="border-t border-slate-100 px-5 py-4 dark:border-slate-800">{{ $suppliers->links() }}</div>@endif
    </section>
@endsection
