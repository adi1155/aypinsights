@extends('layouts.executive')
@section('page-title', 'Branch Management')
@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.branches.store') }}" class="glass-card space-y-3 p-6">
        @csrf
        <select name="company_id" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-white">@foreach($companies as $c)<option value="{{ $c->id }}">{{ $c->erpnext_name }}</option>@endforeach</select>
        <input name="erpnext_name" placeholder="ERPNext Branch" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-white" required>
        <input name="name" placeholder="Display Name" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-white" required>
        <button class="btn-primary">Add Branch</button>
    </form>
    <x-data-table title="Branches" :columns="['Company','Branch','Name']" :rows="$branches->map(fn($b)=>[$b->company->erpnext_name,$b->erpnext_name,$b->name])->all()" />
</div>
@endsection
