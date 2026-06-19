@extends('layouts.executive')
@section('page-title', $role->exists ? 'Edit Role Access' : 'Create Role')
@section('content')
<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="max-w-3xl space-y-6">
    @csrf
    @if($role->exists) @method('PUT') @endif

    <div class="glass-card space-y-4 p-8">
        <label class="block text-sm font-medium ayp-heading">Role name</label>
        <input
            name="name"
            value="{{ old('name', $role->name) }}"
            placeholder="e.g. HR Manager"
            class="ayp-input w-full rounded-xl px-4 py-2"
            required
            @if(in_array($role->name, ['CEO', 'CFO'], true)) readonly @endif
        >
        @error('name')<p class="text-sm text-red-400">{{ $message }}</p>@enderror
    </div>

    <div class="glass-card space-y-6 p-8">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold ayp-heading">Page & feature access</h2>
            <button type="button" class="text-xs text-sky-400 hover:underline" onclick="document.querySelectorAll('.perm-checkbox').forEach(c => c.checked = true)">Select all</button>
        </div>

        @foreach($permissionGroups as $group => $permissions)
            <div>
                <h3 class="mb-3 text-sm font-semibold ayp-heading">{{ $group }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($permissions as $permission)
                        <label class="flex items-center gap-3 rounded-xl border ayp-border px-4 py-3 text-sm ayp-muted">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission }}"
                                class="perm-checkbox rounded"
                                @checked(in_array($permission, old('permissions', $assignedPermissions), true))
                            >
                            <span>{{ ucwords($permission) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
        @error('permissions')<p class="text-sm text-red-400">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">Save Role</button>
        <a href="{{ route('admin.roles.index') }}" class="rounded-xl border ayp-border px-4 py-2 text-sm ayp-muted">Cancel</a>
    </div>
</form>
@endsection
