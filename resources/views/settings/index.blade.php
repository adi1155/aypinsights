@extends('layouts.executive')
@section('page-title', 'Settings')
@section('content')
<div class="max-w-xl glass-card p-8">
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
        @csrf
        <div><label class="text-sm text-slate-400">Default Company</label><input name="default_company" value="{{ $prefs->default_company }}" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white"></div>
        <div><label class="text-sm text-slate-400">Default Branch</label><input name="default_branch" value="{{ $prefs->default_branch }}" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white"></div>
        <div><label class="text-sm text-slate-400">Theme</label><select name="theme" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white"><option value="dark" @selected($prefs->theme==='dark')>Dark</option><option value="light" @selected($prefs->theme==='light')>Light</option></select></div>
        <div><label class="text-sm text-slate-400">Currency</label><input name="currency" value="{{ $prefs->currency }}" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white"></div>
        <button class="btn-primary">Save Preferences</button>
    </form>
</div>
@endsection
