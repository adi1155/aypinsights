@extends('layouts.executive')
@section('page-title', 'Settings')
@section('content')
<div class="max-w-xl glass-card p-8">
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
        @csrf
        <div><label class="text-sm ayp-muted">Default Company</label><input name="default_company" value="{{ $prefs->default_company }}" class="ayp-input mt-1 w-full rounded-xl px-4 py-2"></div>
        <div><label class="text-sm ayp-muted">Default Branch</label><input name="default_branch" value="{{ $prefs->default_branch }}" class="ayp-input mt-1 w-full rounded-xl px-4 py-2"></div>
        <div><label class="text-sm ayp-muted">Theme</label><select name="theme" class="ayp-input mt-1 w-full rounded-xl px-4 py-2"><option value="dark" @selected($prefs->theme==='dark')>Dark</option><option value="light" @selected($prefs->theme==='light')>Light</option></select></div>
        <div><label class="text-sm ayp-muted">Currency</label><input name="currency" value="{{ $prefs->currency }}" class="ayp-input mt-1 w-full rounded-xl px-4 py-2"></div>
        <button class="btn-primary">Save Preferences</button>
    </form>
</div>
@endsection
