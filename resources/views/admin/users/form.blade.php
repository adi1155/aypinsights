@extends('layouts.executive')
@section('page-title', $user->exists ? 'Edit User' : 'Create User')
@section('content')
<form method="POST" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}" class="max-w-lg glass-card space-y-4 p-8">
    @csrf @if($user->exists) @method('PUT') @endif
    <input name="name" value="{{ old('name',$user->name) }}" placeholder="Name" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white" required>
    <input name="email" type="email" value="{{ old('email',$user->email) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white" required>
    <input name="password" type="password" placeholder="Password" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white" {{ $user->exists?'':'required' }}>
    <input name="company" value="{{ old('company',$user->company) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white">
    <input name="branch" value="{{ old('branch',$user->branch) }}" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white">
    <select name="role" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-white">
        @foreach($roles as $role)<option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ $role->name }}</option>@endforeach
    </select>
    @if($user->exists)<label class="flex gap-2 text-sm text-slate-400"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Active</label>@endif
    <button class="btn-primary">Save</button>
</form>
@endsection
