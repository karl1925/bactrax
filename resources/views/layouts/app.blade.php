<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="role-id" content="{{ Auth::user()->roles()?->first()->id ?? '' }}">
    <meta name="office-id" content="{{ Auth::user()->offices()?->first()->id ?? ''}}">
    <meta name="user-id" content="{{ Auth::user()->id }}">
    <title>@yield('title', config('app.name', 'BACTrax')) - DICT R2</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.tailwindcss.css">
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.tailwindcss.min.js"></script>
    @stack('styles')
</head>
<body class="bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100 antialiased overflow-x-hidden transition-colors duration-200">
{{-- App stacking order: content 0, sticky topbar 30, mobile backdrop 40, sidebar 50, menus 60, viewer 100, local dialogs 110–120, history 140, confirmation 160, search 180, toasts 200. --}}
<div x-data="{
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
    @include('layouts.partials.sidebar')
    {{-- Main Application Wrapper --}}
    <div class="flex min-h-screen max-w-full flex-col transition-all duration-300 ease-in-out"
        :class="{
            'lg:pl-20': sidebarCollapsed,
            'lg:pl-72': !sidebarCollapsed
        }"
    >
        {{-- Top Navigation Partial --}}
        @include('layouts.partials.topbar')
        {{-- Main Content --}}
        <main class="flex-1">
            <div id="mainPad" class="mainPad w-full max-w-full px-4 py-6 sm:px-6 lg:px-8">
                {{-- Page Header --}}
                @hasSection('page-header')
                    <div class="relative isolate mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-white to-blue-50/70 p-4 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-blue-950/20 sm:rounded-3xl sm:p-6">
                        <div class="pointer-events-none absolute -right-14 -top-20 h-48 w-48 rounded-full bg-blue-100/70 blur-3xl dark:bg-blue-900/20"></div>
                        <div class="relative min-w-0">@yield('page-header')</div>
                    </div>
                @endif
                {{-- Flash Messages --}}
                @if(session('error'))
                    <div class="mb-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-950/40 dark:border-rose-800/60 px-4 py-3 text-sm text-rose-700 dark:text-rose-300 shadow-xs">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 text-rose-600 dark:text-rose-400"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @elseif(session('success'))
                    <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/40 dark:border-emerald-800/60 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300 shadow-xs">
                        <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600 dark:text-emerald-400"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-950/40 dark:border-rose-800/60 px-4 py-3 text-sm text-rose-700 dark:text-rose-300 shadow-xs">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-exclamation mt-0.5 text-rose-600 dark:text-rose-400"></i>
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
        {{-- <footer class="border-t border-slate-200 bg-white transition-colors duration-200 dark:border-slate-800 dark:bg-slate-950">
            <div class="flex max-w-[1600px] flex-col gap-2 px-4 py-4 text-xs text-slate-500 transition-colors duration-200 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8 dark:text-slate-400">
                <p>{{ config('app.name', 'BACTrax') }} — DICT Region 2</p>
                <p>A RA 12009 Compliant Government Procurement Routing Engine</p>
            </div>
        </footer> --}}
    </div>
</div>

<div id="session-expired-modal"
    class="fixed inset-0 z-[300] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
    data-login-url="{{ route('login') }}"
    data-timeout-seconds="{{ (int) config('session.lifetime') * 60 }}"
    data-session-key="{{ hash_hmac('sha256', session()->getId(), (string) config('app.key')) }}"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="session-expired-title"
    aria-describedby="session-expired-message">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 dark:ring-slate-700">
        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                <i class="fa-solid fa-clock" aria-hidden="true"></i>
            </div>
            <div>
                <h2 id="session-expired-title" class="text-lg font-bold text-slate-900 dark:text-white">Your session has expired</h2>
                <p id="session-expired-message" class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    For your security, please sign in again to continue. Any unsaved changes may be lost.
                </p>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <a id="session-expired-login"
                href="{{ route('login') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-600 dark:hover:bg-blue-500">
                Sign in again
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>

{{-- Global Search Modal --}}
<div id="searchModal"
    class="fixed inset-0 z-[180] flex items-start justify-center pt-16 sm:pt-24 px-4 pointer-events-none"
    style="display: none;">
    {{-- Backdrop --}}
    <div id="documentPreviewBackdrop" class="absolute inset-0 bg-black/50 backdrop-blur-xs pointer-events-auto"></div>
    <div id="searchOverlay" class="fixed inset-0 pointer-events-auto"></div>
    <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.7)] ring-1 ring-slate-900/10 dark:ring-slate-700 pointer-events-auto">
        <form action="{{ route('procurements.index') }}" method="GET">
            <div class="relative flex items-center border-b border-slate-200 dark:border-slate-800 px-4">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-lg ml-2"></i>
                <input id="searchInput" type="text" name="search" placeholder="Search by PR Number, Title, Mode, or Office..." class="w-full border-0 bg-transparent py-4 pl-3 pr-10 text-slate-800 dark:text-slate-100 text-sm placeholder-slate-400 border-none outline-none focus:outline-none focus:ring-0 focus-visible:!outline-none" style="outline: none !important; box-shadow: none !important;" autocomplete="off">
                <button type="button" id="clearSearchBtn" class="hidden text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 mr-2">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            {{-- Live Dynamic Search Results Dropdown Container --}}
            <div id="liveSearchResults" class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 p-2 hidden"></div>
            <div class="bg-slate-50 dark:bg-slate-900/50 px-4 py-3 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <span class="font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-[10px]">Quick Search:</span>
                    <button type="button" data-search="ROUTING" class="quick-search-btn rounded-full bg-white dark:bg-slate-800 px-2.5 py-1 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-[#134991] dark:hover:text-blue-400 transition">
                        Routing
                    </button>
                    <button type="button" data-search="RETURNED" class="quick-search-btn rounded-full bg-white dark:bg-slate-800 px-2.5 py-1 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-rose-50 dark:hover:bg-rose-950/50 hover:text-rose-600 dark:hover:text-rose-400 transition">
                        Returned
                    </button>
                </div>
                <div class="hidden sm:flex items-center gap-2 shrink-0">
                    <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">Search All</button>
                    <span class="text-slate-400 text-[11px]">press <kbd class="font-semibold text-slate-600 dark:text-slate-400">ESC</kbd> to exit</span>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="toastContainer" class="fixed top-5 right-5 z-[200] space-y-3 w-full max-w-sm pointer-events-none"></div>
@stack('modals')
<script>
    $(document).ready(function () {
        const $searchModal = $('#searchModal');
        const $searchInput = $('#searchInput');
        const $clearBtn = $('#clearSearchBtn');
        const $liveResults = $('#liveSearchResults');
        window.openSearchModal = function () { $searchModal.fadeIn(150, function () { $searchInput.focus(); }); };
        window.closeSearchModal = function () { $searchModal.fadeOut(150); };
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
                    type: "GET", data: { q: query },
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
                                    <a href="${item.url}" class="block p-3 hover:bg-blue-50/80 dark:hover:bg-slate-800 rounded-xl transition">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-mono text-xs font-bold text-blue-600 dark:text-blue-400">
                                                ${item.pr_number}
                                            </span>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                ${item.office}
                                            </span>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-800
                                                dark:text-slate-200 truncate">
                                            ${item.title}
                                        </p>
                                        <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                            <span>
                                                ABC:
                                                <strong class="text-slate-700 dark:text-slate-300 font-mono">
                                                    ${item.abc}
                                                </strong>
                                            </span>
                                            <span class="font-bold text-blue-600 dark:text-blue-400">
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
            $liveResults.addClass('hidden').empty();
            $(this).addClass('hidden');
        });
        $('.quick-search-btn').on('click', function () {
            const query = $(this).data('search');
            $searchInput.val(query).trigger('input').focus();
        });
        $('#searchOverlay, #documentPreviewBackdrop').on('click', function () { window.closeSearchModal(); });
        $(document).on('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                window.openSearchModal();
            }
            if (e.key === 'Escape') {
                if ($searchModal.is(':visible')) {
                    window.closeSearchModal();
                }
                $('#routeModal, #returnModal, #approveModal, ' + '#rejectModal, #cancelModal').addClass('hidden');
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
                info: 'border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400'
            };
            const urlLink = url ? `
                    <a href="${url}"
                    class="inline-flex items-center gap-1 mt-2
                            text-[10px] font-bold
                            text-blue-600 dark:text-blue-400
                            hover:text-blue-700 dark:hover:text-blue-300
                            hover:underline">
                        View Procurement
                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                    </a>
                `
            : '';
            const $toast = $(`
                <div class="toast pointer-events-auto rounded-xl border bg-white dark:bg-slate-900 shadow-xl px-4 py-3 translate-x-full opacity-0 transition-all duration-300 ${classes[type] || classes.info}">
                    <!-- Header -->
                    <div class="flex items-center gap-3">
                        <i class="fa-solid ${icons[type] || icons.info} shrink-0 text-base"></i>
                        <!-- Title -->
                        <p class="flex-1 min-w-0 text-xs font-bold text-slate-800 dark:text-slate-100">
                            ${title || 'Notification'}
                        </p>
                        <!-- Close -->
                        <button type="button" class="toast-close shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <!-- Content -->
                    <div class="pl-7 mt-1">
                        <!-- Message -->
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
                            ${message}
                        </p>
                        <!-- URL -->
                        ${urlLink}
                    </div>

                </div>
            `);
            $('#toastContainer').append($toast);
            requestAnimationFrame(() => { $toast.removeClass('translate-x-full opacity-0'); });
            function removeToast() {
                $toast.addClass('translate-x-full opacity-0');
                setTimeout(() => { $toast.remove(); }, 300);
            }
            $toast.find('.toast-close').on('click', removeToast);
            setTimeout(removeToast, 6000);
        }
        const userId = $('meta[name="user-id"]').attr('content');
        const officeId = $('meta[name="office-id"]').attr('content');
        function handleProcurementUpdate(event, context = 'office') {
            let title = 'Procurement Update';
            let toastType = 'info';
            switch (event.action) {
                case 'ROUTING':
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
        if (window.Echo) {
            if (officeId) {
                window.Echo.private(`office.${officeId}`)
                    .listen('.procurement.office_updated', (event) => handleProcurementUpdate(event, 'office'));
            }
            if (userId) {
                window.Echo.private(`user.${userId}`)
                    .listen('.procurement.user_updated', (event) => handleProcurementUpdate(event, 'user'));
            }
        }
    });
</script>
@stack('scripts')
<x-confirmation-modal />
@include('partials.confirmation-modal-script')
@include('partials.unsaved-changes-script')
</body>
</html>
