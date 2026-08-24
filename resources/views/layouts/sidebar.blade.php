<aside
    class="fixed inset-y-0 left-0 z-50 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col transition-all duration-300 ease-in-out shadow-lg lg:shadow-none"
    :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full lg:translate-x-0': !sidebarOpen,
        'w-20': sidebarCollapsed,
        'w-72': !sidebarCollapsed
    }"
>
    <!-- Sidebar Branding Header -->
    <div class="h-16 px-4 flex items-center justify-between border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-800 flex items-center justify-center text-white font-black text-lg shadow-md shadow-indigo-500/20 shrink-0">
                B
            </div>
            <div x-show="!sidebarCollapsed" x-transition.opacity class="flex flex-col truncate">
                <span class="font-extrabold text-base tracking-tight text-indigo-600 dark:text-indigo-400 leading-tight">BACTrax</span>
                <span class="text-[10px] font-semibold text-slate-400 leading-tight">DICT Region 2</span>
            </div>
        </a>

        <!-- Mobile Close Button -->
        <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- Navigation Menu Directory -->
    <nav class="flex-1 p-3 space-y-6 overflow-y-auto">

        <!-- Main Navigation -->
        <div>
            <p x-show="!sidebarCollapsed" x-transition.opacity class="px-3 mb-2 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Main Navigation</p>
            <div class="space-y-1">
                
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                   :title="sidebarCollapsed ? 'Dashboard' : ''">
                    <i class="fa-solid fa-chart-pie text-base w-5 text-center shrink-0"></i>
                    <span x-show="!sidebarCollapsed" class="truncate">Dashboard</span>
                </a>

                @can('procurement.view')
                    <!-- Procurements -->
                    <a href="{{ route('procurements.index') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('procurements.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                    :title="sidebarCollapsed ? 'Procurements' : ''">
                        <i class="fa-solid fa-folder-open text-base w-5 text-center shrink-0"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Procurements</span>
                    </a>
                @endcan

            </div>
        </div>

        <!-- System Management -->
        @canany(['settings.view', 'office.view', 'roles.view', 'users.view'])
            <div>
                <p x-show="!sidebarCollapsed" x-transition.opacity class="px-3 mb-2 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">System Engine</p>
                <div class="space-y-1">
                    @can('modes.view')
                        <a href="{{ route('procurement-modes.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('procurement-modes.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                        :title="sidebarCollapsed ? 'Procurement Modes' : ''">
                            <i class="fa-solid fa-sliders text-base w-5 text-center shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="truncate">Procurement Modes</span>
                        </a>
                    @endcan

                    @can('offices.view')
                        <a href="{{ route('admin.offices.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.offices.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                        :title="sidebarCollapsed ? 'Offices' : ''">
                            <i class="fa-solid fa-building text-base w-5 text-center shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="truncate">Offices</span>
                        </a>
                    @endcan
                    
                    @can('roles.view')
                        <a href="{{ route('admin.roles.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.roles.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                        :title="sidebarCollapsed ? 'Roles' : ''">
                            <i class="fa-solid fa-shield-halved text-base w-5 text-center shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="truncate">Roles</span>
                        </a>
                    @endcan

                    @can('users.view')
                        <a href="{{ route('admin.users.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                        :title="sidebarCollapsed ? 'Users' : ''">
                            <i class="fa-solid fa-users text-base w-5 text-center shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="truncate">Users</span>
                        </a>
                    @endcan

                    @can('settings.view')
                        <a href="{{ route('admin.settings.index') }}" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.settings.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                        :title="sidebarCollapsed ? 'Settings' : ''">
                            <i class="fa-solid fa-gear text-base w-5 text-center shrink-0"></i>
                            <span x-show="!sidebarCollapsed" class="truncate">Settings</span>
                        </a>
                    @endcan

                </div>
            </div>
        @endcanany

        @can('reports.view')
            <!-- System Reports -->
            <div>
                <p x-show="!sidebarCollapsed" x-transition.opacity class="px-3 mb-2 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Reports</p>
                <div class="space-y-1">

                    <a href="{{ route('reports.gppb-compliance') }}" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all"
                    :title="sidebarCollapsed ? 'GPPB Compliance' : ''">
                        <i class="fa-solid fa-file-contract text-base w-5 text-center shrink-0"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">GPPB Compliance</span>
                    </a>
                </div>
            </div>
        @endcan

    </nav>

</aside>