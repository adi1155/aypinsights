@extends('layouts.executive')
@section('page-title', 'User Management')
@section('content')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div>
@endif
<div class="mb-4 flex justify-between"><a href="{{ route('admin.users.create') }}" class="btn-primary">Add User</a></div>
<div class="glass-card overflow-hidden">
    <div class="border-b ayp-border px-5 py-4">
        <h3 class="text-sm font-semibold ayp-heading">Users</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="ayp-table-head text-xs uppercase">
                <tr>
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Roles</th>
                    <th class="px-5 py-3">Active</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y ayp-border">
                @forelse($users as $user)
                    <tr class="ayp-table-row">
                        <td class="px-5 py-3">{{ $user->name }}</td>
                        <td class="px-5 py-3">{{ $user->email }}</td>
                        <td class="px-5 py-3">{{ $user->roles->pluck('name')->join(', ') }}</td>
                        <td class="px-5 py-3">{{ $user->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-5 py-3"><a href="{{ route('admin.users.edit', $user) }}" class="text-sky-400 hover:underline">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="ayp-table-empty px-5 py-8 text-center">No users</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
