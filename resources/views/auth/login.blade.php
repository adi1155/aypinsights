<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — AYP Insights</title>
    <x-assets :entries="['resources/css/app.css']" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-950 p-4">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-violet-600 text-2xl font-bold text-white">AY</div>
            <h1 class="text-2xl font-bold text-white">AYP Executive Insights</h1>
            <p class="mt-2 text-slate-400">Financial cockpit powered by ERPNext</p>
        </div>
        <form method="POST" action="{{ route('login') }}" class="glass-card space-y-5 p-8">
            @csrf
            @if($errors->any())
                <div class="rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">{{ $errors->first() }}</div>
            @endif
            <div>
                <label class="block text-sm text-slate-400 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white focus:border-sky-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white focus:border-sky-500 focus:outline-none">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-white/20">
                Remember me
            </label>
            <button type="submit" class="btn-primary w-full">Sign in to Dashboard</button>
        </form>
        <p class="mt-6 text-center text-xs text-slate-600">Demo: ceo@ayp-insights.local / password</p>
    </div>
</body>
</html>
