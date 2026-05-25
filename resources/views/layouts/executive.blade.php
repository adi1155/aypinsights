<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()?->preferences?->theme ?? 'dark' }}" x-data="{ sidebarOpen: false, theme: localStorage.getItem('theme') || '{{ auth()->user()?->preferences?->theme ?? 'dark' }}' }" x-init="$watch('theme', v => { document.documentElement.setAttribute('data-theme', v); localStorage.setItem('theme', v); })">
<head>
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
      data-progress-steps='@json($progressSteps)'
      :class="theme === 'dark' ? 'bg-slate-950' : 'bg-slate-50'">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-40 w-64 transform border-r border-white/10 bg-slate-900/95 backdrop-blur-xl transition lg:static lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-violet-600 font-bold text-white">AY</div>
                <div>
                    <p class="text-sm font-bold text-white">AYP Insights</p>
                    <p class="text-xs text-slate-500">Executive Cockpit</p>
                </div>
            </div>
            <nav class="space-y-1 p-4">
                @role('CEO|CFO|Director')
                <a href="{{ route('dashboard.ceo') }}" class="sidebar-link {{ request()->routeIs('dashboard.ceo') ? 'active' : '' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                    CEO Dashboard
                </a>
                @endrole
                @can('view daily closing')
                <a href="{{ route('dashboard.closing') }}" class="sidebar-link {{ request()->routeIs('dashboard.closing') ? 'active' : '' }}">Daily Closing</a>
                @endcan
                @can('view ap dashboard')
                <a href="{{ route('dashboard.ap') }}" class="sidebar-link {{ request()->routeIs('dashboard.ap') ? 'active' : '' }}">AP Dashboard</a>
                @endcan
                @can('view ar dashboard')
                <a href="{{ route('dashboard.ar') }}" class="sidebar-link {{ request()->routeIs('dashboard.ar') ? 'active' : '' }}">AR Dashboard</a>
                @endcan
                @can('view expense dashboard')
                <a href="{{ route('dashboard.expense') }}" class="sidebar-link {{ request()->routeIs('dashboard.expense') ? 'active' : '' }}">Expense Dashboard</a>
                @endcan
                <hr class="my-4 border-white/10">
                <a href="{{ route('settings.index') }}" class="sidebar-link">Settings</a>
                <a href="{{ route('settings.scheduled') }}" class="sidebar-link">Scheduled Reports</a>
                @role('CEO|CFO')
                <a href="{{ route('admin.users.index') }}" class="sidebar-link">User Management</a>
                <a href="{{ route('admin.companies.index') }}" class="sidebar-link">Companies</a>
                <a href="{{ route('admin.branches.index') }}" class="sidebar-link">Branches</a>
                <a href="{{ route('admin.audit.index') }}" class="sidebar-link">Audit Logs</a>
                @endrole
            </nav>
        </aside>

        <div class="flex flex-1 flex-col lg:pl-0">
            <header class="sticky top-0 z-30 border-b border-white/10 bg-slate-900/80 backdrop-blur-xl px-4 py-3 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden rounded-lg p-2 text-slate-400 hover:bg-white/5">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-bold text-white">@yield('page-title')</h1>
                    <div class="flex flex-wrap items-center gap-3">
                        @isset($filters)
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? now()->startOfMonth()->toDateString() }}" title="From date" class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-white">
                            <span class="text-slate-500 text-sm">to</span>
                            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? now()->toDateString() }}" title="To date" class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-sm text-white">
                            <select name="company" class="rounded-lg border border-white/10 bg-slate-800 px-3 py-1.5 text-sm text-white max-w-[220px]">
                                @foreach($erpCompanies ?? [] as $erpCompany)
                                    <option value="{{ $erpCompany }}" @selected(($filters['company'] ?? '') === $erpCompany)>{{ $erpCompany }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn-primary text-xs py-1.5">Apply</button>
                        </form>
                        @endisset
                        <button @click="theme = theme === 'dark' ? 'light' : 'dark'" class="rounded-lg border border-white/10 p-2 text-slate-400 hover:text-white" title="Toggle theme">
                            <span x-text="theme === 'dark' ? '☀️' : '🌙'"></span>
                        </button>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-sm text-slate-400 hover:text-white">Logout</button></form>
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
