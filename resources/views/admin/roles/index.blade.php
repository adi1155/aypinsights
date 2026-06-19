@extends('layouts.executive')
@section('page-title', 'Roles & Access')
@section('content')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">{{ session('error') }}</div>
@endif

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm ayp-muted">Assign dashboard and admin permissions to roles. Users inherit access through their assigned role.</p>
    <a href="{{ route('admin.roles.create') }}" class="btn-primary">Add Role</a>
</div>

<div class="grid gap-6">
    @foreach($roles as $role)
        <div class="glass-card p-6">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold ayp-heading">{{ $role->name }}</h2>
                    <p class="text-xs ayp-muted">{{ $role->users_count }} user(s) · {{ $role->permissions->count() }} permission(s)</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn-primary text-xs">Edit Access</a>
                    @if(!in_array($role->name, ['CEO', 'CFO'], true) && $role->users_count === 0)
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-xl border border-red-500/40 px-3 py-2 text-xs text-red-300 hover:bg-red-500/10">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @forelse($role->permissions as $permission)
                    <span class="rounded-full border ayp-border px-3 py-1 text-xs ayp-muted">{{ $permission->name }}</span>
                @empty
                    <span class="text-sm ayp-muted">No permissions assigned.</span>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
@endsection
