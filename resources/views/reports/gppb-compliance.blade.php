@extends('layouts.app')

@section('title', 'GPPB RA 12009 Compliance Report')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-file-contract text-indigo-600 dark:text-indigo-400"></i> GPPB RA 12009 Compliance Report
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Statutory procurement register and 60-day turnaround audit log</p>
    </div>

    <form method="GET" action="{{ route('reports.gppb-compliance') }}" class="flex items-center gap-2">
        <select name="year" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 dark:text-slate-100 outline-none">
            @for($y = date('Y'); $y >= 2024; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Fiscal Year {{ $y }}</option>
            @endfor
        </select>
    </form>
</div>
@endsection

@section('content')
<div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Annual Procurement Register — FY {{ $year }}</h2>
        <button onclick="window.print()" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition">
            <i class="fa-solid fa-print mr-1"></i> Print Report
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
            <thead>
                <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <th class="py-2.5 px-3">Tracking No.</th>
                    <th class="py-2.5 px-3">Project Title</th>
                    <th class="py-2.5 px-3">End-User Office</th>
                    <th class="py-2.5 px-3">Modality</th>
                    <th class="py-2.5 px-3 text-right">ABC Total</th>
                    <th class="py-2.5 px-3 text-center">Lifecycle Days</th>
                    <th class="py-2.5 px-3 text-center">RA 12009 Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($reportData as $row)
                <tr>
                    <td class="py-3 px-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $row['tracking_number'] }}</td>
                    <td class="py-3 px-3 font-medium">{{ $row['project_title'] }}</td>
                    <td class="py-3 px-3">{{ $row['end_user_office'] }}</td>
                    <td class="py-3 px-3">{{ $row['procurement_mode'] }}</td>
                    <td class="py-3 px-3 text-right font-mono">₱ {{ number_format($row['abc_amount'], 2) }}</td>
                    <td class="py-3 px-3 text-center font-mono font-bold">{{ $row['total_elapsed_days'] }} Days</td>
                    <td class="py-3 px-3 text-center">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ str_contains($row['ra12009_compliant'], 'COMPLIANT') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                            {{ $row['ra12009_compliant'] }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400 text-xs">No procurement records logged for Fiscal Year {{ $year }}.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection