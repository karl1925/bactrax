<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'BACTrax')) - DICT R2</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- 1. Load jQuery CDN First -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- 2. Load DataTables CSS & JS CDN immediately after jQuery -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.tailwindcss.css">
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.tailwindcss.min.js"></script>

    @stack('styles')
</head>

<body class="bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100 antialiased overflow-x-hidden transition-colors duration-200">

<div
    x-data="{ 
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        darkMode: localStorage.getItem('darkMode') === 'true',
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
        },
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }"
    x-init="
        if (darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        $watch('darkMode', val => {
            if (val) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })
    "
    class="min-h-screen relative"
>

    {{-- Mobile Overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
        style="display: none;"
    ></div>

    {{-- Sidebar Partial --}}
    @include('layouts.sidebar')

    {{-- Main Application Wrapper --}}
    <div 
        class="flex min-h-screen max-w-full flex-col transition-all duration-300 ease-in-out"
        :class="{
            'lg:pl-20': sidebarCollapsed,
            'lg:pl-72': !sidebarCollapsed
        }"
    >

        {{-- Top Navigation Partial --}}
        @include('layouts.topbar')

        {{-- Main Content --}}
        <main class="flex-1 min-h-[calc(100vh-4rem)]">

            <div class="w-full max-w-full px-4 py-6 sm:px-6 lg:px-8">

                {{-- Page Header --}}
                @hasSection('page-header')
                    <div class="mb-6">
                        @yield('page-header')
                    </div>
                @endif

                {{-- Flash Messages --}}
                @if(session('error'))
                    <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/40 dark:border-red-800/60 px-4 py-3 text-sm text-red-700 dark:text-red-300 shadow-xs">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-600 dark:text-red-400"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @elseif(session('success'))
                    <div class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 dark:bg-green-950/40 dark:border-green-800/60 px-4 py-3 text-sm text-green-700 dark:text-green-300 shadow-xs">
                        <i class="fa-solid fa-circle-check mt-0.5 text-green-600 dark:text-green-400"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/40 dark:border-red-800/60 px-4 py-3 text-sm text-red-700 dark:text-red-300 shadow-xs">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-600 dark:text-red-400"></i>
                            <div>
                                <p class="font-semibold">Please correct the following:</p>
                                <ul class="mt-1 list-disc pl-5 space-y-0.5">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Page Content --}}
                @yield('content')

            </div>

        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-200 bg-white transition-colors duration-200 dark:border-slate-800 dark:bg-slate-950">
            <div class="flex max-w-[1600px] flex-col gap-2 px-4 py-4 text-xs text-slate-500 transition-colors duration-200 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8 dark:text-slate-400">
                <p>{{ config('app.name', 'BACTrax') }} — DICT Region 2</p>
                <p>A RA 12009 Compliant Government Procurement Routing Engine</p>
            </div>
        </footer>

    </div>

</div>

{{-- Global Search Modal --}}
<div
    id="searchModal"
    class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 px-4 pointer-events-none"
    style="display: none;">
    
    {{-- Backdrop --}}
    <div id="documentPreviewBackdrop" class="absolute inset-0 bg-black/50 backdrop-blur-xs pointer-events-auto"></div>
    <div id="searchOverlay" class="fixed inset-0 pointer-events-auto"></div>

    <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.7)] ring-1 ring-slate-900/10 dark:ring-slate-700 pointer-events-auto">
        <form action="{{ route('procurements.index') }}" method="GET">
            <div class="relative flex items-center border-b border-slate-200 dark:border-slate-800 px-4">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-lg ml-2"></i>

                <input
                    id="searchInput"
                    type="text"
                    name="search"
                    placeholder="Search by PR Number, Title, Mode, or Office..."
                    class="w-full border-0 bg-transparent py-4 pl-3 pr-10 text-slate-800 dark:text-slate-100 text-sm placeholder-slate-400 border-none outline-none focus:outline-none focus:ring-0 focus-visible:!outline-none"
                    style="outline: none !important; box-shadow: none !important;"
                    autocomplete="off"
                >

                <button
                    type="button"
                    id="clearSearchBtn"
                    class="hidden text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 mr-2"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Live Dynamic Search Results Dropdown Container --}}
            <div id="liveSearchResults" class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 p-2 hidden">
            </div>

            <div class="bg-slate-50 dark:bg-slate-900/50 px-4 py-3 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <span class="font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Quick Search:</span>
                    <button
                        type="button"
                        data-search="ROUTED"
                        class="quick-search-btn rounded-full bg-white dark:bg-slate-800 px-2.5 py-1 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-[#134991] dark:hover:text-blue-400 transition"
                    >
                        Routed
                    </button>
                    <button
                        type="button"
                        data-search="RETURNED"
                        class="quick-search-btn rounded-full bg-white dark:bg-slate-800 px-2.5 py-1 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-rose-50 dark:hover:bg-rose-950/50 hover:text-rose-600 dark:hover:text-rose-400 transition"
                    >
                        Returned
                    </button>
                </div>

                <div class="hidden sm:flex items-center gap-2 shrink-0">
                    <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">Search All</button>
                    <span class="text-slate-400 text-[11px]">press <kbd class="font-semibold text-slate-600 dark:text-slate-400">ESC</kbd> to exit</span>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ROUTE MODAL --}}
<div id="routeModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div id="routeOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>
        
        <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 border border-slate-200 dark:border-slate-800">
            <form id="routeForm" method="POST" action="">
                @csrf
                
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane text-indigo-600 dark:text-indigo-400"></i> Route Procurement
                    </h3>
                    <button type="button" class="closeRouteModal text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Project & Target Phase Summary -->
                    <div class="p-3 bg-indigo-50/60 dark:bg-slate-800/60 rounded-xl border border-indigo-100 dark:border-slate-700/80 space-y-1">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Routing Project: <span id="routeProcurementTitle" class="font-bold text-slate-800 dark:text-slate-100"></span>
                        </p>
                        <p id="routeNextPhaseContainer" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hidden">
                            Target Phase Step: <span id="routeNextPhaseName" class="font-bold"></span>
                        </p>
                    </div>

                    <!-- Destination Office (Filtered specifically for Next Phase Actors) -->
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Destination Office <span class="text-rose-500">*</span>
                        </label>
                        <select name="to_office_id" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600 dark:focus:border-indigo-400">
                            <option value="">Select Destination Office</option>
                            @if(isset($nextPhaseTargetOffices) && $nextPhaseTargetOffices->count() > 0)
                                @foreach($nextPhaseTargetOffices as $office)
                                    <option value="{{ $office->id }}" {{ $nextPhaseTargetOffices->count() === 1 ? 'selected' : '' }}>
                                        {{ $office->name }} ({{ $office->code }}) — {{ $office->location_type }}
                                    </option>
                                @endforeach
                            @else
                                @foreach(\App\Models\Office::where('is_active', true)->get() as $office)
                                    <option value="{{ $office->id }}">{{ $office->name }} ({{ $office->code }})</option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Filtered specifically for offices with users authorized to act on the next phase.</p>
                    </div>

                    <!-- Assign To Specific Actor (Targeted for Next Phase) -->
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Assign To Specific Actor (Optional)
                        </label>
                        <select name="to_user_id" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600 dark:focus:border-indigo-400">
                            <option value="">Any authorized actor in destination office (Default)</option>
                            @if(isset($nextPhaseTargetUsers) && $nextPhaseTargetUsers->count() > 0)
                                @foreach($nextPhaseTargetUsers as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} — {{ $user->roles->first()->name ?? 'Actor' }} ({{ $user->offices->first()->code ?? 'N/A' }})
                                    </option>
                                @endforeach
                            @else
                                @foreach(\App\Models\User::all() as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->offices->first()->code ?? 'N/A' }})</option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Filtered to display only users with authorized roles for the next phase.</p>
                    </div>

                    <!-- Remarks Field -->
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Remarks / Instructions
                        </label>
                        <textarea name="remarks" rows="3" placeholder="Add optional routing instructions..." class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600 dark:focus:border-indigo-400"></textarea>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-2">
                    <button type="button" class="closeRouteModal rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shadow-xs">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 text-xs font-semibold transition shadow-xs">Submit Routing</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- RETURN MODAL --}}
<div id="returnModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div id="returnOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>
        
        <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 border border-slate-200 dark:border-slate-800">
            <form id="returnForm" method="POST" action="">
                @csrf
                
                <!-- Modal Header -->
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 flex items-center gap-2">
                        <i class="fa-solid fa-rotate-left text-rose-600"></i> Return Procurement
                    </h3>
                    <button type="button" class="closeReturnModal text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Returning project: <span id="returnProcurementTitle" class="font-bold text-slate-800 dark:text-slate-100"></span>
                    </p>

                    <!-- Target Phase (Filtered strictly for phases prior to current phase) -->
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Target Return Phase <span class="text-rose-500">*</span>
                        </label>
                        <select id="target_phase_select" name="target_phase_id" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-rose-500">
                            <option value="">Select Target Return Phase</option>
                            @if(isset($procurement) && $procurement->currentPhase)
                                @php
                                    $priorPhases = $procurement->mode->phases
                                        ->where('phase_sequence', '<', $procurement->currentPhase->phase_sequence)
                                        ->sortBy('phase_sequence');
                                @endphp
                                @foreach($priorPhases as $phase)
                                    <option value="{{ $phase->id }}">
                                        Step {{ $phase->phase_sequence }}: {{ $phase->phase_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Displays only phase steps preceding the project's active stage.</p>
                    </div>

                    <!-- Remarks / Reason Field -->
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Reason for Returning <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="remarks" required rows="4" placeholder="Specify missing documents or technical corrections required..." class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-rose-500"></textarea>
                    </div>
                </div>

                <!-- Modal Footer Controls -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-2">
                    <button type="button" class="closeReturnModal rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shadow-xs">Cancel</button>
                    <button type="submit" class="rounded-lg bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 text-xs font-semibold transition shadow-xs">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- APPROVE / COMPLETE MODAL --}}
<div id="approveModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div id="approveOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>
        
        <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 border border-slate-200 dark:border-slate-800">
            <form id="approveForm" method="POST" action="">
                @csrf
                
                <!-- Modal Header -->
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Final Approval & Completion
                    </h3>
                    <button type="button" class="closeApproveModal text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div class="p-3 bg-emerald-50/60 dark:bg-slate-800/60 rounded-xl border border-emerald-100 dark:border-slate-700/80 space-y-1">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Completing Project: <span id="approveProcurementTitle" class="font-bold text-slate-800 dark:text-slate-100"></span>
                        </p>
                        <p class="text-[11px] text-emerald-700 dark:text-emerald-400">
                            This action will mark the procurement request as fully approved and completed across all phases.
                        </p>
                    </div>

                    <!-- Optional Approval Remarks -->
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Approval Remarks / Final Notes (Optional)
                        </label>
                        <textarea name="remarks" rows="3" placeholder="Add optional closing notes or reference numbers..." class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-emerald-500"></textarea>
                    </div>
                </div>

                <!-- Modal Footer Controls -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-2">
                    <button type="button" class="closeApproveModal rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shadow-xs">Cancel</button>
                    <button type="submit" class="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-xs font-semibold transition shadow-xs">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- REJECT MODAL --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div id="rejectOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>
        
        <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 border border-slate-200 dark:border-slate-800">
            <form id="rejectForm" method="POST" action="">
                @csrf
                
                <!-- Modal Header -->
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 flex items-center gap-2">
                        <i class="fa-solid fa-circle-xmark text-rose-600"></i> Reject Procurement
                    </h3>
                    <button type="button" class="closeRejectModal text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div class="p-3 bg-rose-50/60 dark:bg-slate-800/60 rounded-xl border border-rose-100 dark:border-slate-700/80 space-y-1">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Rejecting Project: <span id="rejectProcurementTitle" class="font-bold text-slate-800 dark:text-slate-100"></span>
                        </p>
                        <p class="text-[11px] text-rose-700 dark:text-rose-400">
                            Warning: Rejecting this procurement will terminate the request workflow immediately.
                        </p>
                    </div>

                    <!-- Rejection Reason -->
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Reason for Rejection <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="remarks" required rows="4" placeholder="Specify the non-compliance or budget issues requiring rejection..." class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-rose-500"></textarea>
                    </div>
                </div>

                <!-- Modal Footer Controls -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-2">
                    <button type="button" class="closeRejectModal rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shadow-xs">Cancel</button>
                    <button type="submit" class="rounded-lg bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 text-xs font-semibold transition shadow-xs">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- CANCEL MODAL --}}
<div id="cancelModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div id="cancelOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>
        
        <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 border border-slate-200 dark:border-slate-800">
            <form id="cancelForm" method="POST" action="">
                @csrf
                
                <!-- Modal Header -->
                <div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 flex items-center gap-2">
                        <i class="fa-solid fa-ban text-amber-600"></i> Cancel Procurement Request
                    </h3>
                    <button type="button" class="closeCancelModal text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div class="p-3 bg-amber-50/60 dark:bg-slate-800/60 rounded-xl border border-amber-100 dark:border-slate-700/80 space-y-1">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Cancelling Request: <span id="cancelProcurementTitle" class="font-bold text-slate-800 dark:text-slate-100"></span>
                        </p>
                        <p class="text-[11px] text-amber-700 dark:text-amber-400">
                            This will void the active procurement request. It can no longer be routed or modified.
                        </p>
                    </div>

                    <!-- Reason for Cancellation -->
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Reason for Cancellation <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="remarks" required rows="4" placeholder="Provide justification for cancelling this request..." class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-amber-500"></textarea>
                    </div>
                </div>

                <!-- Modal Footer Controls -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 px-6 py-3 flex items-center justify-end gap-2">
                    <button type="button" class="closeCancelModal rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shadow-xs">Dismiss</button>
                    <button type="submit" class="rounded-lg bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 text-xs font-semibold transition shadow-xs">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    $(document).ready(function () {
        // ==========================================
        // 1. SEARCH MODAL & LIVE AJAX SEARCH
        // ==========================================
        const $searchModal = $('#searchModal');
        const $searchInput = $('#searchInput');
        const $clearBtn = $('#clearSearchBtn');
        const $liveResults = $('#liveSearchResults');

        window.openSearchModal = function () {
            $searchModal.fadeIn(150, function () {
                $searchInput.focus();
            });
        };

        window.closeSearchModal = function () {
            $searchModal.fadeOut(150);
        };

        let debounceTimer;
        $searchInput.on('input', function () {
            clearTimeout(debounceTimer);
            let query = $(this).val().trim();

            if (query.length > 0) {
                $clearBtn.removeClass('hidden');
            } else {
                $clearBtn.addClass('hidden');
                $liveResults.addClass('hidden').empty();
                return;
            }

            if (query.length < 2) return;

            debounceTimer = setTimeout(function() {
                $.ajax({
                    url: "{{ route('api.search.global') }}",
                    type: "GET",
                    data: { q: query },
                    success: function(data) {
                        let html = '';
                        if (data.length === 0) {
                            html = '<p class="text-xs text-slate-400 p-4 text-center">No matching procurement records found.</p>';
                        } else {
                            data.forEach(function(item) {
                                html += `
                                    <a href="${item.url}" class="block p-3 hover:bg-indigo-50/80 dark:hover:bg-slate-800 rounded-xl transition">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">${item.tracking_number}</span>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">${item.office}</span>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">${item.title}</p>
                                        <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                            <span>ABC: <strong class="text-slate-700 dark:text-slate-300 font-mono">${item.abc}</strong></span>
                                            <span class="font-bold text-indigo-600 dark:text-indigo-400">${item.status}</span>
                                        </div>
                                    </a>`;
                            });
                        }
                        $liveResults.html(html).removeClass('hidden');
                    }
                });
            }, 300);
        });

        $clearBtn.on('click', function () {
            $searchInput.val('').focus();
            $liveResults.addClass('hidden').empty();
            $(this).addClass('hidden');
        });

        $('.quick-search-btn').on('click', function () {
            const query = $(this).data('search');
            $searchInput.val(query).trigger('input').focus();
        });

        $('#searchOverlay, #documentPreviewBackdrop').on('click', function () {
            window.closeSearchModal();
        });

        // ==========================================
        // 2. PROCUREMENT ACTION MODAL HANDLERS
        // ==========================================

        // --- Dynamic Route Modal ---
        window.openRouteModal = function(actionUrl, procurementTitle, nextPhaseName = null) {
            $('#routeForm').attr('action', actionUrl);
            $('#routeForm')[0].reset();
            $('#routeProcurementTitle').text(procurementTitle);

            if (nextPhaseName) {
                $('#routeNextPhaseName').text(nextPhaseName);
                $('#routeNextPhaseContainer').removeClass('hidden');
            } else {
                $('#routeNextPhaseContainer').addClass('hidden');
            }

            $('#routeModal').removeClass('hidden');
        };

        $('.closeRouteModal, #routeOverlay').on('click', function() {
            $('#routeModal').addClass('hidden');
        });

        // --- Dynamic Return Modal ---
        window.openReturnModal = function(actionUrl, procurementTitle, priorPhases = []) {
            $('#returnForm').attr('action', actionUrl);
            $('#returnForm')[0].reset();
            $('#returnProcurementTitle').text(procurementTitle);

            if (typeof priorPhases === 'string') {
                try {
                    priorPhases = JSON.parse(priorPhases);
                } catch (e) {
                    priorPhases = [];
                }
            }

            if (Array.isArray(priorPhases) && priorPhases.length > 0) {
                let optionsHtml = '<option value="">Select Target Return Phase</option>';
                priorPhases.forEach(function(phase) {
                    optionsHtml += `<option value="${phase.id}">Step ${phase.phase_sequence}: ${phase.phase_name}</option>`;
                });
                $('#target_phase_select').html(optionsHtml);
                
                // Auto-select the immediately preceding phase step
                $('#target_phase_select').val(priorPhases[priorPhases.length - 1].id);
            } else {
                $('#target_phase_select').html('<option value="">No previous phases available</option>');
            }

            $('#returnModal').removeClass('hidden');
        };

        $('.closeReturnModal, #returnOverlay').on('click', function() {
            $('#returnModal').addClass('hidden');
        });

        // --- Dynamic Approve / Complete Modal ---
        window.openApproveModal = function(actionUrl, procurementTitle) {
            $('#approveForm').attr('action', actionUrl);
            $('#approveForm')[0].reset();
            $('#approveProcurementTitle').text(procurementTitle);
            $('#approveModal').removeClass('hidden');
        };

        $('.closeApproveModal, #approveOverlay').on('click', function() {
            $('#approveModal').addClass('hidden');
        });

        // --- Dynamic Reject Modal ---
        window.openRejectModal = function(actionUrl, procurementTitle) {
            $('#rejectForm').attr('action', actionUrl);
            $('#rejectForm')[0].reset();
            $('#rejectProcurementTitle').text(procurementTitle);
            $('#rejectModal').removeClass('hidden');
        };

        $('.closeRejectModal, #rejectOverlay').on('click', function() {
            $('#rejectModal').addClass('hidden');
        });

        // --- Dynamic Cancel Modal ---
        window.openCancelModal = function(actionUrl, procurementTitle) {
            $('#cancelForm').attr('action', actionUrl);
            $('#cancelForm')[0].reset();
            $('#cancelProcurementTitle').text(procurementTitle);
            $('#cancelModal').removeClass('hidden');
        };

        $('.closeCancelModal, #cancelOverlay').on('click', function() {
            $('#cancelModal').addClass('hidden');
        });

        // ==========================================
        // 3. GLOBAL KEYBOARD SHORTCUTS
        // ==========================================
        $(document).on('keydown', function(e) {
            // Cmd + K (Mac) or Ctrl + K (Windows/Linux) to open Search
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                window.openSearchModal();
            }

            // Close ANY open modal on Escape key press
            if (e.key === 'Escape') {
                if ($searchModal.is(':visible')) window.closeSearchModal();
                $('#routeModal, #returnModal, #approveModal, #rejectModal, #cancelModal').addClass('hidden');
            }
        });
    });

@auth
    $(document).ready(function() {
        let primaryOfficeId = "{{ Auth::user()->offices->first()->id ?? 1 }}";

        if (window.Echo) {
            window.Echo.private(`office.${primaryOfficeId}`)
                .listen('.procurement.routed', (e) => {
                    alert(`[Inbound Routing Alert] Procurement ${e.procurement.tracking_number} has been routed to your office!\n\nMessage: ${e.message}`);
                });
        }
    });
@endauth
</script>

@stack('scripts')

</body>
</html>