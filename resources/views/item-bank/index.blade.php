@extends('layouts.app')

@section('title', 'Item Bank')

@section('page-header')
    <div class="min-w-0">
        <span class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.15em] text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/50 dark:text-blue-300"><i class="fa-solid fa-boxes-stacked"></i> Procurement resources</span>
        <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">Item Bank</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">Reusable goods, services, units, specifications, and reference prices for procurement item entry.</p>
        <span class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300"><i class="fa-solid fa-list text-blue-600 dark:text-blue-400"></i>{{ number_format($items->total()) }} catalog entries</span>
        @can('items.view')<a href="{{ route('item-bank.archive') }}" class="ml-2 inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><i class="fa-solid fa-box-archive text-blue-600"></i>Archive ({{ $archivedCount }})</a>@endcan
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        @can('items.add')
            <section class="h-fit overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Add an item</h2><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Create a reusable catalog entry.</p></div>
                <div class="p-5">@include('item-bank._form')</div>
            </section>
        @endcan

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 {{ auth()->user()->hasPermission('items.add') ? 'lg:col-span-2' : 'lg:col-span-3' }}">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div><h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Catalog entries</h2><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ number_format($items->total()) }} items</p></div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wide text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/40 dark:text-blue-300"><i class="fa-solid fa-boxes-stacked"></i>Item catalog</span>
            </div>
            <form method="GET" action="{{ route('item-bank.index') }}" class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-950/30 sm:p-4">
                <label class="relative min-w-[12rem] flex-1"><span class="sr-only">Search catalog</span><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="search" value="{{ request('search') }}" type="search" placeholder="Search catalog entries…" aria-label="Search catalog entries" class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-800 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-950"></label>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-xs font-bold text-white transition hover:bg-blue-800"><i class="fa-solid fa-magnifying-glass"></i>Search</button>
                @if(filled(request('search')))<a href="{{ route('item-bank.index') }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"><i class="fa-solid fa-xmark"></i>Clear</a>@endif
            </form>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($items as $item)
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 transition hover:bg-blue-50/40 dark:hover:bg-blue-950/15 sm:px-6">
                            <div class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/50 dark:text-blue-300"><i class="fa-solid fa-cube"></i></span><span class="min-w-0"><span class="block truncate text-xs font-bold text-slate-900 dark:text-white">{{ $item->description }}</span><span class="mt-1 block truncate text-[10px] text-slate-500 dark:text-slate-400">{{ $item->item_code ?: 'No code' }} · {{ $item->category ?: 'Uncategorized' }} · {{ $item->unit ?: 'No unit' }}</span></span></div>
                            <div class="flex shrink-0 items-center gap-3"><span class="hidden text-right sm:block"><span class="block font-mono text-xs font-bold text-slate-800 dark:text-slate-200">{{ $item->reference_price ? '₱ '.number_format($item->reference_price, 2) : 'No manual ref.' }}</span><span class="mt-1 block text-[10px] text-slate-500 dark:text-slate-400">Ref. {{ $item->reference_date?->format('d M Y') ?? 'date not set' }}</span>@if($item->price_source)<span class="mt-1 block max-w-56 truncate text-[10px] text-slate-500 dark:text-slate-400" title="{{ $item->price_source }}">Source: {{ $item->price_source }}</span>@endif<span class="mt-1 block text-[10px] font-semibold {{ $item->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">{{ $item->is_active ? 'Available' : 'Inactive' }} · {{ $item->price_source_count }} bid prices</span></span><i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition group-open:rotate-180"></i></div>
                        </summary>
                        <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/30 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap gap-2 text-[10px] sm:hidden"><span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-mono font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">{{ $item->reference_price ? 'Manual ref. ₱ '.number_format($item->reference_price, 2) : 'No manual reference' }}</span><span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Ref. {{ $item->reference_date?->format('d M Y') ?? 'date not set' }}</span>@if($item->price_source)<span class="max-w-full truncate rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300" title="{{ $item->price_source }}">Source: {{ $item->price_source }}</span>@endif<span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-bold {{ $item->is_active ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500' }} dark:border-slate-700 dark:bg-slate-900">{{ $item->is_active ? 'Available' : 'Inactive' }}</span></div>
                                <p class="mb-2 mt-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Linked procurement lines: {{ $item->procurement_items_count }} · qualifying bid prices: {{ $item->price_source_count }}</p>
                                @if($item->historicalBidItems->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach($item->historicalBidItems as $bidItem)
                                            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                                                <span class="min-w-0 text-[10px] text-slate-600 dark:text-slate-300"><span class="font-bold text-slate-800 dark:text-slate-100">{{ $bidItem->bid?->supplier?->business_name ?? 'Supplier unavailable' }}</span><span class="mx-1.5 text-slate-300">·</span>{{ $bidItem->bid?->procurement?->pr_number ?? 'Procurement record' }}<span class="mx-1.5 text-slate-300">·</span>{{ $bidItem->bid?->received_at?->format('d M Y') ?? 'Date not recorded' }}</span>
                                                <span class="shrink-0 font-mono text-[11px] font-extrabold text-emerald-700 dark:text-emerald-300">₱ {{ number_format((float) $bidItem->unit_price, 2) }} / {{ $bidItem->procurementItem?->unit ?: 'unit' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-[10px] text-slate-400">Bid prices stay in their original procurement records; this catalog only links and displays them.</p>
                                @else
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">No submitted or evaluated bid prices are linked to this catalog entry yet.</p>
                                @endif
                            </div>
                            @canAny(['items.edit','items.delete'])
                                <div class="flex items-center gap-2">
                                    @can('items.edit')<a href="{{ route('item-bank.edit', $item) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-800 dark:hover:bg-blue-950/40 dark:hover:text-blue-300"><i class="fa-solid fa-pen"></i>Edit details</a><form method="POST" action="{{ route('item-bank.toggle-active', $item) }}" data-confirmation data-confirm-title="Archive this catalog entry?" data-confirm-message="This item will no longer appear in item suggestions. Existing procurement history remains linked." data-confirm-button="Archive item" data-confirm-method="PATCH" data-confirm-tone="archive">@csrf @method('PATCH')<button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-white px-3 py-2 text-[10px] font-bold text-amber-700 transition hover:bg-amber-50 dark:border-amber-900/60 dark:bg-slate-900 dark:text-amber-300"><i class="fa-solid fa-box-archive"></i>Archive</button></form>@endcan
                                    @can('items.delete')
                                        @if($item->procurement_items_count === 0)<form method="POST" action="{{ route('item-bank.destroy', $item) }}" data-confirmation data-confirm-title="Remove this item?" data-confirm-message="This unused catalog entry will be removed." data-confirm-button="Remove item" data-confirm-method="DELETE" data-confirm-tone="rose">@csrf @method('DELETE')<button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-[10px] font-bold text-rose-700 transition hover:bg-rose-50 dark:border-rose-900/60 dark:bg-slate-900 dark:text-rose-300 dark:hover:bg-rose-950/40"><i class="fa-solid fa-trash-can"></i>Remove</button></form>@else<span class="text-[10px] text-slate-500 dark:text-slate-400">Linked history prevents removal; deactivate instead.</span>@endif
                                    @endcan
                                </div>
                            @else
                                <span class="text-[10px] text-slate-500 dark:text-slate-400">No management actions available.</span>
                            @endcanAny
                        </div>
                    </details>
                @empty
                    <div class="px-6 py-16 text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800"><i class="fa-solid fa-box-open text-xl"></i></span><p class="mt-4 text-sm font-extrabold text-slate-800 dark:text-slate-100">No catalog items found</p><p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-500 dark:text-slate-400">Adjust your search or add a catalog entry to get started.</p></div>
                @endforelse
            </div>
            @if($items->hasPages())<div class="border-t border-slate-100 px-5 py-4 dark:border-slate-800">{{ $items->links() }}</div>@endif
        </section>
    </div>
@endsection
