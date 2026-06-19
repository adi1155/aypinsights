@extends('layouts.executive')
@section('page-title', $user->exists ? 'Edit User' : 'Create User')
@section('content')
@if($errors->any())
    <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
        <p class="font-semibold">Please fix the following:</p>
        <ul class="mt-2 list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $selectedRole = old('role', $user->exists ? $user->roles->first()?->name : null);
@endphp

<form method="POST" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}" class="max-w-lg glass-card space-y-4 p-8">
    @csrf @if($user->exists) @method('PUT') @endif
    <div>
        <input name="name" value="{{ old('name',$user->name) }}" placeholder="Name" class="ayp-input w-full rounded-xl px-4 py-2" required>
        @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <input name="email" type="email" value="{{ old('email',$user->email) }}" placeholder="Email" class="ayp-input w-full rounded-xl px-4 py-2" required>
        @error('email')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <input name="password" type="password" placeholder="{{ $user->exists ? 'Leave blank to keep current password' : 'Password (min 8 characters)' }}" class="ayp-input w-full rounded-xl px-4 py-2" {{ $user->exists ? '' : 'required' }}>
        @error('password')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <input name="company" value="{{ old('company',$user->company) }}" placeholder="Company" class="ayp-input w-full rounded-xl px-4 py-2">
        @error('company')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <input name="branch" value="{{ old('branch',$user->branch) }}" placeholder="Branch" class="ayp-input w-full rounded-xl px-4 py-2">
        @error('branch')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <select name="role" class="ayp-input w-full rounded-xl px-4 py-2" required>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->name }}</option>
            @endforeach
        </select>
        @error('role')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    @if($user->exists)
        <label class="flex gap-2 text-sm ayp-muted">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Active
        </label>
    @endif
    <button class="btn-primary">Save</button>
</form>
@endsection
