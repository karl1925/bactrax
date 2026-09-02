@extends('layouts.app')

@section('title', 'Procurements Registry')

@push('styles')
    <style>
        /* =========================================================
        Procurements DataTable - Header Theme
        DataTables can override Tailwind text colors, so use
        explicit selectors and !important.
        ========================================================= */

        /* LIGHT MODE */
        #procurements-table.dataTable thead > tr > th,
        #procurements-table.dataTable thead > tr > td,
        #procurements-table.dataTable thead > tr > th .dt-column-title,
        #procurements-table.dataTable thead > tr > td .dt-column-title {
            background-color: #f8fafc !important;
            color: #475569 !important;
            border-bottom-color: #e2e8f0 !important;
            opacity: 1 !important;
        }

        /* DARK MODE */
        html.dark #procurements-table.dataTable thead > tr > th,
        html.dark #procurements-table.dataTable thead > tr > td,
        html.dark #procurements-table.dataTable thead > tr > th .dt-column-title,
        html.dark #procurements-table.dataTable thead > tr > td .dt-column-title,

        body.dark #procurements-table.dataTable thead > tr > th,
        body.dark #procurements-table.dataTable thead > tr > td,
        body.dark #procurements-table.dataTable thead > tr > th .dt-column-title,
        body.dark #procurements-table.dataTable thead > tr > td .dt-column-title,

        .dark #procurements-table.dataTable thead > tr > th,
        .dark #procurements-table.dataTable thead > tr > td,
        .dark #procurements-table.dataTable thead > tr > th .dt-column-title,
        .dark #procurements-table.dataTable thead > tr > td .dt-column-title {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border-bottom-color: #334155 !important;
            opacity: 1 !important;
        }

        /* DataTables sorting headers */
        html.dark #procurements-table.dataTable thead th.sorting,
        html.dark #procurements-table.dataTable thead th.sorting_asc,
        html.dark #procurements-table.dataTable thead th.sorting_desc,
        .dark #procurements-table.dataTable thead th.sorting,
        .dark #procurements-table.dataTable thead th.sorting_asc,
        .dark #procurements-table.dataTable thead th.sorting_desc {
            color: #f8fafc !important;
        }

        /* Sorting icons */
        html.dark #procurements-table.dataTable thead th::before,
        html.dark #procurements-table.dataTable thead th::after,
        .dark #procurements-table.dataTable thead th::before,
        .dark #procurements-table.dataTable thead th::after {
            color: #94a3b8 !important;
        }

        /* Prevent DataTables opacity from hiding header text */
        #procurements-table.dataTable thead th,
        #procurements-table.dataTable thead td {
            opacity: 1 !important;
        }
    </style>
@endpush

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-folder-open text-indigo-600 dark:text-indigo-400"></i> Procurements Directory
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Republic Act 12009 Procurement Routing Matrix</p>
    </div>
    
    @can('procurement.add')
    <a href="{{ route('procurements.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">
        <i class="fa-solid fa-plus"></i> New Purchase Request
    </a>
    @endcan
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Interactive Filters Card -->
    <div class="p-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-2">
            <label for="filter-office" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                Office Scope
            </label>
            <select id="filter-office" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Offices</option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}">{{ $office->name }} ({{ $office->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="p-2">
            <label for="filter-mode" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                Procurement Modality
            </label>
            <select id="filter-mode" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Modalities</option>
                @foreach($modes as $mode)
                    <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="p-2">
            <label for="filter-status" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                Workflow Status
            </label>
            <select id="filter-status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-200 outline-none focus:border-indigo-500">
                <option value="">All Statuses</option>
                <option value="DRAFT">DRAFT</option>
                <option value="ROUTED">ROUTED</option>
                <option value="RETURNED">RETURNED</option>
                <option value="CANCELLED">CANCELLED</option>
                <option value="COMPLETED">COMPLETED</option>
            </select>
        </div>
    </div>

    <!-- DataTables Table Card -->
    <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="overflow-x-auto p-4 sm:p-6">
            <table id="procurements-table" class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold uppercase tracking-wider">
                        <th class="px-4 py-3 whitespace-nowrap">Tracking No.</th>
                        <th class="px-4 py-3 whitespace-nowrap">Project Title</th>
                        <th class="px-4 py-3 whitespace-nowrap">Office</th>
                        <th class="px-4 py-3 whitespace-nowrap">Modality</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">ABC Total</th>
                        <th class="px-4 py-3 whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle URL query string if coming from Global Search modal redirect
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('search');

        let table = $('#procurements-table').DataTable({
            processing: true,
            serverSide: true,
            search: {
                search: searchQuery || ''
            },
            ajax: {
                url: "{{ route('api.procurements.datatable') }}",
                type: 'POST',
                data: function (d) {
                    d.office_id = $('#filter-office').val();
                    d.mode_id = $('#filter-mode').val();
                    d.status = $('#filter-status').val();
                },
                error: function (xhr) {
                    console.error("DataTables AJAX Error:", xhr.responseText);
                }
            },
            columns: [
                { data: 'tracking_number', name: 'tracking_number' },
                { data: 'title', name: 'title' },
                { data: 'office_code', name: 'offices.code' },
                { data: 'mode_name', name: 'procurement_modes.code' },
                { data: 'abc_formatted', name: 'abc_amount', className: 'text-right' },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data) {
                        let badgeClass = {
                            'DRAFT': 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200',
                            'ROUTED': 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200',
                            'RETURNED': 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200',
                            'CANCELLED': 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200',
                            'REJECTED': 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200',
                            'COMPLETED': 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200'
                        };
                        return `<span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full border ${badgeClass[data] || ''}">${data}</span>`;
                    }
                },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        // Trigger DataTables redraw on filter change
        $('#filter-office, #filter-mode, #filter-status').on('change', function() {
            table.draw();
        });
    });
</script>
@endpush