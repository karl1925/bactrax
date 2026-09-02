<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="role-id" content="{{ Auth::user()->roles()->first()->id }}">
    <meta name="office-id" content="{{ Auth::user()->offices()->first()->id }}">
    <meta name="user-id" content="{{ Auth::user()->id }}">

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

<div id="toastContainer"
     class="fixed top-5 right-5 z-[100] space-y-3 w-full max-w-sm pointer-events-none">
</div>

@stack('modals')

<script>
    $(document).ready(function () {
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
            debounceTimer = setTimeout(function () {
                $.ajax({
                    url: "{{ route('api.search.global') }}",
                    type: "GET",
                    data: {
                        q: query
                    },
                    success: function (data) {
                        let html = '';
                        if (data.length === 0) {
                            html = `
                                <p class="text-xs text-slate-400 p-4 text-center">
                                    No matching procurement records found.
                                </p>
                            `;
                        } else {
                            data.forEach(function (item) {
                                html += `
                                    <a href="${item.url}"
                                    class="block p-3 hover:bg-indigo-50/80
                                            dark:hover:bg-slate-800 rounded-xl transition">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-mono text-xs font-bold
                                                        text-indigo-600 dark:text-indigo-400">
                                                ${item.tracking_number}
                                            </span>
                                            <span class="text-[10px] font-semibold px-2 py-0.5
                                                        rounded-full bg-slate-100 dark:bg-slate-800
                                                        text-slate-600 dark:text-slate-300
                                                        border border-slate-200 dark:border-slate-700">
                                                ${item.office}
                                            </span>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-800
                                                dark:text-slate-200 truncate">
                                            ${item.title}
                                        </p>
                                        <div class="flex justify-between text-[11px]
                                                    text-slate-500 dark:text-slate-400 mt-1">
                                            <span>
                                                ABC:
                                                <strong class="text-slate-700
                                                            dark:text-slate-300 font-mono">
                                                    ${item.abc}
                                                </strong>
                                            </span>

                                            <span class="font-bold text-indigo-600
                                                        dark:text-indigo-400">
                                                ${item.status}
                                            </span>
                                        </div>
                                    </a>
                                `;
                            });
                        }
                        $liveResults.html(html).removeClass('hidden');
                    }
                });
            }, 300);
        });
        $clearBtn.on('click', function () {
            $searchInput.val('').focus();
            $liveResults
                .addClass('hidden')
                .empty();
            $(this).addClass('hidden');
        });
        $('.quick-search-btn').on('click', function () {
            const query = $(this).data('search');
            $searchInput
                .val(query)
                .trigger('input')
                .focus();
        });
        $('#searchOverlay, #documentPreviewBackdrop').on('click', function () {
            window.closeSearchModal();
        });
        $(document).on('keydown', function (e) {
            if (
                (e.metaKey || e.ctrlKey) &&
                e.key.toLowerCase() === 'k'
            ) {
                e.preventDefault();
                window.openSearchModal();
            }
            if (e.key === 'Escape') {
                if ($searchModal.is(':visible')) {
                    window.closeSearchModal();
                }
                $(
                    '#routeModal, #returnModal, #approveModal, ' +
                    '#rejectModal, #cancelModal'
                ).addClass('hidden');
            }
        });
        function showToast(title, message, type = 'success', url = null) {
            const icons = {
                success: 'fa-circle-check',
                error: 'fa-circle-exclamation',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info'
            };
            const classes = {
                success: 'border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400',
                error: 'border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400',
                warning: 'border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400',
                info: 'border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400'
            };
            const urlLink = url
                ? `
                    <a href="${url}"
                    class="inline-flex items-center gap-1 mt-2
                            text-[10px] font-bold
                            text-indigo-600 dark:text-indigo-400
                            hover:text-indigo-700 dark:hover:text-indigo-300
                            hover:underline">
                        View Procurement
                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                    </a>
                `
                : '';

           const $toast = $(`
                <div class="toast pointer-events-auto
                            rounded-xl border bg-white dark:bg-slate-900
                            shadow-xl px-4 py-3
                            translate-x-full opacity-0
                            transition-all duration-300
                            ${classes[type] || classes.info}">

                    <!-- Header -->
                    <div class="flex items-center gap-3">

                        <!-- Icon -->
                        <i class="fa-solid ${icons[type] || icons.info}
                                shrink-0 text-base"></i>

                        <!-- Title -->
                        <p class="flex-1 min-w-0 text-xs font-bold
                                text-slate-800 dark:text-slate-100">
                            ${title || 'Notification'}
                        </p>

                        <!-- Close -->
                        <button type="button"
                                class="toast-close shrink-0
                                    text-slate-400
                                    hover:text-slate-600
                                    dark:hover:text-slate-200
                                    transition">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                    </div>

                    <!-- Content -->
                    <div class="pl-7 mt-1">

                        <!-- Message -->
                        <p class="text-xs font-medium
                                text-slate-600 dark:text-slate-300">
                            ${message}
                        </p>

                        <!-- URL -->
                        ${urlLink}

                    </div>

                </div>
            `);
            $('#toastContainer').append($toast);
            // Animate in
            requestAnimationFrame(() => {
                $toast.removeClass('translate-x-full opacity-0');
            });
            function removeToast() {
                $toast.addClass('translate-x-full opacity-0');
                setTimeout(() => {
                    $toast.remove();
                }, 300);
            }
            $toast.find('.toast-close').on('click', removeToast);
            // Auto close
            setTimeout(removeToast, 6000);
        }

        const userId = $('meta[name="user-id"]').attr('content');
        const officeId = $('meta[name="office-id"]').attr('content');

        function handleProcurementUpdate(event, context = 'office') {
            let title = 'Procurement Update';
            let toastType = 'info';
            switch (event.action) {
                case 'ROUTED':
                    title = context === 'user'
                        ? 'Procurement Assigned to You'
                        : 'Procurement Routed to Office';
                    break;
                case 'RETURNED':
                    title = 'Procurement Returned';
                    toastType = 'warning';
                    break;
                case 'COMPLETED':
                    title = 'Procurement Completed';
                    toastType = 'success';
                    break;
                case 'CANCELLED':
                    title = 'Procurement Cancelled';
                    toastType = 'error';
                    break;
                case 'REJECTED':
                    title = 'Procurement Rejected';
                    toastType = 'error';
                    break;
                default:
                    title = 'Procurement Update';
            }
            const message = event.message ||
                `Procurement #${event.procurement_id} has been updated.`;
            showToast(
                title,
                message,
                toastType,
                event.url
            );
        }
        
        Echo.private(`office.${officeId}`)
            .listen('.procurement.office_updated', (event) => {
            handleProcurementUpdate(event, 'office');

        });
        Echo.private(`user.${userId}`)
            .listen('.procurement.user_updated', (event) => {
            handleProcurementUpdate(event, 'user');
        });
    });
</script>

@stack('scripts')

</body>
</html>