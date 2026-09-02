@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-indigo-600 dark:text-indigo-400"></i> Executive Procurement Dashboard
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Republic Act 12009 SLA Tracking & Routing Metrics — DICT Region 2</p>
    </div>
    @can('procurement.add')
    <div class="flex items-center gap-2">
        <a href="{{ route('procurements.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">
            <i class="fa-solid fa-plus"></i> New Purchase Request
        </a>
    </div>
    @endcan
</div>
@endsection

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-4 lg:grid-cols-4 gap-4 sm:grid-cols-2">

        {{-- 1. Active Procurements --}}
        <div class="flex flex-col items-center justify-center p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs transition-all duration-200 hover:border-indigo-300 dark:hover:border-indigo-800 hover:shadow-md text-center">
            <div class="mb-3 text-indigo-500 dark:text-indigo-400 text-3xl">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <div class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">
                {{ number_format($totalActive) }}
            </div>
            <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                Active Procurements
            </div>
        </div>

        {{-- 2. SLA Turnaround Delays --}}
        <div class="flex flex-col items-center justify-center p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs transition-all duration-200 hover:border-rose-300 dark:hover:border-rose-800 hover:shadow-md text-center">
            <div class="mb-3 text-rose-500 dark:text-rose-400 text-3xl">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">
                {{ number_format($slaViolations) }}
            </div>
            <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                SLA Delays
            </div>
        </div>

        {{-- 3. Returned Procurements --}}
        <div class="flex flex-col items-center justify-center p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs transition-all duration-200 hover:border-amber-300 dark:hover:border-amber-800 hover:shadow-md text-center">
            <div class="mb-3 text-amber-500 dark:text-amber-400 text-3xl">
                <i class="fa-solid fa-rotate-left"></i>
            </div>
            <div class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">
                {{ number_format($totalReturned) }}
            </div>
            <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                Returned Procurements
            </div>
        </div>

        {{-- 4. Completed Projects --}}
        <div class="flex flex-col items-center justify-center p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs transition-all duration-200 hover:border-emerald-300 dark:hover:border-emerald-800 hover:shadow-md text-center">
            <div class="mb-3 text-emerald-500 dark:text-emerald-400 text-3xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">
                {{ number_format($totalCompleted) }}
            </div>
            <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                Completed Projects
            </div>
        </div>

    </div>
    
    <!-- Pending Action Register & Recent Activity Stream Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Action Required Register Card -->
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-hourglass-half text-indigo-600 dark:text-indigo-400"></i> Action Required Register
                </h2>
                <a href="{{ route('procurements.index') }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">View Directory</a>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                @forelse($pendingActions as $item)
                <div class="py-3.5 flex items-center justify-between gap-4 group">
                    <div class="truncate">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $item->tracking_number }}</span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700">
                                {{ $item->endUserOffice->code ?? 'N/A' }}
                            </span>
                        </div>
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">{{ $item->title }}</p>
                        <div class="flex items-center gap-2 mt-1 text-[10px] text-slate-400">
                            <span>Step: <strong class="text-slate-600 dark:text-slate-300 font-medium">{{ $item->currentPhase->phase_name ?? 'Phase Not Assigned' }}</strong></span>
                            <span>•</span>
                            <span>ABC: <strong class="text-slate-600 dark:text-slate-300 font-mono">₱ {{ number_format($item->abc_amount, 2) }}</strong></span>
                        </div>
                    </div>

                    <a href="{{ route('procurements.show', $item->id) }}" class="px-3.5 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 dark:hover:text-white rounded-xl text-xs font-semibold transition shrink-0 shadow-2xs">
                        Process <i class="fa-solid fa-chevron-right text-[10px] ml-1"></i>
                    </a>
                </div>
                @empty
                <div class="py-8 text-center text-slate-400">
                    <i class="fa-solid fa-clipboard-check text-2xl mb-1 block opacity-40"></i>
                    <p class="text-xs">No pending action items require immediate processing.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity Directory Card -->
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-indigo-600 dark:text-indigo-400"></i> Recent Procurement Registry
                </h2>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Latest Entries</span>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                @forelse($recentProcurements as $proc)
                @php
                    $statusStyles = [
                        'DRAFT' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200',
                        'ROUTED' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200',
                        'RETURNED' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200',
                        'CANCELLED' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200',
                        'COMPLETED' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200',
                    ];
                @endphp
                <div class="py-3 flex items-center justify-between text-xs gap-4">
                    <div class="truncate">
                        <div class="flex items-center gap-2 mb-0.5">
                            <a href="{{ route('procurements.show', $proc->id) }}" class="font-mono font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $proc->tracking_number }}
                            </a>
                            <span class="text-[10px] text-slate-400">({{ $proc->endUserOffice->code ?? 'N/A' }})</span>
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 truncate text-[11px] font-medium">{{ $proc->title }}</p>
                    </div>

                    <div class="text-right shrink-0 flex flex-col items-end">
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs">₱ {{ number_format($proc->abc_amount, 2) }}</span>
                        <span class="mt-1 px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $statusStyles[$proc->status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $proc->status }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-slate-400">
                    <i class="fa-solid fa-folder-open text-2xl mb-1 block opacity-40"></i>
                    <p class="text-xs">No procurement projects found in the system registry.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection