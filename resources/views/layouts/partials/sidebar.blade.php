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
                        text-blue-600 dark:text-blue-400
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
                        rounded-xl
                        font-semibold text-xs
                        transition-all

                        {{ request()->routeIs('dashboard')
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                    "
                    :class="sidebarCollapsed
                        ?
                        'flex-col justify-center gap-1 px-1 py-2' :
                        'flex-row justify-start gap-3 px-3 py-2.5'"
                    :title="sidebarCollapsed ? 'Dashboard' : ''">

                    <i
                        class="fa-solid fa-chart-pie
                            text-base
                            w-5
                            text-center
                            shrink-0"></i>

                    <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
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
                            rounded-xl
                            font-semibold text-xs
                            transition-all

                            {{ request()->routeIs('procurements.*')
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                        "
                        :class="sidebarCollapsed
                            ?
                            'flex-col justify-center gap-1 px-1 py-2' :
                            'flex-row justify-start gap-3 px-3 py-2.5'"
                        :title="sidebarCollapsed ? 'Procurements' : ''">

                        <i
                            class="fa-solid fa-folder-open
                                text-base
                                w-5
                                text-center
                                shrink-0"></i>

                        <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
                            Procurements
                        </span>

                    </a>
                @endcan

            </div>

        </div>

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
                            rounded-xl
                            font-semibold text-xs
                            transition-all
                            text-slate-600 dark:text-slate-300
                            hover:bg-slate-100 dark:hover:bg-slate-800
                            hover:text-blue-600 dark:hover:text-blue-400
                        "
                        :class="sidebarCollapsed
                            ?
                            'flex-col justify-center gap-1 px-1 py-2' :
                            'flex-row justify-start gap-3 px-3 py-2.5'"
                        :title="sidebarCollapsed ? 'GPPB Compliance' : ''">

                        <i
                            class="fa-solid fa-chart-column
                                text-base
                                w-5
                                text-center
                                shrink-0"></i>

                        <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
                            GPPB Compliance
                        </span>

                    </a>

                </div>

            </div>
        @endcan


        {{-- ============================================================
            ENTITIES MANAGEMENT
        ============================================================= --}}
        @canAny(['modes.view', 'offices.view', 'roles.view', 'users.view', 'suppliers.view', 'items.view'])

            <div>

                {{-- Section Heading --}}
                <p x-show="!sidebarCollapsed" x-transition.opacity
                    class="px-3 mb-2
                        text-[10px]
                        font-bold
                        text-slate-400 dark:text-slate-500
                        uppercase
                        tracking-widest">
                    Entities Management
                </p>


                <div class="space-y-1">

                    @can('suppliers.view')
                        <a href="{{ route('suppliers.index') }}" class="flex items-center rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('suppliers.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}" :class="sidebarCollapsed ? 'flex-col justify-center gap-1 px-1 py-2' : 'flex-row justify-start gap-3 px-3 py-2.5'" :title="sidebarCollapsed ? 'Suppliers' : ''">
                            <i class="fa-solid fa-truck-field w-5 shrink-0 text-center text-base"></i>
                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">Suppliers</span>
                        </a>
                    @endcan

                    @can('items.view')
                        <a href="{{ route('item-bank.index') }}" class="flex items-center rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('item-bank.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}" :class="sidebarCollapsed ? 'flex-col justify-center gap-1 px-1 py-2' : 'flex-row justify-start gap-3 px-3 py-2.5'" :title="sidebarCollapsed ? 'Item Bank' : ''">
                            <i class="fa-solid fa-boxes-stacked w-5 shrink-0 text-center text-base"></i>
                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">Item Bank</span>
                        </a>
                    @endcan

                    {{-- ==================================================
                        PROCUREMENT MODES
                    =================================================== --}}
                    @can('modes.view')
                        <a href="{{ route('admin.modes.index') }}"
                            class="
                                flex items-center
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.modes.*')
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'flex-col justify-center gap-1 px-1 py-2' :
                                'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'Procurement Modes' : ''">

                            <i
                                class="fa-solid fa-sliders
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
                            Procurement Modes
                        </span>

                        </a>
                    @endcan

                    {{-- ==================================================
                        FUND CLUSTERS
                    =================================================== --}}
                    @can('fundclusters.view')
                        <a href="{{ route('admin.fundclusters.index') }}"
                            class="
                                flex items-center
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.fundclusters.*')
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'flex-col justify-center gap-1 px-1 py-2' :
                                'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'Fund Clusters' : ''">

                            <i
                                class="fa-solid fa-coins
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
                            Fund Clusters
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
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.offices.*')
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'flex-col justify-center gap-1 px-1 py-2' :
                                'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'Offices' : ''">

                            <i
                                class="fa-solid fa-building
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
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
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.roles.*')
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'flex-col justify-center gap-1 px-1 py-2' :
                                'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'Roles' : ''">

                            <i
                                class="fa-solid fa-shield-halved
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
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
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.users.*')
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'flex-col justify-center gap-1 px-1 py-2' :
                                'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'Users' : ''">

                            <i
                                class="fa-solid fa-users
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
                            Users
                        </span>

                        </a>
                    @endcan

                    {{-- ==================================================
                        FORM TEMPLATES
                    =================================================== --}}
                    @can('forms.view')
                        <a href="{{ route('admin.forms.index') }}"
                            class="
                                flex items-center
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.forms.*')
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'flex-col justify-center gap-1 px-1 py-2' :
                                'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'Form Templates' : ''">

                            <i
                                class="fa-solid fa-file-lines
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
                            Form Templates
                        </span>

                        </a>
                    @endcan

                </div>

            </div>

        @endcanany

        {{-- ============================================================
            SYSTEM MANAGEMENT
        ============================================================= --}}
        @canAny(['forms.view', 'settings.view', 'rfq_documents.view'])

            <div>

                {{-- Section Heading --}}
                <p x-show="!sidebarCollapsed" x-transition.opacity
                    class="px-3 mb-2
                        text-[10px]
                        font-bold
                        text-slate-400 dark:text-slate-500
                        uppercase
                        tracking-widest">
                    System Management
                </p>


                <div class="space-y-1">

                    {{-- ==================================================
                        SETTINGS
                    =================================================== --}}
                    @can('settings.view')
                        <a href="{{ route('admin.settings.index') }}"
                            class="
                                flex items-center
                                rounded-xl
                                font-semibold text-xs
                                transition-all

                                {{ request()->routeIs('admin.settings.*')
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}
                            "
                            :class="sidebarCollapsed
                                ?
                                'flex-col justify-center gap-1 px-1 py-2' :
                                'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'PR Settings' : ''">

                            <i
                                class="fa-solid fa-gear
                                    text-base
                                    w-5
                                    text-center
                                    shrink-0"></i>

                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">
                            PR Settings
                        </span>

                        </a>
                    @endcan

                    @can('rfq_documents.view')
                        <a href="{{ route('admin.rfq-documents.index') }}"
                            class="flex items-center rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.rfq-documents.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}"
                            :class="sidebarCollapsed ? 'flex-col justify-center gap-1 px-1 py-2' : 'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'RFQ Docs' : ''">
                            <i class="fa-solid fa-file-circle-check text-base w-5 text-center shrink-0"></i>
                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">RFQ Docs</span>
                        </a>
                    @endcan

                    @can('forms.view')
                        <a href="{{ route('admin.form-cms.index') }}"
                            class="flex items-center rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.form-cms.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400' }}"
                            :class="sidebarCollapsed ? 'flex-col justify-center gap-1 px-1 py-2' : 'flex-row justify-start gap-3 px-3 py-2.5'"
                            :title="sidebarCollapsed ? 'Form CMS' : ''">
                            <i class="fa-solid fa-code text-base w-5 text-center shrink-0"></i>
                            <span class="min-w-0 truncate" :class="sidebarCollapsed ? 'block w-full text-center text-[8px] font-extrabold uppercase leading-3 tracking-tight' : 'flex-1 text-xs font-semibold'">Form CMS</span>
                        </a>
                    @endcan

                </div>

            </div>

        @endcanany

    </nav>

</aside>
