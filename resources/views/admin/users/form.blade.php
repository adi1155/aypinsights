@extends('layouts.executive')
@section('page-title', $user->exists ? 'Edit User' : 'Create User')
@section('content')
<form method="POST" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}" class="max-w-lg glass-card space-y-4 p-8">
    @csrf @if($user->exists) @method('PUT') @endif
    <input name="name" value="{{ old('name',$user->name) }}" placeholder="Name" class="ayp-input w-full rounded-xl px-4 py-2" required>
    <input name="email" type="email" value="{{ old('email',$user->email) }}" class="ayp-input w-full rounded-xl px-4 py-2" required>
    <input name="password" type="password" placeholder="Password" class="ayp-input w-full rounded-xl px-4 py-2" {{ $user->exists?'':'required' }}>
    <input name="company" value="{{ old('company',$user->company) }}" class="ayp-input w-full rounded-xl px-4 py-2">
    <input name="branch" value="{{ old('branch',$user->branch) }}" class="ayp-input w-full rounded-xl px-4 py-2">
    <select name="role" class="ayp-input w-full rounded-xl px-4 py-2">
        @foreach($roles as $role)<option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ $role->name }}</option>@endforeach
    </select>
    @if($user->exists)<label class="flex gap-2 text-sm ayp-muted"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Active</label>@endif
    <button class="btn-primary">Save</button>
</form>
@endsection
