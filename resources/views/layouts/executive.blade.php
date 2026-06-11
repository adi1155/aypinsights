<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()?->preferences?->theme ?? 'dark' }}" x-data="{
    sidebarOpen: false,
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    theme: localStorage.getItem('theme') || '{{ auth()->user()?->preferences?->theme ?? 'dark' }}',
    toggleSidebarCollapse() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? 'true' : 'false');
    }
}" x-init="document.documentElement.setAttribute('data-theme', theme); $watch('theme', v => { document.documentElement.setAttribute('data-theme', v); localStorage.setItem('theme', v); window.dispatchEvent(new CustomEvent('ayp-theme-change', { detail: v })); })">
<head>
    <script>
        (function () {
            var stored = localStorage.getItem('theme');
            var fallback = document.documentElement.getAttribute('data-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', stored || fallback);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Executive Dashboard') — AYP Insights</title>
    @php
        $progressSteps = match (true) {
            request()->routeIs('dashboard.ceo') => ['Loading CEO cockpit', 'Fetching ERPNext GL', 'IAS statements & ratios', 'Rendering charts'],
            request()->routeIs('dashboard.closing') => ['Daily closing', 'Payment entries', 'Cash KPIs', 'Charts'],
            request()->routeIs('dashboard.ap') => ['AP dashboard', 'Purchase invoices', 'Aging data', 'Charts'],
            request()->routeIs('dashboard.ar') => ['AR dashboard', 'Sales invoices', 'Collections', 'Charts'],
            request()->routeIs('dashboard.expense') => ['Expense dashboard', 'Expense claims', 'Cost centers', 'Charts'],
            request()->routeIs('dashboard.payroll') => ['Payroll dashboard', 'Salary slips', 'Payroll entries', 'Charts'],
            request()->routeIs('dashboard.attendance') => ['Attendance dashboard', 'Employee check-ins', 'Leave applications', 'Charts'],
            request()->routeIs('dashboard.production') => ['Production dashboard', 'Work orders & job cards', 'Machine utilization', 'Charts'],
            default => ['Loading page', 'Connecting to ERPNext', 'Preparing view'],
        };
        $awaitCharts = request()->routeIs('dashboard.*');
    @endphp
    <x-page-progress-config :await-charts="$awaitCharts" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/executive.css') }}">
    <script src="{{ asset('js/page-progress.js') }}"></script>
    <x-assets />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    @stack('head')
</head>
<body class="min-h-screen ayp-loading"
      data-progress-steps='@json($progressSteps)'>
    <div class="flex min-h-screen">
        {{-- Mobile overlay --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            style="display: none;"
        ></div>

        {{-- Sidebar --}}
        <aside
            class="sidebar-panel fixed inset-y-0 left-0 z-40 border-r backdrop-blur-xl transition-all duration-300 ease-in-out lg:static"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                sidebarCollapsed ? 'sidebar-collapsed w-64 lg:w-[4.5rem]' : 'w-64'
            ]"
        >
            <div class="flex h-16 shrink-0 items-center border-b ayp-border" :class="sidebarCollapsed ? 'justify-center px-2' : 'px-4'">
                <div class="flex items-center gap-3 overflow-hidden" :class="sidebarCollapsed ? 'justify-center' : ''">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-violet-600 font-bold text-white">AY</div>
                    <div class="min-w-0" x-show="!sidebarCollapsed" x-transition.opacity>
                        <p class="truncate text-sm font-bold ayp-heading">AYP Insights</p>
                        <p class="truncate text-xs ayp-muted">Executive Cockpit</p>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav space-y-1 overflow-y-auto overflow-x-hidden p-3" :class="sidebarCollapsed ? 'px-2' : 'p-4'">
                @role('CEO|CFO|Director')
                <a href="{{ route('dashboard.ceo') }}" class="sidebar-link {{ request()->routeIs('dashboard.ceo') ? 'active' : '' }}" title="CEO Dashboard" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">CEO Dashboard</span>
                </a>
                @endrole
                @can('view daily closing')
                <a href="{{ route('dashboard.closing') }}" class="sidebar-link {{ request()->routeIs('dashboard.closing') ? 'active' : '' }}" title="Daily Closing" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Daily Closing</span>
                </a>
                @endcan
                @can('view ap dashboard')
                <a href="{{ route('dashboard.ap') }}" class="sidebar-link {{ request()->routeIs('dashboard.ap') ? 'active' : '' }}" title="AP Dashboard" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">AP Dashboard</span>
                </a>
                @endcan
                @can('view ar dashboard')
                <a href="{{ route('dashboard.ar') }}" class="sidebar-link {{ request()->routeIs('dashboard.ar') ? 'active' : '' }}" title="AR Dashboard" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">AR Dashboard</span>
                </a>
                @endcan
                @can('view expense dashboard')
                <a href="{{ route('dashboard.expense') }}" class="sidebar-link {{ request()->routeIs('dashboard.expense') ? 'active' : '' }}" title="Expense Dashboard" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Expense Dashboard</span>
                </a>
                @endcan
                @can('view payroll dashboard')
                <a href="{{ route('dashboard.payroll') }}" class="sidebar-link {{ request()->routeIs('dashboard.payroll') ? 'active' : '' }}" title="Payroll Dashboard" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Payroll Dashboard</span>
                </a>
                @endcan
                @can('view attendance dashboard')
                <a href="{{ route('dashboard.attendance') }}" class="sidebar-link {{ request()->routeIs('dashboard.attendance') ? 'active' : '' }}" title="Attendance & Leave" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Attendance & Leave</span>
                </a>
                @endcan
                @can('view production dashboard')
                <a href="{{ route('dashboard.production') }}" class="sidebar-link {{ request()->routeIs('dashboard.production') ? 'active' : '' }}" title="Production" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Production</span>
                </a>
                @endcan
                <hr class="my-4 ayp-border" :class="sidebarCollapsed ? 'mx-1' : ''">
                <a href="{{ route('settings.index') }}" class="sidebar-link" title="Settings" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Settings</span>
                </a>
                <a href="{{ route('settings.scheduled') }}" class="sidebar-link" title="Scheduled Reports" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Scheduled Reports</span>
                </a>
                @role('CEO|CFO')
                <a href="{{ route('admin.users.index') }}" class="sidebar-link" title="User Management" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">User Management</span>
                </a>
                <a href="{{ route('admin.companies.index') }}" class="sidebar-link" title="Companies" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Companies</span>
                </a>
                <a href="{{ route('admin.branches.index') }}" class="sidebar-link" title="Branches" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Branches</span>
                </a>
                <a href="{{ route('admin.audit.index') }}" class="sidebar-link" title="Audit Logs" :class="sidebarCollapsed ? 'justify-center !px-2' : ''">
                    <svg class="sidebar-icon h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span class="sidebar-label" x-show="!sidebarCollapsed">Audit Logs</span>
                </a>
                @endrole
            </nav>
            <div class="hidden shrink-0 border-t ayp-border p-2 lg:block" :class="sidebarCollapsed ? 'px-1' : ''">
                <button
                    type="button"
                    @click="toggleSidebarCollapse()"
                    class="ayp-btn-ghost flex w-full items-center rounded-lg p-2"
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-2 px-3'"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                >
                    <svg class="h-5 w-5 shrink-0 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                    <span class="text-xs font-medium" x-show="!sidebarCollapsed">Collapse menu</span>
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="ayp-header-bar sticky top-0 z-30 border-b backdrop-blur-xl px-4 py-3 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <button @click="sidebarOpen = !sidebarOpen" class="ayp-btn-ghost rounded-lg p-2 lg:hidden" title="Open menu">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <button @click="toggleSidebarCollapse()" class="ayp-btn-ghost hidden rounded-lg p-2 lg:block" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                            <svg class="h-5 w-5 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                        </button>
                        <h1 class="text-lg font-bold ayp-heading">@yield('page-title')</h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @isset($filters)
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? now()->startOfMonth()->toDateString() }}" title="From date" class="ayp-input rounded-lg px-3 py-1.5 text-sm">
                            <span class="ayp-muted text-sm">to</span>
                            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? now()->toDateString() }}" title="To date" class="ayp-input rounded-lg px-3 py-1.5 text-sm">
                            <select name="company" class="ayp-input rounded-lg px-3 py-1.5 text-sm max-w-[220px]">
                                @foreach($erpCompanies ?? [] as $erpCompany)
                                    <option value="{{ $erpCompany }}" @selected(($filters['company'] ?? '') === $erpCompany)>{{ $erpCompany }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn-primary text-xs py-1.5">Apply</button>
                        </form>
                        @endisset
                        <button @click="theme = theme === 'dark' ? 'light' : 'dark'" class="ayp-btn-ghost rounded-lg border ayp-border p-2" title="Toggle theme">
                            <span x-text="theme === 'dark' ? '☀️' : '🌙'"></span>
                        </button>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="ayp-btn-ghost text-sm">Logout</button></form>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8">
                @if(session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-400">{{ session('success') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
