@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('page-header')
<!-- Header & Dropdown Action -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-extrabold tracking-tight text-slate-800 dark:text-slate-100">Procurement overview</h1>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Your work queue, active requests, and workflow health at a glance.</p>
    </div>

    @if(($modes ?? collect())->count() === 1)
        <a href="{{ route('procurements.create', ['mode_id' => $modes->first()->id]) }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700">
            <i class="fa-solid fa-plus"></i><span>Create Purchase Request</span>
        </a>
    @else
    <!-- Mode Selection Dropdown Button -->
    <div x-data="{ open: false }" class="relative inline-block text-left">
        <button @click="open = !open"
                @click.away="open = false"
                type="button"
                class="inline-flex items-center justify-between gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition">
            <i class="fa-solid fa-plus"></i>
            <span>Create Purchase Request</span>
            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-150" :class="{ 'rotate-180': open }"></i>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 z-[60] mt-2 w-72 rounded-xl bg-white dark:bg-slate-900 shadow-xl border border-slate-200 dark:border-slate-800 p-1.5 focus:outline-none"
                style="display: none;">

            <div class="px-2 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800 mb-1">
                Select Procurement Mode
            </div>

            <div class="max-h-64 overflow-y-auto space-y-0.5">
                @forelse($modes ?? [] as $mode)
                    <a href="{{ route('procurements.create', ['mode_id' => $mode->id]) }}"
                        class="group flex flex-col p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-950/50 transition">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                {{ $mode->name }}
                            </span>
                            <span class="font-mono text-[10px] font-extrabold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950 px-1.5 py-0.5 rounded border border-blue-100 dark:border-blue-900">
                                {{ $mode->code }}
                            </span>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-0.5">
                            Cap: {{ $mode->max_abc ? '₱ ' . number_format($mode->max_abc, 2) : 'Uncapped' }}
                        </span>
                    </a>
                @empty
                    <div class="p-3 text-center text-xs text-slate-400 italic">
                        No active procurement modes available.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('content')
<div class="space-y-6 pb-4">

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">

        {{-- 1. Active Procurements --}}
        <div class="group flex min-h-28 items-center gap-4 rounded-2xl border border-blue-100 bg-gradient-to-br from-white to-blue-50/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-blue-950 dark:from-slate-900 dark:to-blue-950/30">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-blue-600 text-lg text-white shadow-lg shadow-blue-600/20"><i class="fa-solid fa-folder-open"></i></span>
            <div class="min-w-0"><div class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ number_format($totalActive ?? 0) }}</div><div class="mt-1 text-[11px] font-bold text-slate-500 dark:text-slate-400">Active procurements</div><div class="mt-1 text-[9px] text-slate-400">Draft, routing, or returned</div></div>
        </div>

        {{-- 2. SLA Turnaround Delays --}}
        <div class="group flex min-h-28 items-center gap-4 rounded-2xl border border-rose-100 bg-gradient-to-br from-white to-rose-50/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-rose-950 dark:from-slate-900 dark:to-rose-950/25">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-rose-500 text-lg text-white shadow-lg shadow-rose-500/20"><i class="fa-solid fa-clock-rotate-left"></i></span>
            <div class="min-w-0"><div class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ number_format($slaViolations ?? 0) }}</div><div class="mt-1 text-[11px] font-bold text-slate-500 dark:text-slate-400">SLA delays</div><div class="mt-1 text-[9px] text-slate-400">Active phase past its target</div></div>
        </div>

        {{-- 3. Returned Procurements --}}
        <div class="group flex min-h-28 items-center gap-4 rounded-2xl border border-amber-100 bg-gradient-to-br from-white to-amber-50/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-amber-950 dark:from-slate-900 dark:to-amber-950/25">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-500 text-lg text-white shadow-lg shadow-amber-500/20"><i class="fa-solid fa-rotate-left"></i></span>
            <div class="min-w-0"><div class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ number_format($totalReturned ?? 0) }}</div><div class="mt-1 text-[11px] font-bold text-slate-500 dark:text-slate-400">Returned requests</div><div class="mt-1 text-[9px] text-slate-400">Revisions needed</div></div>
        </div>

        {{-- 4. Completed Projects --}}
        <div class="group flex min-h-28 items-center gap-4 rounded-2xl border border-emerald-100 bg-gradient-to-br from-white to-emerald-50/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-950 dark:from-slate-900 dark:to-emerald-950/25">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-600 text-lg text-white shadow-lg shadow-emerald-600/20"><i class="fa-solid fa-circle-check"></i></span>
            <div class="min-w-0"><div class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ number_format($totalCompleted ?? 0) }}</div><div class="mt-1 text-[11px] font-bold text-slate-500 dark:text-slate-400">Completed procurements</div><div class="mt-1 text-[9px] text-slate-400">Workflow finished</div></div>
        </div>

    </div>

    <section aria-label="My action summary" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <a href="#action-register" class="flex items-center justify-between rounded-2xl border border-amber-200/80 bg-amber-50/60 px-4 py-3 transition hover:border-amber-300 hover:bg-amber-50 dark:border-amber-900/70 dark:bg-amber-950/20 dark:hover:bg-amber-950/35"><span class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300"><i class="fa-solid fa-signature"></i></span><span><span class="block text-[11px] font-extrabold text-slate-800 dark:text-slate-100">Awaiting my signature</span><span class="block text-[9px] text-slate-500 dark:text-slate-400">Documents ready for your turn</span></span></span><span class="text-lg font-black text-amber-700 dark:text-amber-300">{{ number_format($pendingActionCounts['sign'] ?? 0) }}</span></a>
        <a href="#action-register" class="flex items-center justify-between rounded-2xl border border-blue-200/80 bg-blue-50/60 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50 dark:border-blue-900/70 dark:bg-blue-950/20 dark:hover:bg-blue-950/35"><span class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300"><i class="fa-solid fa-share-nodes"></i></span><span><span class="block text-[11px] font-extrabold text-slate-800 dark:text-slate-100">Ready to route</span><span class="block text-[9px] text-slate-500 dark:text-slate-400">Attachments awaiting dispatch</span></span></span><span class="text-lg font-black text-blue-700 dark:text-blue-300">{{ number_format($pendingActionCounts['route'] ?? 0) }}</span></a>
        <a href="#action-register" class="flex items-center justify-between rounded-2xl border border-violet-200/80 bg-violet-50/60 px-4 py-3 transition hover:border-violet-300 hover:bg-violet-50 dark:border-violet-900/70 dark:bg-violet-950/20 dark:hover:bg-violet-950/35"><span class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/60 dark:text-violet-300"><i class="fa-solid fa-pen-to-square"></i></span><span><span class="block text-[11px] font-extrabold text-slate-800 dark:text-slate-100">Revisions to make</span><span class="block text-[9px] text-slate-500 dark:text-slate-400">Returned requests requiring edits</span></span></span><span class="text-lg font-black text-violet-700 dark:text-violet-300">{{ number_format($pendingActionCounts['revise'] ?? 0) }}</span></a>
    </section>
    
    <!-- Pending Action Register & Recent Activity Stream Grid -->
    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">

        <!-- Action Required Register Card -->
        <div id="action-register" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-hourglass-half text-blue-600 dark:text-blue-400"></i> Action required
                </h2>
                <a href="{{ route('procurements.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[10px] font-extrabold text-blue-700 transition hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-950/50">View directory <i class="fa-solid fa-arrow-right text-[9px]"></i></a>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                @if(isset($pendingActions))
                @forelse($pendingActions as $item)
                @php
                    $procurement = $item->procurement;
                    $actionUrl = route('procurements.show', $procurement->id).'?open_attachment='.urlencode($item->attachment_id).($item->open_form ? '&edit_pr=1' : '');
                    $isSignAction = $item->action === 'Sign';
                    $actionRowTone = $isSignAction
                        ? 'border-amber-200 bg-amber-50/70 hover:border-amber-300 hover:bg-amber-50 dark:border-amber-900/70 dark:bg-amber-950/25 dark:hover:bg-amber-950/40'
                        : 'border-slate-100 bg-white hover:border-blue-200 hover:bg-blue-50/50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900 dark:hover:bg-blue-950/20';
                @endphp
                <a href="{{ $actionUrl }}" aria-label="{{ $item->action }} {{ $item->document }} for {{ $procurement->pr_number }}" class="group my-2 flex items-center justify-between gap-3 rounded-2xl border p-3.5 transition {{ $actionRowTone }}">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs font-extrabold text-blue-700 dark:text-blue-300">{{ $procurement->pr_number }}</span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700">
                                {{ $procurement->endUserOffice->code ?? 'N/A' }}
                            </span>
                            @php $actionTone = ['Sign' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300', 'Route' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300', 'Revise' => 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300'][$item->action] ?? 'bg-slate-100 text-slate-700'; @endphp
                            <span class="rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide {{ $actionTone }}">{{ $item->action }}</span>
                        </div>
                        <p class="truncate text-xs font-bold text-slate-800 transition group-hover:text-blue-700 dark:text-slate-100 dark:group-hover:text-blue-300">{{ $procurement->purpose ?: 'Purpose not entered' }}</p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px] text-slate-400">
                            <span class="{{ $isSignAction ? 'font-extrabold text-amber-800 dark:text-amber-300' : 'font-semibold text-slate-600 dark:text-slate-300' }}">{{ $item->action === 'Sign' ? 'Signing as '.$item->role_name : $item->document }}</span><span aria-hidden="true">·</span>
                            <span>{{ $procurement->currentPhase->phase_name ?? ($procurement->status === 'DRAFT' ? 'Draft' : 'Phase not assigned') }}</span><span aria-hidden="true">·</span>
                            <span class="font-mono">₱ {{ number_format((float)$procurement->abc_amount, 2) }}</span>
                        </div>
                    </div>

                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $isSignAction ? 'bg-amber-400 text-amber-950 shadow-sm group-hover:bg-amber-500 dark:bg-amber-500' : 'border border-slate-200 text-slate-500 group-hover:border-blue-300 group-hover:bg-blue-600 group-hover:text-white dark:border-slate-700 dark:group-hover:border-blue-500' }}"><i class="fa-solid {{ $isSignAction ? 'fa-file-signature' : 'fa-arrow-right' }} text-xs"></i></span>
                </a>
                @empty
                <div class="py-8 text-center text-slate-400">
                    <i class="fa-solid fa-clipboard-check text-2xl mb-1 block opacity-40"></i>
                    <p class="text-xs">No pending action items require immediate processing.</p>
                </div>
                @endforelse
                @endif
            </div>
        </div>

        <!-- Recent Activity Directory Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-blue-600 dark:text-blue-400"></i> Recent Procurement Registry
                </h2>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Latest Entries</span>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                @if(($recentProcurements ?? collect())->isNotEmpty())
                @foreach($recentProcurements as $registryItem)
                @php
                    $statusStyles = [
                        'DRAFT' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200',
                        'ROUTING' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200',
                        'RETURNED' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200',
                        'REJECTED' => 'bg-rose-50 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border-rose-200',
                        'CANCELLED' => 'bg-rose-50 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border-rose-200',
                        'COMPLETED' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200',
                        'ARCHIVED' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border-slate-200',
                    ];
                @endphp
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-3.5 text-xs last:border-0 dark:border-slate-800">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-0.5">
                            <a href="{{ route('procurements.show', $registryItem->id) }}" class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $registryItem->pr_number }}
                            </a>
                            <span class="text-[10px] text-slate-400">({{ $registryItem->endUserOffice->code ?? 'N/A' }})</span>
                        </div>
                        <p class="truncate text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ $registryItem->purpose ?: 'Purpose not entered' }}</p>
                    </div>

                    <div class="text-right shrink-0 flex flex-col items-end">
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs">₱ {{ number_format($registryItem->abc_amount, 2) }}</span>
                        <span class="mt-1 px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $statusStyles[$registryItem->status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ str_replace('_', ' ', strtolower($registryItem->status)) }}
                        </span>
                    </div>
                </div>
                @endforeach
                @else
                <div class="py-8 text-center text-slate-400">
                    <i class="fa-solid fa-folder-open text-2xl mb-1 block opacity-40"></i>
                    <p class="text-xs">No procurement projects found in the system registry.</p>
                </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
