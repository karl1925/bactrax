<header
    class="sticky top-0 z-30 h-16 border-b border-slate-200 bg-white/50 backdrop-blur-md transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900/80">


    <div class="flex h-full w-full max-w-[1600px]
    items-center justify-between
    gap-4 px-4 sm:px-6 lg:px-8">

        {{-- ============================================================
        LEFT SIDE
    ============================================================= --}}
        <div class="flex min-w-0 items-center gap-2 sm:gap-3">

            {{-- Desktop Sidebar Toggle --}}
            {{-- Desktop Sidebar Collapse Toggle --}}
            <button type="button" @click="toggleSidebar()"
                class="hidden lg:flex
                h-9 w-9 shrink-0
                items-center justify-center
                rounded-xl
                text-slate-500
                hover:bg-slate-100
                hover:text-indigo-600
                dark:text-slate-400
                dark:hover:bg-slate-800
                dark:hover:text-indigo-400
                transition-all duration-150
                focus:outline-none
                focus:ring-2 focus:ring-indigo-500/20"
                :title="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
                :aria-label="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'">

                <span class="relative flex h-4 w-4 items-center justify-center">

                    {{-- Collapse Icon --}}
                    <i x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 rotate-90 scale-75"
                        x-transition:enter-end="opacity-100 rotate-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 rotate-0 scale-100"
                        x-transition:leave-end="opacity-0 -rotate-90 scale-75"
                        class="fa-solid fa-angles-left absolute text-sm">
                    </i>

                    {{-- Expand Icon --}}
                    <i x-show="sidebarCollapsed" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -rotate-90 scale-75"
                        x-transition:enter-end="opacity-100 rotate-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 rotate-0 scale-100"
                        x-transition:leave-end="opacity-0 rotate-90 scale-75"
                        class="fa-solid fa-angles-right absolute text-sm">
                    </i>

                </span>

            </button>



            {{-- Mobile Sidebar Toggle --}}
            <button type="button" @click="sidebarOpen = !sidebarOpen"
                class="flex lg:hidden
            h-9 w-9 shrink-0
            items-center justify-center
            rounded-xl
            text-slate-500
            hover:bg-slate-100 hover:text-indigo-600
            dark:text-slate-400
            dark:hover:bg-slate-800 dark:hover:text-indigo-400
            transition-all duration-150
            focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                title="Open Navigation" aria-label="Open Navigation">

                <i class="fa-solid fa-bars text-base"></i>
            </button>


            {{-- Divider --}}
            <span class="hidden sm:block
            h-5 w-px
            bg-slate-200 dark:bg-slate-800">
            </span>


            {{-- Application Context --}}
            <div class="min-w-0">

                <div
                    class="inline-flex max-w-full
                items-center
                rounded-full
                border border-indigo-200/70
                bg-indigo-50
                px-3 py-1
                dark:border-indigo-800/60
                dark:bg-indigo-950/50">

                    <span
                        class="truncate
                    text-[10px] sm:text-xs
                    font-bold
                    uppercase
                    tracking-wider
                    text-indigo-600
                    dark:text-indigo-400">

                        Procurement Routing System

                    </span>

                </div>

            </div>

        </div>


        {{-- ============================================================
        RIGHT SIDE
    ============================================================= --}}
        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">


            {{-- ========================================================
            SEARCH
        ========================================================= --}}
            <div class="hidden sm:block">

                <button type="button" onclick="openSearchModal()"
                    class="group
                flex h-9
                w-52 md:w-64
                items-center justify-between
                gap-3
                rounded-xl
                border border-slate-200
                bg-slate-50/80
                px-3
                text-xs
                text-slate-400
                hover:border-indigo-200
                hover:bg-white
                hover:text-slate-500
                dark:border-slate-700
                dark:bg-slate-800/60
                dark:hover:border-slate-600
                dark:hover:bg-slate-800
                dark:hover:text-slate-300
                transition-all duration-150
                focus:outline-none
                focus:ring-2
                focus:ring-indigo-500/20">

                    <span class="flex items-center gap-2.5 min-w-0">

                        <i
                            class="fa-solid fa-magnifying-glass
                        text-slate-400
                        group-hover:text-indigo-500
                        transition-colors">
                        </i>

                        <span class="truncate">
                            Search...
                        </span>

                    </span>


                    <kbd
                        class="hidden md:inline-flex
                    shrink-0
                    items-center
                    rounded-md
                    border border-slate-200
                    bg-white
                    px-1.5 py-0.5
                    font-mono
                    text-[9px]
                    font-semibold
                    text-slate-400
                    shadow-xs
                    dark:border-slate-700
                    dark:bg-slate-900
                    dark:text-slate-500">

                        ⌘ K

                    </kbd>

                </button>

            </div>


            {{-- Mobile Search --}}
            <button type="button" onclick="openSearchModal()"
                class="flex sm:hidden
            h-9 w-9
            items-center justify-center
            rounded-xl
            text-slate-500
            hover:bg-slate-100 hover:text-indigo-600
            dark:text-slate-400
            dark:hover:bg-slate-800 dark:hover:text-indigo-400
            transition-all duration-150
            focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                title="Search" aria-label="Search">

                <i class="fa-solid fa-magnifying-glass text-sm"></i>

            </button>


            {{-- ========================================================
            THEME TOGGLE
        ========================================================= --}}
            <button type="button" @click="toggleDarkMode()"
                class="flex
            h-9 w-9
            items-center justify-center
            rounded-xl
            text-slate-500
            hover:bg-slate-100 hover:text-indigo-600
            dark:text-slate-400
            dark:hover:bg-slate-800 dark:hover:text-indigo-400
            transition-all duration-150
            focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                title="Toggle Light/Dark Theme" aria-label="Toggle Light/Dark Theme">

                <i x-show="!darkMode" class="fa-solid fa-moon text-sm">
                </i>

                <i x-show="darkMode" class="fa-solid fa-sun text-sm text-amber-400" style="display: none;">
                </i>

            </button>


            {{-- Divider --}}
            <span class="mx-1
            h-5 w-px
            bg-slate-200 dark:bg-slate-800">
            </span>


            {{-- ========================================================
            USER MENU
        ========================================================= --}}
            <div class="relative" x-data="{ open: false }">


                {{-- User Button --}}
                <button type="button" @click="open = !open"
                    class="group
                flex items-center
                gap-2.5
                rounded-xl
                p-1
                pr-1.5
                hover:bg-slate-100
                dark:hover:bg-slate-800
                transition-all duration-150
                focus:outline-none
                focus:ring-2
                focus:ring-indigo-500/20">

                    {{-- Avatar --}}
                    <div class="relative shrink-0">

                        <div
                            class="flex
                        h-8 w-8
                        items-center justify-center
                        rounded-lg
                        bg-gradient-to-tr
                        from-indigo-600
                        to-indigo-800
                        text-xs
                        font-bold
                        text-white
                        shadow-sm
                        ring-2 ring-indigo-500/20">

                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}

                        </div>


                        {{-- Online Indicator --}}
                        <span
                            class="absolute
                        -bottom-0.5
                        -right-0.5
                        h-2.5 w-2.5
                        rounded-full
                        border-2
                        border-white
                        bg-emerald-500
                        dark:border-slate-900">
                        </span>

                    </div>


                    {{-- User Information --}}
                    <div class="hidden md:flex min-w-0 flex-col text-left">

                        <span
                            class="max-w-[140px]
                        truncate
                        text-xs
                        font-semibold
                        leading-tight
                        text-slate-800
                        dark:text-slate-200">

                            {{ Auth::user()->name ?? 'User Account' }}

                        </span>

                        <span
                            class="max-w-[140px]
                        truncate
                        text-[10px]
                        leading-tight
                        text-slate-500
                        dark:text-slate-400">

                            {{ Auth::user()->roles->first()->name ?? 'Staff' }}

                        </span>

                    </div>


                    {{-- Chevron --}}
                    <i class="fa-solid fa-chevron-down
                    hidden md:block
                    text-[9px]
                    text-slate-400
                    transition-transform duration-200"
                        :class="{ 'rotate-180': open }">
                    </i>

                </button>


                {{-- ====================================================
                USER DROPDOWN
            ===================================================== --}}
                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                    class="absolute right-0 z-50 mt-2
                w-60
                overflow-hidden
                rounded-2xl
                border border-slate-200
                bg-white
                shadow-xl shadow-slate-900/10
                dark:border-slate-800
                dark:bg-slate-900
                dark:shadow-black/30"
                    style="display: none;">


                    {{-- Account Header --}}
                    <div
                        class="border-b
                    border-slate-100
                    bg-slate-50/70
                    px-4 py-3
                    dark:border-slate-800
                    dark:bg-slate-800/40">

                        <p
                            class="text-[9px]
                        font-bold
                        uppercase
                        tracking-widest
                        text-slate-400
                        dark:text-slate-500">

                            Signed in as

                        </p>

                        <p
                            class="mt-1
                        truncate
                        text-xs
                        font-semibold
                        text-slate-800
                        dark:text-slate-200">

                            {{ Auth::user()->email ?? 'user@dict.gov.ph' }}

                        </p>

                    </div>


                    {{-- Account Links --}}
                    <div class="p-1.5">

                        {{-- Profile --}}
                        <a href="{{ route('user.profile.edit') }}"
                            class="group
                        flex items-center gap-3
                        rounded-xl
                        px-3 py-2.5
                        text-xs
                        font-medium
                        text-slate-700
                        hover:bg-indigo-50
                        hover:text-indigo-600
                        dark:text-slate-300
                        dark:hover:bg-indigo-950/40
                        dark:hover:text-indigo-400
                        transition-colors">

                            <span
                                class="flex h-7 w-7
                            items-center justify-center
                            rounded-lg
                            bg-slate-100
                            text-slate-500
                            group-hover:bg-indigo-100
                            group-hover:text-indigo-600
                            dark:bg-slate-800
                            dark:text-slate-400
                            dark:group-hover:bg-indigo-950
                            dark:group-hover:text-indigo-400">

                                <i class="fa-solid fa-user-gear text-[11px]"></i>

                            </span>

                            <span>
                                Profile
                            </span>

                        </a>


                        {{-- Signature --}}
                        <a href="{{ route('user.signature.edit') }}"
                            class="group
                        flex items-center gap-3
                        rounded-xl
                        px-3 py-2.5
                        text-xs
                        font-medium
                        text-slate-700
                        hover:bg-indigo-50
                        hover:text-indigo-600
                        dark:text-slate-300
                        dark:hover:bg-indigo-950/40
                        dark:hover:text-indigo-400
                        transition-colors">

                            <span
                                class="flex h-7 w-7
                            items-center justify-center
                            rounded-lg
                            bg-slate-100
                            text-slate-500
                            group-hover:bg-indigo-100
                            group-hover:text-indigo-600
                            dark:bg-slate-800
                            dark:text-slate-400
                            dark:group-hover:bg-indigo-950
                            dark:group-hover:text-indigo-400">

                                <i class="fa-solid fa-file-signature text-[11px]"></i>

                            </span>

                            <span>
                                Signature
                            </span>

                        </a>

                    </div>


                    {{-- Logout --}}
                    <div
                        class="border-t
                    border-slate-100
                    p-1.5
                    dark:border-slate-800">

                        <form method="POST" action="{{ route('logout') }}">

                            @csrf

                            <button type="submit"
                                class="group
                            w-full
                            flex items-center gap-3
                            rounded-xl
                            px-3 py-2.5
                            text-left
                            text-xs
                            font-semibold
                            text-rose-600
                            hover:bg-rose-50
                            dark:text-rose-400
                            dark:hover:bg-rose-950/30
                            transition-colors">

                                <span
                                    class="flex h-7 w-7
                                items-center justify-center
                                rounded-lg
                                bg-rose-50
                                text-rose-500
                                group-hover:bg-rose-100
                                dark:bg-rose-950/40
                                dark:group-hover:bg-rose-950/60">

                                    <i class="fa-solid fa-right-from-bracket text-[11px]"></i>

                                </span>

                                <span>
                                    Logout
                                </span>

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>


</header>
