<aside
    class="fixed inset-y-0 left-0 z-50
        bg-white dark:bg-slate-900
        border-r border-slate-200 dark:border-slate-800
        flex flex-col
        transition-all duration-300 ease-in-out
        shadow-lg lg:shadow-none"
    :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full lg:translate-x-0': !sidebarOpen,
        'w-20': sidebarCollapsed,
        'w-72': !sidebarCollapsed
    }">

    {{-- ============================================================
        SIDEBAR BRANDING HEADER
    ============================================================= --}}
    <div class="h-16 min-h-16 max-h-16
            flex items-center
            border-b border-slate-200 dark:border-slate-800
            overflow-hidden"
        :class="sidebarCollapsed
            ?
            'justify-center px-0' :
            'justify-between px-4'">

        {{-- Brand --}}
        <a href="{{ route('dashboard') }}"
            class="flex items-center
                h-16
                min-w-0
                overflow-hidden"
            :class="sidebarCollapsed
                ?
                'justify-center' :
                'gap-3'">

            {{-- Fixed 30px Logo --}}
            <img src="{{ asset('favicon.png') }}" alt="BACTrax" width="50" height="50"
                class="block
                    w-[30px] h-[30px]
                    min-w-[30px] min-h-[30px]
                    max-w-[30px] max-h-[30px]
                    shrink-0
                    object-contain">

            {{-- Brand Text --}}
            <div x-show="!sidebarCollapsed" x-transition.opacity
                class="flex flex-col justify-center
                    min-w-0
                    truncate
                    leading-none">

                <span
                    class="font-extrabold
                        text-base
                        tracking-tight
                        text-indigo-600 dark:text-indigo-400
                        leading-none
                        whitespace-nowrap">
                    BACTrax
                </span>

                <span
                    class="mt-1
                        text-[9px]
                        font-semibold
                        text-slate-400
                        leading-none
                        whitespace-nowrap">
                    DICT Region 2
                </span>

            </div>

        </a>


        {{-- ========================================================
            MOBILE CLOSE BUTTON
        ========================================================= --}}
        <button type="button" @click="sidebarOpen = false"
            class="lg:hidden
                shrink-0
                text-slate-400
                hover:text-slate-600
                dark:hover:text-slate-200
                transition">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

    </div>


    {{-- ============================================================
        NAVIGATION
    ============================================================= --}}
    <nav class="flex-1
            p-3
            space-y-6
            overflow-y-auto">

        {{-- ========================================================
            MAIN NAVIGATION
        ========================================================= --}}
        <div>

            {{-- Section Heading --}}
            <p x-show="!sidebarCollapsed" x-transition.opacity
                class="px-3 mb-2
                    text-[10px]
                    font-bold
                    text-slate-400 dark:text-slate-500
                    uppercase
                    tracking-widest">
                Main Navigation
            </p>


            <div class="space-y-1">

                {{-- ==================================================
                    DASHBOARD
                =================================================== --}}
                <a href="{{ route('dashboard') }}"
                    class="
                        flex items-center
                        py-2.5
                        rounded-xl
                        font-semibold text-xs
                        transition-all

                        {{ request()->routeIs('dashboard')
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}
                    "
                    :class="sidebarCollapsed
                        ?
                        'justify-center px-0' :
                        'justify-start gap-3 px-3'"
                    :title="sidebarCollapsed ? 'Dashboard' : ''">

                    <i
                        class="fa-solid fa-chart-pie
                            text-base
                            w-5
                            text-center
                            shrink-0"></i>

                    <span x-show="!sidebarCollapsed" class="truncate">
                        Dashboard
                    </span>

                </a>


                {{-- ==================================================
                    PROCUREMENTS
                =================================================== --}}
                @can('procurement.view')
                    <a href="{{ route('procurements.index') }}"
                        class="
                            flex items-center
                            py-2.5
                            rounded-xl
                            font-semibold text-xs
                            transition-all

                            {{ request()->routeIs('procurements.*')
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}
                        "
                        :class="sidebarCollapsed
                            ?
                            'justify-center px-0' :
                            'justify-start gap-3 px-3'"
                        :title="sidebarCollapsed ? 'Procurements' : ''">

                        <i
                            class="fa-solid fa-folder-open
                                text-base
                                w-5
                                text-center
                                shrink-0"></i>

                        <span x-show="!sidebarCollapsed" class="truncate">
                            Procurements
                        </span>

                    </a>
                @endcan

            </div>

        </div>


        {{-- ============================================================
            SYSTEM MANAGEMENT
        ============================================================= --}}
        @canany(['settings.view', 'office.view', 'roles.view', 'users.view'])

            <div>

                {{-- Section Heading --}}
                <p x-show="!sidebarCollapsed" x-transition.opacity
                    class="px-3 mb-2
                        text-[10px]
                        font-bold
                        text-slate-400 dark:text-slate-500
                        uppercase
                        tracking-widest">
                    System Engine
                </p>


                <div class="space-y-1">

                    {{-- ==================================================
                        PROCUREMENT MODES
                    =================================================== --}}
                    @can('modes.view')
                        <a href="{{ route('procurement-modes.index') }}"
                            class="
                                flex items-center
                                py-2.5
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('procurement-modes.*')
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'justify-center px-0' :
                                'justify-start gap-3 px-3'"
                            :title="sidebarCollapsed ? 'Procurement Modes' : ''">

                            <i
                                class="fa-solid fa-sliders
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span x-show="!sidebarCollapsed" class="truncate">
                                Procurement Modes
                            </span>

                        </a>
                    @endcan


                    {{-- ==================================================
                        OFFICES
                    =================================================== --}}
                    @can('offices.view')
                        <a href="{{ route('admin.offices.index') }}"
                            class="
                                flex items-center
                                py-2.5
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.offices.*')
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'justify-center px-0' :
                                'justify-start gap-3 px-3'"
                            :title="sidebarCollapsed ? 'Offices' : ''">

                            <i
                                class="fa-solid fa-building
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span x-show="!sidebarCollapsed" class="truncate">
                                Offices
                            </span>

                        </a>
                    @endcan


                    {{-- ==================================================
                        ROLES
                    =================================================== --}}
                    @can('roles.view')
                        <a href="{{ route('admin.roles.index') }}"
                            class="
                                flex items-center
                                py-2.5
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.roles.*')
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'justify-center px-0' :
                                'justify-start gap-3 px-3'"
                            :title="sidebarCollapsed ? 'Roles' : ''">

                            <i
                                class="fa-solid fa-shield-halved
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span x-show="!sidebarCollapsed" class="truncate">
                                Roles
                            </span>

                        </a>
                    @endcan


                    {{-- ==================================================
                        USERS
                    =================================================== --}}
                    @can('users.view')
                        <a href="{{ route('admin.users.index') }}"
                            class="
                                flex items-center
                                py-2.5
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.users.*')
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'justify-center px-0' :
                                'justify-start gap-3 px-3'"
                            :title="sidebarCollapsed ? 'Users' : ''">

                            <i
                                class="fa-solid fa-users
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span x-show="!sidebarCollapsed" class="truncate">
                                Users
                            </span>

                        </a>
                    @endcan


                    {{-- ==================================================
                        SETTINGS
                    =================================================== --}}
                    @can('settings.view')
                        <a href="{{ route('admin.settings.index') }}"
                            class="
                                flex items-center
                                py-2.5
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.settings.*')
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'justify-center px-0' :
                                'justify-start gap-3 px-3'"
                            :title="sidebarCollapsed ? 'Settings' : ''">

                            <i
                                class="fa-solid fa-gear
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span x-show="!sidebarCollapsed" class="truncate">
                                Settings
                            </span>

                        </a>
                    @endcan

                </div>

            </div>

        @endcanany


        {{-- ============================================================
            REPORTS
        ============================================================= --}}
        @can('reports.view')
            <div>

                {{-- Section Heading --}}
                <p x-show="!sidebarCollapsed" x-transition.opacity
                    class="px-3 mb-2
                        text-[10px]
                        font-bold
                        text-slate-400 dark:text-slate-500
                        uppercase
                        tracking-widest">
                    Reports
                </p>


                <div class="space-y-1">

                    {{-- ==================================================
                        GPPB COMPLIANCE
                    =================================================== --}}
                    <a href="{{ route('reports.gppb-compliance') }}"
                        class="
                            flex items-center
                            py-2.5
                            rounded-xl
                            font-semibold text-xs
                            transition-all
                            text-slate-600 dark:text-slate-300
                            hover:bg-slate-100 dark:hover:bg-slate-800
                            hover:text-indigo-600 dark:hover:text-indigo-400
                        "
                        :class="sidebarCollapsed
                            ?
                            'justify-center px-0' :
                            'justify-start gap-3 px-3'"
                        :title="sidebarCollapsed ? 'GPPB Compliance' : ''">

                        <i
                            class="fa-solid fa-file-contract
                                text-base
                                w-5
                                text-center
                                shrink-0"></i>

                        <span x-show="!sidebarCollapsed" class="truncate">
                            GPPB Compliance
                        </span>

                    </a>

                </div>

            </div>
        @endcan

    </nav>

</aside>
