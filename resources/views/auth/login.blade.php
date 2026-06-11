<!DOCTYPE html>
<html lang="en" data-theme="dark" x-data="{ theme: localStorage.getItem('theme') || 'dark' }" x-init="document.documentElement.setAttribute('data-theme', theme)">
<head>
    <script>
        (function () {
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'dark');
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — AYP Insights</title>
    <x-assets :entries="['resources/css/app.css']" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/executive.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="ayp-login-page flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-violet-600 text-2xl font-bold text-white">AY</div>
            <h1 class="text-2xl font-bold ayp-heading">AYP Executive Insights</h1>
            <p class="mt-2 ayp-muted">Financial cockpit powered by ERPNext</p>
        </div>
        <form method="POST" action="{{ route('login') }}" class="glass-card space-y-5 p-8">
            @csrf
            @if($errors->any())
                <div class="rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-500">{{ $errors->first() }}</div>
            @endif
            <div>
                <label class="mb-1 block text-sm ayp-muted">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="ayp-input w-full rounded-xl px-4 py-3">
            </div>
            <div>
                <label class="mb-1 block text-sm ayp-muted">Password</label>
                <input type="password" name="password" required class="ayp-input w-full rounded-xl px-4 py-3">
            </div>
            <label class="flex items-center gap-2 text-sm ayp-muted">
                <input type="checkbox" name="remember" class="rounded ayp-border">
                Remember me
            </label>
            <button type="submit" class="btn-primary w-full">Sign in to Dashboard</button>
        </form>
        <p class="mt-6 text-center text-xs ayp-faint">Demo: ceo@ayp-insights.local / password</p>
    </div>
</body>
</html>
