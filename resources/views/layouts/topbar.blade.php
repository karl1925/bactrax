
<header class="sticky top-0 z-30 h-16 border-b border-slate-200 bg-white/50 backdrop-blur-md transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900/80">
    <div class="flex h-full w-full max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        
        {{-- Left: Toggles & Mobile Trigger --}}
        <div class="flex items-center gap-3 sm:gap-4">
            <!-- Desktop Sidebar Collapse Toggle Button -->
            <button
                @click="toggleSidebar()"
                class="hidden lg:flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition focus:outline-none"
                title="Toggle Sidebar"
            >
                <i class="fa-solid fa-bars-staggered text-sm"></i>
            </button>

            <!-- Mobile Drawer Toggle Button -->
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="flex lg:hidden h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition focus:outline-none"
            >
                <i class="fa-solid fa-bars text-base"></i>
            </button>

            <span class="hidden sm:inline-block h-5 w-px bg-slate-200 dark:bg-slate-800"></span>

            <span class="text-xs font-bold tracking-wider uppercase px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/60">
                DICT R2 Procurement Routing System
            </span>
        </div>


        {{-- Right: Dark Mode Toggle & User Profile --}}
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex-1 max-w-md hidden sm:block">
            <button
                type="button"
                onclick="openSearchModal()"
                class="flex items-center justify-between bg-slate-100/80 dark:bg-slate-800/60 hover:bg-slate-200/70 dark:hover:bg-slate-800 px-3.5 py-1.5 rounded-xl text-xs text-slate-400 dark:text-slate-500 border border-slate-200/80 dark:border-slate-700/80 transition group gap-2"
            >
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 group-hover:text-indigo-500 transition-colors"></i>
                </div>
                <kbd class="px-2 py-0.5 text-[10px] font-mono font-semibold bg-white dark:bg-slate-900 text-slate-500 border border-slate-200 dark:border-slate-700 rounded-md shadow-xs"> ⌘ K</kbd>
            </button>
        </div>
            <!-- Mobile Search Icon Trigger -->
            <button
                type="button"
                onclick="openSearchModal()"
                class="sm:hidden flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition"
            >
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            <!-- Dark / Light Theme Toggle Switch -->
            <button
                @click="toggleDarkMode()"
                class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-indigo-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-indigo-400 transition focus:outline-none"
                title="Toggle Light/Dark Theme"
            >
                <i x-show="!darkMode" class="fa-solid fa-moon"></i>
                <i x-show="darkMode" class="fa-solid fa-sun text-amber-400" style="display: none;"></i>
            </button>

            <span class="h-5 w-px bg-slate-200 dark:bg-slate-800"></span>

            <!-- User Dropdown Menu -->
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="flex items-center gap-2.5 p-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition focus:outline-none"
                >
                    <div class="relative">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 to-indigo-800 text-white font-bold flex items-center justify-center text-xs shadow-xs ring-2 ring-indigo-500/20">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></span>
                    </div>
                    <div class="hidden md:flex flex-col text-left">
                        <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-tight">{{ Auth::user()->name ?? 'User Account' }}</span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">{{ Auth::user()->roles->first()->name ?? 'Staff' }}</span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-slate-400 text-xs hidden md:block"></i>
                </button>

                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                    class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-xl py-1.5 border border-slate-200 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800 z-50"
                    style="display: none;"
                >
                    <div class="px-4 py-2.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Signed in as</p>
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate mt-0.5">{{ Auth::user()->email ?? 'user@dict.gov.ph' }}</p>
                    </div>

                    <div class="py-1">
                        <a href="#" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                            <i class="fa-solid fa-user-gear text-slate-400"></i> Profile Management
                        </a>
                        <a href="#" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                            <i class="fa-solid fa-key text-slate-400"></i> Change Password
                        </a>
                    </div>

                    <div class="py-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                                <i class="fa-solid fa-right-from-bracket text-rose-500"></i> Logout Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </div>
</header>