<header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="shrink-0 rounded-sm text-xl font-black tracking-tight text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:text-amber-400 dark:ring-offset-slate-900" aria-label="Daily Samvad home">
            <span aria-hidden="true">DailySamvad</span>
        </a>

        <x-frontend.navigation />

        <div class="flex items-center gap-2">
            <span class="hidden rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 sm:inline-flex dark:border-slate-700 dark:text-slate-300" aria-label="Language selector placeholder">
                Language
            </span>
            <button type="button" class="rounded-md p-2 text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-slate-200 dark:hover:bg-slate-800" aria-label="Search">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-4-4"></path>
                </svg>
            </button>
            <button type="button" class="rounded-md p-2 text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500 lg:hidden dark:text-slate-200 dark:hover:bg-slate-800" aria-label="Open main menu" aria-controls="mobile-navigation" aria-expanded="false">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>

    <div id="mobile-navigation" class="hidden border-t border-slate-200 px-4 py-3 lg:hidden dark:border-slate-800">
        <x-frontend.navigation mobile />
    </div>
</header>
