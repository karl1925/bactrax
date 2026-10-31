@extends('layouts.app')

@section('title', 'Purchase Requests')

@section('content')
<div class="max-w-7xl mx-auto py-6 space-y-6">
    <section class="relative isolate overflow-visible rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-white to-blue-50 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/30">
        <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full bg-blue-100/70 blur-3xl dark:bg-blue-900/20"></div>
        <div class="flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/60 dark:text-blue-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Procurement workspace
                </div>
                @include('partials.page-back-link', ['href' => route('dashboard'), 'label' => 'Dashboard'])
                <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">Purchase Requests</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">Track purchase requests from draft through every procurement phase, with forms and attachments in one place.</p>
                <div class="mt-5 flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2 font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300"><i class="fa-solid fa-folder-open text-blue-600 dark:text-blue-400"></i>{{ number_format($procurements->total()) }} requests in directory</span>
                    <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2 font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-400"><i class="fa-solid fa-list-check text-emerald-600 dark:text-emerald-400"></i>Phase based workflow</span>
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center lg:justify-end">
                @if($canViewArchive)
                    <a href="{{ route('procurements.archive') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-xs font-extrabold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-800 dark:hover:bg-blue-950/50 dark:hover:text-blue-300">
                        <i class="fa-solid fa-box-archive text-blue-600 dark:text-blue-400"></i><span>View archive</span><i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[9px] text-slate-400"></i>
                    </a>
                @endif
                @if(($modes ?? collect())->count() === 1)
                    <a href="{{ route('procurements.create', ['mode_id' => $modes->first()->id]) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-xs font-extrabold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800 sm:w-auto"><i class="fa-solid fa-plus"></i><span>New request</span></a>
                @else
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.away="open = false" type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-xs font-extrabold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800 sm:w-auto">
                        <i class="fa-solid fa-plus"></i><span>New request</span><i class="fa-solid fa-chevron-down text-[9px] transition-transform duration-150" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div x-cloak x-show="open" x-transition class="absolute right-0 z-[60] mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                        <div class="px-3 pb-2 pt-1"><p class="text-[10px] font-extrabold uppercase tracking-[.16em] text-slate-400">Choose a procurement mode</p><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Start a request with its matching workflow.</p></div>
                        <div class="max-h-72 space-y-1 overflow-y-auto">
                            @forelse($modes ?? [] as $mode)
                                <a href="{{ route('procurements.create', ['mode_id' => $mode->id]) }}" class="group flex items-center justify-between gap-3 rounded-xl border border-transparent px-3 py-3 transition hover:border-blue-100 hover:bg-blue-50 dark:hover:border-blue-900 dark:hover:bg-blue-950/40">
                                    <span class="flex min-w-0 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition group-hover:bg-blue-100 group-hover:text-blue-700 dark:bg-slate-800 dark:text-slate-400 dark:group-hover:bg-blue-950 dark:group-hover:text-blue-300"><i class="fa-solid fa-file-circle-plus"></i></span><span class="min-w-0"><span class="block truncate text-xs font-extrabold text-slate-800 group-hover:text-blue-800 dark:text-slate-100 dark:group-hover:text-blue-300">{{ $mode->name }}</span><span class="mt-0.5 block text-[10px] text-slate-400">ABC cap: {{ $mode->max_abc ? '₱ '.number_format($mode->max_abc, 2) : 'Uncapped' }}</span></span></span>
                                    <span class="shrink-0 rounded-lg border border-blue-100 bg-white px-2 py-1 font-mono text-[9px] font-extrabold text-blue-700 dark:border-blue-900 dark:bg-slate-900 dark:text-blue-300">{{ $mode->code }}</span>
                                </a>
                            @empty
                                <div class="rounded-xl bg-slate-50 px-4 py-5 text-center dark:bg-slate-800/70"><i class="fa-regular fa-folder-open text-xl text-slate-300 dark:text-slate-600"></i><p class="mt-2 text-xs font-bold text-slate-600 dark:text-slate-300">No active modes available</p><p class="mt-1 text-[10px] text-slate-400">Ask an administrator to configure a procurement mode.</p></div>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div><h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Request directory</h2><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Showing {{ $procurements->firstItem() ?? 0 }}–{{ $procurements->lastItem() ?? 0 }} of {{ number_format($procurements->total()) }} requests</p></div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Current directory</span>
        </div>
        @php
            $filtersChanged = collect(request()->only(['search', 'status', 'mode', 'office', 'date_from', 'date_to']))->contains(fn ($value) => filled($value));
        @endphp
        <form method="GET" action="{{ route('procurements.index') }}" data-directory-filters class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-950/30 sm:p-4">
            <label class="relative min-w-[12rem] flex-1"><span class="sr-only">Search requests</span><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="search" value="{{ request('search') }}" type="search" data-live-search placeholder="Search requests…" aria-label="Search by PR number, purpose, mode, or office" class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-800 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-950"></label>
            <label><span class="sr-only">Status</span><select name="status" data-submit-on-change aria-label="Filter by status" class="max-w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>@endforeach</select></label>
            <label><span class="sr-only">Procurement mode</span><select name="mode" data-submit-on-change aria-label="Filter by procurement mode" class="max-w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><option value="">All modes</option>@foreach($modes as $mode)<option value="{{ $mode->id }}" @selected((string) request('mode') === (string) $mode->id)>{{ $mode->code }}</option>@endforeach</select></label>
            <label><span class="sr-only">End user office</span><select name="office" data-submit-on-change aria-label="Filter by end user office" class="max-w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><option value="">All offices</option>@foreach($offices as $office)<option value="{{ $office->id }}" @selected((string) request('office') === (string) $office->id)>{{ $office->code ?? $office->name }}</option>@endforeach</select></label>
            <div class="flex items-center gap-1.5"><label><span class="sr-only">PR date from</span><input type="date" name="date_from" value="{{ request('date_from') }}" data-date-range aria-label="PR date from" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"></label><span class="text-xs text-slate-400">–</span><label><span class="sr-only">PR date to</span><input type="date" name="date_to" value="{{ request('date_to') }}" data-date-range aria-label="PR date to" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"></label></div>
            <a href="{{ route('procurements.index') }}" data-clear-filters @if(!$filtersChanged) hidden @endif class="inline-flex items-center gap-1 rounded-lg px-2.5 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"><i class="fa-solid fa-xmark"></i>Clear</a>
            @if($errors->any())<p class="w-full text-xs font-semibold text-rose-600" role="alert">{{ $errors->first() }}</p>@endif
        </form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[9px] font-extrabold uppercase tracking-[.15em] text-slate-400 dark:border-slate-800 dark:bg-slate-950/60">
                        <th class="px-5 py-3.5">Purchase request</th><th class="px-4 py-3.5">Mode</th><th class="px-4 py-3.5">End user office</th><th class="px-4 py-3.5 text-right">ABC amount</th><th class="px-4 py-3.5 text-center">PR date</th><th class="px-4 py-3.5 text-center">Status</th><th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                    @forelse($procurements as $pr)
                        @php
                            $status = strtoupper($pr->status ?? 'DRAFT');
                            $isDraft = $status === 'DRAFT';
                            $isTerminalForCancel = in_array($status, ['COMPLETED', 'ARCHIVED', 'CANCELLED'], true);
                            $statusClass = match ($status) {
                                'DRAFT' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300',
                                'RETURNED' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-300',
                                'ROUTING' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/70 dark:bg-blue-950/40 dark:text-blue-300',
                                'REJECTED' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/40 dark:text-rose-300',
                                'COMPLETED' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-300',
                                'CANCELLED' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/40 dark:text-rose-300',
                                'ARCHIVED' => 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                default => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                            };
                        @endphp
                        <tr class="group transition-colors hover:bg-blue-50/40 dark:hover:bg-blue-950/15">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-700 transition group-hover:border-blue-200 group-hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-950/50 dark:text-blue-300"><i class="fa-solid fa-file-lines"></i></span>
                                    <span class="min-w-0"><span class="block truncate font-mono text-xs font-extrabold text-slate-900 dark:text-white">{{ $pr->pr_number ?: 'To be assigned' }}</span><span class="mt-1 block text-[10px] text-slate-400">Request #{{ $pr->id }}</span></span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 font-mono text-[10px] font-extrabold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $pr->mode->code ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300"><i class="fa-solid fa-building text-[10px] text-slate-400"></i>{{ $pr->endUserOffice->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <span class="font-mono text-xs font-bold tabular-nums text-slate-800 dark:text-slate-200">₱ {{ number_format((float) $pr->abc_amount, 2) }}</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400"><i class="fa-regular fa-calendar text-slate-400"></i>{{ $pr->pr_date ? \Carbon\Carbon::parse($pr->pr_date)->format('M d, Y') : '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-wide {{ $statusClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ str_replace('_', ' ', $status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1">
                                    <a href="{{ route('procurements.show', $pr) }}" title="Show" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @if(!$isDraft && !$isTerminalForCancel)
                                        <form action="{{ route('procurements.cancel', $pr) }}" method="POST" data-confirmation data-confirm-title="Cancel this purchase request?" data-confirm-message="This will mark the purchase request as cancelled." data-confirm-eyebrow="Cancel request" data-confirm-button="Cancel request" data-confirm-method="PATCH" data-confirm-tone="rose" data-confirm-icon="fa-ban" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Cancel" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-950/40">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($isDraft)
                                        <button type="button" title="Archive draft" aria-label="Archive draft purchase request"
                                            data-confirm-action="{{ route('procurements.destroy', $pr) }}"
                                            data-confirm-title="Move this draft to Archive?"
                                            data-confirm-message="The draft will be preserved in the archive and can no longer be edited or routed."
                                            data-confirm-button="Archive request"
                                            data-confirm-method="DELETE" data-confirm-tone="archive"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300">
                                            <i class="fa-solid fa-box-archive"></i>
                                        </button>
                                    @elseif($isTerminalForCancel && $canArchiveRequests)
                                        <form action="{{ route('procurements.archive-action', $pr) }}" method="POST" data-confirmation data-confirm-title="Archive this purchase request?" data-confirm-message="This {{ strtolower($status) }} request will move to the archive." data-confirm-eyebrow="Archive request" data-confirm-button="Archive request" data-confirm-method="POST" data-confirm-tone="archive" data-confirm-icon="fa-box-archive" class="inline">
                                            @csrf
                                            <button type="submit" title="Archive" aria-label="Archive {{ strtolower($status) }} purchase request" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300">
                                                <i class="fa-solid fa-box-archive"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800"><i class="fa-regular fa-folder-open text-xl"></i></span>
                                <p class="mt-4 text-sm font-extrabold text-slate-800 dark:text-slate-100">No active purchase requests</p>
                                <p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-500 dark:text-slate-400">New requests will appear here as they move through the procurement workflow.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($procurements->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 dark:border-slate-800">
                {{ $procurements->links() }}
            </div>
        @endif
    </div>


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
