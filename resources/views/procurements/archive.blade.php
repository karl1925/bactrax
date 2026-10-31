@extends('layouts.app')

@section('title', 'Archived Purchase Requests')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 py-6">
    <section class="relative isolate overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-white to-blue-50 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/30">
        <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full bg-blue-100/70 blur-3xl dark:bg-blue-900/20"></div>
        <div class="flex flex-col gap-5 p-5 sm:p-7 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                @include('partials.page-back-link', ['href' => route('procurements.index'), 'label' => 'Back to directory'])
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/60 dark:text-blue-300"><span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>Procurement workspace</div>
                <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">Archived Purchase Requests</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">Find and review archived requests. Permanent deletion is available to authorized users.</p>
                <div class="mt-5 flex flex-wrap gap-2 text-xs"><span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2 font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300"><i class="fa-solid fa-box-archive text-blue-600 dark:text-blue-400"></i>{{ number_format($procurements->total()) }} archived requests</span></div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div><h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Archive directory</h2><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Showing {{ $procurements->firstItem() ?? 0 }}–{{ $procurements->lastItem() ?? 0 }} of {{ number_format($procurements->total()) }} records</p></div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Archived records</span>
        </div>

        @php
            $filtersChanged = collect(request()->only(['search', 'mode', 'office', 'date_from', 'date_to']))->contains(fn ($value) => filled($value));
        @endphp
        <form method="GET" action="{{ route('procurements.archive') }}" data-directory-filters class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-950/30 sm:p-4">
            <label class="relative min-w-[12rem] flex-1"><span class="sr-only">Search archive</span><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="search" value="{{ request('search') }}" type="search" data-live-search placeholder="Search archive…" aria-label="Search by PR number, purpose, mode, or office" class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-800 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-950"></label>
            <label><span class="sr-only">Procurement mode</span><select name="mode" data-submit-on-change aria-label="Filter by procurement mode" class="max-w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><option value="">All modes</option>@foreach($modes as $mode)<option value="{{ $mode->id }}" @selected((string) request('mode') === (string) $mode->id)>{{ $mode->code }}</option>@endforeach</select></label>
            <label><span class="sr-only">End user office</span><select name="office" data-submit-on-change aria-label="Filter by end user office" class="max-w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><option value="">All offices</option>@foreach($offices as $office)<option value="{{ $office->id }}" @selected((string) request('office') === (string) $office->id)>{{ $office->code ?? $office->name }}</option>@endforeach</select></label>
            <div class="flex items-center gap-1.5"><label><span class="sr-only">PR date from</span><input type="date" name="date_from" value="{{ request('date_from') }}" data-date-range aria-label="PR date from" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"></label><span class="text-xs text-slate-400">–</span><label><span class="sr-only">PR date to</span><input type="date" name="date_to" value="{{ request('date_to') }}" data-date-range aria-label="PR date to" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"></label></div>
            <a href="{{ route('procurements.archive') }}" data-clear-filters @if(!$filtersChanged) hidden @endif class="inline-flex items-center gap-1 rounded-lg px-2.5 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"><i class="fa-solid fa-xmark"></i>Clear</a>
            @if($errors->any())<p class="w-full text-xs font-semibold text-rose-600" role="alert">{{ $errors->first() }}</p>@endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] border-collapse text-left">
                <thead><tr class="border-b border-slate-200 bg-slate-50/80 text-[9px] font-extrabold uppercase tracking-[.15em] text-slate-400 dark:border-slate-800 dark:bg-slate-950/60"><th class="px-5 py-3.5">Purchase request</th><th class="px-4 py-3.5">Purpose</th><th class="px-4 py-3.5">Mode / office</th><th class="px-4 py-3.5">Dates</th><th class="px-5 py-3.5 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                    @forelse($procurements as $procurement)
                        <tr class="group transition-colors hover:bg-blue-50/40 dark:hover:bg-blue-950/15">
                            <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/50 dark:text-blue-300"><i class="fa-solid fa-file-lines"></i></span><span class="min-w-0"><a href="{{ route('procurements.show', $procurement) }}" class="block truncate font-mono text-xs font-extrabold text-slate-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-300">{{ $procurement->pr_number ?: 'No PR number' }}</a><span class="mt-1 block text-[10px] text-slate-400">{{ $procurement->mode?->code ?? 'PR' }} · Request #{{ $procurement->id }}</span></span></div></td>
                            <td class="max-w-sm px-4 py-4"><p class="line-clamp-2 text-xs font-semibold leading-5 text-slate-700 dark:text-slate-300">{{ $procurement->purpose ?: 'No purpose recorded' }}</p></td>
                            <td class="px-4 py-4"><p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $procurement->mode?->name ?? '—' }}</p><p class="mt-1 text-[10px] text-slate-500">{{ $procurement->endUserOffice?->name ?? '—' }}</p></td>
                            <td class="px-4 py-4"><span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[9px] font-extrabold uppercase text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">Archived</span><p class="mt-1.5 text-[10px] text-slate-500">PR date: {{ $procurement->pr_date?->format('M d, Y') ?? '—' }}</p><p class="mt-1 text-[10px] text-slate-400">Updated: {{ $procurement->updated_at?->format('M d, Y') ?? '—' }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4 text-right"><div class="inline-flex items-center justify-end gap-1"><a href="{{ route('procurements.show', $procurement) }}" title="View request" aria-label="View request" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300"><i class="fa-solid fa-eye"></i></a>@if(auth()->user()?->isAdmin() || auth()->user()?->roles()->whereHas('permissions', fn ($q) => $q->where('name', 'procurement.delete'))->exists() || auth()->user()?->roles()->whereHas('privileges', fn ($q) => $q->where('code', 'can_delete'))->exists())<button type="button" title="Delete permanently" aria-label="Delete archived request permanently" data-confirm-action="{{ route('procurements.destroy', $procurement) }}" data-confirm-title="Permanently delete this archived request?" data-confirm-message="{{ ($procurement->pr_number ?: 'This purchase request') . ' and its related records and stored files will be permanently removed. This cannot be undone.' }}" data-confirm-button="Delete permanently" data-confirm-method="DELETE" data-confirm-tone="delete" class="inline-flex h-9 items-center gap-2 rounded-lg bg-rose-600 px-3 text-[10px] font-extrabold text-white shadow-sm transition hover:bg-rose-700"><i class="fa-solid fa-trash-can"></i><span class="hidden sm:inline">Delete</span></button>@endif</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-16 text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800"><i class="fa-solid fa-box-open text-xl"></i></span><p class="mt-4 text-sm font-extrabold text-slate-800 dark:text-slate-100">No archived purchase requests found</p><p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-500 dark:text-slate-400">Try adjusting your search or filters. Archived requests will appear here.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($procurements->hasPages())<div class="border-t border-slate-100 px-5 py-4 dark:border-slate-800">{{ $procurements->links() }}</div>@endif
    </section>

    
</div>
@endsection

@push('scripts')
<script>
    (() => {
        document.querySelectorAll('[data-directory-filters]').forEach((form) => {
            const clearLink = form.querySelector('[data-clear-filters]');
            const fields = [...form.querySelectorAll('input, select')];
            const search = form.querySelector('[data-live-search]');
            let searchTimer;

            const syncClearLink = () => {
                const hasValues = fields.some((field) => field.value.trim() !== '');
                if (clearLink) clearLink.hidden = !hasValues;
            };

            form.querySelectorAll('[data-submit-on-change]').forEach((select) => {
                select.addEventListener('change', () => form.requestSubmit());
            });

            const fromDate = form.querySelector('[name="date_from"]');
            const toDate = form.querySelector('[name="date_to"]');
            const submitDateRange = () => {
                if (fromDate.value) toDate.min = fromDate.value;
                else toDate.removeAttribute('min');
                if (toDate.value) fromDate.max = toDate.value;
                else fromDate.removeAttribute('max');

                if (fromDate.value && toDate.value && fromDate.validity.valid && toDate.validity.valid && fromDate.value <= toDate.value) {
                    form.requestSubmit();
                }
            };
            fromDate.addEventListener('change', submitDateRange);
            toDate.addEventListener('change', submitDateRange);

            search.addEventListener('input', () => {
                syncClearLink();
                window.clearTimeout(searchTimer);
                if (search.value.trim().length > 1) {
                    searchTimer = window.setTimeout(() => form.requestSubmit(), 350);
                }
            });

            fields.forEach((field) => field.addEventListener('change', syncClearLink));
            syncClearLink();
        });
    })();
</script>
@endpush
