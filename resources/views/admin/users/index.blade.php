@extends('layouts.executive')
@section('page-title', 'User Management')
@section('content')
<div class="mb-4 flex justify-between"><a href="{{ route('admin.users.create') }}" class="btn-primary">Add User</a></div>
<x-data-table title="Users" :columns="['Name','Email','Roles','Active']" :rows="$users->map(fn($u)=>[$u->name,$u->email,$u->roles->pluck('name')->join(', '),$u->is_active?'Yes':'No'])->all()" />
<div class="mt-4">{{ $users->links() }}</div>
@endsection
